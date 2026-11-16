@extends('layouts.app')

@push('styles')
  <style>
    /* Step visibility once JS is running (the report-js class is set inline before the form renders). */
    html:not(.report-js) [data-js-only] {
      display: none !important;
    }

    html.report-js #report-form [data-step-panel] {
      display: none;
    }

    html.report-js #report-form[data-current-step='1'] [data-step-panel='1'],
    html.report-js #report-form[data-current-step='2'] [data-step-panel='2'],
    html.report-js #report-form[data-current-step='3'] [data-step-panel='3'],
    html.report-js #report-form[data-current-step='4'] [data-step-panel='4'] {
      display: block;
    }

    html.report-js #report-form[data-current-step='1'] [data-back-btn] {
      visibility: hidden;
    }

    html.report-js #report-form[data-current-step='4'] [data-next-btn],
    html.report-js #report-form:not([data-current-step='4']) [data-submit-btn] {
      display: none;
    }

    /* Without JS every step is listed in order. */
    html:not(.report-js) #report-form [data-step-panel] + [data-step-panel] {
      border-top: 1px solid var(--color-line);
      margin-top: 2rem;
      padding-top: 2rem;
    }

    #report-form [hidden] {
      display: none !important;
    }
  </style>
@endpush

@section('content')
  <main class="pb-24">
    <x-breadcrumb :links="[['name' => 'Tố cáo lừa đảo', 'url' => route('reports')]]" />

    <x-ucs.container>
      <header class="mb-6 max-w-3xl">
        <h1 class="text-ink text-2xl font-semibold tracking-tight sm:text-3xl">Tố cáo lừa đảo</h1>
        <p class="text-ink-muted mt-2 text-sm sm:text-base">
          Gửi thông tin đối tượng và bằng chứng để cảnh báo cộng đồng. Chỉ mất khoảng 3 phút, tố cáo được kiểm duyệt
          trước khi công khai.
        </p>
      </header>

      @if (session('success'))
        <div
          class="border-success/30 bg-success-soft text-success rounded-control mb-6 flex items-start gap-2 border px-4 py-3 text-sm font-medium"
          role="status"
        >
          <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
          {{ session('success') }}
        </div>
      @endif

      <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        <section class="min-w-0 lg:col-span-8" aria-label="Biểu mẫu tố cáo">
          @include('reports.partials.form')
        </section>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:col-span-4" aria-label="Hướng dẫn trước khi gửi">
          @include('reports.partials.sidebar')
        </aside>
      </div>

      <section class="mt-14 max-w-4xl" aria-labelledby="report-faq-title">
        <x-ucs.section-title title="Câu hỏi thường gặp" id="report-faq-title" />
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <x-ucs.card title="Thông tin của tôi có được bảo mật?">
            <p class="text-ink-muted text-sm leading-relaxed">
              Dữ liệu người tố cáo chỉ được dùng để xác minh nội bộ và hoàn toàn không công khai để bảo vệ danh tính cá
              nhân.
            </p>
          </x-ucs.card>
          <x-ucs.card title="Scamproof có thu phí không?">
            <p class="text-ink-muted text-sm leading-relaxed">
              Scamproof là dự án cộng đồng phi lợi nhuận. Mọi hoạt động tố cáo và tra cứu đều miễn phí 100%.
            </p>
          </x-ucs.card>
        </div>
      </section>
    </x-ucs.container>
  </main>
@endsection

@push('scripts')
  @include('reports.partials.dialogs')
  @include('reports.partials.script')
@endpush
