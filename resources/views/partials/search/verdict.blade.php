{{--
  Verdict header for a search. Expects: $query, $type, $results (paginator), $matchedInsurances.
  Built only from data the search already loaded; never claims a target is "safe".
--}}
@php
  $reportCount = $results->total();
  $hasInsurance = $matchedInsurances->isNotEmpty();
  // Results are ordered newest first, so the first item of page 1 is the latest report.
  $latestReport = $results->currentPage() === 1 ? $results->first() : null;

  $typeLabel = match ($type) {
    'phone' => 'Số điện thoại',
    'bank' => 'Số tài khoản',
    'facebook' => 'Facebook',
    'uuid' => 'Mã bài tố cáo',
    default => 'Tên hoặc từ khóa',
  };

  if ($reportCount >= 3) {
    $level = ['label' => 'Nhiều tố cáo', 'variant' => 'danger', 'icon' => 'fa-solid fa-triangle-exclamation'];
  } elseif ($reportCount > 0) {
    $level = ['label' => 'Có tố cáo', 'variant' => 'danger', 'icon' => 'fa-solid fa-circle-exclamation'];
  } else {
    $level = ['label' => 'Chưa có tố cáo', 'variant' => $hasInsurance ? 'success' : 'neutral', 'icon' => 'fa-regular fa-circle'];
  }

  $iconBox = match ($level['variant']) {
    'danger' => 'bg-danger-soft text-danger',
    'success' => 'bg-success-soft text-success',
    default => 'bg-surface-muted text-ink-muted',
  };
@endphp

<x-ucs.card>
  <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
    <div class="flex min-w-0 items-start gap-4">
      <span
        class="{{ $iconBox }} rounded-control hidden h-11 w-11 shrink-0 items-center justify-center sm:flex"
        aria-hidden="true"
      >
        <i class="{{ $level['icon'] }}"></i>
      </span>
      <div class="min-w-0">
        <p class="text-ink-subtle text-sm">Kết quả kiểm tra · {{ $typeLabel }}</p>
        <h1 class="text-ink mt-1 text-2xl font-semibold break-all">{{ $query }}</h1>
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <x-ucs.badge :variant="$level['variant']" :icon="$level['icon']">{{ $level['label'] }}</x-ucs.badge>
          @if ($hasInsurance)
            <x-ucs.badge variant="success" icon="fa-solid fa-shield-halved">Trùng đối tác bảo hiểm</x-ucs.badge>
          @endif
        </div>
        <p class="text-ink-muted mt-3 text-sm">
          @if ($reportCount > 0)
            Có {{ number_format($reportCount, 0, ',', '.') }} tố cáo đã được kiểm duyệt liên quan đến thông tin này.
            Không nên chuyển tiền trước khi xem kỹ bằng chứng.
          @else
            Chưa có tố cáo trong hệ thống cho thông tin này.
          @endif
        </p>
      </div>
    </div>

    <dl class="border-line divide-line grid shrink-0 grid-cols-3 divide-x rounded-xl border text-center md:w-80">
      <div class="px-3 py-3">
        <dt class="text-ink-subtle text-xs">Tố cáo</dt>
        <dd
          @class(['mt-1 text-lg font-semibold tabular-nums', 'text-danger' => $reportCount > 0, 'text-ink' => $reportCount === 0])
        >
          {{ number_format($reportCount, 0, ',', '.') }}
        </dd>
      </div>
      <div class="px-3 py-3">
        <dt class="text-ink-subtle text-xs">Gần nhất</dt>
        <dd class="text-ink mt-1 text-sm font-medium tabular-nums sm:text-base">
          {{ $latestReport?->created_at?->format('d/m/Y') ?? '—' }}
        </dd>
      </div>
      <div class="px-3 py-3">
        <dt class="text-ink-subtle text-xs">Bảo hiểm</dt>
        <dd
          @class(['mt-1 text-sm font-medium sm:text-base', 'text-success' => $hasInsurance, 'text-ink' => ! $hasInsurance])
        >
          {{ $hasInsurance ? 'Có' : 'Không' }}
        </dd>
      </div>
    </dl>
  </div>

  <x-slot:footer>
    <p class="text-ink-subtle text-xs leading-relaxed">
      Kết quả chỉ phản ánh dữ liệu tố cáo đã được kiểm duyệt trên Scamproof, không phải bảo đảm an toàn. Luôn xác minh
      kỹ người nhận trước khi giao dịch.
    </p>
  </x-slot>
</x-ucs.card>
