@extends('layouts.app')

@section('title', 'Hệ thống Public API - Kết nối dữ liệu bảo vệ hệ thống')

@section('content')
  <main class="pb-24">
    <x-breadcrumb :links="[['name' => 'Hệ thống Public API', 'url' => '/api-checkscam']]" />

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
      <header class="mb-12 text-center">
        <h1 class="mb-4 text-3xl font-black tracking-tighter text-gray-800 uppercase md:text-5xl dark:text-gray-100">
          Hệ thống
          <span class="text-cs_blue">Public API</span>
        </h1>
        <p
          class="mx-auto max-w-2xl text-sm leading-relaxed font-bold tracking-wide text-gray-500 uppercase md:text-base dark:text-gray-400"
        >
          Giải pháp tích hợp kiểm tra lừa đảo tự động cho website và ứng dụng của bạn.
        </p>
      </header>

      <div class="mb-16 grid grid-cols-1 gap-8 md:grid-cols-2">
        <div class="dark:bg-dark_card rounded-3xl border border-gray-100 bg-white p-8 shadow-xs dark:border-gray-800">
          <div
            class="text-cs_blue mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-900/20"
          >
            <i class="fa-solid fa-code-merge text-2xl"></i>
          </div>
          <h2 class="mb-4 text-xl font-black text-gray-800 uppercase dark:text-white">Dữ liệu thời gian thực</h2>
          <p class="text-sm leading-relaxed font-medium text-gray-600 dark:text-gray-400">
            Truy cập vào kho dữ liệu scam lớn nhất Việt Nam với hơn 45,000+ hồ sơ đã được xác minh. Cập nhật liên tục
            mỗi phút.
          </p>
        </div>

        <div class="dark:bg-dark_card rounded-3xl border border-gray-100 bg-white p-8 shadow-xs dark:border-gray-800">
          <div
            class="text-cs_green mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-green-50 dark:bg-green-900/20"
          >
            <i class="fa-solid fa-bolt text-2xl"></i>
          </div>
          <h2 class="mb-4 text-xl font-black text-gray-800 uppercase dark:text-white">Tốc độ & Hiệu suất</h2>
          <p class="text-sm leading-relaxed font-medium text-gray-600 dark:text-gray-400">
            Hệ thống API tối ưu cho độ trễ cực thấp (< 100ms), sẵn sàng phục vụ các hệ thống thanh toán và thương mại
            điện tử lớn.
          </p>
        </div>
      </div>

      <article
        class="relative overflow-hidden rounded-3xl border border-transparent bg-slate-900 p-8 text-white shadow-2xl md:p-12 dark:border-slate-800 dark:bg-slate-900/50"
      >
        <div class="bg-cs_blue/10 absolute top-0 right-0 h-96 w-96 rounded-full opacity-50 blur-3xl"></div>

        <div class="relative z-10">
          <h2 class="border-cs_blue mb-8 border-l-4 pl-4 text-2xl font-black uppercase">Tài liệu kỹ thuật</h2>

          <div class="space-y-6">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 dark:border-white/5 dark:bg-slate-800/20">
              <p class="text-cs_blue mb-2 text-xs font-black tracking-widest uppercase">Endpoint</p>
              <code
                class="block overflow-x-auto rounded-lg border border-white/5 bg-black/30 p-3 font-mono text-sm break-all text-gray-300"
              >
                GET {{ route('api.v1.check') }}?q={SĐT | STK | link Facebook | tên}
              </code>
              <p class="mt-3 text-sm leading-relaxed text-gray-400">
                Hệ thống tự nhận diện loại dữ liệu: số điện thoại (+84, 84 hoặc 0 đều được), số tài khoản, link
                Facebook, mã hồ sơ hoặc tên. Chỉ trả về các tố cáo đã được kiểm duyệt, tên đối tượng luôn được che bớt.
              </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 dark:border-white/5 dark:bg-slate-800/20">
              <p class="text-cs_blue mb-2 text-xs font-black tracking-widest uppercase">Xác thực</p>
              <p class="mb-3 text-sm leading-relaxed text-gray-400">
                Gửi API key qua header
                <code class="text-gray-300">Authorization: Bearer &lt;API_KEY&gt;</code>
                hoặc
                <code class="text-gray-300">X-Api-Key: &lt;API_KEY&gt;</code>
                . Mỗi key có giới hạn số truy vấn mỗi phút (mặc định 30), xem header
                <code class="text-gray-300">X-RateLimit-Remaining</code>
                .
              </p>
              <pre
                class="overflow-x-auto rounded-lg border border-white/5 bg-black/30 p-4 font-mono text-[10px] text-gray-300 md:text-sm"
              >
