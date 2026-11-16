@use('App\Helpers\StringHelper')

@extends('layouts.app')

@section('title', 'Giải quyết khiếu nại - CheckScam Global')

@section('content')
  <main class="pb-24">
    <x-breadcrumb :links="[['name' => 'Giải quyết khiếu nại', 'url' => '/giai-quyet-khieu-nai']]" />

    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <header class="mb-12 text-center">
        <h1 class="mb-4 text-3xl font-black tracking-tighter text-gray-800 uppercase md:text-5xl dark:text-gray-100">
          Giải quyết
          <span class="text-cs_blue">Khiếu nại</span>
        </h1>
        <p
          class="mx-auto max-w-2xl text-sm leading-relaxed font-bold tracking-widest text-gray-500 uppercase md:text-base dark:text-gray-400"
        >
          Chính sách xử lý khi có nhầm lẫn hoặc người tố cáo muốn gỡ bài.
        </p>
      </header>

      <div class="mb-12 grid grid-cols-1 gap-6">
        <div
          class="dark:bg-dark_card flex flex-col items-center gap-8 rounded-3xl border border-gray-100 bg-white p-8 shadow-xs md:flex-row dark:border-gray-800"
        >
          <div class="bg-cs_blue/10 text-cs_blue flex h-20 w-20 shrink-0 items-center justify-center rounded-full">
            <i class="fa-solid fa-scale-balanced text-3xl"></i>
          </div>
          <div class="space-y-3">
            <h2 class="text-xl font-black tracking-tight text-gray-800 uppercase dark:text-white">
              Quy trình công bằng
            </h2>
            <p class="text-sm leading-relaxed font-medium text-gray-600 dark:text-gray-400">
              Chúng tôi luôn lắng nghe cả hai phía. Nếu bạn cho rằng mình bị tố cáo oan, vui lòng cung cấp bằng chứng
              chứng minh giao dịch thành công hoặc sự nhầm lẫn.
            </p>
          </div>
        </div>

        <div
          class="dark:bg-dark_card flex flex-col items-center gap-8 rounded-3xl border border-gray-100 bg-white p-8 shadow-xs md:flex-row dark:border-gray-800"
        >
          <div class="bg-cs_red/10 text-cs_red flex h-20 w-20 shrink-0 items-center justify-center rounded-full">
            <i class="fa-solid fa-hand-holding-heart text-3xl"></i>
          </div>
          <div class="space-y-3">
            <h2 class="text-xl font-black tracking-tight text-gray-800 uppercase dark:text-white">Khôi phục uy tín</h2>
            <p class="text-sm leading-relaxed font-medium text-gray-600 dark:text-gray-400">
              Khi hai bên đã giải quyết xong và có sự xác nhận của người tố cáo ban đầu, CheckScam sẽ tiến hành gỡ bài
              viết sau tối đa 12h làm việc.
            </p>
          </div>
        </div>
      </div>

      <article
        class="dark:bg-dark_card relative overflow-hidden rounded-3xl border border-gray-100 bg-white p-10 shadow-xs dark:border-gray-800"
      >
        <h3 class="border-cs_blue mb-6 border-l-4 pl-4 text-lg font-black text-gray-800 uppercase dark:text-white">
          Lưu ý quan trọng
        </h3>
        <div class="space-y-4">
          <p
            class="flex items-center gap-2 text-sm font-bold tracking-tighter text-gray-600 uppercase dark:text-gray-400"
          >
            <i class="fa-solid fa-circle-exclamation text-cs_red"></i>
            Tuyệt đối KHÔNG hối lộ Admin để gỡ bài
          </p>
          <p
            class="flex items-center gap-2 text-sm font-bold tracking-tighter text-gray-600 uppercase dark:text-gray-400"
          >
            <i class="fa-solid fa-circle-exclamation text-cs_red"></i>
            Tuyệt đối KHÔNG đe dọa người tố cáo
          </p>
          <p
            class="flex items-center gap-2 text-sm font-bold tracking-tighter text-gray-600 uppercase dark:text-gray-400"
          >
            <i class="fa-solid fa-circle-exclamation text-cs_red"></i>
            Mọi bằng chứng giả mạo sẽ bị blacklist vĩnh viễn
          </p>
        </div>

        <div class="mt-10 border-t border-gray-50 pt-10 text-center dark:border-gray-800">
          <p class="mb-6 text-xs font-black tracking-[3px] text-gray-400 uppercase">Bạn cần khiếu nại?</p>
          <a
            href="#gui-khieu-nai"
            class="inline-block rounded-2xl bg-gray-900 px-10 py-4 text-xs font-black tracking-widest text-white uppercase shadow-xl transition-all hover:scale-105 active:scale-95 dark:bg-white dark:text-gray-900"
          >
            Gửi khiếu nại
          </a>
        </div>
      </article>

      <section
        id="gui-khieu-nai"
        class="dark:bg-dark_card mt-12 scroll-mt-24 rounded-3xl border border-gray-100 bg-white p-6 shadow-xs md:p-10 dark:border-gray-800"
      >
        <h2 class="border-cs_blue mb-6 border-l-4 pl-4 text-lg font-black text-gray-800 uppercase dark:text-white">
          Gửi khiếu nại / phản hồi báo cáo sai
        </h2>

        @if (session('success'))
          <div
            class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700"
          >
            <i class="fa-solid fa-circle-check mr-2"></i>
            {{ session('success') }}
          </div>
        @endif

        @if (session('error'))
          <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            <i class="fa-solid fa-circle-xmark mr-2"></i>
            {{ session('error') }}
          </div>
        @endif

        @if ($errors->any())
          <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
            <ul class="space-y-1">
              @foreach ($errors->all() as $error)
                <li class="text-xs font-semibold text-red-600">
                  <i class="fa-solid fa-circle-xmark mr-1"></i>
                  {{ $error }}
                </li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
          @csrf

          @php
            $reportSlug = old('report', $report?->slug);
          @endphp

          @if ($reportSlug)
            <input type="hidden" name="report" value="{{ $reportSlug }}" />
          @endif

          @if ($report)
            <div
              class="rounded-md border border-gray-100 bg-gray-50 px-4 py-3 text-sm dark:border-gray-800 dark:bg-slate-800"
            >
              <span class="font-semibold text-gray-500 dark:text-gray-400">Báo cáo được khiếu nại:</span>
              <a href="{{ route('scammer.show', $report->slug) }}" class="text-cs_blue font-bold hover:underline">
                {{ StringHelper::mask_name($report->target_name) }} -
                {{ StringHelper::mask_id($report->target_id, $report->type) }}
              </a>
            </div>
          @endif

          {{-- Honeypot: hidden from real users --}}
          <div class="hidden" aria-hidden="true">
            <label for="complaint_website">Website</label>
            <input id="complaint_website" type="text" name="website" tabindex="-1" autocomplete="off" />
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2 md:gap-6">
            <div class="space-y-2">
              <label
                for="contact_name"
                class="ml-1 text-[10px] font-semibold tracking-widest text-gray-800 uppercase md:text-[11px] dark:text-gray-200"
              >
                Họ và tên
                <span class="text-cs_red">*</span>
              </label>
              <input
                id="contact_name"
                type="text"
                name="contact_name"
                value="{{ old('contact_name') }}"
                required
                maxlength="255"
                placeholder="Nguyễn Văn A"
                class="focus:border-cs_blue w-full rounded-md border border-gray-200 bg-gray-50 px-4 py-3.5 text-sm font-semibold text-gray-900 outline-none focus:ring-0 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
              />
            </div>

            <div class="space-y-2">
              <label
                for="contact_email"
                class="ml-1 text-[10px] font-semibold tracking-widest text-gray-800 uppercase md:text-[11px] dark:text-gray-200"
              >
                Email
                <span class="text-cs_red">*</span>
              </label>
              <input
                id="contact_email"
                type="email"
                name="contact_email"
                value="{{ old('contact_email') }}"
                required
                maxlength="255"
                placeholder="email@example.com"
                class="focus:border-cs_blue w-full rounded-md border border-gray-200 bg-gray-50 px-4 py-3.5 text-sm font-semibold text-gray-900 outline-none focus:ring-0 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
              />
            </div>

            <div class="space-y-2">
              <label
                for="contact_phone"
                class="ml-1 text-[10px] font-semibold tracking-widest text-gray-800 uppercase md:text-[11px] dark:text-gray-200"
              >
                Số điện thoại / Zalo
              </label>
              <input
                id="contact_phone"
                type="text"
                name="contact_phone"
                value="{{ old('contact_phone') }}"
                maxlength="20"
                placeholder="09xxxxxxxx"
                class="focus:border-cs_blue w-full rounded-md border border-gray-200 bg-gray-50 px-4 py-3.5 text-sm font-semibold text-gray-900 outline-none focus:ring-0 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
              />
            </div>

            <div class="space-y-2">
              <label
                for="reason"
                class="ml-1 text-[10px] font-semibold tracking-widest text-gray-800 uppercase md:text-[11px] dark:text-gray-200"
              >
                Lý do khiếu nại
                <span class="text-cs_red">*</span>
              </label>
              <select
                id="reason"
                name="reason"
                required
                class="focus:border-cs_blue w-full rounded-md border border-gray-200 bg-gray-50 px-4 py-3.5 text-sm font-semibold text-gray-900 outline-none focus:ring-0 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300 cursor-pointer"
              >
                <option value="">-- Chọn lý do --</option>
                @foreach ($reasons as $reason)
                  <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>
                    {{ $reason->label() }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label
              for="message"
              class="ml-1 text-[10px] font-semibold tracking-widest text-gray-800 uppercase md:text-[11px] dark:text-gray-200"
            >
              Nội dung khiếu nại
              <span class="text-cs_red">*</span>
            </label>
            <textarea
              id="message"
              name="message"
              rows="5"
              required
              minlength="20"
              maxlength="5000"
              placeholder="Trình bày rõ thông tin sai, kèm mã giao dịch hoặc bằng chứng đã giải quyết..."
              class="focus:border-cs_blue w-full rounded-md border border-gray-200 bg-gray-50 px-4 py-3.5 text-sm font-semibold text-gray-900 outline-none focus:ring-0 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300 resize-none leading-relaxed"
            >
{{ old('message') }}</textarea
            >
          </div>

          <div class="space-y-2">
            <label
              for="complaint_evidence"
              class="ml-1 text-[10px] font-semibold tracking-widest text-gray-800 uppercase md:text-[11px] dark:text-gray-200"
            >
              Ảnh bằng chứng (tối đa 5 ảnh, mỗi ảnh 5MB)
            </label>
            <input
              id="complaint_evidence"
              type="file"
              name="evidence_images[]"
              multiple
              accept="image/jpeg,image/png,image/jpg,image/gif"
              class="block w-full text-sm text-gray-500 dark:text-gray-400"
            />
          </div>

          <div class="flex justify-center pt-2">
            <button
              type="submit"
              class="bg-cs_blue w-full rounded-md px-12 py-3 text-xs font-semibold tracking-widest text-white uppercase shadow-lg transition-all hover:bg-black active:scale-95 sm:w-auto md:py-4 md:text-sm"
            >
              Gửi khiếu nại
            </button>
          </div>
        </form>
      </section>
    </div>
  </main>
@endsection
