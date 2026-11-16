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

      <x-ucs.container class="space-y-12 pb-8 lg:space-y-14 lg:pb-10">
        @include('partials.home.how-it-works')

        @include('partials.home.moderation')

        <div class="grid gap-8 lg:grid-cols-12">
          <div class="min-w-0 space-y-8 lg:col-span-8">
            @include(
              'partials.home.latest-reports',
              [
                'latestReports' => $latestReports ?? collect(),
                'latestApprovedAt' => $latestApprovedAt ?? null,
              ]
            )
            <x-banner-ads position="home_between" class="mb-0" />
          </div>

          <aside class="min-w-0 space-y-6 lg:col-span-4" aria-label="Thông tin thêm">
            @include('partials.home.top-searches', ['topWeeklySearches' => $topWeeklySearches ?? collect()])
            @include('partials.report-cta')
            <x-banner-ads position="home_sidebar" :limit="3" class="mb-4" />
          </aside>
        </div>

        @include('partials.home.comments')

        <x-banner-ads position="home_between" :skip="1" class="mb-0" />

        @include('partials.home.trusted-insurances', ['trustedInsurances' => $trustedInsurances ?? collect()])

        @include('partials.home.commitments')

        <div class="space-y-6">
          @include('partials.home.official-channels')

          @include('partials.home.operator')
        </div>

        @include('partials.home.faq')

        <x-banner-ads position="home_top" class="mb-0" />
      </x-ucs.container>
    @endif
  </main>

  <x-notification />
@endsection
