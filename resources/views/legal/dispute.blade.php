@extends('layouts.app')

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Giải quyết khiếu nại"
      description="Chính sách xử lý khi có nhầm lẫn hoặc người tố cáo muốn gỡ bài."
      :breadcrumbs="[['name' => 'Giải quyết khiếu nại', 'url' => route('complaints.create')]]"
    >
      <a
        href="#gui-khieu-nai"
        class="bg-hero-action hover:bg-hero-action-hover rounded-control inline-flex h-10 items-center gap-2 px-4 text-sm font-medium text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hero-action"
      >
        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
        Gửi khiếu nại
      </a>
    </x-ucs.page-header>

    <x-ucs.container size="narrow" class="space-y-8">
      @include('legal.partials.dispute-policy')
      @include('legal.partials.complaint-form')
    </x-ucs.container>
  </main>
@endsection
