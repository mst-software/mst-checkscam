@extends('layouts.app')

@section('title', 'Danh sách theo dõi')

@section('content')
  <main class="pb-24">
    <x-breadcrumb :links="[['name' => 'Theo dõi', 'url' => route('watch.index')]]" />

    <div class="mx-auto max-w-3xl px-4 sm:px-6">
      @if (session('watch_status'))
        <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
          <i class="fa-solid fa-circle-check mr-2"></i>
          {{ session('watch_status') }}
        </div>
      @endif

      <div
        class="dark:bg-dark_card rounded-3xl border border-gray-100 bg-white p-5 shadow-sm md:p-6 dark:border-gray-800"
      >
        <h1 class="text-lg font-bold tracking-tight text-gray-800 uppercase dark:text-gray-100">Danh sách theo dõi</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Nhận email khi có báo cáo lừa đảo mới được duyệt cho số điện thoại, số tài khoản hoặc Facebook bạn theo dõi.
          Tối đa {{ $maxWatches }} mục.
        </p>

        <form action="{{ route('watch.store') }}" method="POST" class="mt-5 flex flex-col gap-2 sm:flex-row">
          @csrf
          <input
            type="text"
            name="value"
            value="{{ old('value') }}"
            maxlength="255"
            required
            placeholder="SĐT, số tài khoản hoặc link Facebook"
            class="focus:border-cs_blue min-w-0 flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none dark:border-gray-700 dark:bg-slate-800 dark:text-gray-100"
          />
          <button
            type="submit"
            class="bg-cs_blue cursor-pointer rounded-xl px-5 py-3 text-xs font-bold tracking-widest text-white uppercase transition-all hover:opacity-90"
          >
            <i class="fa-regular fa-bell mr-1"></i>
            Theo dõi
          </button>
        </form>
        @error('value')
          <p class="text-cs_red mt-2 text-xs font-semibold">{{ $message }}</p>
        @enderror

        <div class="mt-6 flex items-center justify-between text-xs font-bold tracking-widest text-gray-400 uppercase">
          <span>Đang theo dõi</span>
          <span>{{ $watches->count() }}/{{ $maxWatches }}</span>
        </div>

        @forelse ($watches as $watch)
          <div
            class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-gray-100 px-4 py-3 dark:border-gray-800"
          >
            <div class="min-w-0">
              <p class="text-[10px] font-bold tracking-widest text-gray-400 uppercase">{{ $watch->typeLabel() }}</p>
              <p class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ $watch->value }}</p>
              <p class="text-xs text-gray-400">
                @if ($watch->last_notified_at)
                  Thông báo gần nhất: {{ $watch->last_notified_at->format('d/m/Y H:i') }}
                @else
                  Chưa có báo cáo mới
                @endif
              </p>
            </div>
            <form action="{{ route('watch.destroy', $watch) }}" method="POST">
              @csrf
              @method('DELETE')
              <button
                type="submit"
                class="text-cs_red cursor-pointer rounded-lg border border-gray-100 px-3 py-2 text-xs font-bold whitespace-nowrap transition-all hover:bg-red-50 dark:border-gray-700 dark:hover:bg-red-900/20"
              >
                Bỏ theo dõi
              </button>
            </form>
          </div>
        @empty
          <p
            class="mt-3 rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400 dark:border-gray-700"
          >
            Bạn chưa theo dõi đối tượng nào.
          </p>
        @endforelse
      </div>
    </div>
  </main>
@endsection
