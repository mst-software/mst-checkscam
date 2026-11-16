@extends('layouts.app')

@php
  $sections = [
    [
      'title' => 'Quy định về dữ liệu',
      'body' => 'Tất cả thông tin trên hệ thống được đóng góp bởi cộng đồng và đội ngũ biên tập viên. Chúng tôi không chịu trách nhiệm về tính chính xác tuyệt đối nhưng cam kết xác minh kỹ lưỡng nhất có thể dựa trên các bằng chứng được cung cấp.',
    ],
    [
      'title' => 'Bảo mật thông tin người tố cáo',
      'body' => 'Hệ thống cam kết bảo mật danh tính người tố cáo một cách tuyệt đối. Thông tin cá nhân của bạn sẽ không bao giờ được công khai hoặc cung cấp cho bên thứ ba, trừ khi có yêu cầu bằng văn bản từ cơ quan chức năng có thẩm quyền.',
    ],
    [
      'title' => 'Gỡ bài viết',
      'body' => 'Bài viết tố cáo chỉ được gỡ bỏ khi: (1) hai bên đã tự thỏa thuận và người tố cáo xác nhận thu hồi đơn; (2) có bằng chứng xác thực bài viết là sai sự thật hoặc vu khống; (3) có sai sót trong quá trình thẩm định của ban quản trị.',
    ],
  ];
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Điều khoản sử dụng"
      description="Cập nhật lần cuối ngày 08/03/2026."
      :breadcrumbs="[['name' => 'Điều khoản sử dụng', 'url' => url('/dieu-khoan')]]"
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
              </section>
            </li>
          @endforeach
        </ol>

        <p class="border-line bg-surface-muted text-ink-muted rounded-control mt-8 border p-4 text-sm leading-relaxed">
          Việc sử dụng website này đồng nghĩa với việc bạn đã đọc và chấp thuận các điều khoản trên. Chúng tôi có quyền
          sửa đổi điều khoản mà không cần thông báo trước.
        </p>
      </article>

      <p class="text-ink-subtle mt-6 text-sm">
        Cho rằng một tố cáo về bạn chưa đúng?
        <a
          href="{{ route('complaints.create') }}"
          class="text-accent hover:text-accent-hover rounded-sm font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Gửi khiếu nại
        </a>
      </p>
    </x-ucs.container>
  </main>
@endsection
