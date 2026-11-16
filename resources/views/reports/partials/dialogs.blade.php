{{-- Success dialog shown after an AJAX submit --}}
<dialog
  id="report-success-dialog"
  class="border-line bg-surface text-ink rounded-card m-auto w-[calc(100%-2rem)] max-w-md border p-0 backdrop:bg-slate-950/60"
  aria-labelledby="report-success-title"
  aria-describedby="report-success-text"
>
  <div class="px-5 pt-6 pb-5 text-center sm:px-6">
    <span
      class="bg-success-soft text-success mx-auto flex size-12 items-center justify-center rounded-full"
      aria-hidden="true"
    >
      <i class="fa-solid fa-check text-xl"></i>
    </span>
    <h2 id="report-success-title" class="mt-4 text-lg font-semibold">Đã gửi tố cáo</h2>
    <p id="report-success-text" class="text-ink-muted mt-2 text-sm">
      Cảm ơn bạn. Đội kiểm duyệt sẽ xem xét trong 30 phút đến 12 giờ. Sau đó bạn có thể tra cứu lại số tài khoản hoặc
      website vừa tố cáo để kiểm tra.
    </p>
    <p class="text-ink-subtle mt-3 text-xs">Hãy nhắc người thân tra cứu trên Scamproof trước khi chuyển tiền.</p>
  </div>
  <div class="border-line flex flex-col gap-2 border-t px-5 py-4 sm:flex-row-reverse sm:px-6">
    <a
      href="{{ route('home') }}"
      class="bg-hero-action hover:bg-hero-action-hover rounded-control inline-flex h-10 sm:flex-1 items-center justify-center px-4 text-sm font-semibold text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      data-success-home
      autofocus
    >
      Về trang chủ
    </a>
    <a
      href="{{ route('reports') }}"
      class="border-line text-ink hover:bg-surface-muted rounded-control inline-flex h-10 sm:flex-1 items-center justify-center border px-4 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    >
      Gửi tố cáo khác
    </a>
  </div>
</dialog>

{{-- Evidence image viewer --}}
<dialog
  id="evidence-lightbox"
  class="m-auto h-full max-h-none w-full max-w-none bg-slate-950/90 p-0 backdrop:bg-transparent"
  aria-label="Xem ảnh bằng chứng"
>
  <div class="flex h-full w-full items-center justify-center p-4 sm:p-12" data-lightbox-backdrop>
    <img src="" alt="" class="max-h-full max-w-full rounded-lg object-contain" data-lightbox-image />
  </div>
  <button
    type="button"
    class="absolute top-3 right-3 flex size-10 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"
    aria-label="Đóng"
    data-lightbox-close
  >
    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
  </button>
  <button
    type="button"
    class="absolute top-1/2 left-3 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"
    aria-label="Ảnh trước"
    data-lightbox-prev
  >
    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
  </button>
  <button
    type="button"
    class="absolute top-1/2 right-3 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"
    aria-label="Ảnh sau"
    data-lightbox-next
  >
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
  </button>
  <p
    class="absolute bottom-4 left-1/2 -translate-x-1/2 text-xs text-white/70"
    aria-live="polite"
    data-lightbox-counter
  ></p>
</dialog>
