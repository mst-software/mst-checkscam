{{--
  Approved reports after the five shown in the hero card: masked target, type, scam type, evidence count and approval date.
  Expects: $latestReports (Report models). Renders nothing when there are none.
--}}
@use('App\Helpers\StringHelper')

@php
  $columns = 'md:grid-cols-[minmax(0,1.5fr)_5.5rem_minmax(0,1fr)_6.5rem_6rem]';
@endphp

@if ($latestReports->isNotEmpty())
  <section aria-label="Thêm cảnh báo gần đây">
    <x-ucs.section-title
      title="Thêm cảnh báo gần đây"
      subtitle="Chỉ gồm tố cáo đã được kiểm duyệt."
      action-label="Xem tất cả"
      :action-url="route('warnings.index')"
    />

    <x-ucs.card :padding="false" class="overflow-hidden">
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

      <ul class="divide-line divide-y" data-latest-reports>
        @foreach ($latestReports as $report)
          @php
            $scamTypeLabel = $report->scam_type?->label() ?? (filled($report->category) ? $report->category : '—');
            $evidenceCount = count($report->evidence_images ?? []);
          @endphp

          <li>
            <a
              href="{{ route('scammer.show', $report->slug) }}"
              class="{{ $columns }} hover:bg-surface-muted focus-visible:outline-accent grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-2 px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:px-5"
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
    </x-ucs.card>
  </section>
@endif
