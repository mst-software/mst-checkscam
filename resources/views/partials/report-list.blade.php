{{--
  Compact list of approved reports (masked target, type, date, views, comments).
  Expects: $reports (iterable of Report models).
--}}
@use('App\Helpers\StringHelper')

@php
  $columns = 'md:grid-cols-[minmax(0,1fr)_6.5rem_6rem_5rem_5rem]';
@endphp

<x-ucs.card :padding="false" class="overflow-hidden">
  <div
    class="{{ $columns }} border-line text-ink-subtle bg-surface-muted hidden gap-4 border-b px-5 py-2.5 text-xs font-medium md:grid"
    aria-hidden="true"
  >
    <span>Đối tượng</span>
    <span>Loại</span>
    <span>Ngày đăng</span>
    <span class="text-right">Lượt xem</span>
    <span class="text-right">Bình luận</span>
  </div>

  <ul class="divide-line divide-y">
    @foreach ($reports as $report)
      @php
        $commentCount = $report->comments_count ?? ($report->relationLoaded('comments') ? $report->comments->count() : 0);
      @endphp

      <li>
        <a
          href="{{ route('scammer.show', $report->slug) }}"
          class="{{ $columns }} hover:bg-surface-muted focus-visible:outline-accent grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1.5 px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:px-5"
        >
          <span class="min-w-0">
            <span class="text-ink block truncate text-sm font-medium">
              {{ StringHelper::mask_name($report->target_name) }}
            </span>
            <span class="text-ink-muted block truncate text-xs tabular-nums">
              {{ StringHelper::mask_id($report->target_id, $report->type) }}
            </span>
          </span>

          <span class="justify-self-end md:justify-self-start">
            <x-ucs.badge>{{ $report->typeLabel() }}</x-ucs.badge>
          </span>

          <span class="text-ink-subtle col-span-2 flex flex-wrap gap-x-3 text-xs tabular-nums md:contents">
            <span>
              <span class="sr-only">Ngày đăng</span>
              <time datetime="{{ $report->created_at?->toDateString() }}">
                {{ $report->created_at?->format('d/m/Y') ?? 'Đang cập nhật' }}
              </time>
            </span>
            <span class="md:text-right">
              {{ number_format((int) $report->view_count, 0, ',', '.') }}
              <span class="md:sr-only">lượt xem</span>
            </span>
            <span class="md:text-right">
              {{ number_format((int) $commentCount, 0, ',', '.') }}
              <span class="md:sr-only">bình luận</span>
            </span>
          </span>
        </a>
      </li>
    @endforeach
  </ul>
</x-ucs.card>
