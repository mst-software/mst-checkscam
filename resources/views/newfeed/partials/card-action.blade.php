{{--
  Delete (owner/admin via policy) or report button in the top-right corner of a post.
  Expects: $post, $userReportedIds. Calls deletePost()/openReport() defined by the page script.
--}}
@php
  $buttonClass =
    'rounded-control absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent';
@endphp

@auth
  @can('delete', $post)
    <button
      type="button"
      data-no-nav
      onclick="deletePost({{ $post->id }}, this)"
      class="{{ $buttonClass }} text-danger hover:bg-danger-soft cursor-pointer"
      title="Xóa bài"
      aria-label="Xóa bài đăng"
    >
      <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
    </button>
  @elseif (! in_array($post->id, $userReportedIds))
    <button
      type="button"
      data-no-nav
      onclick="openReport({{ $post->id }}, this)"
      class="{{ $buttonClass }} text-ink-subtle hover:bg-surface-muted hover:text-ink cursor-pointer"
      title="Báo cáo"
      aria-label="Báo cáo bài đăng"
    >
      <i class="fa-regular fa-flag" aria-hidden="true"></i>
    </button>
  @else
    <button
      type="button"
      data-no-nav
      class="{{ $buttonClass }} text-ink-subtle cursor-not-allowed opacity-70"
      title="Đã báo cáo"
      aria-label="Bạn đã báo cáo bài đăng này"
      disabled
    >
      <i class="fa-solid fa-flag" aria-hidden="true"></i>
    </button>
  @endif
@endauth
