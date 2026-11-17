<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChannelType;
use App\Enums\LedgerEntryType;
use App\Exceptions\InsuranceLedgerException;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InsuranceRequest;
use App\Models\Insurance;
use App\Services\InsuranceChannelService;
use App\Services\InsuranceLedgerService;
use App\Services\InsuranceSuspensionService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminInsuranceController extends Controller
{
    /** Ledger entry types an admin may record; Payout only exists on entries written before claims were removed. */
    public const MANUAL_LEDGER_TYPES = [LedgerEntryType::Deposit, LedgerEntryType::Refund, LedgerEntryType::Adjustment];

    public function __construct(
        private InsuranceChannelService $channels,
        private InsuranceLedgerService $ledger,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Insurance::class);

        $query = Insurance::query()
            ->withExists(['ledgerEntries', 'statusEvents'])
            ->addSelect(['legacy_claims_exists' => $this->legacyClaimsQuery()->whereColumn('insurance_id', 'insurances.id')->selectRaw('1')->limit(1)])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->integer('status'));
        }

        if ($request->filled('time_range')) {
            switch ($request->time_range) {
                case 'today':
                    $query->whereDate('created_at', now()->today());
                    break;
                case '3_days':
                    $query->where('created_at', '>=', now()->subDays(3));
                    break;
                case '7_days':
                    $query->where('created_at', '>=', now()->subDays(7));
                    break;
                case '1_month':
                    $query->where('created_at', '>=', now()->subMonths(1));
                    break;
            }
        }

        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%'.$request->search.'%');
        }

        $insurances = $query->paginate(15)->withQueryString();

        return view('admin.insurances.index', compact('insurances'));
    }

    public function create(): View
    {
        Gate::authorize('create', Insurance::class);

        return view('admin.insurances.create', [
            'channelRows' => [],
            'channelTypes' => ChannelType::cases(),
        ]);
    }

    public function store(InsuranceRequest $request): RedirectResponse
    {
        Gate::authorize('create', Insurance::class);

        $validated = $request->validated();

        try {
            $insurance = DB::transaction(function () use ($validated, $request) {
                $insurance = Insurance::create([
                    'full_name' => $validated['full_name'],
                    'slug' => StringHelper::generateGlobalUniqueSlug($validated['full_name']),
                    'amount' => 0,
                    'insurance_date' => $validated['insurance_date'],
                    'expired_at' => $validated['expired_at'],
                    'verified_at' => $validated['insurance_date'],
                    'status' => $validated['status'],
                    'services' => $this->filterEmptyArrayItems($validated['services'], ['title']) ?: null,
                ]);

                $this->channels->sync($insurance, $validated['channels']);

                if ((int) $validated['opening_deposit'] > 0) {
                    $this->ledger->record(
                        $insurance,
                        LedgerEntryType::Deposit,
                        (string) $validated['opening_deposit'],
                        $request->user(),
                        null,
                        'Ký quỹ ban đầu',
                    );
                }

                return $insurance;
            });
        } catch (InsuranceLedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->hasFile('avatar')) {
            $insurance->update(['avatar' => FileHelper::uploadImage($request->file('avatar'), 'insurances')]);
        }

        return redirect()->route('admin.insurances.show', $insurance)
            ->with('success', 'Đã thêm thành viên bảo hiểm thành công.');
    }

    public function show(Insurance $insurance): View
    {
        Gate::authorize('view', $insurance);

        $ledgerEntries = $insurance->ledgerEntries()
            ->reorder('id', 'desc')
            ->with('recorder')
            ->paginate(15, ['*'], 'ledger_page')
            ->withQueryString();

        $ledgerBalance = $this->ledger->balanceFromLedger($insurance);
        $isReconciled = InsuranceLedgerService::toCents($ledgerBalance) === InsuranceLedgerService::toCents((string) $insurance->amount);

        $statusEvents = $insurance->statusEvents()->reorder('id', 'desc')->with('actor')->get();
        $channels = $insurance->channels()->orderBy('id')->get();
        $hasHistory = $this->hasHistory($insurance);
        $manualLedgerTypes = self::MANUAL_LEDGER_TYPES;

        return view('admin.insurances.show', compact(
            'insurance',
            'ledgerEntries',
            'ledgerBalance',
            'isReconciled',
            'statusEvents',
            'channels',
            'hasHistory',
            'manualLedgerTypes',
        ));
    }

    public function edit(Insurance $insurance): View
    {
        Gate::authorize('update', $insurance);

        return view('admin.insurances.create', [
            'insurance' => $insurance,
            'channelRows' => $this->channels->formRows($insurance),
            'channelTypes' => ChannelType::cases(),
        ]);
    }

    /**
     * The balance (`amount`) is not editable here: it only changes through the ledger.
     */
    public function update(InsuranceRequest $request, Insurance $insurance): RedirectResponse
    {
        Gate::authorize('update', $insurance);

        $validated = $request->validated();

        $slug = $insurance->slug;
        if ($insurance->full_name !== $validated['full_name']) {
            $slug = StringHelper::generateGlobalUniqueSlug($validated['full_name'], $insurance->id);
        }

        $data = [
            'full_name' => $validated['full_name'],
            'slug' => $slug,
            'insurance_date' => $validated['insurance_date'],
            'expired_at' => $validated['expired_at'],
            'status' => $validated['status'],
            'services' => $this->filterEmptyArrayItems($validated['services'], ['title']) ?: null,
        ];

        DB::transaction(function () use ($insurance, $data, $validated) {
            $insurance->update($data);
            $this->channels->sync($insurance, $validated['channels']);
        });

        if ($request->hasFile('avatar')) {
            if ($insurance->avatar && ! filter_var($insurance->avatar, FILTER_VALIDATE_URL)) {
                FileHelper::deleteImage($insurance->avatar);
            }
            $insurance->update(['avatar' => FileHelper::uploadImage($request->file('avatar'), 'insurances')]);
        }

        return redirect()->route('admin.insurances.show', $insurance)
            ->with('success', 'Đã cập nhật thông tin bảo hiểm thành công.');
    }

    /**
     * Partners with ledger, legacy claim rows or status history cannot be deleted (suspend them instead).
     */
    public function destroy(Request $request, Insurance $insurance): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $insurance);

        if ($this->hasHistory($insurance)) {
            $message = 'Không thể xóa "'.$insurance->full_name.'" vì đã có sổ quỹ hoặc lịch sử. Hãy tạm đình chỉ thành viên thay vì xóa.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $this->deletePartner($insurance);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Đã xóa thành viên bảo hiểm.'])
            : redirect()->route('admin.insurances.index')->with('success', 'Đã xóa thành viên bảo hiểm.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Không có mục nào được chọn.']);
        }

        $deleted = 0;
        $skipped = 0;

        foreach (Insurance::whereIn('id', $ids)->get() as $insurance) {
            Gate::authorize('delete', $insurance);

            if ($this->hasHistory($insurance)) {
                $skipped++;

                continue;
            }

            $this->deletePartner($insurance);
            $deleted++;
        }

        $message = 'Đã xóa '.$deleted.' mục thành công.';
        if ($skipped > 0) {
            $message .= ' Bỏ qua '.$skipped.' thành viên đã có sổ quỹ hoặc lịch sử (hãy tạm đình chỉ thay vì xóa).';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Record a deposit, refund or adjustment that an admin confirmed outside the system.
     * Entries are append-only: there is no edit or delete action.
     */
    public function storeLedgerEntry(Request $request, Insurance $insurance): RedirectResponse
    {
        Gate::authorize('manageFunds', $insurance);

        $validated = $request->validateWithBag('ledger', [
            'type' => ['required', 'in:'.implode(',', array_map(fn (LedgerEntryType $type) => $type->value, self::MANUAL_LEDGER_TYPES))],
            'direction' => ['required_if:type,adjustment', 'nullable', 'in:increase,decrease'],
            'amount' => ['required', 'integer', 'min:1', 'max:'.InsuranceRequest::MAX_AMOUNT],
            'reference' => ['required_unless:type,adjustment', 'nullable', 'string', 'max:255'],
            'note' => ['required_if:type,adjustment', 'nullable', 'string', 'max:1000'],
            'submission_token' => ['required', 'string', 'max:64'],
        ], [
            'type.required' => 'Vui lòng chọn loại bút toán.',
            'type.in' => 'Loại bút toán không hợp lệ.',
            'direction.required_if' => 'Vui lòng chọn điều chỉnh tăng hay giảm quỹ.',
            'direction.in' => 'Chiều điều chỉnh không hợp lệ.',
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.integer' => 'Số tiền phải là số nguyên (VND).',
            'amount.min' => 'Số tiền phải lớn hơn 0.',
            'amount.max' => 'Số tiền quá lớn.',
            'reference.required_unless' => 'Vui lòng nhập mã giao dịch ngân hàng.',
            'reference.max' => 'Mã giao dịch tối đa :max ký tự.',
            'note.required_if' => 'Vui lòng ghi lý do điều chỉnh.',
            'note.max' => 'Ghi chú tối đa :max ký tự.',
            'submission_token.required' => 'Phiên biểu mẫu không hợp lệ, vui lòng tải lại trang.',
        ]);

        $type = LedgerEntryType::from($validated['type']);
        $negative = $type === LedgerEntryType::Refund
            || ($type === LedgerEntryType::Adjustment && $validated['direction'] === 'decrease');
        $amount = ($negative ? '-' : '').$validated['amount'];

        // A double click or resubmitted form must not write the same entry twice.
        $tokenKey = 'admin-ledger-submission:'.$validated['submission_token'];
        if (! Cache::add($tokenKey, true, now()->addHour())) {
            return back()->with('error', 'Bút toán này đã được gửi trước đó, không ghi nhận lại.');
        }

        try {
            $entry = $this->ledger->record(
                $insurance,
                $type,
                $amount,
                $request->user(),
                $validated['reference'] ?? null,
                $validated['note'] ?? null,
            );
        } catch (InsuranceLedgerException $e) {
            Cache::forget($tokenKey);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.insurances.show', $insurance)
            ->with('success', 'Đã ghi nhận bút toán "'.$type->label().'". Số dư mới: '.number_format((float) $entry->balance_after, 0, ',', '.').' đ.');
    }

    public function suspend(Request $request, Insurance $insurance, InsuranceSuspensionService $suspensions): RedirectResponse
    {
        Gate::authorize('changeStanding', $insurance);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do tạm đình chỉ.',
            'reason.max' => 'Lý do tối đa :max ký tự.',
        ]);

        if (! $suspensions->suspend($insurance, $validated['reason'], $request->user())) {
            return back()->with('error', 'Thành viên này đang bị tạm đình chỉ rồi.');
        }

        return back()->with('success', 'Đã tạm đình chỉ thành viên "'.$insurance->full_name.'".');
    }

    public function reinstate(Request $request, Insurance $insurance, InsuranceSuspensionService $suspensions): RedirectResponse
    {
        Gate::authorize('changeStanding', $insurance);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'reason.max' => 'Ghi chú tối đa :max ký tự.',
        ]);

        if (! $suspensions->reinstate($insurance, $request->user(), $validated['reason'] ?? null)) {
            return back()->with('error', 'Thành viên này không bị tạm đình chỉ.');
        }

        return back()->with('success', 'Đã khôi phục hoạt động cho thành viên "'.$insurance->full_name.'".');
    }

    private function hasHistory(Insurance $insurance): bool
    {
        return $insurance->ledgerEntries()->exists()
            || $this->legacyClaimsQuery()->where('insurance_id', $insurance->id)->exists()
            || $insurance->statusEvents()->exists();
    }

    /**
     * Rows left in the orphaned insurance_claims table by the removed claims feature.
     * shortcut: this guard must be removed in the same change that drops the table.
     * Their foreign key restricts deleting the partner, so they still count as history.
     */
    private function legacyClaimsQuery(): Builder
    {
        return DB::table('insurance_claims');
    }

    private function deletePartner(Insurance $insurance): void
    {
        if ($insurance->avatar && ! filter_var($insurance->avatar, FILTER_VALIDATE_URL)) {
            FileHelper::deleteImage($insurance->avatar);
        }

        $insurance->delete();
    }

    private function filterEmptyArrayItems(array $items, array $requiredKeys): array
    {
        return array_values(array_filter($items, function ($item) use ($requiredKeys) {
            foreach ($requiredKeys as $key) {
                if (! empty($item[$key] ?? null)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
