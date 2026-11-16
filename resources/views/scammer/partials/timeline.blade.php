{{-- Approved reports for the same target, newest first. Expects: $report, $targetReports, $reportsCount. --}}
<x-ucs.card
  title="Lịch sử tố cáo đối tượng này"
  :subtitle="$reportsCount > $targetReports->count()
    ? 'Hiển thị ' . $targetReports->count() . ' / ' . number_format($reportsCount, 0, ',', '.') . ' tố cáo gần nhất.'
  : number_format($reportsCount, 0, ',', '.') . ' tố cáo đã được kiểm duyệt.'"
>
  <ol class="border-line-strong relative ms-1.5 space-y-5 border-s">
    @foreach ($targetReports as $item)
      @php
        $isCurrent = $item->id === $report->id;
      @endphp

      <li class="relative ps-5">
        <span
          @class([
            'absolute top-1.5 -start-[5px] h-2.5 w-2.5 rounded-full ring-4 ring-surface',
            'bg-accent' => $isCurrent,
            'bg-line-strong' => ! $isCurrent,
          ])
          aria-hidden="true"
        ></span>

        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
          <time class="text-ink text-sm font-medium tabular-nums" datetime="{{ $item->created_at->toDateString() }}">
            {{ $item->created_at->format('d/m/Y') }}
          </time>
          @if ($isCurrent)
            <span class="text-ink-subtle text-xs">Đang xem</span>
          @else
            <a
              href="{{ route('scammer.show', $item->slug) }}"
              class="text-accent hover:text-accent-hover text-sm font-medium"
            >
              Xem tố cáo
              <span class="sr-only">ngày {{ $item->created_at->format('d/m/Y') }}</span>
            </a>
          @endif
        </div>
        <p class="text-ink-muted mt-0.5 text-sm">
          {{ $item->scam_type?->label() ?? ($item->category ?: 'Chưa phân loại') }}
          @if ($item->damage_amount > 0)
            <span aria-hidden="true">·</span>
            <span class="tabular-nums">{{ number_format((float) $item->damage_amount, 0, ',', '.') }} ₫</span>
          @endif
        </p>
      </li>
    @endforeach
  </ol>
</x-ucs.card>
