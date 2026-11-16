@php
  $checklist = [
    'Chuẩn bị ảnh bill chuyển khoản, đoạn chat và trang cá nhân của đối tượng.',
    'Che số dư, mã OTP và thông tin nhạy cảm của bạn trong ảnh.',
    'Ghi đúng số tài khoản, SĐT hoặc link mà đối tượng đã dùng.',
    'Mô tả theo trình tự: cách liên hệ, lời hứa hẹn, số tiền, thời điểm.',
  ];

  $assurances = [
    ['icon' => 'fa-solid fa-user-shield', 'text' => 'Mọi tố cáo được kiểm duyệt trước khi công khai, thường được xem xét trong vòng 24 giờ.'],
    ['icon' => 'fa-solid fa-lock', 'text' => 'Tên và thông tin liên hệ của bạn không hiển thị công khai; chỉ quản trị viên xem được để xác minh.'],
    ['icon' => 'fa-solid fa-scale-balanced', 'text' => 'Tố cáo thiếu căn cứ hoặc sai sự thật sẽ bị từ chối hoặc gỡ.'],
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

  <a
    href="{{ route('moderation.policy') }}"
    class="text-accent hover:text-accent-hover focus-visible:outline-accent mt-4 inline-flex items-center gap-1 rounded-sm text-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
  >
    Xem quy trình kiểm duyệt
    <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
  </a>

  <x-slot:footer>
    <p class="text-ink-subtle flex items-start gap-2 text-xs" data-rate-limit-notice>
      <i class="fa-solid fa-circle-info mt-0.5" aria-hidden="true"></i>
      <span>Để hạn chế spam, mỗi địa chỉ IP được gửi tối đa 3 tố cáo trong 24 giờ.</span>
    </p>
  </x-slot>
</x-ucs.card>
