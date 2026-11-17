{{--
  One-page report form with three type tabs. The checked `type` radio drives which labels and fields
  show (pure CSS via :has, see reports/index), so the form also works without JavaScript; the script
  adds the tablist, inline validation, image previews, draft autosave and the AJAX submit.
--}}
@php
  $typeTabs = [
    'account' => ['label' => 'STK scam', 'hint' => 'Lừa qua số tài khoản / SĐT', 'icon' => 'fa-solid fa-building-columns'],
    'website' => ['label' => 'Web scam', 'hint' => 'Website, trang bán hàng giả', 'icon' => 'fa-solid fa-globe'],
    'back' => ['label' => 'Acc bị back', 'hint' => 'Mua acc rồi bị lấy lại', 'icon' => 'fa-solid fa-user-lock'],
  ];

  $requestedType = old('type', request()->query('type'));
  $type = in_array($requestedType, array_keys($typeTabs), true) ? $requestedType : 'account';

  $control = 'border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control block w-full border px-3 text-base transition-colors focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none aria-invalid:border-danger sm:text-sm';
  $label = 'text-ink mb-1.5 block text-sm font-medium';
  $optional = 'text-ink-subtle font-normal';
  $hint = 'text-ink-subtle mt-1.5 text-xs';
  $sectionTitle = 'text-ink flex items-center gap-2.5 text-base font-semibold sm:text-lg';
  $sectionNumber = 'bg-accent-soft text-accent flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-bold';
@endphp

<script>
  document.documentElement.classList.add('report-js');
</script>

<x-ucs.card :padding="false" class="scroll-mt-24" id="report-form-card">
  <form
    id="report-form"
    action="{{ route('report.store') }}"
    method="POST"
    enctype="multipart/form-data"
    data-has-errors="{{ $errors->any() ? 'true' : 'false' }}"
    data-home-url="{{ route('home') }}"
  >
    @csrf

    {{-- Type tabs. JS shows the tablist; without JS the radios below are the switch. --}}
    <div class="border-line border-b px-4 pt-4 sm:px-6 sm:pt-5">
      <p id="report-type-label" class="text-ink mb-2 text-sm font-medium">Bạn bị lừa theo kiểu nào?</p>

      <div role="tablist" aria-labelledby="report-type-label" class="-mb-px grid grid-cols-3 gap-1" data-js-only>
        @foreach ($typeTabs as $value => $tab)
          <button
            type="button"
            role="tab"
            id="report-tab-{{ $value }}"
            aria-controls="report-panel"
            aria-selected="{{ $type === $value ? 'true' : 'false' }}"
            tabindex="{{ $type === $value ? '0' : '-1' }}"
            data-type-tab="{{ $value }}"
            class="text-ink-muted hover:text-ink aria-selected:border-accent aria-selected:text-accent focus-visible:outline-accent flex min-w-0 cursor-pointer flex-col items-center gap-0.5 border-b-2 border-transparent px-1 pt-1 pb-2.5 text-center transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 sm:flex-row sm:justify-center sm:gap-2"
          >
            <i class="{{ $tab['icon'] }} text-sm" aria-hidden="true"></i>
            <span class="truncate text-sm font-semibold">{{ $tab['label'] }}</span>
          </button>
        @endforeach
      </div>

      <fieldset class="pb-4" data-no-js>
        <legend class="sr-only">Loại tố cáo</legend>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
          @foreach ($typeTabs as $value => $tab)
            <label
              class="border-line bg-surface rounded-card has-checked:border-accent has-checked:bg-accent-soft flex cursor-pointer items-start gap-2.5 border p-3"
            >
              <input
                type="radio"
                name="type"
                id="type-{{ $value }}"
                value="{{ $value }}"
                class="accent-accent mt-0.5 size-4 shrink-0"
                data-field="type"
                @checked($type === $value)
              />
              <span class="min-w-0">
                <span class="text-ink block text-sm font-semibold">{{ $tab['label'] }}</span>
                <span class="text-ink-subtle block text-xs">{{ $tab['hint'] }}</span>
              </span>
            </label>
          @endforeach
        </div>
      </fieldset>
    </div>

    <div
      id="report-panel"
      role="tabpanel"
      aria-labelledby="report-tab-{{ $type }}"
      class="space-y-8 px-4 py-5 sm:px-6 sm:py-6"
    >
      <p
        class="bg-surface-muted text-ink-muted rounded-control flex flex-wrap items-center gap-x-2 gap-y-1 px-3 py-2 text-xs"
        hidden
        data-draft-note
      >
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
        Đã khôi phục bản nháp chưa gửi trên máy này (ảnh cần chọn lại).
        <button
          type="button"
          class="text-accent hover:text-accent-hover cursor-pointer rounded-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-accent"
          data-draft-clear
        >
          Xoá nháp
        </button>
      </p>

      {{-- Error summary: rate limit, server validation or AJAX failures --}}
      <div
        id="report-form-alert"
        class="border-danger/30 bg-danger-soft text-danger rounded-control border px-4 py-3 text-sm"
        role="alert"
        tabindex="-1"
        @if (! session('error') && ! $errors->any()) hidden @endif
      >
        <p class="flex items-start gap-2 font-medium">
          <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
          <span data-alert-title>
            @if (session('error'))
              {{ session('error') }} Mỗi địa chỉ IP được gửi tối đa 3 tố cáo trong 24 giờ, vui lòng quay lại sau.
            @elseif ($errors->any())
              Vui lòng kiểm tra lại các mục được đánh dấu bên dưới.
            @endif
          </span>
        </p>
        <ul class="mt-2 list-disc space-y-0.5 pl-9 text-xs" data-alert-list @if (! $errors->any()) hidden @endif>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>

      @include('reports.partials.section-target')
      @include('reports.partials.section-story')
      @include('reports.partials.section-evidence')
      @include('reports.partials.section-reporter')
    </div>

    {{-- Submit bar: sticks to the bottom of the screen on mobile --}}
    <div
      class="border-line bg-surface/95 sticky bottom-0 z-10 flex items-center gap-3 rounded-b-[inherit] border-t px-4 py-3 backdrop-blur sm:px-6 md:static md:bg-transparent md:py-5 md:backdrop-blur-none"
    >
      <p class="text-ink-subtle hidden min-w-0 flex-1 text-xs sm:block">
        Tố cáo được kiểm duyệt trước khi công khai. Kết quả sẽ gửi về email của bạn.
      </p>
      <button
        type="submit"
        class="bg-hero-action hover:bg-hero-action-hover rounded-control inline-flex h-12 w-full cursor-pointer items-center justify-center gap-2 px-6 text-base font-semibold text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-wait disabled:opacity-70 sm:w-auto sm:shrink-0"
        data-submit-btn
      >
        <i class="fa-solid fa-paper-plane text-sm" aria-hidden="true" data-submit-icon></i>
        <i class="fa-solid fa-spinner fa-spin text-sm" aria-hidden="true" hidden data-submit-spinner></i>
        <span data-submit-label>Gửi tố cáo</span>
      </button>
    </div>
  </form>
</x-ucs.card>
