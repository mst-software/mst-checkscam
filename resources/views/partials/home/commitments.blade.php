@php
  $linkClasses =
    'text-accent hover:text-accent-hover focus-visible:outline-accent rounded-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2';
@endphp

<section aria-label="Cam kết của chúng tôi">
  <x-ucs.section-title title="Cam kết của chúng tôi" subtitle="Những điều bạn có thể yêu cầu chúng tôi giữ đúng." />

  {{-- On mobile the icon sits beside the text to keep the page short. --}}
  <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
    <li
      class="border-line bg-surface rounded-card grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-3 border p-4 sm:block sm:p-5"
    >
      <span
        class="bg-accent-soft text-accent rounded-control row-span-3 flex size-10 items-center justify-center"
        aria-hidden="true"
      >
        <i class="fa-solid fa-user-shield"></i>
      </span>
      <h3 class="text-ink text-[15px] font-bold sm:mt-3">Không công khai người báo cáo</h3>
      <p class="text-ink-muted mt-1.5 text-sm leading-relaxed">
        Tên, liên hệ và địa chỉ IP của người gửi không hiển thị công khai; chỉ quản trị viên xem được để xác minh.
      </p>
    </li>

    <li
      class="border-line bg-surface rounded-card grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-3 border p-4 sm:block sm:p-5"
    >
      <span
        class="bg-accent-soft text-accent rounded-control row-span-3 flex size-10 items-center justify-center"
        aria-hidden="true"
      >
        <i class="fa-solid fa-scale-balanced"></i>
      </span>
      <h3 class="text-ink text-[15px] font-bold sm:mt-3">Quyền phản hồi cho cả hai bên</h3>
      <p class="text-ink-muted mt-1.5 text-sm leading-relaxed">
        Bị tố cáo sai?
        <a href="{{ route('complaints.create') }}" class="{{ $linkClasses }}">Gửi khiếu nại</a>
        kèm chứng cứ để được xem xét. Báo cáo thiếu căn cứ hoặc sai sự thật sẽ bị từ chối hoặc gỡ.
      </p>
    </li>

    <li
      class="border-line bg-surface rounded-card grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-3 border p-4 sm:block sm:p-5"
    >
      <span
        class="bg-accent-soft text-accent rounded-control row-span-3 flex size-10 items-center justify-center"
        aria-hidden="true"
      >
        <i class="fa-solid fa-hand-holding-heart"></i>
      </span>
      <h3 class="text-ink text-[15px] font-bold sm:mt-3">Miễn phí cho cộng đồng</h3>
      <p class="text-ink-muted mt-1.5 text-sm leading-relaxed">Tra cứu và tố cáo không thu phí.</p>
    </li>

    <li
      class="border-line bg-surface rounded-card grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-3 border p-4 sm:block sm:p-5"
    >
      <span
        class="bg-accent-soft text-accent rounded-control row-span-3 flex size-10 items-center justify-center"
        aria-hidden="true"
      >
        <i class="fa-solid fa-file-lines"></i>
      </span>
      <h3 class="text-ink text-[15px] font-bold sm:mt-3">Minh bạch chính sách</h3>
      <p class="text-ink-muted mt-1.5 text-sm leading-relaxed">Các chính sách được công bố đầy đủ:</p>
      <ul class="mt-2 space-y-1 text-sm">
        <li><a href="{{ url('/dieu-khoan') }}" class="{{ $linkClasses }}">Điều khoản sử dụng</a></li>
        <li><a href="{{ route('moderation.policy') }}" class="{{ $linkClasses }}">Quy trình kiểm duyệt</a></li>
        <li><a href="{{ route('complaints.create') }}" class="{{ $linkClasses }}">Giải quyết khiếu nại</a></li>
      </ul>
    </li>
  </ul>
</section>
