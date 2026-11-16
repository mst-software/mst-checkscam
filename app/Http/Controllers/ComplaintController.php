<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Helpers\FileHelper;
use App\Helpers\SeoHelper;
use App\Models\Complaint;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    private const MAX_COMPLAINTS_PER_DAY = 3;

    private const SUCCESS_MESSAGE = 'Khiếu nại của bạn đã được gửi. Ban quản trị sẽ xem xét và phản hồi qua email trong thời gian sớm nhất.';

    public function create(Request $request): View
    {
        $report = $request->filled('report')
            ? Report::approved()->where('slug', $request->string('report')->toString())->first()
            : null;

        SeoHelper::setPage(
            'Giải quyết khiếu nại',
            'Gửi khiếu nại nếu bạn cho rằng một báo cáo lừa đảo chưa chính xác; ban quản trị sẽ xem xét và phản hồi qua email.',
        );

        return view('legal.dispute', [
            'report' => $report,
            'reasons' => ComplaintReason::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real users never see this field, so pretend success without saving.
        if ($request->filled('website')) {
            return redirect()->to(route('complaints.create').'#gui-khieu-nai')->with('success', self::SUCCESS_MESSAGE);
        }

        $ip = $request->ip();

        $recentComplaintsCount = Complaint::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentComplaintsCount >= self::MAX_COMPLAINTS_PER_DAY) {
            return back()->withInput()->with('error', 'Bạn đã gửi quá nhiều khiếu nại hôm nay. Vui lòng thử lại vào ngày mai!');
        }

        $validated = $request->validate([
            'report' => ['nullable', 'string', Rule::exists('reports', 'slug')->where('status', 'approved')],
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'reason' => ['required', Rule::enum(ComplaintReason::class)],
            'message' => 'required|string|min:20|max:5000',
            'evidence_images' => 'nullable|array|max:5',
            'evidence_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ], [
            'report.exists' => 'Báo cáo cần khiếu nại không tồn tại.',
            'contact_name.required' => 'Vui lòng nhập họ tên.',
            'contact_email.required' => 'Vui lòng nhập email để chúng tôi phản hồi.',
            'contact_email.email' => 'Email không hợp lệ.',
            'reason.required' => 'Vui lòng chọn lý do khiếu nại.',
            'reason.enum' => 'Lý do khiếu nại không hợp lệ.',
            'message.required' => 'Vui lòng nhập nội dung khiếu nại.',
            'message.min' => 'Vui lòng mô tả khiếu nại với ít nhất 20 ký tự.',
            'evidence_images.max' => 'Chỉ được tải lên tối đa 5 ảnh.',
            'evidence_images.*.image' => 'File tải lên phải là hình ảnh.',
            'evidence_images.*.max' => 'Mỗi hình ảnh không được vượt quá 5MB.',
        ]);

        $reportId = isset($validated['report'])
            ? Report::where('slug', $validated['report'])->value('id')
            : null;

        $complaint = Complaint::create([
            'report_id' => $reportId,
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'reason' => $validated['reason'],
            'message' => $validated['message'],
            'status' => ComplaintStatus::Pending,
            'ip_address' => $ip,
        ]);

        if ($request->hasFile('evidence_images')) {
            $paths = FileHelper::uploadMultipleImages($request->file('evidence_images'), 'complaints');
            $complaint->update(['evidence_images' => $paths]);
        }

        return redirect()->to(route('complaints.create').'#gui-khieu-nai')->with('success', self::SUCCESS_MESSAGE);
    }
}
