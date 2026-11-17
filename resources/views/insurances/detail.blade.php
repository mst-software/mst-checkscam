@extends('layouts.app')

@php
  $services = is_array($insurance->services) ? array_filter($insurance->services, fn ($s) => ! empty($s['title'])) : [];
  $isTrusted = $insurance->isTrusted();
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      :title="$insurance->full_name"
      :description="$isTrusted
        ? 'Hồ sơ xác thực giao dịch viên trên Scamproof. Đối chiếu kênh liên hệ trước khi giao dịch.'
      : 'Hồ sơ giao dịch viên trên Scamproof. Trạng thái hiện tại: ' . $insurance->publicStatusLabel() . '.'"
      :breadcrumbs="[
        ['name' => 'Quỹ bảo hiểm', 'url' => route('insurances.frontend.index')],
        ['name' => $insurance->full_name, 'url' => $profileUrl],
      ]"
    >
      <x-ucs.button :href="route('intermediary.verify')" variant="secondary" icon="fa-solid fa-user-shield">
        Kiểm tra giao dịch viên khác
      </x-ucs.button>
    </x-ucs.page-header>

    <x-ucs.container>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-6 lg:col-span-2">
          @include('insurances.partials.profile-identity')

          @if ($totalReports > 0)
            <section
              class="border-danger/30 bg-danger-soft rounded-card border px-4 py-4 sm:px-5"
              role="alert"
              data-reports-notice
            >
              <p class="text-danger flex items-start gap-2 text-sm font-semibold">
                <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
                Có {{ $totalReports }} tố cáo đã được duyệt liên quan đến kênh của thành viên này
              </p>
              <ul class="mt-3 space-y-1.5 pl-6">
                @foreach ($reportedChannels as $channel)
                  <li class="text-ink text-sm">
                    <a
                      href="{{ route('search.index', ['q' => $channel->value]) }}"
                      class="text-danger focus-visible:outline-danger rounded-sm font-semibold break-all underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                      {{ $channel->value }}
                    </a>
                    <span class="text-ink-muted">
                      ({{ $channel->type->label() }}: {{ $reportCounts[trim($channel->value)] }} tố cáo)
                    </span>
                  </li>
                @endforeach
              </ul>
            </section>
          @endif

          @if ($isTrusted)
            @include('insurances.partials.profile-checklist')
          @endif

          @include('insurances.partials.channels')

          @include('insurances.partials.bank-accounts')
        </div>

        <aside class="min-w-0 space-y-6">
          @if ($isTrusted)
            @include('insurances.partials.profile-share')
          @endif

          <x-ucs.card title="Về thành viên" data-about>
            <dl class="space-y-3 text-sm">
              <div class="flex items-start justify-between gap-3">
                <dt class="text-ink-subtle">Thành viên từ</dt>
                <dd class="text-ink text-right font-medium tabular-nums">
                  {{ $insurance->insurance_date ? $insurance->insurance_date->format('d/m/Y') : 'Chưa cập nhật' }}
                </dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="text-ink-subtle">Số thứ tự</dt>
                <dd class="text-ink text-right font-medium">{{ $insurance->memberCode() }}</dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="text-ink-subtle">Kênh chính thức</dt>
                <dd class="text-ink text-right font-medium tabular-nums">{{ $channelCount }}</dd>
              </div>
            </dl>

            <div class="border-line mt-4 border-t pt-4">
              <h3 class="text-ink text-sm font-semibold">Dịch vụ cung cấp</h3>
              @if (count($services) > 0)
                <ul class="mt-2 space-y-1.5">
                  @foreach ($services as $service)
                    <li class="text-ink-muted flex items-start gap-2 text-sm">
                      <i class="fa-solid fa-angle-right text-ink-subtle mt-1 text-xs" aria-hidden="true"></i>
                      <span>{{ $service['title'] }}</span>
                    </li>
                  @endforeach
                </ul>
              @else
                <p class="text-ink-subtle mt-1 text-sm">Thành viên chưa cập nhật dịch vụ.</p>
              @endif
            </div>
          </x-ucs.card>

          <nav aria-label="Liên kết liên quan" class="border-line bg-surface rounded-card divide-line divide-y border">
            <a
              href="{{ route('intermediary.verify') }}"
              class="hover:bg-surface-muted focus-visible:outline-accent flex items-center gap-3 px-4 py-3 text-sm transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2"
            >
              <i class="fa-solid fa-user-shield text-ink-subtle" aria-hidden="true"></i>
              <span class="text-ink flex-1 font-medium">Kiểm tra một SĐT, Telegram hoặc số tài khoản</span>
              <i class="fa-solid fa-chevron-right text-ink-subtle text-xs" aria-hidden="true"></i>
            </a>
            <a
              href="{{ route('insurances.frontend.index') }}"
              class="hover:bg-surface-muted focus-visible:outline-accent flex items-center gap-3 px-4 py-3 text-sm transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2"
            >
              <i class="fa-solid fa-list text-ink-subtle" aria-hidden="true"></i>
              <span class="text-ink flex-1 font-medium">Danh sách giao dịch viên đã ký quỹ</span>
              <i class="fa-solid fa-chevron-right text-ink-subtle text-xs" aria-hidden="true"></i>
            </a>
          </nav>
        </aside>
      </div>
    </x-ucs.container>
  </main>

  @include('partials.copy-to-clipboard')
@endsection
