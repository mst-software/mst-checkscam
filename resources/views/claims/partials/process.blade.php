{{-- How a claim is handled. Describes the process only: the compensation policy is not defined here. --}}
@php
  $steps = [
    ['icon' => 'fa-solid fa-file-pen', 'title' => 'Gửi hồ sơ', 'text' => 'Chọn GDV, mô tả sự việc và đính kèm ảnh bằng chứng (tin nhắn, biên lai chuyển khoản).'],
    ['icon' => 'fa-solid fa-magnifying-glass', 'title' => 'Ban quản trị xem xét', 'text' => 'Hồ sơ được đối chiếu với GDV. Nếu cần thêm thông tin, chúng tôi liên hệ qua email.'],
    ['icon' => 'fa-solid fa-envelope-open-text', 'title' => 'Nhận kết quả qua email', 'text' => 'Mỗi lần trạng thái yêu cầu thay đổi, bạn nhận được email thông báo.'],
  ];
@endphp

<section class="border-line bg-surface rounded-card border p-5 sm:p-6" aria-labelledby="claim-process-title">
  <h2 id="claim-process-title" class="text-ink text-base font-semibold">Quy trình xử lý</h2>
  <ol class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
    @foreach ($steps as $index => $step)
      <li class="flex gap-3 sm:flex-col">
        <span
          class="bg-accent-soft text-accent flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm"
          aria-hidden="true"
        >
          <i class="{{ $step['icon'] }}"></i>
        </span>
        <div>
          <p class="text-ink text-sm font-semibold">{{ $index + 1 }}. {{ $step['title'] }}</p>
          <p class="text-ink-muted mt-1 text-sm leading-relaxed">{{ $step['text'] }}</p>
        </div>
      </li>
    @endforeach
  </ol>
  <p class="border-line text-ink-subtle mt-5 border-t pt-4 text-xs leading-relaxed">
    <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
    Điều kiện bồi thường do từng trung gian và quản trị viên xác định.
  </p>
</section>
