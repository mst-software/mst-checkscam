<section aria-label="Tìm kiếm nhiều trong tuần">
  <x-ucs.section-title
    title="Tìm kiếm nhiều trong tuần"
    subtitle="Đối tượng bị tố cáo được tra cứu nhiều nhất 7 ngày qua."
  />

  @include(
    'partials.ranked-targets',
    [
      'items' => $topWeeklySearches,
      'metricField' => 'search_count',
      'metricLabel' => 'lượt',
      'emptyText' => 'Chưa có lượt tra cứu nổi bật trong tuần.',
    ]
  )
</section>
