{{-- Action row under the verdict. Expects: $report, $maskedTarget. --}}
<div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-2 xl:grid-cols-4">
  @if (App\Models\Watch::normalizeTarget($report->target_id))
    <div>
      @include('partials.watch-button', ['value' => $report->target_id])
    </div>
  @endif

  @include(
    'partials.share-links',
    [
      'url' => route('scammer.show', $report->slug),
      'title' => 'Cảnh báo lừa đảo: ' . $maskedTarget,
    ]
  )

  <x-ucs.button
    variant="secondary"
    :href="route('complaints.create', ['report' => $report->slug]) . '#gui-khieu-nai'"
    icon="fa-regular fa-flag"
    class="w-full"
  >
    Gỡ phốt / Khiếu nại
  </x-ucs.button>

  <x-ucs.button :href="route('reports')" icon="fa-solid fa-plus" class="w-full">Tố cáo thêm</x-ucs.button>
</div>
