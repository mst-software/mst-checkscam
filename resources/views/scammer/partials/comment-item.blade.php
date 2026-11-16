{{--
  One comment. Also rendered once inside a <template> for comments added by AJAX.
  Expects: $commentId, $authorName, $content, $timeLabel, $isAnonymous, $canModify.
--}}
@php
  $words = preg_split('/\s+/u', trim((string) $authorName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $initials = $words === [] ? '' : mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
@endphp

<li class="flex gap-3" data-comment-item data-comment-id="{{ $commentId }}">
  <span
    class="bg-surface-muted text-ink-muted flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
    aria-hidden="true"
    data-comment-avatar
  >
    @if ($isAnonymous)
      <i class="fa-solid fa-user-secret"></i>
    @else
      {{ $initials }}
    @endif
  </span>

  <div class="min-w-0 flex-1">
    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
      <span class="text-ink text-sm font-semibold" data-comment-author>{{ $authorName }}</span>
      <span class="text-ink-subtle text-xs" data-comment-time>{{ $timeLabel }}</span>
    </div>

    <p class="text-ink-muted mt-1 text-sm leading-relaxed break-words whitespace-pre-line" data-comment-content>
      {{ $content }}
    </p>

    @if ($canModify)
      <div class="mt-1 flex gap-3" data-comment-tools>
        <button
          type="button"
          class="text-ink-subtle hover:text-ink cursor-pointer text-xs font-medium"
          data-comment-edit
        >
          Sửa
        </button>
        <button
          type="button"
          class="text-ink-subtle hover:text-danger cursor-pointer text-xs font-medium"
          data-comment-delete
        >
          Xoá
        </button>
      </div>

      <div class="mt-2" data-comment-edit-form hidden>
        <label class="sr-only" for="comment-edit-{{ $commentId }}">Sửa bình luận</label>
        <textarea
          id="comment-edit-{{ $commentId }}"
          rows="3"
          maxlength="1000"
          class="rounded-control border-line bg-surface text-ink focus:border-accent focus:ring-accent w-full resize-y border p-3 text-sm outline-none focus:ring-1"
        >
{{ $content }}</textarea
        >
        <div class="mt-2 flex justify-end gap-2">
          <x-ucs.button variant="secondary" size="sm" data-comment-edit-cancel>Huỷ</x-ucs.button>
          <x-ucs.button size="sm" data-comment-edit-save>Lưu</x-ucs.button>
        </div>
      </div>
    @endif
  </div>
</li>
