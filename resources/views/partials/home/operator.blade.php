{{-- Who runs the site, with the support contacts from site settings. --}}
@php
  $hotline = $siteConfig['hotline'] ?? null;
  $supportEmail = $siteConfig['support_email'] ?? null;
@endphp

<section
  aria-label="Đơn vị vận hành"
  class="border-line bg-surface rounded-card flex flex-col gap-4 border p-5 sm:p-6 md:flex-row md:items-center md:justify-between"
>
  <div class="flex items-center gap-4">
    <span
      class="bg-ink text-page flex size-12 shrink-0 items-center justify-center rounded-xl text-sm font-black tracking-tight"
      aria-hidden="true"
    >
      MST
    </span>
    <div class="min-w-0">
      <p class="text-ink text-[15px] font-bold">Vận hành bởi MST Software</p>
      <p class="text-ink-subtle mt-0.5 text-[13px]">Liên hệ khi cần hỗ trợ hoặc báo sai sót dữ liệu.</p>
    </div>
  </div>

  @if (filled($hotline) || filled($supportEmail))
    <ul class="text-ink-muted flex flex-col gap-2 text-sm sm:flex-row sm:flex-wrap sm:gap-x-6">
      @if (filled($hotline))
        <li>
          <a
            href="tel:{{ preg_replace('/[^\d+]/', '', $hotline) }}"
            class="hover:text-ink focus-visible:outline-accent inline-flex items-center gap-2 rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2"
          >
            <i class="fa-solid fa-phone text-accent" aria-hidden="true"></i>
            {{ $hotline }}
          </a>
        </li>
      @endif

      @if (filled($supportEmail))
        <li>
          <a
            href="mailto:{{ $supportEmail }}"
            class="hover:text-ink focus-visible:outline-accent inline-flex items-center gap-2 rounded-sm break-all focus-visible:outline-2 focus-visible:outline-offset-2"
          >
            <i class="fa-solid fa-envelope text-accent" aria-hidden="true"></i>
            {{ $supportEmail }}
          </a>
        </li>
      @endif
    </ul>
  @endif
</section>
