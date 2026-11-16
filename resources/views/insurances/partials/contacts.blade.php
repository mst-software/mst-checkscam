{{-- Contact channels of an insurance member. Expects: $contacts (array of ['platform', 'link']). --}}
<x-ucs.card title="Liên hệ">
  @if (count($contacts) > 0)
    <ul class="space-y-3">
      @foreach ($contacts as $index => $contact)
        @php
          $platform = strtolower($contact['platform'] ?? '');
          $icon = match (true) {
            in_array($platform, ['facebook', 'fb']) => 'fa-brands fa-facebook-f',
            $platform === 'zalo' => 'fa-solid fa-comment-dots',
            $platform === 'telegram' => 'fa-brands fa-telegram',
            default => 'fa-solid fa-link',
          };
        @endphp

        <li class="flex items-center gap-3">
          <span
            class="bg-surface-muted text-ink-muted flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sm"
            aria-hidden="true"
          >
            <i class="{{ $icon }}"></i>
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-ink-subtle text-xs">{{ $contact['platform'] ?? 'Liên kết' }}</p>
            <a
              href="{{ $contact['link'] }}"
              target="_blank"
              rel="noopener noreferrer"
              class="text-accent hover:text-accent-hover block truncate rounded-sm text-sm font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            >
              {{ $contact['link'] }}
            </a>
          </div>
          <button
            type="button"
            data-copy="{{ $contact['link'] }}"
            class="text-ink-subtle hover:bg-surface-muted hover:text-ink rounded-control flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
          >
            <i class="fa-regular fa-copy text-sm" aria-hidden="true"></i>
            <span class="sr-only" data-copy-label aria-live="polite">Sao chép liên kết</span>
          </button>
        </li>
      @endforeach
    </ul>
  @else
    <p class="text-ink-subtle text-sm">Thành viên chưa cập nhật kênh liên hệ.</p>
  @endif
</x-ucs.card>
