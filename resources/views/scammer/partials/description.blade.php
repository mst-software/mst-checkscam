{{-- Reporter's description, with the raw target already masked by the controller. Expects: $description, $report. --}}
<x-ucs.card title="Mô tả vụ việc" subtitle="Nội dung do người tố cáo cung cấp, đã được ban quản trị kiểm duyệt.">
  <p class="text-ink-muted text-base leading-relaxed break-words whitespace-pre-line">{{ $description }}</p>

  @if ($report->category)
    <p class="text-ink-subtle mt-4 text-sm">
      Danh mục:
      <span class="text-ink font-medium">{{ $report->category }}</span>
    </p>
  @endif
</x-ucs.card>
