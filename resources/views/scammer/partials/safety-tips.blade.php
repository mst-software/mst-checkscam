@php
  $tips = [
    'Không chuyển tiền đặt cọc hoặc "phí giữ chỗ" cho người lạ.',
    'Kiểm tra số tài khoản, SĐT trên Scamproof trước mỗi giao dịch.',
    'Không cung cấp mã OTP, mật khẩu hoặc ảnh CCCD cho bất kỳ ai.',
    'Ưu tiên giao dịch qua sàn có trung gian hoặc nhận hàng rồi thanh toán.',
    'Bị lừa: báo ngay ngân hàng để phong toả và trình báo công an.',
  ];
@endphp

<x-ucs.card title="Lời khuyên an toàn">
  <ul class="space-y-3" role="list">
    @foreach ($tips as $tip)
      <li class="text-ink-muted flex gap-2.5 text-sm leading-relaxed">
        <i class="fa-solid fa-check text-success mt-1 text-xs" aria-hidden="true"></i>
        <span>{{ $tip }}</span>
      </li>
    @endforeach
  </ul>
</x-ucs.card>
