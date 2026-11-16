@php
  $checklist = [
    'Chuẩn bị ảnh bill chuyển khoản, đoạn chat và trang cá nhân của đối tượng.',
    'Che số dư, mã OTP và thông tin nhạy cảm của bạn trong ảnh.',
    'Ghi đúng số tài khoản, SĐT hoặc link mà đối tượng đã dùng.',
    'Mô tả theo trình tự: cách liên hệ, lời hứa hẹn, số tiền, thời điểm.',
  ];

  $assurances = [
    ['icon' => 'fa-solid fa-user-shield', 'text' => 'Mọi tố cáo được kiểm duyệt trước khi công khai, thường trong 30 phút đến 12 giờ.'],
    ['icon' => 'fa-solid fa-lock', 'text' => 'Chúng tôi không bao giờ công khai tên và thông tin liên hệ của bạn.'],
    ['icon' => 'fa-solid fa-hand-holding-heart', 'text' => 'Tố cáo và tra cứu hoàn toàn miễn phí.'],
  ];
@endphp

<x-ucs.card title="Trước khi gửi">
  <ul class="space-y-3">
    @foreach ($checklist as $item)
      <li class="text-ink-muted flex items-start gap-2.5 text-sm">
        <i class="fa-solid fa-check text-success mt-1 text-xs" aria-hidden="true"></i>
        <span>{{ $item }}</span>
      </li>
    @endforeach
  </ul>
</x-ucs.card>

<x-ucs.card title="Cam kết của Scamproof">
  <ul class="space-y-3">
    @foreach ($assurances as $assurance)
      <li class="text-ink-muted flex items-start gap-2.5 text-sm">
        <i class="{{ $assurance['icon'] }} text-accent mt-0.5 w-4 shrink-0 text-center" aria-hidden="true"></i>
        <span>{{ $assurance['text'] }}</span>
      </li>
    @endforeach
  </ul>

  <x-slot:footer>
    <p class="text-ink-subtle flex items-start gap-2 text-xs" data-rate-limit-notice>
      <i class="fa-solid fa-circle-info mt-0.5" aria-hidden="true"></i>
      <span>Để hạn chế spam, mỗi địa chỉ IP được gửi tối đa 3 tố cáo trong 24 giờ.</span>
    </p>
  </x-slot>
</x-ucs.card>
