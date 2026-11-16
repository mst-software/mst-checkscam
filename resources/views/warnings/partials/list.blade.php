@use('App\Helpers\StringHelper')

@php
  $typeBadges = [
    'bank' => ['label' => 'Ngân hàng', 'icon' => 'fa-solid fa-building-columns'],
    'phone' => ['label' => 'SĐT', 'icon' => 'fa-solid fa-phone'],
    'facebook' => ['label' => 'Facebook', 'icon' => 'fa-brands fa-facebook'],
    'website' => ['label' => 'Website', 'icon' => 'fa-solid fa-globe'],
  ];
@endphp

{{-- Desktop: compact table --}}
<div class="border-line bg-surface rounded-card hidden overflow-hidden border lg:block">
  <table class="w-full text-left text-sm">
    <caption class="sr-only">Danh sách cảnh báo lừa đảo</caption>
    <thead class="bg-surface-muted text-ink-muted text-xs">
      <tr>
        <th scope="col" class="px-4 py-3 font-medium">Đối tượng</th>
        <th scope="col" class="px-4 py-3 font-medium">Loại</th>
        <th scope="col" class="px-4 py-3 font-medium">Ngân hàng</th>
        <th scope="col" class="px-4 py-3 font-medium">Ngày đăng</th>
        <th scope="col" class="px-4 py-3 text-right font-medium">Lượt xem</th>
        <th scope="col" class="px-4 py-3 text-right font-medium">Bình luận</th>
        <th scope="col" class="px-4 py-3"><span class="sr-only">Chi tiết</span></th>
      </tr>
    </thead>
    <tbody class="divide-line divide-y">
      @foreach ($reports as $report)
        @php($badge = $typeBadges[$report->warning_type] ?? $typeBadges['website'])
        <tr class="hover:bg-surface-muted/60 transition-colors">
          <td class="px-4 py-3">
            <p class="text-ink font-semibold tabular-nums">
              {{ StringHelper::mask_id($report->target_id, $report->type) }}
            </p>
            <p class="text-ink-subtle mt-0.5 text-xs">{{ StringHelper::mask_name($report->target_name) }}</p>
          </td>
          <td class="px-4 py-3">
            <x-ucs.badge :icon="$badge['icon']">{{ $badge['label'] }}</x-ucs.badge>
          </td>
          <td class="text-ink-muted px-4 py-3">{{ $report->target_bank ?: '—' }}</td>
          <td class="text-ink-muted px-4 py-3 whitespace-nowrap tabular-nums">
            <time datetime="{{ $report->created_at->toDateString() }}">
              {{ $report->created_at->format('d/m/Y') }}
            </time>
          </td>
          <td class="text-ink-muted px-4 py-3 text-right tabular-nums">
            {{ number_format($report->view_count, 0, ',', '.') }}
          </td>
          <td class="text-ink-muted px-4 py-3 text-right tabular-nums">
            {{ number_format($report->comments_count, 0, ',', '.') }}
          </td>
          <td class="px-4 py-3 text-right">
            <a
              href="{{ route('scammer.show', $report->slug) }}"
              class="text-accent hover:text-accent-hover rounded-control focus-visible:outline-accent inline-flex items-center gap-1 text-sm font-medium whitespace-nowrap focus-visible:outline-2 focus-visible:outline-offset-2"
            >
              Xem chi tiết
              <span class="sr-only">cảnh báo {{ StringHelper::mask_id($report->target_id, $report->type) }}</span>
              <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

{{-- Mobile and tablet: stacked rows --}}
<ul class="border-line bg-surface rounded-card divide-line divide-y border lg:hidden">
  @foreach ($reports as $report)
    @php($badge = $typeBadges[$report->warning_type] ?? $typeBadges['website'])
    <li>
      <a
        href="{{ route('scammer.show', $report->slug) }}"
        class="hover:bg-surface-muted/60 focus-visible:outline-accent block px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-ink truncate font-semibold tabular-nums">
              {{ StringHelper::mask_id($report->target_id, $report->type) }}
            </p>
            <p class="text-ink-subtle mt-0.5 truncate text-xs">
              {{ StringHelper::mask_name($report->target_name) }}
              @if ($report->target_bank)
                · {{ $report->target_bank }}
              @endif
            </p>
          </div>
          <x-ucs.badge :icon="$badge['icon']" class="shrink-0">{{ $badge['label'] }}</x-ucs.badge>
        </div>
        <div class="text-ink-subtle mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs tabular-nums">
          <span>
            <i class="fa-regular fa-calendar mr-1" aria-hidden="true"></i>
            <time datetime="{{ $report->created_at->toDateString() }}">
              {{ $report->created_at->format('d/m/Y') }}
            </time>
          </span>
          <span>
            <i class="fa-regular fa-eye mr-1" aria-hidden="true"></i>
            {{ number_format($report->view_count, 0, ',', '.') }}
            <span class="sr-only">lượt xem</span>
          </span>
          <span>
            <i class="fa-regular fa-comment mr-1" aria-hidden="true"></i>
            {{ number_format($report->comments_count, 0, ',', '.') }}
            <span class="sr-only">bình luận</span>
          </span>
        </div>
      </a>
    </li>
  @endforeach
</ul>
