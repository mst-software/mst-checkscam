@extends('layouts.app')

@php
  $authorName = $post->user->full_name ?? $post->user->username;
  $imageCount = count($imageUrls);
  $showCount = min($imageCount, 4);
  $extraCount = $imageCount - $showCount;
@endphp

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css" />
@endpush

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Chi tiết bài đăng"
      :description="$post->category . ' · đăng ' . $post->created_at->locale('vi')->diffForHumans()"
      :breadcrumbs="[
        ['name' => 'Khu mua bán', 'url' => route('newfeed.index')],
        ['name' => 'Chi tiết bài đăng', 'url' => route('newfeed.show', $post->id)],
      ]"
    />

    <x-ucs.container size="narrow">
      <article class="border-line bg-surface rounded-card relative border p-5" data-id="{{ $post->id }}">
        @include('newfeed.partials.card-action', ['post' => $post, 'userReportedIds' => $userReportedIds])

        <div class="flex items-center gap-3 pr-10">
          <img
            src="{{ $post->user->avatar_url }}"
            alt=""
            width="40"
            height="40"
            class="h-10 w-10 rounded-full object-cover"
          />
          <div class="min-w-0">
            <p class="text-ink flex items-center gap-1 text-sm font-medium">
              <span class="truncate">{{ $authorName }}</span>
              @if ($post->user->is_verified)
                <i
                  class="fa-solid fa-circle-check text-success text-xs"
                  title="Tài khoản đã được Scamproof xác minh uy tín"
                  aria-hidden="true"
                ></i>
                <span class="sr-only">(đã xác minh)</span>
              @endif
            </p>
            <p class="text-ink-subtle text-xs">
              <time datetime="{{ $post->created_at->toIso8601String() }}">
                {{ $post->created_at->locale('vi')->diffForHumans() }}
              </time>
              ·
              <x-ucs.badge>{{ $post->category }}</x-ucs.badge>
            </p>
          </div>
        </div>

        <div class="text-ink mt-4 text-base leading-relaxed break-words">
          {!! \App\Helpers\StringHelper::formatPostContent($post->content) !!}
        </div>

        @if ($post->price && $post->price > 0)
          <p class="text-ink mt-4 text-lg font-semibold tabular-nums">
            {{ number_format($post->price, 0, ',', '.') }} đ
          </p>
        @endif

        @if ($imageCount > 0)
          <div @class(['mt-4 grid gap-1', 'grid-cols-1' => $showCount === 1, 'grid-cols-2' => $showCount > 1])>
            @foreach (array_slice($imageUrls, 0, $showCount) as $idx => $url)
              <a
                href="{{ $url }}"
                data-fancybox="post-gallery"
                @class([
                  'bg-surface-muted relative block overflow-hidden rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
                  'col-span-2' => $showCount === 3 && $idx === 0,
                  'max-h-96' => $showCount === 1,
                  'h-44' => $showCount > 1,
                ])
                aria-label="Xem ảnh {{ $idx + 1 }}/{{ $imageCount }}"
              >
                <img src="{{ $url }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                @if ($idx === $showCount - 1 && $extraCount > 0)
                  <span
                    class="absolute inset-0 flex items-center justify-center bg-black/50 text-xl font-semibold text-white"
                  >
                    +{{ $extraCount }}
                  </span>
                @endif
              </a>
            @endforeach
          </div>

          {{-- Hidden anchors so the gallery includes the remaining images --}}
          @foreach (array_slice($imageUrls, $showCount) as $url)
            <a href="{{ $url }}" data-fancybox="post-gallery" class="hidden" tabindex="-1" aria-hidden="true"></a>
          @endforeach
        @endif
      </article>

      <div class="mt-6">
        <x-ucs.button variant="ghost" :href="route('newfeed.index')" icon="fa-solid fa-arrow-left">
          Quay lại khu mua bán
        </x-ucs.button>
      </div>
    </x-ucs.container>
  </main>

  @include('newfeed.partials.report-modal')

  @push('scripts')
    <script>
      window._authUserId = @json(auth()->id());
      window._csrfToken = @json(csrf_token());
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js"></script>
    @include('newfeed.partials.popup-scripts')
    <script>
      Fancybox.bind('[data-fancybox="post-gallery"]', {
        animated: true,
        showClass: 'f-fadeIn',
        hideClass: 'f-fadeOut',
        Images: { zoom: true },
      });

      function openReport(postId) {
        document.getElementById('report-post-id').value = postId;
        document.querySelectorAll('input[name="report-reason"]').forEach(function (r) {
          r.checked = false;
        });
        document.getElementById('btn-submit-report').disabled = true;
        openPopup('popup-report');
      }
      document.addEventListener('change', function (e) {
        if (e.target.name === 'report-reason') {
          document.getElementById('btn-submit-report').disabled = false;
        }
      });

      function submitReport() {
        const postId = document.getElementById('report-post-id').value;
        const reason = document.querySelector('input[name="report-reason"]:checked')?.value;
        if (!reason) return;
        fetch('/api/newfeed/posts/' + postId + '/report', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window._csrfToken,
            Accept: 'application/json',
          },
          body: JSON.stringify({ reason: reason }),
        })
          .then(function (r) {
            if (r.ok) {
              closePopup('popup-report');
              location.reload();
            } else {
              r.json().then(function (d) {
                alert(d.error || 'Không thể gửi báo cáo.');
              });
            }
          })
          .catch(function () {
            alert('Không thể gửi báo cáo.');
          });
      }

      function deletePost(postId) {
        if (!confirm('Bạn có chắc muốn xóa bài đăng này?')) return;
        fetch('/api/newfeed/posts/' + postId, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': window._csrfToken, Accept: 'application/json' },
        })
          .then(function (r) {
            if (r.ok) {
              window.location.href = '{{ route('newfeed.index') }}';
            } else {
              alert('Không thể xóa bài này.');
            }
          })
          .catch(function () {
            alert('Không thể xóa bài này.');
          });
      }
    </script>
  @endpush
@endsection
