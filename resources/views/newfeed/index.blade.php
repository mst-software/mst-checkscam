@extends('layouts.app')

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Khu mua bán"
      description="Nơi cộng đồng đăng tin mua bán tài khoản, dịch vụ MMO. Đăng nhập bằng Google để đăng tin và báo cáo bài vi phạm."
      :breadcrumbs="[['name' => 'Khu mua bán', 'url' => route('newfeed.index')]]"
    >
      <button
        type="button"
        id="btn-open-post-form"
        class="bg-hero-action hover:bg-hero-action-hover rounded-control inline-flex h-10 cursor-pointer items-center gap-2 px-4 text-sm font-medium text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hero-action"
      >
        <i class="fa-solid fa-plus" aria-hidden="true"></i>
        Đăng bài
      </button>
    </x-ucs.page-header>

    <x-ucs.container>
      <div class="mb-6 space-y-3">
        <div class="relative max-w-xl">
          <label for="nf-search" class="sr-only">Tìm bài đăng</label>
          <i
            class="fa-solid fa-magnifying-glass text-ink-subtle pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm"
            aria-hidden="true"
          ></i>
          <input
            id="nf-search"
            type="search"
            placeholder="Tìm tài khoản, dịch vụ MMO..."
            autocomplete="off"
            class="border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent h-11 w-full border pr-3 pl-9 text-base focus-visible:outline-2 focus-visible:outline-offset-1 sm:text-sm"
          />
        </div>

        @php
          $chipClass =
            'nf-chip border-line text-ink-muted hover:border-line-strong hover:text-ink aria-pressed:border-accent aria-pressed:bg-accent aria-pressed:text-on-accent shrink-0 cursor-pointer rounded-full border px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent';
        @endphp

        <div
          class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0"
          id="nf-filter-bar"
          role="group"
          aria-label="Lọc theo danh mục"
        >
          <button type="button" class="{{ $chipClass }}" data-category="" aria-pressed="true">
            Hoạt động mới
            <span class="tabular-nums opacity-75">({{ $posts->total() }})</span>
          </button>
          @foreach ($categories as $cat)
            <button type="button" class="{{ $chipClass }}" data-category="{{ $cat }}" aria-pressed="false">
              {{ $cat }}
              @if (isset($categoryCounts[$cat]))
                <span class="tabular-nums opacity-75">({{ $categoryCounts[$cat] }})</span>
              @endif
            </button>
          @endforeach
        </div>
      </div>

      <div id="nf-feed" class="columns-1 gap-4 md:columns-2 lg:columns-3" aria-live="polite">
        @forelse ($posts as $post)
          @include('newfeed.partials.card', ['post' => $post, 'userReportedIds' => $userReportedIds])
        @empty
          @include('newfeed.partials.empty', ['message' => 'Chưa có bài đăng nào.'])
        @endforelse
      </div>

      <div id="nf-sentinel" class="mt-6 flex justify-center py-4">
        <span id="nf-loader" class="text-ink-subtle hidden text-sm">
          <i class="fa-solid fa-circle-notch fa-spin mr-2" aria-hidden="true"></i>
          Đang tải...
        </span>
      </div>
    </x-ucs.container>
  </main>

  <button
    type="button"
    id="btn-fab"
    class="bg-hero-action hover:bg-hero-action-hover fixed right-4 bottom-20 z-40 flex h-14 w-14 cursor-pointer items-center justify-center rounded-full text-white shadow-lg transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent md:hidden"
    aria-label="Đăng bài"
  >
    <i class="fa-solid fa-plus text-xl" aria-hidden="true"></i>
  </button>

  @include('newfeed.partials.login-modal', ['title' => 'Bạn cần đăng nhập để đăng bài'])
  @include('newfeed.partials.post-form-modal')
  @include('newfeed.partials.report-modal')
  @include('newfeed.partials.index-scripts')
@endsection
