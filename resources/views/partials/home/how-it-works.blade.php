@php
  $steps = [
    [
      'title' => 'Tra cứu đối tượng',
      'text' => 'Nhập số điện thoại, số tài khoản hoặc link Facebook. Hệ thống tự nhận diện và đối chiếu mọi cách viết khác nhau của cùng một số.',
    ],
    [
      'title' => 'Xem bằng chứng đã kiểm duyệt',
      'text' => 'Mỗi cảnh báo hiển thị số tố cáo, thời điểm, hình thức lừa đảo và bằng chứng đã được người kiểm duyệt xác nhận.',
    ],
    [
      'title' => 'Quyết định hoặc cảnh báo',
      'text' => 'Không thấy cảnh báo không có nghĩa là an toàn: hãy dùng trung gian đã ký quỹ. Bị lừa thì gửi tố cáo để bảo vệ người khác.',
    ],
  ];
@endphp

<section aria-label="Cách Scamproof hoạt động">
  <x-ucs.section-title title="Cách Scamproof hoạt động" subtitle="Ba bước để quyết định có nên giao dịch hay không." />

  <ol class="grid grid-cols-1 gap-4 md:grid-cols-3">
    @foreach ($steps as $index => $step)
      <li class="border-line bg-surface rounded-card border p-5 sm:p-6">
        <span
          class="bg-accent-soft text-accent flex size-9 items-center justify-center rounded-full text-sm font-extrabold tabular-nums"
          aria-hidden="true"
        >
          {{ $index + 1 }}
        </span>
        <h3 class="text-ink mt-4 text-base font-bold">{{ $step['title'] }}</h3>
        <p class="text-ink-muted mt-1.5 text-sm leading-relaxed">{{ $step['text'] }}</p>
      </li>
    @endforeach
  </ol>
</section>
