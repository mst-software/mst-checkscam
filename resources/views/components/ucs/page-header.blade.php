{{--
  Compact navy header for inner pages: breadcrumb, h1, one-line description, optional actions slot.
  Usage: <x-ucs.page-header title="..." description="..." :breadcrumbs="[['name' => '...', 'url' => '...']]">actions</x-ucs.page-header>
--}}

@props([
  'title',
  'description' => null,
  'breadcrumbs' => [],
])

<section
  {{ $attributes->class(['bg-hero text-on-hero mb-8 sm:mb-10']) }}
  aria-labelledby="page-title"
>
  <x-ucs.container class="py-6 sm:py-8">
    @if (count($breadcrumbs) > 0)
      <x-breadcrumb :links="$breadcrumbs" :on-hero="true" />
    @endif

    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div class="max-w-3xl min-w-0">
        <h1 id="page-title" class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ $title }}</h1>
        @if ($description)
          <p class="text-on-hero-muted mt-2 text-sm sm:text-base">{{ $description }}</p>
        @endif
      </div>

      @if (! $slot->isEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
      @endif
    </div>
  </x-ucs.container>
</section>
