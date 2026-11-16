{{--
  Live "Cảnh báo mới nhất" card beside the home search: newest approved reports with masked data and relative time.
  Expects: $feedReports (Report models, max 5), $latestApprovedAt (?Carbon).
--}}
@use('App\Helpers\StringHelper')

<section
  aria-labelledby="hero-feed-title"
  class="border-line bg-surface overflow-hidden rounded-[18px] border shadow-[0_24px_48px_-32px_color-mix(in_oklab,var(--color-ink)_35%,transparent)] dark:shadow-none"
  data-hero-feed
>
  <div class="border-line flex items-center justify-between gap-3 border-b px-4 py-3 sm:px-5 sm:py-4">
    <h2 id="hero-feed-title" class="text-ink text-[15px] font-extrabold whitespace-nowrap sm:text-base">
      Cảnh báo mới nhất
    </h2>

    @if ($latestApprovedAt)
      <span
        class="bg-success-soft text-success inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold whitespace-nowrap"
        data-hero-feed-updated
      >
        <span class="bg-success size-1.5 shrink-0 rounded-full" aria-hidden="true"></span>
        <span class="sr-only sm:not-sr-only">Cập nhật</span>
        <time datetime="{{ $latestApprovedAt->toIso8601String() }}">{{ $latestApprovedAt->diffForHumans() }}</time>
      </span>
    @endif
  </div>

  @if ($feedReports->isNotEmpty())
    <ul class="divide-line divide-y">
      @foreach ($feedReports as $report)
        @php
          $scamTypeLabel = $report->scam_type?->label() ?? (filled($report->category) ? $report->category : null);
          $reportedAt = $report->approved_at ?? $report->created_at;
        @endphp

        <li>
          <a
            href="{{ route('scammer.show', $report->slug) }}"
            class="hover:bg-surface-muted focus-visible:outline-accent grid grid-cols-[2.25rem_minmax(0,1fr)_auto] items-center gap-3 px-4 py-2.5 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:grid-cols-[2.375rem_minmax(0,1fr)_auto] sm:px-5 sm:py-3"
            data-hero-feed-row
          >
            <span
              class="bg-accent-soft text-accent flex size-9 items-center justify-center rounded-[11px] text-sm sm:size-9.5"
              aria-hidden="true"
            >
              <i class="fa-solid fa-triangle-exclamation"></i>
            </span>

            <span class="min-w-0">
              <span class="flex min-w-0 items-center gap-1.5">
                <span class="text-ink truncate text-sm font-bold sm:text-[15px]">
                  {{ StringHelper::mask_name($report->target_name) }}
                </span>
                <span class="hidden shrink-0 sm:inline-flex">
                  <x-ucs.badge class="rounded-full">
                    {{ $report->type === 'website' ? 'Website' : 'Tài khoản' }}
                  </x-ucs.badge>
                </span>
              </span>
              <span class="text-ink-subtle mt-0.5 block truncate text-xs sm:text-[13px]">
                <span class="tabular-nums">{{ StringHelper::mask_id($report->target_id, $report->type) }}</span>
                @if ($scamTypeLabel)
                  · {{ $scamTypeLabel }}
                @endif
              </span>
            </span>

            <time
              datetime="{{ $reportedAt->toIso8601String() }}"
              class="text-ink-subtle text-right text-xs whitespace-nowrap tabular-nums sm:text-[13px]"
            >
              {{ $reportedAt->diffForHumans() }}
            </time>
          </a>
        </li>
      @endforeach
    </ul>
  @else
    <p class="text-ink-muted px-5 py-10 text-center text-sm font-semibold">Chưa có cảnh báo nào được duyệt.</p>
  @endif

  <a
    href="{{ route('warnings.index') }}"
    class="border-line text-accent hover:bg-surface-muted focus-visible:outline-accent group flex items-center justify-between border-t px-4 py-3 text-sm font-bold transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:px-5"
  >
    Xem tất cả cảnh báo
    <i
      class="fa-solid fa-arrow-right text-xs motion-safe:transition-transform motion-safe:group-hover:translate-x-0.5"
      aria-hidden="true"
    ></i>
  </a>
</section>
