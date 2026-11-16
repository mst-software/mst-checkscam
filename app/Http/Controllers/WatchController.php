<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WatchController extends Controller
{
    public function index(): View
    {
        $watches = Auth::user()->watches()->latest()->get();

        return view('watch.index', [
            'watches' => $watches,
            'maxWatches' => Watch::MAX_PER_USER,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
        ], [
            'value.required' => 'Vui lòng nhập số điện thoại, số tài khoản hoặc link Facebook cần theo dõi.',
            'value.max' => 'Giá trị theo dõi không được vượt quá 255 ký tự.',
        ]);

        $target = Watch::normalizeTarget($validated['value']);

        if ($target === null) {
            throw ValidationException::withMessages([
                'value' => 'Chỉ có thể theo dõi số điện thoại, số tài khoản ngân hàng hoặc link Facebook.',
            ]);
        }

        [$type, $value] = $target;
        $user = Auth::user();

        if ($user->watches()->matchingTarget($type, $value)->exists()) {
            return back()->with('watch_status', 'Bạn đã theo dõi đối tượng này rồi.');
        }

        if ($user->watches()->count() >= Watch::MAX_PER_USER) {
            throw ValidationException::withMessages([
                'value' => 'Bạn chỉ được theo dõi tối đa '.Watch::MAX_PER_USER.' đối tượng. Vui lòng bỏ theo dõi bớt trước khi thêm mới.',
            ]);
        }

        $user->watches()->create([
            'type' => $type,
            'value' => $value,
        ]);

        return back()->with('watch_status', 'Đã theo dõi. Bạn sẽ nhận email khi có báo cáo mới được duyệt.');
    }

    public function destroy(Watch $watch): RedirectResponse
    {
        if (Auth::user()->cannot('delete', $watch)) {
            abort(403);
        }

        $watch->delete();

        return back()->with('watch_status', 'Đã bỏ theo dõi.');
    }
}
