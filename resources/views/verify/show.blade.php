@extends('layouts.app')

{{-- Expects: $query (string), $result (App\Services\IntermediaryVerification|null), $throttled (bool). --}}
@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Kiểm tra giao dịch viên"
      description="Trước khi chuyển tiền, hãy kiểm tra số điện thoại, Telegram, Facebook, Zalo hoặc số tài khoản đó có đúng là kênh chính thức của giao dịch viên (GDV) đã ký quỹ với Scamproof hay không."
      :breadcrumbs="[['name' => 'Kiểm tra giao dịch viên', 'url' => route('intermediary.verify')]]"
    >
      <x-ucs.button :href="route('insurances.frontend.index')" variant="secondary" icon="fa-solid fa-list">
        Danh sách GDV
      </x-ucs.button>
    </x-ucs.page-header>

    <x-ucs.container size="narrow" class="space-y-6">
      <form
        action="{{ route('intermediary.verify') }}"
        method="GET"
        role="search"
        class="border-line bg-surface rounded-card border p-4 sm:p-5"
      >
        <label for="verify-q" class="text-ink block text-sm font-semibold">
          Thông tin liên hệ hoặc thanh toán cần kiểm tra
        </label>
        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
          <div class="relative min-w-0 flex-1">
            <i
              class="fa-solid fa-user-shield text-ink-subtle pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm"
              aria-hidden="true"
            ></i>
            <input
              id="verify-q"
              type="text"
              name="q"
              value="{{ $query }}"
              required
              maxlength="255"
              autocomplete="off"
              enterkeyhint="search"
              placeholder="SĐT, @telegram, link Facebook/Zalo hoặc số tài khoản của người bạn sắp giao dịch"
              aria-describedby="verify-q-hint"
              class="border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent h-11 w-full border pr-3 pl-10 text-base text-ellipsis focus-visible:outline-2 focus-visible:outline-offset-1 sm:text-sm"
            />
          </div>
          <button
            type="submit"
            class="bg-hero-action hover:bg-hero-action-hover rounded-control focus-visible:outline-hero-action inline-flex h-11 shrink-0 cursor-pointer items-center justify-center gap-2 px-5 text-sm font-semibold text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
          >
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            Kiểm tra
          </button>
        </div>
        <p id="verify-q-hint" class="text-ink-subtle mt-2 text-xs leading-relaxed">
          Hệ thống so khớp với các kênh chính thức mà ban quản trị đã xác minh cho từng GDV. Số điện thoại có thể nhập
          dạng +84 hoặc 0.
        </p>
      </form>

      @if ($throttled)
        <div
          class="border-danger/30 bg-danger-soft text-danger rounded-card flex items-start gap-2 border px-4 py-3 text-sm font-medium"
          role="alert"
        >
          <i class="fa-solid fa-hourglass-half mt-0.5" aria-hidden="true"></i>
          Bạn đã kiểm tra quá nhiều lần trong một phút. Vui lòng thử lại sau ít phút.
        </div>
      @elseif ($result)
        @include('verify.partials.result', ['result' => $result])
      @endif

      <x-ucs.card title="Kết quả kiểm tra có ý nghĩa gì?">
        <ul class="space-y-3 text-sm leading-relaxed">
          <li class="flex gap-3">
            <i class="fa-solid fa-circle-check text-success mt-1 w-4 shrink-0 text-center" aria-hidden="true"></i>
            <span class="text-ink-muted">
              <strong class="text-ink font-semibold">Kênh GDV chính thức:</strong>
              kênh thuộc một GDV đang còn hiệu lực trên Scamproof. Kết quả không đánh giá nội dung giao dịch, hãy trao
              đổi rõ điều kiện với GDV trước khi chuyển tiền.
            </span>
          </li>
          <li class="flex gap-3">
            <i class="fa-solid fa-clock-rotate-left text-warning mt-1 w-4 shrink-0 text-center" aria-hidden="true"></i>
            <span class="text-ink-muted">
              <strong class="text-ink font-semibold">Không còn hiệu lực:</strong>
              kênh từng thuộc một GDV nhưng GDV đó đã hết hạn, ngừng hoạt động hoặc bị tạm đình chỉ.
            </span>
          </li>
          <li class="flex gap-3">
            <i class="fa-solid fa-circle-xmark text-accent mt-1 w-4 shrink-0 text-center" aria-hidden="true"></i>
            <span class="text-ink-muted">
              <strong class="text-ink font-semibold">Không phải kênh GDV chính thức:</strong>
              thông tin không nằm trong danh sách kênh của GDV nào. Người dùng có thể đang mạo danh GDV hoặc ban quản
              trị Scamproof.
            </span>
          </li>
        </ul>
      </x-ucs.card>

      @include('partials.home.official-channels')
    </x-ucs.container>
  </main>
@endsection
