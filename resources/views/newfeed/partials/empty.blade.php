{{-- Empty feed message. Expects: $message. Mirrored by emptyStateHtml() in index-scripts. --}}
<div
  class="nf-empty-state border-line text-ink-subtle rounded-card flex w-full flex-col items-center justify-center border border-dashed py-16 text-center text-sm [column-span:all]"
>
  <i class="fa-regular fa-folder-open mb-3 text-2xl" aria-hidden="true"></i>
  {{ $message }}
</div>
