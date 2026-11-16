{{--
  Verdict header: masked target, badges, counts and the action row.
  Expects: $report, $maskedTarget, $maskedName, $scamTypeLabel, $reportsCount, $comments, $firstReportedAt, $lastReportedAt.
--}}
@php
  $isWebsite = $report->type === 'website';
  $stats = [
    ['label' => 'Tố cáo', 'value' => number_format($reportsCount, 0, ',', '.'), 'danger' => true],
    ['label' => 'Bình luận', 'value' => number_format($comments->count(), 0, ',', '.'), 'danger' => false],
    ['label' => 'Lần đầu', 'value' => $firstReportedAt->format('d/m/Y'), 'danger' => false],
    ['label' => 'Gần nhất', 'value' => $lastReportedAt->format('d/m/Y'), 'danger' => false],
  ];
@endphp

<x-ucs.card>
  <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
    <div class="flex min-w-0 items-start gap-4">
      <span
        class="bg-danger-soft text-danger rounded-control hidden h-11 w-11 shrink-0 items-center justify-center sm:flex"
        aria-hidden="true"
      >
        <i class="fa-solid fa-triangle-exclamation"></i>
      </span>
      <div class="min-w-0">
        <p class="text-ink-subtle text-sm">
          Cảnh báo lừa đảo · {{ $isWebsite ? 'Website / mạng xã hội' : 'Tài khoản ngân hàng / SĐT' }}
        </p>
        <h1 class="text-ink mt-1 text-2xl font-semibold tracking-tight break-all tabular-nums sm:text-3xl">
          {{ $maskedTarget }}
        </h1>
        <p class="text-ink-muted mt-1 text-sm">
          {{ $maskedName }}
          @if ($report->target_bank)
            <span aria-hidden="true">·</span>
            {{ $report->target_bank }}
          @endif
        </p>

        <div class="mt-3 flex flex-wrap items-center gap-2">
          <x-ucs.badge variant="danger" icon="fa-solid fa-circle-exclamation">Bị tố cáo lừa đảo</x-ucs.badge>
          @if ($scamTypeLabel)
            <x-ucs.badge icon="fa-solid fa-tag">{{ $scamTypeLabel }}</x-ucs.badge>
          @endif

          <x-ucs.badge>{{ $isWebsite ? 'Website' : 'Tài khoản' }}</x-ucs.badge>
          <x-ucs.badge variant="success" data-verified-badge>
            <svg
              class="h-3.5 w-3.5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path
                d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"
              />
              <path d="m9 12 2 2 4-4" />
            </svg>
            Đã được kiểm duyệt
          </x-ucs.badge>
        </div>
      </div>
    </div>

    <dl
      class="border-line bg-line grid shrink-0 grid-cols-2 gap-px overflow-hidden rounded-xl border text-center sm:grid-cols-4 lg:w-[30rem]"
    >
      @foreach ($stats as $stat)
        <div class="bg-surface px-3 py-3">
          <dt class="text-ink-subtle text-xs">{{ $stat['label'] }}</dt>
          <dd
            @class(['mt-1 font-semibold tabular-nums', 'text-danger text-lg' => $stat['danger'], 'text-ink text-sm' => ! $stat['danger']])
          >
            {{ $stat['value'] }}
          </dd>
        </div>
      @endforeach
    </dl>
  </div>

  <x-slot:footer>
    @include('scammer.partials.actions')
  </x-slot>
</x-ucs.card>
