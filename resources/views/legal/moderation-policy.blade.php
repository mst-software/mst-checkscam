@extends('layouts.app')

@php
  $sections = [
    [
      'title' => 'Một tố cáo cần có gì',
      'body' => 'Tố cáo chỉ được tiếp nhận khi có đủ thông tin để người kiểm duyệt đối chiếu:',
      'items' => ['Số tài khoản, số điện thoại hoặc đường link của đối tượng.', 'Mô tả diễn biến: cách liên hệ, lời hứa hẹn, số tiền và thời điểm.', 'Ít nhất một ảnh bằng chứng như hóa đơn chuyển khoản hoặc đoạn hội thoại.'],
    ],
    [
      'title' => 'Người kiểm duyệt xem xét thế nào',
      'body' => 'Mọi tố cáo mới đều ở trạng thái chờ duyệt, không có tố cáo nào tự động đăng. Tố cáo thường được xem xét trong vòng 24 giờ. Người kiểm duyệt đọc từng tố cáo và:',
      'items' => ['Đối chiếu bằng chứng với thông tin đối tượng được tố cáo.', 'Loại bỏ tố cáo trùng lặp, thiếu căn cứ hoặc không liên quan đến lừa đảo.', 'Có thể chỉnh sửa để che thông tin nhạy cảm trước khi công khai.'],
    ],
    [
      'title' => 'Kết quả kiểm duyệt',
      'body' => 'Mỗi tố cáo chỉ có một trong hai kết quả:',
      'items' => ['Được duyệt: tố cáo được công khai kèm ngày duyệt và xuất hiện trong kết quả tra cứu.', 'Bị từ chối: tố cáo không được công khai; lý do từ chối được ghi lại trong hệ thống.'],
    ],
    [
      'title' => 'Thông tin được hiển thị và không hiển thị',
      'body' => 'Trang công khai chỉ hiển thị tên đối tượng dạng rút gọn, số tài khoản hoặc số điện thoại đã che bớt, hình thức lừa đảo, mô tả và ảnh bằng chứng. Ảnh bằng chứng được công khai, vì vậy hãy che số dư, mã OTP và thông tin cá nhân của bạn trước khi gửi.',
      'items' => [],
    ],
    [
      'title' => 'Thông tin của người báo cáo',
      'body' => 'Tên, thông tin liên hệ và địa chỉ IP của người gửi không hiển thị công khai. Chỉ quản trị viên xem được các thông tin này để xác minh tố cáo và xử lý khi có khiếu nại hoặc tố cáo sai sự thật.',
      'items' => [],
    ],
    [
      'title' => 'Quyền phản hồi của bên bị tố cáo',
      'body' => 'Nếu cho rằng mình bị tố cáo sai, bạn có thể gửi khiếu nại kèm chứng cứ để được xem xét. Tố cáo thiếu căn cứ hoặc sai sự thật sẽ bị từ chối hoặc gỡ khỏi hệ thống.',
      'items' => [],
      'link' => ['label' => 'Gửi khiếu nại', 'url' => route('complaints.create')],
    ],
    [
      'title' => 'Trách nhiệm của người gửi tố cáo',
      'body' => 'Người gửi cam đoan nội dung tố cáo là sự thật và chịu trách nhiệm về thông tin đã cung cấp theo Điều khoản sử dụng.',
      'items' => [],
      'link' => ['label' => 'Đọc Điều khoản sử dụng', 'url' => url('/dieu-khoan')],
    ],
  ];
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Quy trình kiểm duyệt"
      description="Cách Scamproof tiếp nhận, kiểm tra và công khai một tố cáo lừa đảo."
      :breadcrumbs="[['name' => 'Quy trình kiểm duyệt', 'url' => route('moderation.policy')]]"
    />

    <x-ucs.container size="narrow">
      <article class="border-line bg-surface rounded-card border p-5 sm:p-8">
        <ol class="space-y-8">
          @foreach ($sections as $index => $section)
            <li class="flex gap-4">
              <span
                class="border-line text-ink-muted flex h-8 w-8 shrink-0 items-center justify-center rounded-md border text-sm font-semibold tabular-nums"
                aria-hidden="true"
              >
                {{ $index + 1 }}
              </span>
              <section class="min-w-0">
                <h2 class="text-ink text-lg font-semibold">{{ $section['title'] }}</h2>
                <p class="text-ink-muted mt-2 text-base leading-relaxed">{{ $section['body'] }}</p>

                @if (count($section['items']) > 0)
                  <ul class="mt-3 space-y-2">
                    @foreach ($section['items'] as $item)
                      <li class="text-ink-muted flex items-start gap-2.5 text-base leading-relaxed">
                        <i class="fa-solid fa-circle-check text-success mt-1.5 text-sm" aria-hidden="true"></i>
                        <span>{{ $item }}</span>
                      </li>
                    @endforeach
                  </ul>
                @endif

                @isset($section['link'])
                  <a
                    href="{{ $section['link']['url'] }}"
                    class="text-accent hover:text-accent-hover focus-visible:outline-accent mt-3 inline-flex items-center gap-1 rounded-sm text-sm font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                  >
                    {{ $section['link']['label'] }}
                    <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                  </a>
                @endisset
              </section>
            </li>
          @endforeach
        </ol>

        <p class="border-line bg-surface-muted text-ink-muted rounded-control mt-8 border p-4 text-sm leading-relaxed">
          Kết quả tra cứu chỉ phản ánh dữ liệu tố cáo đã được kiểm duyệt trên Scamproof, không phải bảo đảm an toàn.
          Không thấy cảnh báo không có nghĩa là đối tượng đáng tin cậy.
        </p>
      </article>
    </x-ucs.container>
  </main>
@endsection
