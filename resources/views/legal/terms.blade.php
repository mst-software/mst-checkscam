@extends('layouts.app')

@php
  $sections = [
    ['id' => 'data', 'title' => 'Quy định về dữ liệu'],
    ['id' => 'reporter-privacy', 'title' => 'Bảo mật thông tin người tố cáo'],
    ['id' => 'reporter-responsibility', 'title' => 'Trách nhiệm của người gửi tố cáo'],
    ['id' => 'removal-and-appeals', 'title' => 'Gỡ bài viết và quyền khiếu nại'],
    ['id' => 'acceptance', 'title' => 'Chấp thuận và thay đổi điều khoản'],
  ];

  $linkClasses = 'text-accent hover:text-accent-hover focus-visible:outline-accent inline-flex items-center gap-1 rounded-sm font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2';
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Điều khoản sử dụng"
      description="Quy định khi tra cứu và gửi tố cáo lừa đảo trên Scamproof: dữ liệu được kiểm duyệt ra sao, thông tin của người tố cáo được bảo vệ thế nào và quyền của cả hai bên."
      :breadcrumbs="[['name' => 'Điều khoản sử dụng', 'url' => url('/dieu-khoan')]]"
    >
      <x-ucs.badge icon="fa-regular fa-calendar">
        Cập nhật lần cuối:
        <time datetime="2026-11-16">16/11/2026</time>
      </x-ucs.badge>
    </x-ucs.page-header>

    <x-ucs.container>
      <x-ucs.document :sections="$sections">
        <x-ucs.document-section id="data" :number="1" title="Quy định về dữ liệu">
          <p>
            Tất cả thông tin trên hệ thống được đóng góp bởi cộng đồng và đội ngũ biên tập viên. Chúng tôi không chịu
            trách nhiệm về tính chính xác tuyệt đối nhưng cam kết xác minh kỹ lưỡng nhất có thể dựa trên các bằng chứng
            được cung cấp.
          </p>
          <x-ucs.callout icon="fa-solid fa-shield-halved" title="Mọi tố cáo đều được kiểm duyệt trước khi công khai">
            <p>Mỗi tố cáo thường được xem xét trong vòng 24 giờ.</p>
            <p>
              <a href="{{ route('moderation.policy') }}" class="{{ $linkClasses }}">
                Xem quy trình kiểm duyệt
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
              </a>
            </p>
          </x-ucs.callout>
        </x-ucs.document-section>

        <x-ucs.document-section id="reporter-privacy" :number="2" title="Bảo mật thông tin người tố cáo">
          <ul class="space-y-3">
            <li class="flex items-start gap-3">
              <i
                class="fa-solid fa-eye-slash text-success mt-1.5 w-4 shrink-0 text-center text-sm"
                aria-hidden="true"
              ></i>
              <span>Thông tin cá nhân của người tố cáo (tên, liên hệ, địa chỉ IP) không được hiển thị công khai.</span>
            </li>
            <li class="flex items-start gap-3">
              <i
                class="fa-solid fa-user-shield text-success mt-1.5 w-4 shrink-0 text-center text-sm"
                aria-hidden="true"
              ></i>
              <span>Chỉ quản trị viên xem được các thông tin này để xác minh tố cáo và xử lý tố cáo sai sự thật.</span>
            </li>
            <li class="flex items-start gap-3">
              <i class="fa-solid fa-lock text-success mt-1.5 w-4 shrink-0 text-center text-sm" aria-hidden="true"></i>
              <span>
                Thông tin của bạn sẽ không được cung cấp cho bên thứ ba, trừ khi có yêu cầu bằng văn bản từ cơ quan chức
                năng có thẩm quyền.
              </span>
            </li>
          </ul>
        </x-ucs.document-section>

        <x-ucs.document-section id="reporter-responsibility" :number="3" title="Trách nhiệm của người gửi tố cáo">
          <x-ucs.callout variant="warning" icon="fa-solid fa-triangle-exclamation" title="Chỉ tố cáo khi có căn cứ">
            <p>Người gửi tố cáo chịu trách nhiệm về tính trung thực của nội dung đã cung cấp.</p>
            <p>
              Tố cáo thiếu căn cứ hoặc sai sự thật sẽ bị từ chối hoặc gỡ khỏi hệ thống; quản trị viên dùng thông tin của
              người gửi để xử lý các trường hợp này.
            </p>
          </x-ucs.callout>
        </x-ucs.document-section>

        <x-ucs.document-section id="removal-and-appeals" :number="4" title="Gỡ bài viết và quyền khiếu nại">
          <p>Bài viết tố cáo chỉ được gỡ bỏ khi:</p>
          <ol class="marker:text-ink-subtle list-decimal space-y-2 pl-6">
            <li>Hai bên đã tự thỏa thuận và người tố cáo xác nhận thu hồi đơn;</li>
            <li>Có bằng chứng xác thực bài viết là sai sự thật hoặc vu khống;</li>
            <li>Có sai sót trong quá trình thẩm định của ban quản trị.</li>
          </ol>
          <x-ucs.callout variant="success" icon="fa-solid fa-scale-balanced" title="Cả hai bên đều được lắng nghe">
            <p>
              Nếu cho rằng mình bị tố cáo sai, bạn có thể gửi khiếu nại kèm chứng cứ để được xem xét. Người tố cáo có
              thể xác nhận thu hồi đơn khi hai bên đã thỏa thuận xong.
            </p>
            <p>
              <a href="{{ route('complaints.create') }}" class="{{ $linkClasses }}">
                Gửi khiếu nại
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
              </a>
            </p>
          </x-ucs.callout>
        </x-ucs.document-section>

        <x-ucs.document-section id="acceptance" :number="5" title="Chấp thuận và thay đổi điều khoản">
          <p>
            Việc sử dụng website này đồng nghĩa với việc bạn đã đọc và chấp thuận các điều khoản trên. Chúng tôi có
            quyền sửa đổi điều khoản mà không cần thông báo trước; ngày cập nhật gần nhất được ghi ở đầu trang.
          </p>
        </x-ucs.document-section>

        <section
          class="border-line bg-surface rounded-card border p-5 sm:p-6 print:break-inside-avoid"
          aria-labelledby="terms-help-title"
        >
          <h2 id="terms-help-title" class="text-ink flex items-center gap-2 text-lg font-semibold">
            <i class="fa-solid fa-headset text-ink-subtle" aria-hidden="true"></i>
            Cần hỗ trợ về điều khoản?
          </h2>
          <p class="text-ink-muted mt-2 text-sm leading-relaxed">
            Liên hệ ban quản trị nếu bạn có câu hỏi về dữ liệu, quyền riêng tư hoặc một tố cáo liên quan đến mình.
          </p>

          <ul class="text-ink mt-4 space-y-2 text-sm">
            @if (! empty($siteConfig['hotline']))
              <li>
                <a href="tel:{{ $siteConfig['hotline'] }}" class="{{ $linkClasses }} gap-2">
                  <i class="fa-solid fa-phone text-ink-subtle w-4 text-center text-xs" aria-hidden="true"></i>
                  {{ $siteConfig['hotline'] }}
                </a>
              </li>
            @endif

            @if (! empty($siteConfig['support_email']))
              <li>
                <a href="mailto:{{ $siteConfig['support_email'] }}" class="{{ $linkClasses }} gap-2 break-all">
                  <i class="fa-solid fa-envelope text-ink-subtle w-4 text-center text-xs" aria-hidden="true"></i>
                  {{ $siteConfig['support_email'] }}
                </a>
              </li>
            @endif

            @if (! empty($siteConfig['zalo_link']))
              <li>
                <a
                  href="{{ $siteConfig['zalo_link'] }}"
                  target="_blank"
                  rel="noopener"
                  class="{{ $linkClasses }} gap-2"
                >
                  <i class="fa-solid fa-comment-dots text-ink-subtle w-4 text-center text-xs" aria-hidden="true"></i>
                  Zalo hỗ trợ
                </a>
              </li>
            @endif
          </ul>

          <div class="mt-5 flex flex-wrap gap-2 print:hidden">
            <x-ucs.button :href="route('complaints.create')" icon="fa-solid fa-scale-balanced">
              Gửi khiếu nại
            </x-ucs.button>
            <x-ucs.button :href="route('moderation.policy')" variant="secondary">Quy trình kiểm duyệt</x-ucs.button>
          </div>
        </section>
      </x-ucs.document>
    </x-ucs.container>
  </main>
@endsection
