{{--
  Light header for inner pages: optional breadcrumb, h1, short description and an actions slot.
  Usage: <x-ucs.page-header title="..." description="..." :breadcrumbs="[['name' => '...', 'url' => '...']]">actions</x-ucs.page-header>
--}}

@props([
  'title',
  'description' => null,
  'breadcrumbs' => [],
])

<section
  {{ $attributes->class(['border-line bg-surface mb-8 border-b sm:mb-10']) }}
  aria-labelledby="page-title"
>
  @if (count($breadcrumbs) > 0)
    {{-- The shared breadcrumb brings its own strip; flatten it into this header --}}
    <div class="[&>div]:mb-0 [&>div]:border-b-0 [&>div]:pb-0">
      <x-breadcrumb :links="$breadcrumbs" />
    </div>
  @endif

  <x-ucs.container :class="count($breadcrumbs) > 0 ? 'pt-4 pb-6 sm:pt-5 sm:pb-8' : 'py-6 sm:py-8'">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div class="max-w-3xl min-w-0">
        <h1 id="page-title" class="text-ink text-2xl font-semibold tracking-tight text-balance sm:text-3xl">
          {{ $title }}
        </h1>
        @if ($description)
          <p class="text-ink-muted mt-2 text-sm leading-relaxed sm:text-base">{{ $description }}</p>
        @endif
      </div>

      @if (! $slot->isEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
      @endif
    </div>
  </x-ucs.container>
</section>
