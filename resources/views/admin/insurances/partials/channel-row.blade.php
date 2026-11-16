{{-- One official channel row of the partner form. $index is a number, or "__INDEX__" inside the JS template. --}}
@php
  $isBank = ($row['type'] ?? '') === \App\Enums\ChannelType::BankAccount->value;
@endphp

<div class="channel-row border rounded p-3 mb-3" data-index="{{ $index }}">
  <div class="row g-2 align-items-end">
    <div class="col-md-4 col-12">
      <div class="form-group mb-0">
        <label for="channel-type-{{ $index }}">
          Loại kênh
          <span class="text-danger">*</span>
        </label>
        <select name="channels[{{ $index }}][type]" id="channel-type-{{ $index }}" class="form-select channel-type">
          @foreach ($channelTypes as $type)
            <option value="{{ $type->value }}" @selected(($row['type'] ?? '') === $type->value)>
              {{ $type->label() }}
            </option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="col-md-8 col-12">
      <div class="form-group mb-0">
        <label for="channel-value-{{ $index }}">
          Giá trị (số / link / &#64;username)
          <span class="text-danger">*</span>
        </label>
        <input
          type="text"
          name="channels[{{ $index }}][value]"
          id="channel-value-{{ $index }}"
          class="form-control"
          maxlength="500"
          value="{{ $row['value'] ?? '' }}"
        />
      </div>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
    <div>
      <div class="form-check mb-0">
        <input type="hidden" name="channels[{{ $index }}][is_official]" value="0" />
        <input
          type="checkbox"
          class="form-check-input"
          name="channels[{{ $index }}][is_official]"
          id="channel-official-{{ $index }}"
          value="1"
          @checked($row['is_official'] ?? true)
        />
        <label class="form-check-label" for="channel-official-{{ $index }}">Kênh chính thức</label>
      </div>
    </div>
    <div>
      <div class="form-check mb-0">
        <input type="hidden" name="channels[{{ $index }}][verified]" value="0" />
        <input
          type="checkbox"
          class="form-check-input"
          name="channels[{{ $index }}][verified]"
          id="channel-verified-{{ $index }}"
          value="1"
          @checked($row['verified'] ?? false)
        />
        <label class="form-check-label" for="channel-verified-{{ $index }}">Đánh dấu đã xác minh</label>
      </div>
    </div>
    <div class="ms-auto">
      <button type="button" class="btn btn-danger btn-sm btn-remove-row" title="Xóa kênh">
        <i data-feather="trash-2"></i>
      </button>
    </div>
  </div>
  <div class="row g-2 mt-1 channel-bank-fields" @if (! $isBank) style="display: none" @endif>
    <div class="col-md-6 col-12">
      <div class="form-group mb-0">
        <label for="channel-bank-{{ $index }}">Ngân hàng / Ví</label>
        <input
          type="text"
          name="channels[{{ $index }}][bank]"
          id="channel-bank-{{ $index }}"
          class="form-control"
          maxlength="100"
          placeholder="VD: Vietcombank"
          value="{{ $row['bank'] ?? '' }}"
        />
      </div>
    </div>
    <div class="col-md-6 col-12">
      <div class="form-group mb-0">
        <label for="channel-holder-{{ $index }}">Chủ tài khoản</label>
        <input
          type="text"
          name="channels[{{ $index }}][holder]"
          id="channel-holder-{{ $index }}"
          class="form-control"
          maxlength="255"
          value="{{ $row['holder'] ?? '' }}"
        />
      </div>
    </div>
  </div>
</div>
