{{--
  Search result page body. Expects the data passed by SearchController:
  $query, $type, $results, $matchedInsurances, $isFound, $topWeeklyReports.
--}}
@php
  $hasReports = $results->total() > 0;
  $hasInsurances = $matchedInsurances->isNotEmpty();
@endphp

<x-ucs.container class="py-6 lg:py-8">
  <div class="grid gap-8 lg:grid-cols-12">
    <div class="min-w-0 space-y-6 lg:col-span-8">
      @include('partials.search.verdict')

      @if ($hasReports && $hasInsurances)
        @include('partials.search.conflict-warning')
      @endif

      @if ($hasReports)
        <section aria-label="Tố cáo liên quan">
          <x-ucs.section-title
            title="Tố cáo liên quan"
            :subtitle="number_format($results->total(), 0, ',', '.') . ' bài đã được kiểm duyệt, mới nhất trước.'"
          />
          @include('partials.report-list', ['reports' => $results])

          @if ($results->hasPages())
            <div class="mt-4">{{ $results->links('pagination::tailwind') }}</div>
          @endif
        </section>
      @endif

      @if ($hasInsurances)
        <section aria-label="Đối tác bảo hiểm trùng khớp">
          <x-ucs.section-title
            title="Đối tác bảo hiểm trùng khớp"
            subtitle="Thông tin trùng với trung gian đang tham gia quỹ bảo hiểm CS."
          />
          <ul class="border-line bg-surface rounded-card divide-line divide-y overflow-hidden border">
            @foreach ($matchedInsurances as $insurance)
              <li>@include('partials.insurance-item', ['insurance' => $insurance, 'showServices' => true])</li>
            @endforeach
          </ul>
        </section>
      @endif

      @if (! $isFound)
        <x-ucs.empty-state
          title="Chưa có dữ liệu về thông tin này"
          description="Nếu bạn từng bị lừa bởi thông tin này, hãy gửi tố cáo kèm bằng chứng để cảnh báo cộng đồng."
          icon="fa-solid fa-magnifying-glass"
          class="bg-surface"
        >
          <x-ucs.button :href="route('reports')" icon="fa-solid fa-flag">Gửi tố cáo</x-ucs.button>
        </x-ucs.empty-state>
      @endif
    </div>

    <aside class="min-w-0 space-y-6 lg:col-span-4" aria-label="Thông tin thêm">
      <section aria-label="Lừa đảo phổ biến 7 ngày qua">
        <x-ucs.section-title title="Phổ biến 7 ngày qua" subtitle="Đối tượng bị tố cáo và quan tâm nhiều nhất." />
        @include(
          'partials.ranked-targets',
          [
            'items' => $topWeeklyReports,
            'metricField' => 'report_count',
            'metricLabel' => 'tố cáo',
            'emptyText' => 'Chưa có dữ liệu cảnh báo trong 7 ngày qua.',
          ]
        )
      </section>

      @include('partials.report-cta')
    </aside>
  </div>
</x-ucs.container>
