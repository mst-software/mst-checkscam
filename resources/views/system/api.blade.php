@extends('layouts.app')

@php
  $endpoint = route('api.v1.check');

  $curlExample = <<<TXT
  curl -G "{$endpoint}" \\
    -H "Authorization: Bearer mst_xxxxxxxxxxxxxxxx" \\
    --data-urlencode "q=0912345678"

  curl -G "{$endpoint}" \\
    -H "X-Api-Key: mst_xxxxxxxxxxxxxxxx" \\
    --data-urlencode "q=https://facebook.com/ten.doi.tuong"
  TXT;

  $sampleUrl = url('/0912345678-ab12c');
  $responseExample = <<<JSON
  {
    "query": { "type": "phone", "value": "0912345678" },
    "risk": "high",
    "total_reports": 3,
    "reports": [
      {
        "slug": "0912345678-ab12c",
        "url": "{$sampleUrl}",
        "target_name_masked": "Nguyễn Văn A.",
        "type": "account",
        "reported_at": "2026-11-10T08:15:00+07:00",
        "comments_count": 2
      }
    ],
    "insurance": [],
    "verified_intermediary": false
  }
  JSON;

  $errorExample = '{ "error": { "code": "unauthenticated", "message": "API key không hợp lệ hoặc đã bị thu hồi." } }';

  $thClass = 'text-ink-subtle border-line border-b px-3 py-2 text-left text-xs font-medium';
  $tdClass = 'border-line text-ink-muted border-b px-3 py-2.5 align-top text-sm';
  $codeClass = 'bg-surface-muted text-ink rounded px-1 py-0.5 font-mono text-[0.85em]';
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Public API tra cứu lừa đảo"
      description="Tích hợp kiểm tra lừa đảo tự động vào website hoặc ứng dụng của bạn."
      :breadcrumbs="[['name' => 'Public API', 'url' => url('/api-checkscam')]]"
    >
      <a
        href="{{ url('/lien-he-admin') }}"
        class="bg-hero-action hover:bg-hero-action-hover rounded-control inline-flex h-10 items-center gap-2 px-4 text-sm font-medium text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hero-action"
      >
        <i class="fa-solid fa-key" aria-hidden="true"></i>
        Đăng ký API key
      </a>
    </x-ucs.page-header>

    <x-ucs.container size="narrow" class="space-y-6">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ucs.card>
          <h2 class="text-ink text-base font-semibold">Dữ liệu đã kiểm duyệt</h2>
          <p class="text-ink-muted mt-2 text-sm leading-relaxed">
            Chỉ trả về các tố cáo đã được ban quản trị duyệt, tên đối tượng luôn được che bớt.
          </p>
        </x-ucs.card>
        <x-ucs.card>
          <h2 class="text-ink text-base font-semibold">Tự nhận diện dữ liệu</h2>
          <p class="text-ink-muted mt-2 text-sm leading-relaxed">
            Số điện thoại (+84, 84 hoặc 0), số tài khoản, link Facebook, mã hồ sơ hoặc tên.
          </p>
        </x-ucs.card>
      </div>

      <x-ucs.card title="Endpoint">
        <p class="text-ink font-mono text-sm break-all">
          <x-ucs.badge variant="info">GET</x-ucs.badge>
          {{ $endpoint }}
        </p>

        <h3 class="text-ink mt-5 text-sm font-semibold">Tham số</h3>
        <div class="mt-2 overflow-x-auto">
          <table class="w-full min-w-[480px]">
            <thead>
              <tr>
                <th scope="col" class="{{ $thClass }}">Tên</th>
                <th scope="col" class="{{ $thClass }}">Bắt buộc</th>
                <th scope="col" class="{{ $thClass }}">Mô tả</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">q</code></td>
                <td class="{{ $tdClass }}">Có</td>
                <td class="{{ $tdClass }}">SĐT, số tài khoản, link Facebook, mã hồ sơ hoặc tên. Tối thiểu 2 ký tự.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </x-ucs.card>

      <x-ucs.card title="Xác thực">
        <p class="text-ink-muted text-sm leading-relaxed">
          Gửi API key qua một trong hai header dưới đây. Mỗi key có giới hạn số truy vấn mỗi phút (mặc định 30). Số lượt
          còn lại được trả về trong header
          <code class="{{ $codeClass }}">X-RateLimit-Remaining</code>
        </p>
        <div class="mt-3 overflow-x-auto">
          <table class="w-full min-w-[480px]">
            <thead>
              <tr>
                <th scope="col" class="{{ $thClass }}">Header</th>
                <th scope="col" class="{{ $thClass }}">Giá trị</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">Authorization</code></td>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">Bearer &lt;API_KEY&gt;</code></td>
              </tr>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">X-Api-Key</code></td>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">&lt;API_KEY&gt;</code></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-4">
          @include('system.partials.code-block', ['id' => 'code-request', 'label' => 'Yêu cầu mẫu (curl)', 'code' => $curlExample])
        </div>
      </x-ucs.card>

      <x-ucs.card title="Dữ liệu trả về (200)">
        @include('system.partials.code-block', ['id' => 'code-response', 'label' => 'JSON', 'code' => $responseExample])
        <div class="mt-4 overflow-x-auto">
          <table class="w-full min-w-[480px]">
            <thead>
              <tr>
                <th scope="col" class="{{ $thClass }}">Trường</th>
                <th scope="col" class="{{ $thClass }}">Mô tả</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">query</code></td>
                <td class="{{ $tdClass }}">Loại dữ liệu hệ thống nhận diện và giá trị đã chuẩn hoá.</td>
              </tr>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">risk</code></td>
                <td class="{{ $tdClass }}">
                  Mức rủi ro tính theo số tố cáo đã duyệt và độ mới của tố cáo gần nhất, nhận một trong các giá trị
                  <code class="{{ $codeClass }}">low</code>
                  <code class="{{ $codeClass }}">medium</code>
                  <code class="{{ $codeClass }}">high</code>
                </td>
              </tr>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">total_reports</code></td>
                <td class="{{ $tdClass }}">Tổng số tố cáo đã duyệt khớp với truy vấn.</td>
              </tr>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">reports</code></td>
                <td class="{{ $tdClass }}">Tối đa 10 tố cáo mới nhất, tên đối tượng đã được che bớt.</td>
              </tr>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">insurance</code></td>
                <td class="{{ $tdClass }}">Đối tác bảo hiểm uy tín khớp với dữ liệu tra cứu.</td>
              </tr>
              <tr>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">verified_intermediary</code></td>
                <td class="{{ $tdClass }}">
                  <code class="{{ $codeClass }}">true</code>
                  khi dữ liệu tra cứu là kênh chính thức (SĐT, số tài khoản, Facebook, Telegram, Zalo, website) của một
                  giao dịch viên còn hiệu lực trên Scamproof;
                  <code class="{{ $codeClass }}">false</code>
                  trong mọi trường hợp còn lại, kể cả kênh của giao dịch viên đã hết hạn hoặc bị tạm đình chỉ.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </x-ucs.card>

      <x-ucs.card title="Mã lỗi">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[480px]">
            <thead>
              <tr>
                <th scope="col" class="{{ $thClass }}">HTTP</th>
                <th scope="col" class="{{ $thClass }}">Mã</th>
                <th scope="col" class="{{ $thClass }}">Ý nghĩa</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="{{ $tdClass }} tabular-nums">401</td>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">unauthenticated</code></td>
                <td class="{{ $tdClass }}">Thiếu API key, key sai hoặc đã bị thu hồi.</td>
              </tr>
              <tr>
                <td class="{{ $tdClass }} tabular-nums">422</td>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">validation_failed</code></td>
                <td class="{{ $tdClass }}">
                  Thiếu tham số
                  <code class="{{ $codeClass }}">q</code>
                  hoặc ngắn hơn 2 ký tự.
                </td>
              </tr>
              <tr>
                <td class="{{ $tdClass }} tabular-nums">429</td>
                <td class="{{ $tdClass }}"><code class="{{ $codeClass }}">rate_limited</code></td>
                <td class="{{ $tdClass }}">
                  Vượt giới hạn truy vấn. Thời gian chờ (giây) nằm trong header
                  <code class="{{ $codeClass }}">Retry-After</code>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-4">
          @include('system.partials.code-block', ['id' => 'code-error', 'label' => 'Ví dụ lỗi', 'code' => $errorExample])
        </div>
      </x-ucs.card>

      <x-ucs.card title="Lấy API key">
        <p class="text-ink-muted text-sm leading-relaxed">
          Hiện tại API key được cấp thủ công bởi quản trị viên. Vui lòng liên hệ admin, cho biết tên website hoặc ứng
          dụng và nhu cầu truy vấn. Key chỉ hiển thị một lần khi được cấp, hãy lưu trữ an toàn và không đưa key vào mã
          nguồn phía trình duyệt.
        </p>
        <div class="mt-4">
          <x-ucs.button :href="url('/lien-he-admin')" icon="fa-solid fa-envelope">Liên hệ admin</x-ucs.button>
        </div>
      </x-ucs.card>
    </x-ucs.container>
  </main>

  @include('partials.copy-to-clipboard')
@endsection