curl -G "{{ route('api.v1.check') }}" \
  -H "Authorization: Bearer mst_xxxxxxxxxxxxxxxx" \
  --data-urlencode "q=0912345678"

curl -G "{{ route('api.v1.check') }}" \
  -H "X-Api-Key: mst_xxxxxxxxxxxxxxxx" \
  --data-urlencode "q=https://facebook.com/ten.doi.tuong"</pre
              >
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 dark:border-white/5 dark:bg-slate-800/20">
              <p class="text-cs_blue mb-2 text-xs font-black tracking-widest uppercase">Dữ liệu trả về (200)</p>
              <pre
                class="overflow-x-auto rounded-lg border border-white/5 bg-black/30 p-4 font-mono text-[10px] text-gray-400 md:text-sm"
              >
{
  "query": { "type": "phone", "value": "0912345678" },
  "risk": "high",
  "total_reports": 3,
  "reports": [
    {
      "slug": "0912345678-ab12c",
      "url": "{{ url('/0912345678-ab12c') }}",
      "target_name_masked": "Nguyễn Văn A.",
      "type": "account",
      "reported_at": "2026-11-10T08:15:00+07:00",
      "comments_count": 2
    }
  ],
  "insurance": []
}</pre
              >
              <p class="mt-3 text-sm leading-relaxed text-gray-400">
                <code class="text-gray-300">risk</code>
                gồm
                <code class="text-gray-300">low</code>
                ,
                <code class="text-gray-300">medium</code>
                ,
                <code class="text-gray-300">high</code>
                , tính theo số tố cáo đã duyệt và độ mới của tố cáo gần nhất.
                <code class="text-gray-300">reports</code>
                trả về tối đa 10 tố cáo mới nhất;
                <code class="text-gray-300">insurance</code>
                liệt kê đối tác bảo hiểm uy tín khớp với dữ liệu tra cứu.
              </p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 dark:border-white/5 dark:bg-slate-800/20">
              <p class="text-cs_blue mb-2 text-xs font-black tracking-widest uppercase">Mã lỗi</p>
              <ul class="space-y-2 text-sm leading-relaxed text-gray-400">
                <li>
                  <code class="text-gray-300">401 unauthenticated</code>
                  : thiếu API key, key sai hoặc đã bị thu hồi.
                </li>
                <li>
                  <code class="text-gray-300">422 validation_failed</code>
                  : thiếu tham số
                  <code class="text-gray-300">q</code>
                  hoặc
                  <code class="text-gray-300">q</code>
                  ngắn hơn 2 ký tự.
                </li>
                <li>
                  <code class="text-gray-300">429 rate_limited</code>
                  : vượt giới hạn truy vấn, thử lại sau số giây trong header
                  <code class="text-gray-300">Retry-After</code>
                  .
                </li>
              </ul>
              <pre
                class="mt-3 overflow-x-auto rounded-lg border border-white/5 bg-black/30 p-4 font-mono text-[10px] text-gray-400 md:text-sm"
              >
{ "error": { "code": "unauthenticated", "message": "API key không hợp lệ hoặc đã bị thu hồi." } }</pre
              >
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6 dark:border-white/5 dark:bg-slate-800/20">
              <p class="text-cs_blue mb-2 text-xs font-black tracking-widest uppercase">Lấy API key</p>
              <p class="text-sm leading-relaxed text-gray-400">
                Hiện tại API key được cấp thủ công bởi quản trị viên. Vui lòng liên hệ admin, cho biết tên website hoặc
                ứng dụng và nhu cầu truy vấn. Key chỉ hiển thị một lần khi được cấp, hãy lưu trữ an toàn và không đưa
                key vào mã nguồn phía trình duyệt.
              </p>
            </div>
          </div>

          <div class="mt-12 text-center">
            <a
              href="{{ url('/lien-he-admin') }}"
              class="bg-cs_blue inline-block rounded-2xl px-10 py-4 text-xs leading-none font-black tracking-widest text-white uppercase shadow-xl shadow-blue-500/20 transition-all hover:bg-blue-600 active:scale-95"
            >
              Đăng ký API Key ngay
            </a>
          </div>
        </div>
      </article>
    </div>
  </main>
@endsection
