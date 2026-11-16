@props([
  'stats' => [],
  'isAction' => true,
  'recentSearches' => collect(),
])

@if ($isAction)
  <section class="border-line bg-surface border-b" aria-labelledby="hero-title">
    <x-ucs.container class="py-10 lg:py-14">
      <div class="grid gap-8 lg:grid-cols-12 lg:items-center lg:gap-12">
        <div class="min-w-0 lg:col-span-7">
          <h1 id="hero-title" class="text-ink text-3xl font-semibold tracking-tight sm:text-4xl">
            Kiểm tra trước khi chuyển tiền
          </h1>
          <p class="text-ink-muted mt-3 text-base">Tra cứu trong dữ liệu tố cáo đã được kiểm duyệt từ cộng đồng.</p>

          <x-hero.search-form class="mt-6" :recent-searches="$recentSearches" />
        </div>

        <div class="lg:col-span-5">
          <x-hero.stats :stats="$stats" />
        </div>
      </div>
    </x-ucs.container>
  </section>
@else
  <section class="border-line bg-surface border-b" aria-label="Tra cứu">
    <x-ucs.container class="py-4 sm:py-5">
      <x-hero.search-form :hints="false" />
    </x-ucs.container>
  </section>
@endif
