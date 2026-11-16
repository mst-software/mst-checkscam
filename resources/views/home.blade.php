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

      <x-ucs.container class="space-y-10 py-8 lg:py-10">
        @include('partials.home.quick-actions')

        <x-banner-ads position="home_top" class="mb-0" />

        <div class="grid gap-8 lg:grid-cols-12">
          <div class="min-w-0 space-y-8 lg:col-span-8">
            @include('partials.home.latest-reports', ['latestReports' => $latestReports ?? collect()])
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

        @include('partials.home.community')

        @include('partials.home.faq')
      </x-ucs.container>
    @endif
  </main>

  <x-notification />
@endsection
