<section aria-label="Cảnh báo mới nhất">
  <x-ucs.section-title
    title="Cảnh báo mới nhất"
    subtitle="Tố cáo vừa được ban quản trị kiểm duyệt."
    action-label="Gửi tố cáo"
    :action-url="route('reports')"
  />

  @if ($latestReports->isNotEmpty())
    @include('partials.report-list', ['reports' => $latestReports])
  @else
    <x-ucs.empty-state title="Chưa có cảnh báo nào được duyệt." class="bg-surface" />
  @endif
</section>
