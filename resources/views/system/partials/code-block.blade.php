{{-- Code sample with a copy button. Expects: $id (unique), $code (plain text), optional $label. --}}
<div class="border-line bg-surface-muted rounded-control overflow-hidden border">
  <div class="border-line flex items-center justify-between gap-2 border-b px-3 py-1.5">
    <span class="text-ink-subtle text-xs font-medium">{{ $label ?? 'Ví dụ' }}</span>
    <button
      type="button"
      data-copy-target="#{{ $id }}"
      class="text-ink-muted hover:bg-surface hover:text-ink rounded-control inline-flex h-7 cursor-pointer items-center gap-1.5 px-2 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    >
      <i class="fa-regular fa-copy" aria-hidden="true"></i>
      <span data-copy-label aria-live="polite">Sao chép</span>
    </button>
  </div>
  <pre
    class="text-ink overflow-x-auto p-4 font-mono text-xs leading-relaxed sm:text-sm"
    tabindex="0"
  ><code id="{{ $id }}">{{ $code }}</code></pre>
</div>
