@extends('layouts.app')

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Danh sách cảnh báo"
      description="Tài khoản ngân hàng, số điện thoại và Facebook đã bị cộng đồng tố cáo và được kiểm duyệt. Thông tin đã được che bớt để bảo vệ quyền riêng tư."
      :breadcrumbs="[['name' => 'Danh sách cảnh báo', 'url' => route('warnings.index')]]"
    />

    <x-ucs.container>
      @include('warnings.partials.filters')

      <p class="text-ink-muted mt-6 mb-3 text-sm">
        Tìm thấy
        <strong class="text-ink font-semibold tabular-nums">
          {{ number_format($reports->total(), 0, ',', '.') }}
        </strong>
        cảnh báo
      </p>

      @if ($reports->isEmpty())
        <x-ucs.empty-state
          title="Không có cảnh báo nào phù hợp"
          :description="$hasFilters ? 'Hãy thử bỏ bớt bộ lọc hoặc đổi từ khoá tìm kiếm.' : 'Hiện chưa có cảnh báo nào được duyệt.'"
          icon="fa-solid fa-shield-halved"
        >
          <div class="flex flex-wrap items-center justify-center gap-2">
            @if ($hasFilters)
              <x-ucs.button variant="secondary" :href="route('warnings.index')" icon="fa-solid fa-rotate-left">
                Xoá bộ lọc
              </x-ucs.button>
            @endif

            <x-ucs.button :href="route('reports')" icon="fa-solid fa-triangle-exclamation">
              Tố cáo lừa đảo
            </x-ucs.button>
          </div>
        </x-ucs.empty-state>
      @else
        @include('warnings.partials.list')

        @if ($reports->hasPages())
          <div class="mt-6">
            {{ $reports->onEachSide(1)->links('warnings.partials.pagination') }}
          </div>
        @endif
      @endif
    </x-ucs.container>
  </main>
@endsection
