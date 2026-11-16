@props([
  'stats' => [],
  'isAction' => true,
  'recentSearches' => collect(),
])

@if ($isAction)
  {{-- Soft red glow at the top of the light page; colors come from tokens so dark mode follows. --}}
  <section
    aria-labelledby="hero-title"
    class="bg-[radial-gradient(70%_55%_at_50%_-10%,color-mix(in_oklab,var(--color-accent)_10%,transparent),transparent_70%)]"
  >
    <x-ucs.container class="pt-10 pb-12 text-center md:pt-14 md:pb-16">
      <div class="mx-auto max-w-5xl">
        <div>
          <span
            class="border-line bg-surface text-ink-muted inline-flex items-center gap-2 rounded-full border py-1.5 pr-4 pl-3 text-[13px] font-semibold shadow-xs"
          >
            <span class="bg-success ring-success/15 size-2 shrink-0 rounded-full ring-4" aria-hidden="true"></span>
            Dữ liệu cộng đồng, được kiểm duyệt
          </span>
        </div>

        <h1
          id="hero-title"
          class="scanner-title text-ink mt-1 text-[clamp(1.1rem,5.2vw,2.75rem)] leading-[1.15] font-black tracking-[-0.02em] whitespace-nowrap uppercase md:mt-2"
        >
          Kiểm tra &amp; tố cáo
          <span
            class="text-accent relative inline-block whitespace-nowrap after:absolute after:right-0 after:-bottom-1.5 after:left-[2%] after:h-2 after:rounded-full after:bg-[linear-gradient(90deg,transparent,color-mix(in_oklab,var(--color-accent)_25%,transparent),transparent)]"
          >
            scam.
          </span>
        </h1>

        <p class="text-ink-muted mx-auto mt-5 max-w-[40rem] text-base leading-relaxed md:mt-6 md:text-lg">
          Hệ thống dữ liệu lớn nhất Việt Nam giúp bạn kiểm tra độ tín nhiệm của đối tác thông qua SĐT, số tài khoản hoặc
          link mạng xã hội.
        </p>

        <x-hero.search-form class="mx-auto mt-8 max-w-[48.75rem] md:mt-9" hints :recent-searches="$recentSearches" />

        <x-hero.stats :stats="$stats" class="mt-8" />

        <nav aria-label="Lối tắt" class="mt-8">
          <ul class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
            <li>
              <x-hero.action-card :href="route('reports')" icon="fa-solid fa-flag" label="Tố cáo scam" sub="Report" />
            </li>
            <li>
              <x-hero.action-card
                :href="route('insurances.frontend.index')"
                icon="fa-solid fa-shield-halved"
                label="Bảo hiểm CS"
                sub="Insurance"
              />
            </li>
            <li>
              <x-hero.action-card
                :href="route('newfeed.index')"
                icon="fa-solid fa-store"
                label="Chợ buôn bán"
                sub="Trading"
              />
            </li>
            <li>
              <x-hero.action-card
                :href="url('/api-checkscam')"
                icon="fa-solid fa-robot"
                label="Bot check"
                sub="Automation"
              />
            </li>
          </ul>
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
