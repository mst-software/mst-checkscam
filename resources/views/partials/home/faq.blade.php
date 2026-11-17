@php
  $faqs = [
    [
      'question' => 'Làm sao để tố cáo lừa đảo?',
      'answer' => 'Bấm "Tố cáo", điền thông tin đối tượng kèm hình ảnh bằng chứng rõ ràng. Tố cáo thường được xem xét trong vòng 24 giờ.',
    ],
    [
      'question' => 'Tại sao bài tố cáo chưa được duyệt?',
      'answer' => 'Mỗi bài được kiểm tra thủ công để tránh tố cáo sai. Nếu sau 24 giờ chưa được duyệt, hãy kiểm tra lại bằng chứng bạn gửi.',
    ],
    [
      'question' => 'Quỹ bảo hiểm hoạt động thế nào?',
      'answer' => 'Trung gian đóng một khoản ký quỹ, số dư do ban quản trị ghi nhận. Scamproof xác minh danh tính và kênh liên hệ chính thức của trung gian; hãy mở hồ sơ của họ trên Scamproof và chỉ giao dịch qua các kênh được liệt kê.',
    ],
    [
      'question' => 'Tôi có thể xin gỡ bài tố cáo không?',
      'answer' => 'Bài chỉ được gỡ khi người tố cáo xác nhận sự việc đã được giải quyết. Chúng tôi không gỡ bài vì áp lực từ đối tượng bị tố cáo.',
    ],
  ];
@endphp

<section aria-label="Câu hỏi thường gặp" class="mx-auto w-full max-w-3xl">
  <x-ucs.section-title title="Câu hỏi thường gặp" />

  <div class="border-line bg-surface rounded-card divide-line divide-y border">
    @foreach ($faqs as $faq)
      <details class="group [&_summary::-webkit-details-marker]:hidden">
        <summary
          class="text-ink focus-visible:outline-accent flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3.5 text-sm font-medium focus-visible:outline-2 focus-visible:-outline-offset-2 sm:px-5"
        >
          {{ $faq['question'] }}
          <i
            class="fa-solid fa-chevron-down text-ink-subtle text-xs transition-transform group-open:rotate-180"
            aria-hidden="true"
          ></i>
        </summary>
        <p class="text-ink-muted px-4 pb-4 text-sm leading-relaxed sm:px-5">{{ $faq['answer'] }}</p>
      </details>
    @endforeach
  </div>
</section>
