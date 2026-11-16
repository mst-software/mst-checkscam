@extends('layouts.app')

{{-- Expects: $partner (Insurance|null, from ?partner=), $partners (Collection of partners for the select). --}}
@php
  $submitted = session('claim_submitted');
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Gửi yêu cầu bồi thường"
      description="Dành cho người bị thiệt hại khi giao dịch qua một giao dịch viên (GDV) đã ký quỹ với Scamproof. Ban quản trị xem xét từng hồ sơ và gửi cập nhật qua email."
      :breadcrumbs="[
        ['name' => 'Quỹ bảo hiểm', 'url' => route('insurances.frontend.index')],
        ['name' => 'Gửi yêu cầu bồi thường', 'url' => route('insurance-claims.create')],
      ]"
    >
      <x-ucs.button :href="route('intermediary.verify')" variant="secondary" icon="fa-solid fa-user-shield">
        Kiểm tra GDV
      </x-ucs.button>
    </x-ucs.page-header>

    <x-ucs.container size="narrow" class="space-y-6">
      @if ($submitted)
        @include('claims.partials.success', ['submitted' => $submitted])
      @else
        @include('claims.partials.process')
        @include('claims.partials.form')
      @endif
    </x-ucs.container>
  </main>
@endsection
