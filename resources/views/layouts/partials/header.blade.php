@php
  $siteTitle = $siteConfig['title'] ?? 'Scamproof';

  $navItems = [
    ['label' => 'Tra cứu', 'url' => route('home'), 'active' => request()->routeIs('home') || request()->is('search*')],
    ['label' => 'Cảnh báo', 'url' => route('warnings.index'), 'active' => request()->routeIs('warnings.index')],
    ['label' => 'Tố cáo', 'url' => route('reports'), 'active' => request()->is('to-cao-lua-dao*')],
    ['label' => 'Bảo hiểm CS', 'url' => route('insurances.frontend.index'), 'active' => request()->is('bao-hiem-cs*')],
    ['label' => 'Kiểm tra GDV', 'url' => route('intermediary.verify'), 'active' => request()->routeIs('intermediary.verify')],
    ['label' => 'Khu mua bán', 'url' => route('newfeed.index'), 'active' => request()->is('newfeed*')],
    ['label' => 'Bài viết', 'url' => route('posts.frontend.index'), 'active' => request()->is('bai-viet*')],
    ['label' => 'API', 'url' => url('/api-checkscam'), 'active' => request()->is('api-checkscam')],
  ];

  $googleUser = auth()->check() && auth()->user()->role === 'user' ? auth()->user() : null;
@endphp

<header class="border-line bg-surface sticky top-0 z-50 border-b">
  <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
    {{-- Logo --}}
    <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="Scamproof">
      <x-brand />
    </a>

    {{-- Desktop navigation --}}
    <nav class="hidden items-center gap-0.5 lg:flex xl:ml-4 xl:gap-1" aria-label="Điều hướng chính">
      @foreach ($navItems as $item)
        <a
          href="{{ $item['url'] }}"
          @if ($item['active']) aria-current="page" @endif
          class="{{ $item['active'] ? 'bg-accent-soft text-accent' : 'text-ink-muted hover:bg-surface-muted hover:text-ink' }} rounded-control px-2 py-2 text-sm font-medium whitespace-nowrap transition-colors xl:px-3"
        >
          {{ $item['label'] }}
        </a>
      @endforeach
    </nav>

    <div class="ml-auto flex items-center gap-2">
      {{-- Theme toggle --}}
      <button
        type="button"
        data-theme-toggle
        class="border-line text-ink-muted hover:bg-surface-muted hover:text-ink rounded-control flex h-9 w-9 cursor-pointer items-center justify-center border transition-colors"
        aria-label="Chuyển giao diện sáng/tối"
        title="Chuyển giao diện sáng/tối"
      >
        {{-- Font Awesome sets display on <i>, so visibility is toggled on wrapper spans --}}
        <span class="dark:hidden"><i class="fa-regular fa-moon text-sm"></i></span>
        <span class="hidden dark:inline"><i class="fa-solid fa-sun text-sm"></i></span>
      </button>

      {{-- Account --}}
      @auth
        @if ($googleUser)
          <div class="relative">
            <button
              type="button"
              data-toggle-target="#account-menu"
              aria-expanded="false"
              aria-haspopup="true"
              class="border-line text-ink hover:bg-surface-muted rounded-control flex h-9 cursor-pointer items-center gap-2 border px-2 text-sm font-medium transition-colors"
            >
              <img src="{{ $googleUser->avatar_url }}" class="h-6 w-6 rounded-full object-cover" alt="" />
              <span class="hidden max-w-32 truncate sm:inline">
                {{ Str::limit($googleUser->full_name ?? $googleUser->username, 16) }}
              </span>
              @if ($googleUser->is_verified)
                <i class="fa-solid fa-circle-check text-success text-xs" title="Đã xác minh"></i>
              @endif
            </button>
            <div
              id="account-menu"
              class="border-line bg-surface rounded-card absolute top-full right-0 z-50 mt-2 hidden w-48 border py-1 shadow-sm"
            >
              <form action="{{ route('auth.logout') }}" method="POST">
                @csrf
                <button
                  type="submit"
                  class="text-ink-muted hover:bg-surface-muted hover:text-ink flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-sm"
                >
                  <i class="fa-solid fa-right-from-bracket text-xs"></i>
                  Đăng xuất
                </button>
              </form>
            </div>
          </div>
        @endif
      @else
        <a
          href="{{ route('auth.google') }}"
          class="border-line bg-surface text-ink hover:bg-surface-muted rounded-control flex h-9 items-center gap-2 border px-3 text-sm font-medium transition-colors"
        >
          <svg width="14" height="14" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path
              d="M17.64 9.2045c0-.638-.0573-1.2518-.1636-1.8409H9v3.4814h4.8436c-.2086 1.125-.8427 2.0782-1.7959 2.7164v2.2581h2.9087c1.7018-1.567 2.6836-3.874 2.6836-6.6149z"
              fill="#4285F4"
            />
            <path
              d="M9 18c2.43 0 4.4673-.806 5.9564-2.1805l-2.9087-2.2582c-.8064.54-1.8368.859-3.0477.859-2.3441 0-4.3277-1.5832-5.036-3.71H.9574v2.3318A8.9973 8.9973 0 009 18z"
              fill="#34A853"
            />
            <path
              d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.5905.1018-1.1641.282-1.71V4.9582H.9573A8.9965 8.9965 0 000 9c0 1.4518.3477 2.8268.9573 4.0418L3.964 10.71z"
              fill="#FBBC05"
            />
            <path
              d="M9 3.5795c1.3214 0 2.5077.4541 3.4405 1.346l2.5813-2.5814C13.4632.8918 11.426 0 9 0A8.9973 8.9973 0 00.9573 4.9582L3.964 7.29C4.6723 5.1627 6.6559 3.5795 9 3.5795z"
              fill="#EA4335"
            />
          </svg>
          <span class="hidden sm:inline">Đăng nhập</span>
        </a>
      @endauth

      {{-- Mobile menu toggle --}}
      <button
        type="button"
        data-toggle-target="#mobile-menu"
        aria-expanded="false"
        aria-controls="mobile-menu"
        class="border-line text-ink hover:bg-surface-muted rounded-control flex h-9 w-9 cursor-pointer items-center justify-center border transition-colors lg:hidden"
        aria-label="Mở menu"
      >
        <i class="fa-solid fa-bars text-sm"></i>
      </button>
    </div>
  </div>

  {{-- Mobile navigation --}}
  <nav id="mobile-menu" class="border-line bg-surface hidden border-t lg:hidden" aria-label="Điều hướng di động">
    <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-3 sm:px-6">
      @foreach ($navItems as $item)
        <a
          href="{{ $item['url'] }}"
          @if ($item['active']) aria-current="page" @endif
          class="{{ $item['active'] ? 'bg-accent-soft text-accent' : 'text-ink hover:bg-surface-muted' }} rounded-control px-3 py-2.5 text-sm font-medium"
        >
          {{ $item['label'] }}
        </a>
      @endforeach
    </div>
  </nav>
</header>
