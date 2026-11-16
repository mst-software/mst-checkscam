{{--
  Numbered list of report targets (masked) linking to their report page.
  Expects: $items (objects with target_id, target_name, type, slug), $metricField, $metricLabel, $emptyText.
--}}
@use('App\Helpers\StringHelper')

@if ($items->isNotEmpty())
  <x-ucs.card :padding="false" class="overflow-hidden">
    <ol class="divide-line divide-y">
      @foreach ($items as $item)
        <li>
          <a
            href="{{ route('scammer.show', $item->slug) }}"
            class="hover:bg-surface-muted focus-visible:outline-accent flex items-center gap-3 px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2"
          >
            <span
              class="border-line text-ink-muted flex h-7 w-7 shrink-0 items-center justify-center rounded-md border text-xs font-semibold tabular-nums"
              aria-hidden="true"
            >
              {{ $loop->iteration }}
            </span>
            <span class="min-w-0 flex-1">
              <span class="text-ink block truncate text-sm font-medium">
                {{ StringHelper::mask_name($item->target_name) }}
              </span>
              <span class="text-ink-muted block truncate text-xs tabular-nums">
                {{ StringHelper::mask_id($item->target_id, $item->type) }}
              </span>
            </span>
            <span class="text-ink-subtle shrink-0 text-xs tabular-nums">
              {{ number_format((int) $item->{$metricField}, 0, ',', '.') }} {{ $metricLabel }}
            </span>
          </a>
        </li>
      @endforeach
    </ol>
  </x-ucs.card>
@else
  <x-ucs.empty-state :title="$emptyText" icon="fa-regular fa-clock" class="bg-surface" />
@endif
