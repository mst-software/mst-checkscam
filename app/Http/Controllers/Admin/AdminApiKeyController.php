<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminApiKeyController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $apiKeys = ApiKey::with('user')->latest()->paginate(20);

        return view('admin.api-keys.index', compact('apiKeys'));
    }

    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.api-keys.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
        ], [
            'name.required' => 'Vui lòng nhập tên đối tác / ứng dụng.',
            'rate_limit_per_minute.required' => 'Vui lòng nhập giới hạn truy vấn.',
            'rate_limit_per_minute.integer' => 'Giới hạn truy vấn phải là số nguyên.',
            'rate_limit_per_minute.min' => 'Giới hạn truy vấn tối thiểu là :min.',
            'rate_limit_per_minute.max' => 'Giới hạn truy vấn tối đa là :max.',
        ]);

        [, $plainKey] = ApiKey::issue(
            $validated['name'],
            $request->user()->id,
            (int) $validated['rate_limit_per_minute'],
        );

        // Shown exactly once on the next page; only the hash is stored.
        return redirect()->route('admin.api-keys.index')
            ->with('success', 'Đã tạo API key. Hãy sao chép ngay, key sẽ không hiển thị lại.')
            ->with('plain_api_key', $plainKey);
    }

    public function revoke(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $this->ensureAdmin($request);

        $apiKey->update(['is_active' => false]);

        return back()->with('success', 'Đã thu hồi API key "'.$apiKey->name.'".');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Chỉ quản trị viên mới được quản lý API key.');
    }
}
