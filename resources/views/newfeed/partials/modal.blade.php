{{--
  Modal shell used by the marketplace popups (opened/closed by openPopup()/closePopup()).
  Use with @component('newfeed.partials.modal', ['id' => ..., 'title' => ..., 'size' => 'sm|lg']) and an optional @slot('footer').
--}}
<div
  id="{{ $id }}"
  class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4"
  role="dialog"
  aria-modal="true"
  aria-labelledby="{{ $id }}-title"
>
  <div
    @class([
      'border-line bg-surface rounded-card relative flex max-h-[90vh] w-full flex-col border shadow-xl',
      'max-w-sm' => ($size ?? 'sm') === 'sm',
      'max-w-lg' => ($size ?? 'sm') === 'lg',
    ])
  >
    <div class="border-line flex shrink-0 items-center justify-between gap-3 border-b px-4 py-3">
      <h2 id="{{ $id }}-title" class="text-ink text-base font-semibold">{{ $title }}</h2>
      <button
        type="button"
        onclick="closePopup('{{ $id }}')"
        class="text-ink-subtle hover:bg-surface-muted hover:text-ink rounded-control flex h-8 w-8 cursor-pointer items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        aria-label="Đóng"
      >
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </div>
    <div class="overflow-y-auto p-4">{{ $slot }}</div>
    @isset($footer)
      <div class="border-line shrink-0 border-t px-4 py-3">{{ $footer }}</div>
    @endisset
  </div>
</div>
