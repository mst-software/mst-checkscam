<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminComplaintController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Complaint::class);

        $status = ComplaintStatus::tryFrom($request->string('status')->toString());

        $complaints = Complaint::query()
            ->with('report')
            ->when($status, fn ($query) => $query->ofStatus($status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pendingCount = Complaint::query()->ofStatus(ComplaintStatus::Pending)->count();

        return view('admin.complaints.index', compact('complaints', 'status', 'pendingCount'));
    }

    public function show(Complaint $complaint): View
    {
        Gate::authorize('view', $complaint);

        $complaint->load(['report', 'handler']);

        return view('admin.complaints.show', compact('complaint'));
    }

    public function resolve(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        return $this->handle($complaint, ComplaintStatus::Resolved, $validated['admin_note'] ?? null);
    }

    public function dismiss(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize('update', $complaint);

        $validated = $request->validate([
            'admin_note' => 'required|string|max:2000',
        ], [
            'admin_note.required' => 'Vui lòng ghi lý do bác bỏ khiếu nại.',
        ]);

        return $this->handle($complaint, ComplaintStatus::Dismissed, $validated['admin_note']);
    }

    private function handle(Complaint $complaint, ComplaintStatus $status, ?string $adminNote): RedirectResponse
    {
        if (! $complaint->isPending()) {
            return back()->with('error', 'Khiếu nại này đã được xử lý rồi.');
        }

        $complaint->update([
            'status' => $status,
            'admin_note' => $adminNote,
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ]);

        return back()->with('success', $status === ComplaintStatus::Resolved
            ? 'Đã đánh dấu khiếu nại là đã xử lý.'
            : 'Đã bác bỏ khiếu nại.');
    }
}
