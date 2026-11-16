@props([
  'stats' => [],
])

@php
  // Only render numbers the controller actually computed; never show placeholders.
  $items = collect([
    'total_reports' => 'Tố cáo đã duyệt',
    'total_scammers' => 'Đối tượng bị tố cáo',
    'total_comments' => 'Bình luận cộng đồng',
  ])->filter(fn ($label, $key) => isset($stats[$key]));
@endphp

@if ($items->isNotEmpty())
  <div {{ $attributes->class(['bg-hero-2 border-t border-white/10']) }}>
    <x-ucs.container class="py-6 sm:py-8">
      <h2 class="sr-only">Dữ liệu trên hệ thống</h2>
      <dl class="mx-auto grid max-w-3xl grid-cols-3 divide-x divide-white/15 text-center">
        @foreach ($items as $key => $label)
          <div class="flex flex-col-reverse gap-1 px-2">
            <dt class="text-on-hero-muted text-xs sm:text-sm">{{ $label }}</dt>
            <dd class="text-on-hero text-2xl font-semibold tabular-nums sm:text-3xl">
              {{ number_format((int) $stats[$key], 0, ',', '.') }}
            </dd>
          </div>
        @endforeach
      </dl>
      <p class="text-on-hero-muted mt-5 flex items-center justify-center gap-2 text-center text-xs sm:text-sm">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        Dữ liệu do cộng đồng đóng góp và được kiểm duyệt
      </p>
    </x-ucs.container>
  </div>
@endif
