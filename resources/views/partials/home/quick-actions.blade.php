@php
  $actions = [
    ['url' => route('reports'), 'icon' => 'fa-solid fa-flag', 'label' => 'Tố cáo', 'description' => 'Gửi bằng chứng cảnh báo'],
    ['url' => route('insurances.frontend.index'), 'icon' => 'fa-solid fa-shield-halved', 'label' => 'Bảo hiểm CS', 'description' => 'Giao dịch qua người đã ký quỹ'],
    ['url' => route('newfeed.index'), 'icon' => 'fa-solid fa-store', 'label' => 'Khu mua bán', 'description' => 'Đăng và tìm tin mua bán'],
    ['url' => url('/api-checkscam'), 'icon' => 'fa-solid fa-code', 'label' => 'Bot / API', 'description' => 'Tích hợp tra cứu vào hệ thống'],
  ];
@endphp

<nav aria-label="Lối tắt">
  <ul class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach ($actions as $action)
      <li>
        <a
          href="{{ $action['url'] }}"
          class="group border-line bg-surface rounded-card hover:border-line-strong focus-visible:outline-accent flex h-full items-start gap-3 border p-3 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 sm:p-4"
        >
          <i class="{{ $action['icon'] }} text-accent mt-0.5 w-5 shrink-0 text-center" aria-hidden="true"></i>
          <span class="min-w-0">
            <span class="text-ink group-hover:text-accent block text-sm font-semibold transition-colors">
              {{ $action['label'] }}
            </span>
            <span class="text-ink-subtle mt-0.5 hidden text-xs sm:block">{{ $action['description'] }}</span>
          </span>
        </a>
      </li>
    @endforeach
  </ul>
</nav>
