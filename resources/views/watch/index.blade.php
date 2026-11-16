@extends("layouts.app")

@php
  $atLimit = $watches->count() >= $maxWatches;
  $describedBy = trim(($errors->has("value") ? "watch-value-error " : "") . ($atLimit ? "watch-limit" : ""));
@endphp

@section("content")
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Danh sách theo dõi"
      description="Nhận email khi có tố cáo mới được duyệt cho số điện thoại, số tài khoản hoặc Facebook bạn theo dõi."
      :breadcrumbs="[['name' => 'Danh sách theo dõi', 'url' => route('watch.index')]]"
    />

    <x-ucs.container size="narrow" class="space-y-6">
      @if (session("watch_status"))
        <div
          class="border-success/30 bg-success-soft text-success rounded-control flex items-start gap-2 border px-4 py-3 text-sm font-medium"
          role="status"
        >
          <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
          {{ session("watch_status") }}
        </div>
      @endif

      <x-ucs.card title="Thêm đối tượng theo dõi">
        <form action="{{ route('watch.store') }}" method="POST">
          @csrf
          <label for="watch-value" class="text-ink mb-1.5 block text-sm font-medium">
            Số điện thoại, số tài khoản hoặc link Facebook
          </label>
          <div class="flex flex-col gap-2 sm:flex-row">
            <input
              id="watch-value"
              type="text"
              name="value"
              value="{{ old('value') }}"
              maxlength="255"
              required
              placeholder="Ví dụ: 0912345678"
              class="border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent aria-invalid:border-danger h-10 min-w-0 flex-1 border px-3 text-base focus-visible:outline-2 focus-visible:outline-offset-1 sm:text-sm"
              @error("value")
                  aria-invalid="true"
              @enderror
              @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            />
            <x-ucs.button type="submit" icon="fa-regular fa-bell">Theo dõi</x-ucs.button>
          </div>
          @error("value")
            <p id="watch-value-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
          @enderror
        </form>
      </x-ucs.card>

      <section aria-labelledby="watch-list-title">
        <div class="mb-3 flex items-center justify-between gap-3">
          <h2 id="watch-list-title" class="text-ink text-base font-semibold">Đang theo dõi</h2>
          <x-ucs.badge :variant="$atLimit ? 'danger' : 'neutral'">
            <span class="tabular-nums">{{ $watches->count() }}/{{ $maxWatches }}</span>
          </x-ucs.badge>
        </div>

        @if ($atLimit)
          <p
            id="watch-limit"
            class="border-line bg-surface-muted text-ink-muted rounded-control mb-3 border px-4 py-3 text-sm"
          >
            Bạn đã theo dõi tối đa {{ $maxWatches }} đối tượng. Hãy bỏ theo dõi bớt trước khi thêm mới.
          </p>
        @endif

        @if ($watches->isEmpty())
          <x-ucs.empty-state
            title="Bạn chưa theo dõi đối tượng nào."
            :description="'Thêm tối đa ' . $maxWatches . ' số điện thoại, số tài khoản hoặc Facebook để nhận cảnh báo qua email.'"
            icon="fa-regular fa-bell"
            class="bg-surface"
          />
        @else
          <ul class="border-line bg-surface rounded-card divide-line divide-y border">
            @foreach ($watches as $watch)
              <li class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                  <p class="text-ink-subtle text-xs">{{ $watch->typeLabel() }}</p>
                  <p class="text-ink truncate text-sm font-medium">{{ $watch->value }}</p>
                  <p class="text-ink-subtle mt-0.5 text-xs">
                    @if ($watch->last_notified_at)
                      Thông báo gần nhất: {{ $watch->last_notified_at->format("d/m/Y H:i") }}
                    @else
                      Chưa có tố cáo mới
                    @endif
                  </p>
                </div>
                <form action="{{ route('watch.destroy', $watch) }}" method="POST" class="shrink-0">
                  @csrf
                  @method("DELETE")
                  <x-ucs.button
                    type="submit"
                    variant="secondary"
                    size="sm"
                    icon="fa-solid fa-xmark"
                    aria-label="Bỏ theo dõi {{ $watch->value }}"
                  >
                    Bỏ theo dõi
                  </x-ucs.button>
                </form>
              </li>
            @endforeach
          </ul>
        @endif
      </section>
    </x-ucs.container>
  </main>
@endsection
