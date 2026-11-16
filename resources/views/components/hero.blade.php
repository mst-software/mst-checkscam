@props([
  'stats' => [],
  'isAction' => true,
  'recentSearches' => collect(),
])

@if ($isAction)
  <section class="bg-hero text-on-hero" aria-labelledby="hero-title">
    <x-ucs.container class="py-12 text-center sm:py-16">
      <h1 id="hero-title" class="text-3xl font-semibold tracking-tight sm:text-4xl lg:text-5xl">
        Kiểm tra trước khi chuyển tiền
      </h1>
      <p class="text-on-hero-muted mx-auto mt-3 max-w-2xl text-base sm:text-lg">
        Tra cứu SĐT, số tài khoản, Facebook hoặc link trong dữ liệu tố cáo.
      </p>

      <x-hero.search-form class="mx-auto mt-8 max-w-3xl text-left" :recent-searches="$recentSearches" />
    </x-ucs.container>

    <x-hero.stats :stats="$stats" />
  </section>
@else
  <section class="bg-hero" aria-label="Tra cứu">
    <x-ucs.container class="py-5 sm:py-6">
      <x-hero.search-form class="mx-auto max-w-4xl" :hints="false" size="md" />
    </x-ucs.container>
  </section>
@endif
