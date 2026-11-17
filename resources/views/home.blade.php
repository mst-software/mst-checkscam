@extends('layouts.app')

{{--
  Rendered by HomeController (home sections) and SearchController (search results, when $query is filled).
--}}
@php
  $isSearch = isset($results) && filled($query ?? null);
@endphp

@section('content')
  <main class="grow">
    @if ($isSearch)
      <x-hero :stats="$stats" :is-action="false" />
      @include('partials.search.results')
    @else
      <x-hero :stats="$stats" :recent-searches="$recentSearches ?? collect()" />

      <x-ucs.container class="space-y-10 pb-8 md:space-y-12 lg:space-y-14 lg:pb-10">
        @include('partials.home.how-it-works')

        @include('partials.home.moderation')

        <div class="grid gap-8 lg:grid-cols-12">
          <div class="min-w-0 lg:col-span-8">
            @include(
              'partials.home.latest-reports',
              [
                'latestReports' => $latestReports ?? collect(),
                'latestApprovedAt' => $latestApprovedAt ?? null,
              ]
            )
          </div>

          <aside class="min-w-0 space-y-6 lg:col-span-4" aria-label="Thông tin thêm">
            @include('partials.home.top-searches', ['topWeeklySearches' => $topWeeklySearches ?? collect()])
            @include('partials.report-cta')
          </aside>
        </div>

        @include('partials.home.comments')

        @include('partials.home.trusted-insurances', ['trustedInsurances' => $trustedInsurances ?? collect()])

        @include('partials.home.commitments')

        @include('partials.home.official-channels')

        @include('partials.home.faq')
      </x-ucs.container>
    @endif
  </main>

  <x-notification />
@endsection
