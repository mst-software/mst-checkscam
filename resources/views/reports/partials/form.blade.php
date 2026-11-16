{{--
  Multi-step report form. Every step lives in one <form> and posts once to report.store with the
  original field names. Without JavaScript all steps render one after another; with it, only the
  current step shows (see the report-js rules in reports/index).
--}}
@php
  $steps = [
    1 => ['title' => 'Đối tượng', 'description' => 'Thông tin tài khoản, số điện thoại hoặc website lừa đảo.'],
    2 => ['title' => 'Hình thức lừa đảo', 'description' => 'Chọn hình thức gần nhất để người khác dễ nhận diện.'],
    3 => ['title' => 'Nội dung & bằng chứng', 'description' => 'Kể lại sự việc và đính kèm ảnh chụp làm bằng chứng.'],
    4 => ['title' => 'Người báo cáo', 'description' => 'Thông tin để đội kiểm duyệt liên hệ xác minh khi cần.'],
  ];

  // Server validation errors reopen the first step that has a failing field.
  $stepFields = [
    1 => ['type', 'target_id', 'target_name', 'target_bank'],
    2 => ['scam_type', 'category'],
    3 => ['description', 'damage_amount', 'evidence_images'],
    4 => ['reporter_name', 'reporter_contact'],
  ];
  $initialStep = 1;
  foreach ($stepFields as $step => $fields) {
    foreach ($fields as $field) {
      if ($errors->has($field) || $errors->has($field . '.*')) {
        $initialStep = $step;
        break 2;
      }
    }
  }

  $type = in_array(old('type'), ['account', 'website'], true) ? old('type') : 'account';

  $control = 'border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control block w-full border px-3 text-sm transition-colors focus:border-accent focus:ring-2 focus:ring-accent/20 focus:outline-none aria-invalid:border-danger';
  $label = 'text-ink mb-1.5 block text-sm font-medium';
  $optional = 'text-ink-subtle font-normal';
  $hint = 'text-ink-subtle mt-1.5 text-xs';
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
    data-current-step="{{ $initialStep }}"
    data-has-errors="{{ $errors->any() ? 'true' : 'false' }}"
    data-home-url="{{ route('home') }}"
  >
    @csrf

    {{-- Stepper --}}
    <div class="border-line border-b px-4 pt-4 pb-3 sm:px-6" data-js-only>
      <ol class="grid grid-cols-4 gap-2" aria-label="Các bước tố cáo">
        @foreach ($steps as $number => $step)
          @php
            $state = $number < $initialStep ? 'done' : ($number === $initialStep ? 'current' : 'upcoming');
          @endphp

          <li class="min-w-0">
            <button
              type="button"
              class="group flex w-full cursor-pointer flex-col gap-2 rounded-md pb-1 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
              data-step-target="{{ $number }}"
              data-state="{{ $state }}"
              @if ($state === 'current') aria-current="step" @endif
            >
              <span
                class="bg-line group-data-[state=current]:bg-accent group-data-[state=done]:bg-accent h-1 w-full rounded-full"
                aria-hidden="true"
              ></span>
              <span class="flex min-w-0 items-center gap-2">
                <span
                  class="border-line-strong text-ink-subtle group-data-[state=current]:border-accent group-data-[state=current]:bg-accent group-data-[state=current]:text-on-accent group-data-[state=done]:border-accent group-data-[state=done]:text-accent flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold"
                  aria-hidden="true"
                >
                  <span class="group-data-[state=done]:hidden">{{ $number }}</span>
                  <span class="hidden group-data-[state=done]:inline">
                    <i class="fa-solid fa-check text-[10px]"></i>
                  </span>
                </span>
                <span
                  class="text-ink-subtle group-data-[state=current]:text-ink group-data-[state=done]:text-ink-muted hidden truncate text-sm font-medium md:block"
                  title="{{ $step['title'] }}"
                >
                  <span class="sr-only">Bước {{ $number }}:</span>
                  {{ $step['title'] }}
                </span>
                <span class="sr-only md:hidden">Bước {{ $number }}: {{ $step['title'] }}</span>
              </span>
            </button>
          </li>
        @endforeach
      </ol>
    </div>

    <div class="px-4 py-5 sm:px-6 sm:py-6">
      {{-- General errors: rate limit, server summary or AJAX failures --}}
      <div
        id="report-form-alert"
        class="border-danger/30 bg-danger-soft text-danger rounded-control mb-5 border px-4 py-3 text-sm"
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

      @include('reports.partials.step-target')
      @include('reports.partials.step-scam-type')
      @include('reports.partials.step-evidence')
      @include('reports.partials.step-reporter')

      {{-- Navigation --}}
      <div
        class="border-line mt-6 flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:items-center sm:justify-between"
      >
        <x-ucs.button variant="secondary" icon="fa-solid fa-arrow-left" data-js-only data-back-btn>
          Quay lại
        </x-ucs.button>

        <div class="flex flex-col gap-3 sm:ml-auto sm:flex-row sm:items-center">
          <x-ucs.button data-js-only data-next-btn>
            Tiếp tục
            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
          </x-ucs.button>

          <button
            type="submit"
            class="bg-hero-action hover:bg-hero-action-hover rounded-control inline-flex h-11 cursor-pointer items-center justify-center gap-2 px-6 text-base font-semibold text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-60"
            data-submit-btn
          >
            <i class="fa-solid fa-paper-plane text-sm" aria-hidden="true"></i>
            <span data-submit-label>Gửi tố cáo</span>
          </button>
        </div>
      </div>
    </div>
  </form>
</x-ucs.card>
