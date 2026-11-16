{{-- Related approved warnings (same scam type or bank). Expects: $relatedReports. --}}
@use('App\Helpers\StringHelper')

<x-ucs.card title="Cảnh báo liên quan" :padding="false">
  @if ($relatedReports->isNotEmpty())
    <ul class="divide-line divide-y" role="list" data-related-reports>
      @foreach ($relatedReports as $related)
        <li>
          <a
            href="{{ route('scammer.show', $related->slug) }}"
            class="hover:bg-surface-muted focus-visible:outline-accent block px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:px-5"
          >
            <span class="text-ink block truncate text-sm font-medium tabular-nums">
              {{ StringHelper::mask_id($related->target_id, $related->type) }}
            </span>
            <span class="text-ink-subtle mt-0.5 flex flex-wrap gap-x-2 text-xs">
              <span class="truncate">{{ StringHelper::mask_name($related->target_name) }}</span>
              <span aria-hidden="true">·</span>
              <span>
                {{ $related->scam_type?->label() ?? ($related->type === 'website' ? 'Website' : 'Tài khoản') }}
              </span>
              <span aria-hidden="true">·</span>
              <time datetime="{{ $related->created_at->toDateString() }}">
                {{ $related->created_at->format('d/m/Y') }}
              </time>
            </span>
          </a>
        </li>
      @endforeach
    </ul>
  @else
    <p class="text-ink-subtle px-4 py-4 text-sm sm:px-5">Chưa có cảnh báo liên quan.</p>
  @endif

  <x-slot:footer>
    <a
      href="{{ route('warnings.index') }}"
      class="text-accent hover:text-accent-hover inline-flex items-center gap-1 text-sm font-medium"
    >
      Xem danh sách cảnh báo
      <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
    </a>
  </x-slot>
</x-ucs.card>
