@extends('layouts.app')

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Quỹ bảo hiểm"
      description="Giao dịch viên đã ký quỹ với Scamproof. Số dư ký quỹ do quản trị viên ghi nhận; Scamproof xác minh danh tính và kênh liên hệ chính thức của từng thành viên."
      :breadcrumbs="[['name' => 'Quỹ bảo hiểm', 'url' => route('insurances.frontend.index')]]"
    >
      <a
        href="{{ route('intermediary.verify') }}"
        class="bg-hero-action hover:bg-hero-action-hover rounded-control focus-visible:outline-hero-action inline-flex h-10 items-center gap-2 px-4 text-sm font-medium text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
      >
        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
        Kiểm tra GDV
      </a>
    </x-ucs.page-header>

    <x-ucs.container>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-ucs.stat
          label="Tổng quỹ đang ký"
          :value="number_format((float) $total_fund, 0, ',', '.') . ' đ'"
          tone="success"
        />
        <x-ucs.stat label="Thành viên đã xác thực" :value="number_format($total_members, 0, ',', '.')" />

        <form
          action="{{ route('insurances.frontend.index') }}"
          method="GET"
          role="search"
          class="border-line bg-surface rounded-card flex flex-col justify-center border p-4"
        >
          <label for="insurance-search" class="text-ink-subtle text-xs font-medium">Tìm thành viên</label>
          <div class="relative mt-1">
            <i
              class="fa-solid fa-magnifying-glass text-ink-subtle pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-xs"
              aria-hidden="true"
            ></i>
            <input
              id="insurance-search"
              type="search"
              name="search"
              value="{{ request('search') }}"
              placeholder="Tên hoặc số GDV, ví dụ: GDV số 5"
              autocomplete="off"
              class="border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent h-10 w-full border pr-3 pl-8 text-sm focus-visible:outline-2 focus-visible:outline-offset-1"
            />
          </div>
        </form>
      </div>

      <section aria-label="Danh sách thành viên" class="mt-8">
        <div id="insurance-list-container" aria-live="polite">
          @if ($insurances->isEmpty())
            <x-ucs.empty-state
              title="Không tìm thấy thành viên"
              :description="request('search') ? 'Không có thành viên nào khớp với “' . request('search') . '”.' : 'Hiện chưa có thành viên nào đóng quỹ.'"
              icon="fa-solid fa-magnifying-glass"
              class="bg-surface"
            />
          @else
            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
              @foreach ($insurances as $insurance)
                <li class="border-line bg-surface rounded-card overflow-hidden border">
                  @include('partials.insurance-item', ['insurance' => $insurance, 'showServices' => true])
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </section>

      <x-ucs.card class="mt-10" title="Quy định về quỹ bảo hiểm">
        <p class="text-ink-muted max-w-3xl text-sm leading-relaxed">
          Thành viên trong danh sách đã đóng một khoản ký quỹ. Số dư ký quỹ do quản trị viên ghi nhận; Scamproof xác
          minh danh tính và kênh liên hệ chính thức của thành viên. Trước khi giao dịch, hãy mở hồ sơ của thành viên và
          chỉ liên hệ, chuyển tiền qua đúng các kênh được liệt kê tại đó.
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
          <x-ucs.button href="/lien-he-admin" icon="fa-solid fa-envelope">Liên hệ admin để đóng quỹ</x-ucs.button>
          <x-ucs.button href="/dieu-khoan" variant="secondary">Xem điều khoản</x-ucs.button>
        </div>
      </x-ucs.card>
    </x-ucs.container>
  </main>

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('insurance-search');
        const listContainer = document.getElementById('insurance-list-container');
        let debounceTimer;

        if (!searchInput || !listContainer) {
          return;
        }

        searchInput.addEventListener('input', function () {
          clearTimeout(debounceTimer);

          debounceTimer = setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('search', searchInput.value);
            listContainer.style.opacity = '0.5';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then((response) => response.text())
              .then((html) => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newList = doc.getElementById('insurance-list-container');

                if (newList) {
                  listContainer.innerHTML = newList.innerHTML;
                  window.history.replaceState({}, '', url);
                }
              })
              .catch((error) => console.error('Search error:', error))
              .finally(() => {
                listContainer.style.opacity = '1';
              });
          }, 400);
        });

        // Keep the search purely AJAX while JS is available.
        searchInput.form.addEventListener('submit', (event) => event.preventDefault());
      });
    </script>
  @endpush
@endsection
