@extends('layouts.app')

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Kiến thức MMO"
      description="Kinh nghiệm kiếm tiền online an toàn, cách bảo vệ tài sản và các chiêu lừa đảo mới nhất."
      :breadcrumbs="[['name' => 'Kiến thức MMO', 'url' => route('posts.frontend.index')]]"
    />

    <x-ucs.container>
      <form action="{{ route('posts.frontend.index') }}" method="GET" role="search" class="max-w-md">
        <label for="post-search" class="sr-only">Tìm bài viết</label>
        <div class="relative">
          <i
            class="fa-solid fa-magnifying-glass text-ink-subtle pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm"
            aria-hidden="true"
          ></i>
          <input
            id="post-search"
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Tìm thủ thuật, cảnh báo..."
            autocomplete="off"
            class="border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent h-11 w-full border pr-3 pl-9 text-sm focus-visible:outline-2 focus-visible:outline-offset-1"
          />
        </div>
      </form>

      <div id="posts-list-container" class="mt-8" aria-live="polite">
        @if ($featuredPost)
          @include('posts.partials.featured', ['post' => $featuredPost])
        @endif

        @if ($posts->isEmpty())
          <x-ucs.empty-state
            title="Không tìm thấy bài viết"
            description="Không có bài viết nào khớp với từ khoá tìm kiếm của bạn."
            icon="fa-regular fa-newspaper"
            class="bg-surface"
          />
        @else
          <h2 class="sr-only">Bài viết mới nhất</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" id="posts-grid">
            @foreach ($posts as $post)
              @include('posts.partials.card', ['post' => $post])
            @endforeach
          </div>
        @endif

        @if ($posts->hasMorePages())
          <div class="mt-10 flex justify-center" id="load-more-container">
            <x-ucs.button
              id="btn-load-more"
              variant="secondary"
              data-next-page="{{ $posts->currentPage() + 1 }}"
              icon="fa-solid fa-arrow-down"
            >
              Xem thêm bài viết
            </x-ucs.button>
          </div>
        @endif
      </div>
    </x-ucs.container>
  </main>

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('post-search');
        const listContainer = document.getElementById('posts-list-container');
        let debounceTimer;

        function fetchDocument(url) {
          return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.text())
            .then((html) => new DOMParser().parseFromString(html, 'text/html'));
        }

        if (searchInput) {
          searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
              const url = new URL(window.location.href);
              url.search = new URLSearchParams(new FormData(searchInput.form)).toString();
              url.searchParams.delete('page');
              listContainer.style.opacity = '0.5';

              fetchDocument(url)
                .then((doc) => {
                  const newList = doc.getElementById('posts-list-container');
                  if (newList) {
                    listContainer.innerHTML = newList.innerHTML;
                    window.history.replaceState({}, '', url);
                    initLoadMore();
                  }
                })
                .catch((error) => console.error('Search error:', error))
                .finally(() => {
                  listContainer.style.opacity = '1';
                });
            }, 400);
          });

          searchInput.form.addEventListener('submit', (event) => event.preventDefault());
        }

        function initLoadMore() {
          const button = document.getElementById('btn-load-more');
          if (!button) {
            return;
          }

          button.addEventListener('click', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('page', button.dataset.nextPage);

            button.disabled = true;
            button.textContent = 'Đang tải bài viết...';

            fetchDocument(url)
              .then((doc) => {
                const postsGrid = document.getElementById('posts-grid');
                doc.querySelectorAll('#posts-grid > article').forEach((post) => postsGrid.appendChild(post));

                const nextContainer = doc.getElementById('load-more-container');
                const currentContainer = document.getElementById('load-more-container');

                if (nextContainer) {
                  currentContainer.innerHTML = nextContainer.innerHTML;
                  initLoadMore();
                } else {
                  currentContainer.remove();
                }

                window.history.replaceState({}, '', url);
              })
              .catch((error) => {
                console.error('Load more error:', error);
                button.disabled = false;
                button.textContent = 'Thử lại';
              });
          });
        }

        initLoadMore();
      });
    </script>
  @endpush
@endsection
