{{-- Report reason picker; submitted by submitReport() to POST /api/newfeed/posts/{id}/report. --}}
@component('newfeed.partials.modal', ['id' => 'popup-report', 'title' => 'Báo cáo bài đăng'])
  <input type="hidden" id="report-post-id" value="" />
  <fieldset>
    <legend class="text-ink-muted mb-3 text-sm">Chọn lý do báo cáo để tiếp tục.</legend>
    <div class="space-y-2">
      @foreach (['Spam', 'Lừa đảo', 'Nội dung không phù hợp', 'Khác'] as $reason)
        <label
          class="border-line hover:bg-surface-muted has-checked:border-accent rounded-control has-focus-visible:outline-accent flex cursor-pointer items-center gap-3 border px-3 py-2.5 transition-colors has-focus-visible:outline-2"
        >
          <input
            type="radio"
            name="report-reason"
            value="{{ $reason }}"
            class="accent-accent h-4 w-4 cursor-pointer"
          />
          <span class="text-ink text-sm">{{ $reason }}</span>
        </label>
      @endforeach
    </div>
  </fieldset>

  @slot('footer')
    <x-ucs.button id="btn-submit-report" variant="danger" class="w-full" onclick="submitReport()" disabled>
      Xác nhận báo cáo
    </x-ucs.button>
  @endslot
@endcomponent
