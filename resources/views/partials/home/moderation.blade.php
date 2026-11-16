@php
  $criteria = [
    ['label' => 'Có bằng chứng:', 'text' => 'hóa đơn chuyển khoản, đoạn hội thoại hoặc hình ảnh liên quan.'],
    ['label' => 'Được người kiểm duyệt đọc:', 'text' => 'không có tố cáo nào tự động đăng.'],
    ['label' => 'Che thông tin nhạy cảm:', 'text' => 'tên, số tài khoản hiển thị dạng rút gọn.'],
    ['label' => 'Có quyền phản hồi:', 'text' => 'bên bị tố cáo có thể gửi khiếu nại kèm chứng cứ.'],
  ];

  $flow = [
    ['tone' => 'neutral', 'marker' => '1', 'title' => 'Gửi tố cáo', 'text' => 'Người dùng nộp mẫu kèm bằng chứng.'],
    [
      'tone' => 'neutral',
      'marker' => '2',
      'title' => 'Chờ kiểm duyệt',
      'text' => 'Đối chiếu bằng chứng, loại báo cáo trùng lặp hoặc thiếu căn cứ.',
    ],
    [
      'tone' => 'success',
      'marker' => 'fa-solid fa-check',
      'title' => 'Được duyệt → công khai',
      'text' => 'Hiển thị kèm ngày duyệt, thông tin nhạy cảm được che bớt.',
    ],
    [
      'tone' => 'danger',
      'marker' => 'fa-solid fa-xmark',
      'title' => 'Không đủ căn cứ → từ chối',
      'text' => 'Không công khai; lý do từ chối được ghi lại.',
    ],
  ];
@endphp

<section aria-label="Chúng tôi kiểm duyệt như thế nào">
  <x-ucs.section-title
    title="Chúng tôi kiểm duyệt như thế nào"
    subtitle="Công khai quy trình để bạn biết dữ liệu đến từ đâu."
  />

  <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1.1fr_1fr] lg:gap-5">
    <div class="border-line bg-surface rounded-card border p-5 sm:p-6">
      <p class="text-ink text-[15px] font-bold">Một tố cáo chỉ được hiển thị khi:</p>
      <ul class="mt-4 space-y-3">
        @foreach ($criteria as $item)
          <li class="text-ink-muted flex items-start gap-3 text-sm leading-relaxed">
            <i class="fa-solid fa-circle-check text-success mt-1" aria-hidden="true"></i>
            <span>
              <strong class="text-ink font-semibold">{{ $item['label'] }}</strong>
              {{ $item['text'] }}
            </span>
          </li>
        @endforeach
      </ul>
    </div>

    <ol class="border-line bg-surface rounded-card border p-5 sm:p-6" aria-label="Các bước kiểm duyệt">
      @foreach ($flow as $step)
        @php
          $markerClasses = match ($step['tone']) {
            'success' => 'bg-success-soft text-success',
            'danger' => 'bg-accent-soft text-accent',
            default => 'bg-surface-muted text-ink-muted',
          };
        @endphp

        <li class="relative flex gap-3.5 pb-5 last:pb-0">
          @unless ($loop->last)
            <span class="bg-line absolute top-8 bottom-0 left-[15px] w-0.5" aria-hidden="true"></span>
          @endunless

          <span
            class="{{ $markerClasses }} flex size-8 shrink-0 items-center justify-center rounded-full text-[13px] font-semibold"
            aria-hidden="true"
          >
            @if (str_starts_with($step['marker'], 'fa-'))
              <i class="{{ $step['marker'] }}"></i>
            @else
              {{ $step['marker'] }}
            @endif
          </span>

          <div class="min-w-0 pt-1">
            <p class="text-ink text-sm font-semibold">{{ $step['title'] }}</p>
            <p class="text-ink-subtle mt-0.5 text-[13px] leading-relaxed">{{ $step['text'] }}</p>
          </div>
        </li>
      @endforeach
    </ol>
  </div>

  <a
    href="{{ route('moderation.policy') }}"
    class="text-accent hover:text-accent-hover focus-visible:outline-accent mt-4 inline-flex items-center gap-1 rounded-sm text-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
  >
    Xem quy trình kiểm duyệt đầy đủ
    <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
  </a>
</section>
