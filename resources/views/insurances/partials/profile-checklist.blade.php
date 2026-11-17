{{--
  "Scamproof đã kiểm tra": only statements that are true from the data of this request.
  Expects: $insurance, $channelCount, $isReconciled, $totalReports.
--}}
@php
  $checks = [];

  if ($insurance->verified_at) {
    $checks[] = [
      'key' => 'identity',
      'ok' => true,
      'text' => 'Danh tính đã được quản trị viên xác thực',
      'hint' => 'Từ ngày ' . $insurance->verified_at->format('d/m/Y'),
    ];
  }

  if ($channelCount > 0) {
    $checks[] = [
      'key' => 'channels',
      'ok' => true,
      'text' => 'Kênh liên hệ chính thức đã được ghi nhận',
      'hint' => $channelCount . ' kênh, liệt kê đầy đủ bên dưới',
    ];
  }

  if ($isReconciled) {
    $checks[] = [
      'key' => 'ledger',
      'ok' => true,
      'text' => 'Số dư ký quỹ khớp sổ quỹ',
      'hint' => 'Đối chiếu với các khoản quản trị viên đã ghi nhận',
    ];
  }

  if ($channelCount > 0) {
    $checks[] =
      $totalReports === 0
        ? [
          'key' => 'reports',
          'ok' => true,
          'text' => 'Chưa có tố cáo được duyệt nhắm vào các kênh chính thức',
          'hint' => 'Đối chiếu với dữ liệu tố cáo đã kiểm duyệt trên Scamproof',
        ]
        : [
          'key' => 'reports',
          'ok' => false,
          'text' => 'Có ' . $totalReports . ' tố cáo đã được duyệt liên quan đến kênh của thành viên này',
          'hint' => 'Xem chi tiết trong cảnh báo phía trên',
        ];
  }
@endphp

@if (count($checks) > 0)
  <x-ucs.card data-verification-checklist>
    <x-slot:header>
      <h2 class="text-ink flex items-center gap-2 text-base font-semibold">
        <i class="fa-solid fa-clipboard-check text-ink-subtle" aria-hidden="true"></i>
        Scamproof đã kiểm tra
      </h2>
    </x-slot>

    <ul class="space-y-3">
      @foreach ($checks as $check)
        <li class="flex items-start gap-3" data-check="{{ $check['key'] }}">
          @if ($check['ok'])
            <span
              class="bg-success-soft text-success mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
              aria-hidden="true"
            >
              <i class="fa-solid fa-check text-xs"></i>
            </span>
          @else
            <span
              class="bg-danger-soft text-danger mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
              aria-hidden="true"
            >
              <i class="fa-solid fa-exclamation text-xs"></i>
            </span>
          @endif
          <div class="min-w-0">
            <p @class(['text-sm font-medium', 'text-ink' => $check['ok'], 'text-danger' => ! $check['ok']])>
              {{ $check['text'] }}
            </p>
            <p class="text-ink-subtle mt-0.5 text-xs">{{ $check['hint'] }}</p>
          </div>
        </li>
      @endforeach
    </ul>

    <x-slot:footer>
      <p class="text-ink-subtle text-xs leading-relaxed">
        Số dư ký quỹ do quản trị viên ghi nhận; Scamproof xác minh danh tính và kênh liên hệ chính thức của thành viên.
        Hãy tự kiểm tra kỹ trước mỗi giao dịch.
      </p>
    </x-slot>
  </x-ucs.card>
@endif
