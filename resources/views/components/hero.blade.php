@props([
  'stats' => [],
  'isAction' => true,
  'recentSearches' => collect(),
  'feedReports' => collect(),
  'latestApprovedAt' => null,
])

@if ($isAction)
  {{--
    Home hero: search on the left, live approved warnings on the right (lg+).
    Below lg it is one column: heading, search, short assurances, the live card, then the shortcuts.
    Soft red glow comes from tokens so dark mode follows.
  --}}
  @php
    $shortcuts = [
      ['href' => route('reports'), 'icon' => 'fa-solid fa-flag', 'label' => 'Tố cáo scam', 'featured' => true],
      ['href' => route('insurances.frontend.index'), 'icon' => 'fa-solid fa-shield-halved', 'label' => 'Bảo hiểm CS', 'featured' => false],
      ['href' => route('intermediary.verify'), 'icon' => 'fa-solid fa-user-check', 'label' => 'Kiểm tra GDV', 'featured' => false],
    ];
  @endphp

  <section
    aria-labelledby="hero-title"
    class="bg-[radial-gradient(60%_60%_at_25%_0%,color-mix(in_oklab,var(--color-accent)_8%,transparent),transparent_70%)]"
  >
    <x-ucs.container class="pt-5 pb-8 sm:pt-8 lg:pt-10 lg:pb-12">
      <div class="grid gap-x-11 gap-y-5 lg:grid-cols-[1.08fr_1fr] lg:grid-rows-[auto_1fr]">
        <div class="min-w-0 lg:col-start-1 lg:row-start-1">
          <span
            class="border-line bg-surface text-ink-muted inline-flex items-center gap-2 rounded-full border py-1 pr-3.5 pl-2.5 text-xs font-semibold shadow-xs sm:text-[13px]"
          >
            <span class="bg-success ring-success/15 size-2 shrink-0 rounded-full ring-4" aria-hidden="true"></span>
            Dữ liệu cộng đồng, được kiểm duyệt
          </span>

          <div>
            <h1
              id="hero-title"
              class="scanner-title text-ink mt-1 text-[clamp(1.25rem,6.4vw,2.875rem)] leading-[1.1] font-black tracking-[-0.025em] whitespace-nowrap uppercase sm:mt-2 lg:max-w-[34rem] lg:whitespace-normal"
            >
              Kiểm tra &amp; tố cáo
              <span
                class="text-accent relative inline-block whitespace-nowrap after:absolute after:right-0 after:-bottom-1.5 after:left-[2%] after:h-2 after:rounded-full after:bg-[linear-gradient(90deg,transparent,color-mix(in_oklab,var(--color-accent)_25%,transparent),transparent)]"
              >
                scam.
              </span>
            </h1>
          </div>

          <p class="text-ink-muted mt-1 hidden text-base leading-relaxed sm:block lg:text-[17px]">
            Tra cứu SĐT, số tài khoản, Facebook hoặc link trước khi chuyển tiền.
          </p>

          <x-hero.search-form class="mt-4 sm:mt-6" hints :recent-searches="$recentSearches" />

          {{-- Short assurances on small screens, the full sentences stacked on desktop. --}}
          <ul
            aria-label="Cam kết khi tra cứu"
            class="text-ink-muted mt-3.5 flex flex-wrap gap-x-3 gap-y-1.5 text-xs font-medium sm:gap-x-4 lg:hidden"
          >
            @foreach (['Miễn phí', 'Có kiểm duyệt', 'Bảo mật người báo'] as $assurance)
              <li class="inline-flex items-center gap-1.5">
                <i class="fa-solid fa-circle-check text-success" aria-hidden="true"></i>
                {{ $assurance }}
              </li>
            @endforeach
          </ul>
          <ul
            aria-label="Cam kết khi tra cứu"
            class="text-ink-muted mt-5 hidden flex-col gap-2 text-[13.5px] font-medium lg:flex"
          >
            @foreach (['Tra cứu miễn phí, không cần đăng ký', 'Mọi tố cáo đều qua kiểm duyệt', 'Không công khai danh tính người báo cáo'] as $assurance)
              <li class="inline-flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-success" aria-hidden="true"></i>
                {{ $assurance }}
              </li>
            @endforeach
          </ul>
        </div>

        <div class="min-w-0 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-start">
          @include('partials.home.hero-feed', ['feedReports' => $feedReports, 'latestApprovedAt' => $latestApprovedAt])
        </div>

        <nav aria-label="Lối tắt" class="min-w-0 lg:col-start-1 lg:row-start-2">
          <ul class="flex flex-wrap gap-2.5">
            @foreach ($shortcuts as $shortcut)
              <li>
                <a
                  href="{{ $shortcut['href'] }}"
                  @class([
                    'focus-visible:outline-accent inline-flex items-center gap-2 rounded-xl border px-3.5 py-2.5 text-[13.5px] font-bold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2',
                    'border-accent/30 bg-accent-soft text-accent hover:border-accent/60' => $shortcut['featured'],
                    'border-line bg-surface text-ink hover:border-line-strong shadow-xs' => ! $shortcut['featured'],
                  ])
                >
                  <i class="{{ $shortcut['icon'] }} text-accent" aria-hidden="true"></i>
                  {{ $shortcut['label'] }}
                </a>
              </li>
            @endforeach
          </ul>

          <p class="text-ink-subtle mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px]">
            <span>Tiện ích khác:</span>
            <a
              href="{{ route('newfeed.index') }}"
              class="text-ink-muted hover:text-accent focus-visible:outline-accent rounded-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2"
            >
              Chợ buôn bán
            </a>
            <span aria-hidden="true">·</span>
            <a
              href="{{ url('/api-checkscam') }}"
              class="text-ink-muted hover:text-accent focus-visible:outline-accent rounded-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2"
            >
              Bot check
            </a>
          </p>
        </nav>
      </div>
    </x-ucs.container>
  </section>
@else
  <section aria-label="Tra cứu">
    <x-ucs.container class="pt-6 pb-2 sm:pt-8">
      <x-hero.search-form class="mx-auto max-w-4xl" size="md" />
    </x-ucs.container>
  </section>
@endif
