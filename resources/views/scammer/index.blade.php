@extends('layouts.app')

@section('structured_data')
  {{-- Masked values only: this block is read by search engines and must not carry raw PII --}}
  @php
    $breadcrumbJsonLd = [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Danh sách cảnh báo', 'item' => route('warnings.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Cảnh báo ' . $maskedTarget, 'item' => route('scammer.show', $report->slug)],
      ],
    ];
  @endphp

  <script type="application/ld+json">
    {!! json_encode($breadcrumbJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
  </script>
@endsection

@section('content')
  <x-hero :is-action="false" />

  <main class="flex-1 pb-16">
    <x-breadcrumb
      :links="[
        ['name' => 'Cảnh báo', 'url' => route('warnings.index')],
        ['name' => $maskedTarget],
      ]"
    />

    <x-ucs.container>
      @include('scammer.partials.verdict')

      <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-6">
          @include('scammer.partials.description')
          @include('scammer.partials.evidence')
          @include('scammer.partials.details')

          @if ($targetReports->count() > 1)
            @include('scammer.partials.timeline')
          @endif

          @include('scammer.partials.comments')
        </div>

        <aside class="min-w-0 space-y-6" aria-label="Thông tin thêm">
          @include('scammer.partials.safety-tips')
          @include('scammer.partials.related')
        </aside>
      </div>
    </x-ucs.container>
  </main>
@endsection
