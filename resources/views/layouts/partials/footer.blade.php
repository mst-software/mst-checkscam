@php
  $systemLinks = [
    ['label' => 'Kiểm tra scam', 'url' => route('home')],
    ['label' => 'Danh sách cảnh báo', 'url' => route('warnings.index')],
    ['label' => 'Quỹ bảo hiểm CS', 'url' => route('insurances.frontend.index')],
    ['label' => 'Kiểm tra GDV', 'url' => route('intermediary.verify')],
    ['label' => 'Khu mua bán', 'url' => route('newfeed.index')],
    ['label' => 'Hệ thống Public API', 'url' => url('/api-checkscam')],
    ['label' => 'Đối tác uy tín', 'url' => url('/doi-tac-uy-tin')],
  ];

  $helpLinks = [
    ['label' => 'Hướng dẫn tố cáo', 'url' => url('/huong-dan-to-cao'), 'external' => false],
    ['label' => 'Liên hệ Admin', 'url' => $siteConfig['zalo_link'] ?? '#', 'external' => true],
    ['label' => 'Điều khoản', 'url' => url('/dieu-khoan'), 'external' => false],
    ['label' => 'Quy trình kiểm duyệt', 'url' => route('moderation.policy'), 'external' => false],
    ['label' => 'Giải quyết khiếu nại', 'url' => url('/giai-quyet-khieu-nai'), 'external' => false],
  ];
@endphp

{{-- The footer stays on a dark ink surface in both themes; logos follow the active theme. --}}
<footer class="bg-dark_bg mt-20 border-t border-white/10 text-slate-300">
  <div class="mx-auto max-w-7xl px-4 pt-12 pb-20 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-12">
      {{-- Brand --}}
      <div class="space-y-4 lg:col-span-4">
        <a href="{{ route('home') }}" class="inline-block" aria-label="Scamproof">
          <x-brand size="lg" dark />
        </a>
        <p class="max-w-sm text-sm leading-relaxed text-slate-400">
          {{ $siteConfig['description'] ?? 'Nền tảng kiểm tra độ tín nhiệm dữ liệu lớn nhất Việt Nam.' }}
        </p>
        <div class="flex gap-2">
          <a
            href="{{ $siteConfig['facebook_link'] ?? '#' }}"
            target="_blank"
            rel="noopener"
            aria-label="Facebook"
            class="rounded-control flex h-9 w-9 items-center justify-center border border-white/10 text-slate-300 transition-colors hover:border-white/30 hover:text-white"
          >
            <i class="fa-brands fa-facebook-f text-sm"></i>
          </a>
          <a
            href="{{ $siteConfig['telegram_link'] ?? '#' }}"
            target="_blank"
            rel="noopener"
            aria-label="Telegram"
            class="rounded-control flex h-9 w-9 items-center justify-center border border-white/10 text-slate-300 transition-colors hover:border-white/30 hover:text-white"
          >
            <i class="fa-brands fa-telegram text-sm"></i>
          </a>
          <a
            href="{{ $siteConfig['zalo_link'] ?? '#' }}"
            target="_blank"
            rel="noopener"
            aria-label="Zalo"
            class="rounded-control flex h-9 w-9 items-center justify-center border border-white/10 text-xs font-semibold text-slate-300 transition-colors hover:border-white/30 hover:text-white"
          >
            ZL
          </a>
        </div>
      </div>

      {{-- System links --}}
      <div class="space-y-4 lg:col-span-2">
        <h4 class="text-sm font-semibold text-white">Hệ thống</h4>
        <ul class="space-y-2.5">
          @foreach ($systemLinks as $link)
            <li>
              <a href="{{ $link['url'] }}" class="text-sm text-slate-400 transition-colors hover:text-white">
                {{ $link['label'] }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>

      {{-- Help links --}}
      <div class="space-y-4 lg:col-span-2">
        <h4 class="text-sm font-semibold text-white">Trợ giúp</h4>
        <ul class="space-y-2.5">
          @foreach ($helpLinks as $link)
            <li>
              <a
                href="{{ $link['url'] }}"
                @if ($link['external']) target="_blank" rel="noopener" @endif
                class="text-sm text-slate-400 transition-colors hover:text-white"
              >
                {{ $link['label'] }}
              </a>
            </li>
          @endforeach
        </ul>
      </div>

      {{-- Contact --}}
      <div class="space-y-4 lg:col-span-4">
        <h4 class="text-sm font-semibold text-white">Liên hệ</h4>
        <ul class="space-y-2.5 text-sm">
          @if (! empty($siteConfig['hotline']))
            <li>
              <a
                href="tel:{{ $siteConfig['hotline'] }}"
                class="flex items-center gap-3 text-slate-400 transition-colors hover:text-white"
              >
                <i class="fa-solid fa-phone w-4 text-center text-xs"></i>
                {{ $siteConfig['hotline'] }}
              </a>
            </li>
          @endif

          @if (! empty($siteConfig['support_email']))
            <li>
              <a
                href="mailto:{{ $siteConfig['support_email'] }}"
                class="flex items-center gap-3 break-all text-slate-400 transition-colors hover:text-white"
              >
                <i class="fa-solid fa-envelope w-4 text-center text-xs"></i>
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
                class="flex items-center gap-3 text-slate-400 transition-colors hover:text-white"
              >
                <i class="fa-solid fa-comment-dots w-4 text-center text-xs"></i>
                Zalo hỗ trợ
              </a>
            </li>
          @endif
        </ul>

        <a
          href="{{ route('reports') }}"
          class="rounded-control inline-flex items-center gap-2 border border-white/20 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-white/10"
        >
          <i class="fa-solid fa-flag text-xs"></i>
          Gửi tố cáo lừa đảo
        </a>
      </div>
    </div>

    <p class="mt-10 border-t border-white/10 pt-6 text-center text-xs text-slate-400 md:text-left">
      Kết quả chỉ phản ánh dữ liệu tố cáo đã được kiểm duyệt trên Scamproof, không phải bảo đảm an toàn.
    </p>

    <div class="mt-3 flex flex-col items-center justify-between gap-3 text-xs text-slate-500 md:flex-row">
      <p class="text-center md:text-left">
        &copy; {{ date('Y') }}
        <span class="text-slate-300">{{ $siteConfig['title'] ?? 'Tra cứu lừa đảo' }}</span>
        . All Rights Reserved.
      </p>
      <p>
        Development by
        <a href="https://www.facebook.com/mstsoftware.vn" class="text-slate-300 hover:text-white hover:underline">
          MST SOFTWARE
        </a>
      </p>
    </div>
  </div>
</footer>

{{-- Back to top --}}
<button
  type="button"
  id="back-to-top"
  aria-label="Lên đầu trang"
  class="border-line bg-surface text-ink-muted hover:text-ink rounded-control fixed right-4 bottom-4 z-40 hidden h-10 w-10 cursor-pointer items-center justify-center border shadow-sm transition-colors md:right-6 md:bottom-6"
>
  <i class="fa-solid fa-arrow-up text-sm"></i>
</button>

@yield('scripts')
@stack('scripts')
