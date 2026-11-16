{{--
  Public compensation and deposit history of a partner. Shows only public data: masked claimant names, amounts,
  statuses and admin-written public summaries; ledger rows without reference or note.
  Expects: $insurance, $claimStats, $publicClaims, $ledgerEntries.
--}}
@php
  $money = fn ($amount) => number_format(abs((float) $amount), 0, ',', '.') . ' đ';
  $statusIcon = fn (\App\Enums\ClaimStatus $status) => match ($status) {
    \App\Enums\ClaimStatus::Paid => 'fa-solid fa-hand-holding-dollar',
    \App\Enums\ClaimStatus::Approved => 'fa-solid fa-check',
    default => 'fa-solid fa-xmark',
  };
@endphp

<section class="border-line bg-surface rounded-card border" aria-labelledby="claim-history-title" data-claim-history>
  <div class="border-line flex flex-col gap-3 border-b px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-5">
    <div class="min-w-0">
      <h2 id="claim-history-title" class="text-ink text-base font-semibold">Lịch sử bồi thường & quỹ</h2>
      <p class="text-ink-subtle mt-0.5 text-sm">
        Kết quả các yêu cầu bồi thường và biến động quỹ do ban quản trị ghi nhận.
      </p>
    </div>
    <x-ucs.button
      :href="route('insurance-claims.create', ['partner' => $insurance->slug])"
      variant="secondary"
      size="sm"
      icon="fa-solid fa-file-circle-exclamation"
      class="shrink-0"
    >
      Gửi yêu cầu bồi thường
    </x-ucs.button>
  </div>

  <div class="space-y-6 p-4 sm:p-5">
    <dl class="grid grid-cols-2 gap-3 lg:grid-cols-5">
      @foreach ([
          ['label' => 'Đã tiếp nhận', 'value' => $claimStats['total'], 'key' => 'total'],
          ['label' => 'Đã duyệt / chi trả', 'value' => $claimStats['approved'], 'key' => 'approved'],
          ['label' => 'Từ chối', 'value' => $claimStats['rejected'], 'key' => 'rejected'],
          ['label' => 'Đang xử lý', 'value' => $claimStats['in_progress'], 'key' => 'in_progress']
        ]
        as $stat)
        <div class="border-line bg-surface-muted rounded-control border px-3 py-2.5">
          <dt class="text-ink-subtle text-xs">{{ $stat['label'] }}</dt>
          <dd class="text-ink mt-0.5 text-lg font-semibold tabular-nums" data-claim-stat="{{ $stat['key'] }}">
            {{ number_format($stat['value'], 0, ',', '.') }}
          </dd>
        </div>
      @endforeach

      <div class="border-line bg-surface-muted rounded-control col-span-2 border px-3 py-2.5 lg:col-span-1">
        <dt class="text-ink-subtle text-xs">Tổng đã chi trả</dt>
        <dd class="text-ink mt-0.5 text-lg font-semibold tabular-nums" data-claim-stat="total_paid">
          {{ $money($claimStats['total_paid']) }}
        </dd>
      </div>
    </dl>

    <div>
      <h3 class="text-ink text-sm font-semibold">Yêu cầu bồi thường đã có kết quả</h3>
      @if ($publicClaims->isEmpty())
        <x-ucs.empty-state
          class="mt-3"
          title="Chưa có yêu cầu bồi thường nào"
          description="Khi ban quản trị duyệt hoặc từ chối một yêu cầu, kết quả sẽ hiển thị tại đây."
          icon="fa-regular fa-folder-open"
        />
      @else
        <ul class="divide-line border-line rounded-control mt-3 divide-y border">
          @foreach ($publicClaims as $claim)
            @php
              $settledAmount = match ($claim->status) {
                \App\Enums\ClaimStatus::Paid => $claim->payoutEntry?->amount ?? $claim->approved_amount,
                \App\Enums\ClaimStatus::Approved => $claim->approved_amount,
                default => null,
              };
              $date = $claim->paid_at ?? ($claim->decided_at ?? $claim->created_at);
            @endphp

            <li class="px-3 py-3 sm:px-4" data-public-claim>
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-ink text-sm font-medium">{{ $claim->maskedClaimantName() }}</p>
                <x-ucs.badge :icon="$statusIcon($claim->status)">{{ $claim->status->label() }}</x-ucs.badge>
              </div>
              <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs sm:grid-cols-3">
                <div>
                  <dt class="text-ink-subtle">Ngày</dt>
                  <dd class="text-ink tabular-nums">{{ $date?->format('d/m/Y') }}</dd>
                </div>
                <div>
                  <dt class="text-ink-subtle">Số tiền yêu cầu</dt>
                  <dd class="text-ink tabular-nums">{{ $money($claim->amount_claimed) }}</dd>
                </div>
                <div>
                  <dt class="text-ink-subtle">
                    {{ $claim->status === \App\Enums\ClaimStatus::Paid ? 'Đã chi trả' : 'Được duyệt' }}
                  </dt>
                  <dd class="text-ink font-medium tabular-nums">
                    {{ $settledAmount !== null ? $money($settledAmount) : '—' }}
                  </dd>
                </div>
              </dl>
              @if (filled($claim->public_summary))
                <p class="text-ink-muted mt-2 text-sm leading-relaxed">{{ $claim->public_summary }}</p>
              @endif
            </li>
          @endforeach
        </ul>
      @endif
    </div>

    <div>
      <h3 class="text-ink text-sm font-semibold">Biến động quỹ gần đây</h3>
      @if ($ledgerEntries->isEmpty())
        <x-ucs.empty-state
          class="mt-3"
          title="Chưa có biến động quỹ"
          description="Các lần nạp quỹ, chi trả và điều chỉnh sẽ hiển thị tại đây."
          icon="fa-solid fa-scale-balanced"
        />
      @else
        <ul class="divide-line border-line rounded-control mt-3 divide-y border">
          @foreach ($ledgerEntries as $entry)
            @php
              $isCredit = (float) $entry->amount > 0;
            @endphp

            <li class="flex items-start justify-between gap-3 px-3 py-2.5 sm:px-4" data-ledger-entry>
              <div class="min-w-0">
                <p class="text-ink text-sm font-medium">{{ $entry->type->label() }}</p>
                <p class="text-ink-subtle text-xs tabular-nums">{{ $entry->created_at?->format('d/m/Y') }}</p>
              </div>
              <div class="shrink-0 text-right">
                <p class="text-ink text-sm font-semibold tabular-nums">
                  {{ $isCredit ? '+' : '−' }}{{ $money($entry->amount) }}
                </p>
                <p class="text-ink-subtle text-xs tabular-nums">Số dư: {{ $money($entry->balance_after) }}</p>
              </div>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</section>
