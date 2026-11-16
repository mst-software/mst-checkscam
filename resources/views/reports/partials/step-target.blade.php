@php
  $typeOptions = [
    'account' => ['label' => 'Tài khoản / Số điện thoại', 'description' => 'Số tài khoản ngân hàng, ví điện tử hoặc SĐT', 'icon' => 'fa-solid fa-building-columns'],
    'website' => ['label' => 'Website', 'description' => 'Trang web, trang bán hàng hoặc link lừa đảo', 'icon' => 'fa-solid fa-globe'],
  ];

  $targetCopy = [
    'account' => [
      'id_label' => 'Số tài khoản hoặc số điện thoại',
      'id_placeholder' => 'Ví dụ: 0912345678',
      'id_hint' => 'Nhập đúng dãy số đối tượng dùng để nhận tiền hoặc liên lạc.',
      'name_label' => 'Tên chủ tài khoản',
      'name_placeholder' => 'Ví dụ: NGUYEN VAN A',
    ],
    'website' => [
      'id_label' => 'Link website',
      'id_placeholder' => 'Ví dụ: https://ten-mien.com',
      'id_hint' => 'Dán đường link trang web hoặc trang bán hàng lừa đảo.',
      'name_label' => 'Tên website',
      'name_placeholder' => 'Tên hiển thị của website',
    ],
  ];

  $banks = ['Vietcombank', 'VietinBank', 'BIDV', 'Agribank', 'Techcombank', 'MB Bank', 'ACB', 'VPBank', 'TPBank', 'Sacombank', 'VIB', 'MoMo', 'ZaloPay', 'ViettelPay'];
  $copy = $targetCopy[$type];
@endphp

<section data-step-panel="1" aria-labelledby="step-1-title">
  @include('reports.partials.step-heading', ['number' => 1])

  <div class="space-y-5">
    <fieldset>
      <legend class="{{ $label }}">Bạn muốn tố cáo</legend>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach ($typeOptions as $value => $option)
          <label
            class="border-line bg-surface rounded-card has-checked:border-accent has-checked:bg-accent-soft hover:border-line-strong has-focus-visible:outline-accent flex cursor-pointer items-start gap-3 border p-3 transition-colors has-focus-visible:outline-2 has-focus-visible:outline-offset-2"
          >
            <input
              type="radio"
              name="type"
              value="{{ $value }}"
              class="accent-accent mt-1 size-4 shrink-0"
              data-field="type"
              @checked($type === $value)
            />
            <span class="min-w-0">
              <span class="text-ink flex items-center gap-2 text-sm font-semibold">
                <i class="{{ $option['icon'] }} text-accent text-xs" aria-hidden="true"></i>
                {{ $option['label'] }}
              </span>
              <span class="text-ink-subtle mt-0.5 block text-xs">{{ $option['description'] }}</span>
            </span>
          </label>
        @endforeach
      </div>
      @include('reports.partials.field-error', ['field' => 'type'])
    </fieldset>

    <div>
      <label
        for="target_id"
        class="{{ $label }}"
        data-type-text
        data-account="{{ $targetCopy['account']['id_label'] }}"
        data-website="{{ $targetCopy['website']['id_label'] }}"
      >
        {{ $copy['id_label'] }}
      </label>
      <input
        id="target_id"
        type="text"
        name="target_id"
        value="{{ old('target_id') }}"
        required
        maxlength="255"
        autocomplete="off"
        placeholder="{{ $copy['id_placeholder'] }}"
        data-placeholder-account="{{ $targetCopy['account']['id_placeholder'] }}"
        data-placeholder-website="{{ $targetCopy['website']['id_placeholder'] }}"
        data-field="target_id"
        aria-describedby="hint-target_id error-target_id"
        @if ($errors->has('target_id')) aria-invalid="true" @endif
        class="{{ $control }} h-11"
      />
      <p
        id="hint-target_id"
        class="{{ $hint }}"
        data-type-text
        data-account="{{ $targetCopy['account']['id_hint'] }}"
        data-website="{{ $targetCopy['website']['id_hint'] }}"
      >
        {{ $copy['id_hint'] }}
      </p>
      @include('reports.partials.field-error', ['field' => 'target_id'])
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label for="target_name" class="{{ $label }}">
          <span
            data-type-text
            data-account="{{ $targetCopy['account']['name_label'] }}"
            data-website="{{ $targetCopy['website']['name_label'] }}"
          >
            {{ $copy['name_label'] }}
          </span>
          <span class="{{ $optional }}">(không bắt buộc)</span>
        </label>
        <input
          id="target_name"
          type="text"
          name="target_name"
          value="{{ old('target_name') }}"
          maxlength="255"
          autocomplete="off"
          placeholder="{{ $copy['name_placeholder'] }}"
          data-placeholder-account="{{ $targetCopy['account']['name_placeholder'] }}"
          data-placeholder-website="{{ $targetCopy['website']['name_placeholder'] }}"
          data-field="target_name"
          aria-describedby="error-target_name"
          @if ($errors->has('target_name')) aria-invalid="true" @endif
          class="{{ $control }} h-11"
        />
        @include('reports.partials.field-error', ['field' => 'target_name'])
      </div>

      <div data-account-only>
        <label for="target_bank" class="{{ $label }}">
          Ngân hàng / ví điện tử
          <span class="{{ $optional }}">(không bắt buộc)</span>
        </label>
        <input
          id="target_bank"
          type="text"
          name="target_bank"
          value="{{ old('target_bank') }}"
          maxlength="255"
          autocomplete="off"
          list="report-bank-list"
          placeholder="Ví dụ: Vietcombank, MoMo"
          data-field="target_bank"
          aria-describedby="error-target_bank"
          @if ($errors->has('target_bank')) aria-invalid="true" @endif
          class="{{ $control }} h-11"
        />
        <datalist id="report-bank-list">
          @foreach ($banks as $bank)
            <option value="{{ $bank }}"></option>
          @endforeach
        </datalist>
        @include('reports.partials.field-error', ['field' => 'target_bank'])
      </div>
    </div>
  </div>
</section>
