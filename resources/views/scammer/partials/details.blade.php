{{-- Amount and account details, masked. Expects: $report, $maskedTarget, $maskedName, $scamTypeLabel, $maskedSocials, $maskedSoldAccount. --}}
@php
  $isWebsite = $report->type === 'website';
  $isBack = $report->type === 'back';
  $rows = [
    [
      'label' => $isWebsite ? 'Website / liên kết' : ($isBack ? 'SĐT / Zalo / link người bán' : 'Số tài khoản / SĐT'),
      'value' => $maskedTarget,
      'mono' => true,
    ],
    ['label' => $isWebsite ? 'Tên hiển thị' : ($isBack ? 'Người bán' : 'Chủ tài khoản'), 'value' => $maskedName, 'mono' => false],
  ];

  if (! $isWebsite) {
    $rows[] = ['label' => 'Ngân hàng', 'value' => $report->target_bank ?: 'Chưa rõ', 'mono' => false];
  }

  if (($maskedSocials ?? collect())->isNotEmpty()) {
    $rows[] = ['label' => 'Kênh liên hệ khác', 'value' => $maskedSocials->join(', '), 'mono' => true];
  }

  if (filled($maskedSoldAccount ?? null)) {
    $rows[] = ['label' => 'Tài khoản đã mua', 'value' => $maskedSoldAccount, 'mono' => false];
  }

  $rows[] = ['label' => 'Hình thức lừa đảo', 'value' => $scamTypeLabel ?? 'Chưa phân loại', 'mono' => false];
  $rows[] = [
    'label' => 'Số tiền thiệt hại',
    'value' => $report->damage_amount > 0 ? number_format((float) $report->damage_amount, 0, ',', '.') . ' ₫' : 'Chưa rõ',
    'mono' => true,
  ];
@endphp

<x-ucs.card title="Thông tin đối tượng" subtitle="Thông tin định danh đã được che bớt để bảo vệ quyền riêng tư.">
  <dl class="divide-line divide-y">
    @foreach ($rows as $row)
      <div
        class="flex flex-col gap-1 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4"
      >
        <dt class="text-ink-subtle text-sm">{{ $row['label'] }}</dt>
        <dd @class(['text-ink text-base font-medium break-words sm:text-right', 'tabular-nums' => $row['mono']])>
          {{ $row['value'] }}
        </dd>
      </div>
    @endforeach
  </dl>
</x-ucs.card>
