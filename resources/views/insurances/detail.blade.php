@extends('layouts.app')

@php
  $contacts = is_array($insurance->contact_info) ? array_filter($insurance->contact_info, fn ($c) => ! empty($c['link'])) : [];
  $services = is_array($insurance->services) ? array_filter($insurance->services, fn ($s) => ! empty($s['title'])) : [];
  $accounts = is_array($insurance->payment_accounts) ? array_filter($insurance->payment_accounts, fn ($a) => ! empty($a['number'])) : [];
  $siteName = $siteConfig['og_site_name'] ?? 'Scamproof';
  $isTrusted = $insurance->isTrusted();
  $verifiedAccountNumbers = $channels->get(\App\Enums\ChannelType::BankAccount->value, collect())->pluck('normalized_value');
  $hasContactChannels = $channels->keys()->contains(fn ($type) => $type !== \App\Enums\ChannelType::BankAccount->value);
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      :title="$insurance->full_name"
      :description="$isTrusted
        ? 'Thành viên đã ký quỹ bảo hiểm và được Scamproof xác minh danh tính.'
      : 'Hồ sơ giao dịch viên. Trạng thái hiện tại: ' . $insurance->status_label . '.'"
      :breadcrumbs="[
        ['name' => 'Quỹ bảo hiểm', 'url' => route('insurances.frontend.index')],
        ['name' => $insurance->full_name, 'url' => route('insurances.frontend.show', $insurance->slug)],
      ]"
    >
      @if ($isTrusted && count($contacts) > 0)
        <a
          href="{{ reset($contacts)['link'] }}"
          target="_blank"
          rel="noopener noreferrer"
          class="bg-hero-action hover:bg-hero-action-hover rounded-control inline-flex h-10 items-center gap-2 px-4 text-sm font-medium text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hero-action"
        >
          <i class="fa-solid fa-message" aria-hidden="true"></i>
          Liên hệ thành viên
        </a>
      @endif

      <x-ucs.button
        :href="route('insurance-claims.create', ['partner' => $insurance->slug])"
        variant="secondary"
        icon="fa-solid fa-file-circle-exclamation"
      >
        Gửi yêu cầu bồi thường
      </x-ucs.button>
    </x-ucs.page-header>

    <x-ucs.container>
      @unless ($isTrusted)
        <div
          @class([
            'rounded-card mb-6 flex items-start gap-3 border px-4 py-3',
            'border-danger/30 bg-danger-soft' => $insurance->isSuspended(),
            'border-warning/40 bg-warning-soft' => ! $insurance->isSuspended(),
          ])
          role="status"
          data-lapsed-notice
        >
          <i
            @class([
              'mt-0.5',
              'fa-solid fa-ban text-danger' => $insurance->isSuspended(),
              'fa-regular fa-clock text-warning' => ! $insurance->isSuspended(),
            ])
            aria-hidden="true"
          ></i>
          <p class="text-ink text-sm leading-relaxed">
            <strong class="font-semibold">GDV này hiện {{ mb_strtolower($insurance->status_label) }}.</strong>
            Các kênh dưới đây không còn là kênh GDV chính thức đang hoạt động của Scamproof.
          </p>
        </div>
      @endunless

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-6 lg:col-span-2">
          @include('insurances.partials.fund-card')

          @include('insurances.partials.claim-history')

          <x-ucs.card title="Dịch vụ cung cấp">
            @if (count($services) > 0)
              <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($services as $service)
                  <li class="text-ink flex items-start gap-2 text-sm">
                    <i class="fa-solid fa-check text-ink-subtle mt-1 text-xs" aria-hidden="true"></i>
                    <span>{{ $service['title'] }}</span>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="text-ink-subtle text-sm">Thành viên chưa cập nhật dịch vụ.</p>
            @endif
          </x-ucs.card>
        </div>

        <aside class="min-w-0 space-y-6">
          @if ($hasContactChannels)
            @include('insurances.partials.channels', ['channels' => $channels])
          @else
            @include('insurances.partials.contacts', ['contacts' => $contacts])
          @endif

          <x-ucs.card title="Tài khoản thanh toán" subtitle="Chỉ chuyển tiền vào các tài khoản được liệt kê tại đây.">
            @if (count($accounts) > 0)
              <ul class="divide-line -my-2 divide-y">
                @foreach ($accounts as $account)
                  @php
                    $isVerifiedAccount = $verifiedAccountNumbers->contains(
                      \App\Models\InsuranceChannel::normalize(\App\Enums\ChannelType::BankAccount, (string) $account['number']),
                    );
                  @endphp

                  <li class="flex items-center justify-between gap-3 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                      <span
                        class="bg-surface-muted text-ink-muted flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-xs font-semibold"
                        aria-hidden="true"
                      >
                        {{ mb_strtoupper(mb_substr($account['bank'] ?? '?', 0, 2)) }}
                      </span>
                      <div class="min-w-0">
                        <p class="text-ink-subtle text-xs">{{ $account['bank'] ?? 'Ngân hàng' }}</p>
                        <p class="text-ink truncate text-sm font-medium tabular-nums">{{ $account['number'] }}</p>
                        @if (! empty($account['name']))
                          <p class="text-ink-muted truncate text-xs">{{ $account['name'] }}</p>
                        @endif

                        @if ($isTrusted && $isVerifiedAccount)
                          <p class="text-success mt-0.5 flex items-center gap-1 text-xs">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            Tài khoản chính thức
                          </p>
                        @endif
                      </div>
                    </div>
                    <x-ucs.button
                      variant="secondary"
                      size="sm"
                      icon="fa-regular fa-copy"
                      data-copy="{{ $account['number'] }}"
                      aria-label="Sao chép số tài khoản {{ $account['number'] }}"
                    >
                      <span data-copy-label aria-live="polite">Sao chép</span>
                    </x-ucs.button>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="text-ink-subtle text-sm">Thành viên chưa cập nhật tài khoản thanh toán.</p>
            @endif
          </x-ucs.card>

          <section
            class="border-danger/30 bg-danger-soft rounded-card border p-4 sm:p-5"
            aria-labelledby="safety-title"
          >
            <h2 id="safety-title" class="text-danger flex items-center gap-2 text-base font-semibold">
              <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
              Lưu ý khi giao dịch
            </h2>
            <ol class="text-ink mt-3 list-decimal space-y-2 pl-5 text-sm leading-relaxed">
              <li>Chỉ giao dịch qua số tài khoản và kênh liên hệ được liệt kê tại trang này.</li>
              <li>
                <strong class="text-danger font-semibold">Lừa đảo:</strong>
                mọi yêu cầu chuyển tiền từ tài khoản khác, dù trùng tên “{{ $insurance->full_name }}”.
              </li>
              <li>Luôn kiểm tra đúng địa chỉ website {{ $siteName }} trước khi giao dịch.</li>
            </ol>
            <a
              href="{{ route('intermediary.verify') }}"
              class="text-danger focus-visible:outline-danger mt-4 inline-flex items-center gap-1.5 rounded-sm text-sm font-semibold underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2"
            >
              <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
              Kiểm tra một SĐT, Telegram hoặc số tài khoản
            </a>
          </section>
        </aside>
      </div>
    </x-ucs.container>
  </main>

  @include('partials.copy-to-clipboard')
@endsection
