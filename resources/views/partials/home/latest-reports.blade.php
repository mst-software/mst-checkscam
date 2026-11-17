{{--
  Latest approved reports as a table: masked target, type, scam type, evidence count and approval date.
  Expects: $latestReports (Report models), $latestApprovedAt (?Carbon).
--}}
@use('App\Helpers\StringHelper')

@php
  $columns = 'md:grid-cols-[minmax(0,1.5fr)_5.5rem_minmax(0,1fr)_6.5rem_6rem]';
@endphp

<section aria-label="Cảnh báo mới nhất" class="flex flex-1 flex-col">
  <x-ucs.section-title
    title="Cảnh báo mới nhất"
    subtitle="Chỉ gồm tố cáo đã được kiểm duyệt."
    action-label="Xem tất cả"
    :action-url="route('warnings.index')"
  />

  @if ($latestReports->isNotEmpty())
    {{-- Plain box instead of x-ucs.card: its inner wrapper would block the rows from stretching to the sidebar height. --}}
    <div class="border-line bg-surface rounded-card flex flex-1 flex-col overflow-hidden border">
      <div
        class="{{ $columns }} border-line text-ink-subtle bg-surface-muted hidden gap-4 border-b px-5 py-3 text-xs font-semibold tracking-wider uppercase md:grid"
        aria-hidden="true"
      >
        <span>Đối tượng</span>
        <span>Loại</span>
        <span>Hình thức</span>
        <span>Bằng chứng</span>
        <span>Duyệt ngày</span>
      </div>

      <ul class="divide-line flex flex-1 flex-col divide-y" data-latest-reports>
        @foreach ($latestReports as $report)
          @php
            $scamTypeLabel = $report->scam_type?->label() ?? (filled($report->category) ? $report->category : '—');
            $evidenceCount = count($report->evidence_images ?? []);
          @endphp

          <li class="flex-1">
            <a
              href="{{ route('scammer.show', $report->slug) }}"
              class="{{ $columns }} hover:bg-surface-muted focus-visible:outline-accent grid h-full grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-2 px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:px-5"
            >
              <span class="min-w-0">
                <span class="text-ink block truncate text-sm font-semibold">
                  {{ StringHelper::mask_name($report->target_name) }}
                </span>
                <span class="text-ink-subtle block truncate text-xs tabular-nums">
                  {{ StringHelper::mask_id($report->target_id, $report->type) }}
                </span>
              </span>

              <span class="justify-self-end md:justify-self-start">
                <x-ucs.badge>{{ $report->type === 'website' ? 'Website' : 'Tài khoản' }}</x-ucs.badge>
              </span>

              <span
                class="text-ink-muted col-span-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs md:contents md:text-sm"
              >
                <span class="min-w-0 md:truncate">
                  <span class="sr-only">Hình thức:</span>
                  {{ $scamTypeLabel }}
                </span>
                <span>
                  @if ($evidenceCount > 0)
                    <x-ucs.badge variant="success" icon="fa-solid fa-image" data-evidence-count="{{ $evidenceCount }}">
                      {{ $evidenceCount }} ảnh
                      <span class="sr-only">bằng chứng</span>
                    </x-ucs.badge>
                  @else
                    <span class="text-ink-subtle">—</span>
                  @endif
                </span>
                <span class="tabular-nums">
                  <span class="sr-only">Duyệt ngày</span>
                  @if ($report->approved_at)
                    <time datetime="{{ $report->approved_at->toDateString() }}">
                      {{ $report->approved_at->format('d/m/Y') }}
                    </time>
                  @else
                    —
                  @endif
                </span>
              </span>
            </a>
          </li>
        @endforeach
      </ul>
    </div>

    @if ($latestApprovedAt)
      <p class="text-ink-subtle mt-3 flex items-center gap-2 text-xs" data-freshness>
        <span class="bg-success ring-success/15 ml-1 size-2 shrink-0 rounded-full ring-4" aria-hidden="true"></span>
        <span>
          Cập nhật lần cuối
          <time datetime="{{ $latestApprovedAt->toIso8601String() }}">{{ $latestApprovedAt->diffForHumans() }}</time>
          · Dữ liệu được kiểm duyệt thủ công
        </span>
      </p>
    @endif
  @else
    <x-ucs.empty-state title="Chưa có cảnh báo nào được duyệt." class="bg-surface" />
  @endif
</section>
