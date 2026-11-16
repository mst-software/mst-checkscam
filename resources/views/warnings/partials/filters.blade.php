@php
  $controlClasses =
    'border-line bg-surface text-ink rounded-control focus-visible:outline-accent h-10 w-full border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-1';
  $labelClasses = 'text-ink-muted mb-1 block text-xs font-medium';
@endphp

<form
  method="GET"
  action="{{ route('warnings.index') }}"
  role="search"
  aria-label="Lọc danh sách cảnh báo"
  class="border-line bg-surface rounded-card border p-4 sm:p-5"
>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12">
    <div class="sm:col-span-2 lg:col-span-4">
      <label for="warning-q" class="{{ $labelClasses }}">Từ khoá</label>
      <input
        id="warning-q"
        type="search"
        name="q"
        value="{{ $filters['q'] }}"
        maxlength="100"
        placeholder="STK, số điện thoại, link Facebook, tên..."
        class="{{ $controlClasses }} placeholder:text-ink-subtle"
      />
    </div>

    <div class="lg:col-span-2">
      <label for="warning-type" class="{{ $labelClasses }}">Loại</label>
      <select id="warning-type" name="type" class="{{ $controlClasses }}">
        @foreach ($typeOptions as $value => $label)
          <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    @if ($bankOptions->isNotEmpty())
      <div class="lg:col-span-2">
        <label for="warning-bank" class="{{ $labelClasses }}">Ngân hàng</label>
        <select id="warning-bank" name="bank" class="{{ $controlClasses }}">
          <option value="">Tất cả ngân hàng</option>
          @foreach ($bankOptions as $bank)
            <option value="{{ $bank }}" @selected($filters['bank'] === $bank)>{{ $bank }}</option>
          @endforeach
        </select>
      </div>
    @endif

    <div class="lg:col-span-2">
      <label for="warning-period" class="{{ $labelClasses }}">Thời gian</label>
      <select id="warning-period" name="period" class="{{ $controlClasses }}">
        <option value="">Mọi thời điểm</option>
        @foreach ($periodOptions as $days => $label)
          <option value="{{ $days }}" @selected($filters['period'] === $days)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <div @class(['lg:col-span-2', 'sm:col-span-2' => $bankOptions->isEmpty()])>
      <label for="warning-sort" class="{{ $labelClasses }}">Sắp xếp</label>
      <select id="warning-sort" name="sort" class="{{ $controlClasses }}">
        @foreach ($sortOptions as $value => $label)
          <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="mt-4 flex flex-wrap items-center gap-2">
    <x-ucs.button type="submit" icon="fa-solid fa-filter">Lọc kết quả</x-ucs.button>
    @if ($hasFilters)
      <x-ucs.button variant="ghost" :href="route('warnings.index')">Xoá bộ lọc</x-ucs.button>
    @endif
  </div>
</form>
