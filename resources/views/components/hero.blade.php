@props([
  'stats' => [],
  'isAction' => true,
  'recentSearches' => collect(),
])

@if ($isAction)
  <section aria-labelledby="hero-title">
    <x-ucs.container class="pt-12 pb-10 text-center md:pt-16 md:pb-14">
      <div class="mx-auto max-w-4xl">
        <h1
          id="hero-title"
          class="hero-title text-accent text-3xl leading-[1.3] font-black tracking-tight uppercase md:text-4xl lg:text-5xl"
        >
          KIỂM TRA &amp; TỐ CÁO SCAM.
        </h1>
        <p class="text-ink-muted mx-auto mt-5 max-w-2xl text-sm leading-relaxed font-medium md:text-lg">
          Hệ thống dữ liệu lớn nhất Việt Nam giúp bạn kiểm tra độ tín nhiệm của đối tác thông qua SĐT, Số TK hoặc Link
          mạng xã hội.
        </p>

        <x-hero.search-form class="mx-auto mt-8 max-w-3xl md:mt-10" :recent-searches="$recentSearches" />

        <x-hero.stats :stats="$stats" class="mt-6" />

        <nav aria-label="Lối tắt" class="mt-8 md:mt-12">
          <ul class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
            <li>
              <x-hero.action-card
                :href="route('reports')"
                icon="fa-solid fa-bullhorn"
                label="Tố cáo Scam"
                sub="Report"
              />
            </li>
            <li>
              <x-hero.action-card
                :href="route('insurances.frontend.index')"
                icon="fa-solid fa-shield-cat"
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
                icon="fa-brands fa-telegram"
                label="Bot Check"
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
