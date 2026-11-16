{{-- Usage: @include('partials.watch-button', ['value' => $report->target_id]) --}}
@if (App\Models\Watch::normalizeTarget($value ?? null))
  @auth
    <form action="{{ route('watch.store') }}" method="POST">
      @csrf
      <input type="hidden" name="value" value="{{ $value }}" />
      <button
        type="submit"
        class="text-cs_blue flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-100 bg-white py-3 text-xs font-bold tracking-widest uppercase transition-all hover:bg-blue-50 dark:border-gray-700 dark:bg-slate-800 dark:hover:bg-slate-700"
      >
        <i class="fa-regular fa-bell text-sm"></i>
        Theo dõi
      </button>
    </form>
  @else
    <a
      href="{{ route('auth.google') }}"
      class="text-cs_blue flex w-full items-center justify-center gap-2 rounded-2xl border border-gray-100 bg-white py-3 text-xs font-bold tracking-widest uppercase transition-all hover:bg-blue-50 dark:border-gray-700 dark:bg-slate-800 dark:hover:bg-slate-700"
    >
      <i class="fa-regular fa-bell text-sm"></i>
      Đăng nhập để theo dõi
    </a>
  @endauth
  @if (session('watch_status'))
    <p class="mt-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ session('watch_status') }}</p>
  @endif

  @error('value')
    <p class="text-cs_red mt-1 text-xs font-semibold">{{ $message }}</p>
  @enderror
@endif
