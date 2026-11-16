{{-- Complaint policy summary shown above the complaint form. --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
  <x-ucs.card>
    <h2 class="text-ink flex items-center gap-2 text-base font-semibold">
      <i class="fa-solid fa-scale-balanced text-ink-subtle" aria-hidden="true"></i>
      Quy trình công bằng
    </h2>
    <p class="text-ink-muted mt-2 text-sm leading-relaxed">
      Chúng tôi luôn lắng nghe cả hai phía. Nếu bạn cho rằng mình bị tố cáo oan, vui lòng cung cấp bằng chứng giao dịch
      thành công hoặc sự nhầm lẫn.
    </p>
  </x-ucs.card>

  <x-ucs.card>
    <h2 class="text-ink flex items-center gap-2 text-base font-semibold">
      <i class="fa-solid fa-rotate-left text-ink-subtle" aria-hidden="true"></i>
      Khôi phục uy tín
    </h2>
    <p class="text-ink-muted mt-2 text-sm leading-relaxed">
      Khi hai bên đã giải quyết xong và người tố cáo ban đầu xác nhận, Scamproof sẽ gỡ bài viết trong tối đa 12 giờ làm
      việc.
    </p>
  </x-ucs.card>
</div>

<section class="border-danger/30 bg-danger-soft rounded-card border p-4 sm:p-5" aria-labelledby="dispute-rules-title">
  <h2 id="dispute-rules-title" class="text-danger flex items-center gap-2 text-base font-semibold">
    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
    Lưu ý quan trọng
  </h2>
  <ul class="text-ink mt-3 list-disc space-y-1.5 pl-5 text-sm">
    <li>Tuyệt đối không hối lộ admin để gỡ bài.</li>
    <li>Tuyệt đối không đe doạ người tố cáo.</li>
    <li>Mọi bằng chứng giả mạo sẽ bị đưa vào danh sách đen vĩnh viễn.</li>
  </ul>
</section>
