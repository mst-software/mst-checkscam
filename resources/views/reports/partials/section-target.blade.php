{{-- Section 1: who is reported. Labels per type are swapped by CSS ([data-show-for]), placeholders by the script. --}}
@php
  $copy = [
    'target_name' => [
      'account' => ['label' => 'Tên chủ tài khoản', 'placeholder' => 'Ví dụ: NGUYEN VAN A'],
      'website' => ['label' => 'Tên website / shop', 'placeholder' => 'Tên hiển thị của website'],
      'back' => ['label' => 'Tên người bán', 'placeholder' => 'Tên Facebook / Zalo của người bán'],
    ],
    'target_id' => [
      'account' => ['label' => 'Số tài khoản, SĐT hoặc link', 'placeholder' => 'Ví dụ: 0912345678', 'hint' => 'Dãy số hoặc link đối tượng dùng để nhận tiền, liên lạc.'],
      'website' => ['label' => 'Link website', 'placeholder' => 'Ví dụ: https://ten-mien.com', 'hint' => 'Dán đường link trang web hoặc trang bán hàng lừa đảo.'],
      'back' => ['label' => 'SĐT, Zalo hoặc link Facebook người bán', 'placeholder' => 'Ví dụ: 0912345678 hoặc facebook.com/ten', 'hint' => 'Kênh người bán dùng để bán acc cho bạn.'],
    ],
    'target_bank' => [
      'account' => ['label' => 'Ngân hàng / ví điện tử'],
      'back' => ['label' => 'Ngân hàng đã chuyển tiền'],
    ],
    'target_socials' => [
      'account' => ['label' => 'Kênh liên hệ khác', 'placeholder' => 'Zalo, Facebook, TikTok của đối tượng'],
      'back' => ['label' => 'STK đã chuyển tiền', 'placeholder' => 'Số tài khoản bạn đã chuyển tiền mua acc'],
    ],
  ];

  $banks = ['Vietcombank', 'VietinBank', 'BIDV', 'Agribank', 'Techcombank', 'MB Bank', 'ACB', 'VPBank', 'TPBank', 'Sacombank', 'VIB', 'SHB', 'HDBank', 'OCB', 'MSB', 'SeABank', 'LPBank', 'Eximbank', 'Nam A Bank', 'Cake', 'Timo', 'MoMo', 'ZaloPay', 'ViettelPay', 'VNPay'];
@endphp

<section aria-labelledby="report-section-target" class="space-y-5">
  <h2 id="report-section-target" class="{{ $sectionTitle }}">
    <span class="{{ $sectionNumber }}" aria-hidden="true">1</span>
    <span>
      <span data-show-for="account website">Đối tượng lừa đảo</span>
      <span data-show-for="back">Người bán acc</span>
    </span>
  </h2>

  <div>
    <label for="target_id" class="{{ $label }}">
      @foreach ($copy['target_id'] as $for => $text)
        <span data-show-for="{{ $for }}">{{ $text['label'] }}</span>
      @endforeach
    </label>
    <input
      id="target_id"
      type="text"
      name="target_id"
      value="{{ old('target_id') }}"
      required
      maxlength="255"
      autocomplete="off"
      placeholder="{{ $copy['target_id'][$type]['placeholder'] }}"
      data-placeholders="{{ json_encode(array_map(fn ($text) => $text['placeholder'], $copy['target_id'])) }}"
      data-field="target_id"
      aria-describedby="hint-target_id error-target_id"
      @if ($errors->has('target_id')) aria-invalid="true" @endif
      class="{{ $control }} h-11"
    />
    <p id="hint-target_id" class="{{ $hint }}">
      @foreach ($copy['target_id'] as $for => $text)
        <span data-show-for="{{ $for }}">{{ $text['hint'] }}</span>
      @endforeach
    </p>
    @include('reports.partials.field-error', ['field' => 'target_id'])
  </div>

  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
      <label for="target_name" class="{{ $label }}">
        @foreach ($copy['target_name'] as $for => $text)
          <span data-show-for="{{ $for }}">{{ $text['label'] }}</span>
        @endforeach

        <span class="{{ $optional }}">(không bắt buộc)</span>
      </label>
      <input
        id="target_name"
        type="text"
        name="target_name"
        value="{{ old('target_name') }}"
        maxlength="255"
        autocomplete="off"
        placeholder="{{ $copy['target_name'][$type]['placeholder'] }}"
        data-placeholders="{{ json_encode(array_map(fn ($text) => $text['placeholder'], $copy['target_name'])) }}"
        data-field="target_name"
        aria-describedby="error-target_name"
        @if ($errors->has('target_name')) aria-invalid="true" @endif
        class="{{ $control }} h-11"
      />
      @include('reports.partials.field-error', ['field' => 'target_name'])
    </div>

    <div data-show-for="account back">
      <label for="target_bank" class="{{ $label }}">
        @foreach ($copy['target_bank'] as $for => $text)
          <span data-show-for="{{ $for }}">{{ $text['label'] }}</span>
        @endforeach

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
        placeholder="Chọn hoặc gõ tên ngân hàng"
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

  <div data-show-for="account back">
    <label for="target_socials" class="{{ $label }}">
      @foreach ($copy['target_socials'] as $for => $text)
        <span data-show-for="{{ $for }}">{{ $text['label'] }}</span>
      @endforeach

      <span class="{{ $optional }}">(không bắt buộc)</span>
    </label>
    <input
      id="target_socials"
      type="text"
      name="target_socials"
      value="{{ old('target_socials') }}"
      maxlength="255"
      autocomplete="off"
      placeholder="{{ ($copy['target_socials'][$type] ?? $copy['target_socials']['account'])['placeholder'] }}"
      data-placeholders="{{ json_encode(array_map(fn ($text) => $text['placeholder'], $copy['target_socials'])) }}"
      data-field="target_socials"
      aria-describedby="hint-target_socials error-target_socials"
      @if ($errors->has('target_socials')) aria-invalid="true" @endif
      class="{{ $control }} h-11"
    />
    <p id="hint-target_socials" class="{{ $hint }}">
      Nhiều kênh thì cách nhau bằng dấu phẩy. Công khai ở dạng đã che bớt.
    </p>
    @include('reports.partials.field-error', ['field' => 'target_socials'])
  </div>

  <div data-show-for="back">
    <label for="sold_account" class="{{ $label }}">
      Tài khoản đã mua
      <span class="{{ $optional }}">(không bắt buộc)</span>
    </label>
    <input
      id="sold_account"
      type="text"
      name="sold_account"
      value="{{ old('sold_account') }}"
      maxlength="255"
      autocomplete="off"
      placeholder="Ví dụ: Liên Quân - nick ThanhLong99, hoặc link Facebook"
      data-field="sold_account"
      aria-describedby="hint-sold_account error-sold_account"
      @if ($errors->has('sold_account')) aria-invalid="true" @endif
      class="{{ $control }} h-11"
    />
    <p id="hint-sold_account" class="{{ $hint }}">
      Game hoặc mạng xã hội + tên nick / link. Công khai ở dạng đã che bớt.
    </p>
    @include('reports.partials.field-error', ['field' => 'sold_account'])
  </div>
</section>
