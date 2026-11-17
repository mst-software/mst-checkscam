@extends('admin.layouts.master')
@section('title', 'Chi tiết thành viên bảo hiểm')
@section('content')
  @include(
    'admin.components.page-header',
    [
      'title' => $insurance->full_name,
      'subtitle' => 'Hồ sơ, kênh chính thức, sổ quỹ ký quỹ và trạng thái của thành viên',
    ]
  )

  @php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' ₫';
    [$standingClass, $standingLabel] = match (true) {
      $insurance->isSuspended() => ['bg-lightred', 'Tạm đình chỉ'],
      ! $insurance->isActive() => ['bg-lightgrey', 'Tạm dừng'],
      $insurance->isExpired() => ['bg-lightred', 'Hết hạn'],
      default => ['bg-lightgreen', 'Đã xác thực'],
    };
    $staffName = fn ($user) => $user ? $user->full_name ?? $user->username : 'Hệ thống';
  @endphp

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <x-admin-error />

  <div class="row">
    <div class="col-lg-8">
      {{-- Tổng quan --}}
      <div class="card">
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
            <img
              src="{{ $insurance->avatar_url ?? '/assets/img/customer/customer1.jpg' }}"
              alt="{{ $insurance->full_name }}"
              class="rounded-circle border"
              style="width: 64px; height: 64px; object-fit: cover"
            />
            <div class="flex-grow-1">
              <div class="text-muted small">{{ $insurance->memberTitle() }}</div>
              <h4 class="mb-1">{{ $insurance->full_name }}</h4>
              <span class="badges {{ $standingClass }}">{{ $standingLabel }}</span>
              @if ($insurance->isSuspended())
                <div class="text-danger small mt-1">
                  Lý do: {{ $insurance->suspension_reason ?: 'Không ghi lý do' }} &middot; từ
                  {{ $insurance->suspended_at->format('d/m/Y H:i') }}
                </div>
              @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
              @can('update', $insurance)
                <a href="{{ route('admin.insurances.edit', $insurance) }}" class="btn btn-primary btn-sm">
                  <i data-feather="edit-2" style="width: 14px"></i>
                  Sửa hồ sơ
                </a>
              @endcan

              <a href="{{ route('admin.insurances.index') }}" class="btn btn-light btn-sm">Danh sách</a>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Số dư quỹ</label>
                <p class="mb-0"><strong>{{ $money($insurance->amount) }}</strong></p>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Ngày tham gia</label>
                <p class="mb-0"><strong>{{ $insurance->insurance_date?->format('d/m/Y') ?? '-' }}</strong></p>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Ngày hết hạn</label>
                <p class="mb-0"><strong>{{ $insurance->expired_at?->format('d/m/Y') ?? '-' }}</strong></p>
              </div>
            </div>
          </div>

          @if (! $isReconciled)
            <div class="alert alert-warning mb-0" role="alert">
              <strong>Lệch sổ quỹ:</strong>
              số dư đang lưu là {{ $money($insurance->amount) }} nhưng tổng sổ quỹ là {{ $money($ledgerBalance) }}.
              Hãy kiểm tra và ghi bút toán điều chỉnh nếu cần.
            </div>
          @else
            <p class="text-success small mb-0">
              <i data-feather="check-circle" style="width: 14px"></i>
              Số dư khớp với sổ quỹ ({{ $money($ledgerBalance) }}).
            </p>
          @endif
        </div>
      </div>

      {{-- Kênh chính thức --}}
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Kênh liên hệ & thanh toán ({{ $channels->count() }})</h5>
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Loại</th>
                  <th>Giá trị</th>
                  <th>Chính thức</th>
                  <th>Xác minh</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($channels as $channel)
                  <tr>
                    <td>{{ $channel->type->label() }}</td>
                    <td class="text-break">{{ $channel->value }}</td>
                    <td>
                      @if ($channel->is_official)
                        <span class="badges bg-lightgreen">Chính thức</span>
                      @else
                        <span class="badges bg-lightgrey">Không</span>
                      @endif
                    </td>
                    <td>{{ $channel->verified_at?->format('d/m/Y') ?? 'Chưa' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-muted text-center py-3">Chưa có kênh nào.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Sổ quỹ --}}
      <div class="card" id="ledger">
        <div class="card-body">
          <h5 class="card-title">Sổ quỹ</h5>
          <p class="text-muted small">
            Ghi lại các khoản quản trị viên đã xác nhận chuyển/nhận ngoài hệ thống. Bút toán không thể sửa hoặc xóa.
          </p>
          <div class="table-responsive">
            <table class="table hover-table">
              <thead>
                <tr>
                  <th>Ngày</th>
                  <th>Loại</th>
                  <th class="text-end">Số tiền</th>
                  <th class="text-end">Số dư sau</th>
                  <th>Mã giao dịch</th>
                  <th>Ghi chú</th>
                  <th>Người ghi</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($ledgerEntries as $entry)
                  <tr>
                    <td class="text-nowrap">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-nowrap">{{ $entry->type->label() }}</td>
                    <td class="text-end text-nowrap {{ (float) $entry->amount < 0 ? 'text-danger' : 'text-success' }}">
                      {{ (float) $entry->amount > 0 ? '+' : '' }}{{ $money($entry->amount) }}
                    </td>
                    <td class="text-end text-nowrap">{{ $money($entry->balance_after) }}</td>
                    <td>{{ $entry->reference ?: '-' }}</td>
                    <td style="min-width: 160px">{{ $entry->note ?: '-' }}</td>
                    <td>{{ $staffName($entry->recorder) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-muted text-center py-3">Chưa có bút toán nào.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $ledgerEntries->fragment('ledger')->links('pagination::bootstrap-5') }}
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      {{-- Trạng thái hoạt động --}}
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Trạng thái hoạt động</h5>
          @can('changeStanding', $insurance)
            @if ($insurance->isSuspended())
              <form
                action="{{ route('admin.insurances.reinstate', $insurance) }}"
                method="POST"
                class="js-once"
                data-confirm="Khôi phục hoạt động cho thành viên này?"
              >
                @csrf
                <div class="form-group mb-2">
                  <label for="reinstate_reason">Ghi chú (không bắt buộc)</label>
                  <input
                    type="text"
                    id="reinstate_reason"
                    name="reason"
                    class="form-control"
                    maxlength="255"
                    placeholder="VD: Đã xác minh tố cáo sai"
                  />
                </div>
                <button type="submit" class="btn btn-success w-100">
                  <i data-feather="play-circle" class="me-1" style="width: 16px"></i>
                  Khôi phục hoạt động
                </button>
              </form>
            @else
              <form
                action="{{ route('admin.insurances.suspend', $insurance) }}"
                method="POST"
                class="js-once"
                data-confirm="Tạm đình chỉ thành viên này? Thành viên sẽ không còn hiển thị là đã xác thực."
              >
                @csrf
                <div class="form-group mb-2">
                  <label for="suspend_reason">
                    Lý do tạm đình chỉ
                    <span class="text-danger">*</span>
                  </label>
                  <input
                    type="text"
                    id="suspend_reason"
                    name="reason"
                    class="form-control"
                    maxlength="255"
                    required
                    value="{{ old('reason') }}"
                    placeholder="VD: Có tố cáo đang xác minh"
                  />
                </div>
                <button type="submit" class="btn btn-danger w-100">
                  <i data-feather="pause-circle" class="me-1" style="width: 16px"></i>
                  Tạm đình chỉ
                </button>
              </form>
            @endif
          @else
            <p class="text-muted small mb-0">Chỉ quản trị viên mới được tạm đình chỉ hoặc khôi phục thành viên.</p>
          @endcan
          @unless ($hasHistory)
            <p class="text-muted small mt-3 mb-0">Thành viên chưa có lịch sử nên vẫn có thể xóa từ danh sách.</p>
          @else
            <p class="text-muted small mt-3 mb-0">
              Thành viên đã có sổ quỹ / lịch sử nên không thể xóa; hãy tạm đình chỉ khi cần.
            </p>
          @endunless
        </div>
      </div>

      {{-- Ghi bút toán --}}
      @can('manageFunds', $insurance)
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Ghi bút toán sổ quỹ</h5>
            @if ($errors->ledger->any())
              <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">
                  @foreach ($errors->ledger->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form
              action="{{ route('admin.insurances.ledger.store', $insurance) }}"
              method="POST"
              class="js-once"
              id="ledger-form"
              data-confirm="Bút toán không thể sửa hoặc xóa. Bạn đã kiểm tra kỹ số tiền và mã giao dịch?"
            >
              @csrf
              <input type="hidden" name="submission_token" value="{{ \Illuminate\Support\Str::uuid() }}" />
              <div class="form-group mb-2">
                <label for="ledger_type">Loại bút toán</label>
                <select name="type" id="ledger_type" class="form-select">
                  @foreach ($manualLedgerTypes as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>
                      {{ $type->label() }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Nạp quỹ, hoàn quỹ cho thành viên hoặc điều chỉnh số dư.</small>
              </div>
              <div class="form-group mb-2" id="ledger-direction" style="display: none">
                <label for="ledger_direction">Chiều điều chỉnh</label>
                <select name="direction" id="ledger_direction" class="form-select">
                  <option value="increase" @selected(old('direction') === 'increase')>Tăng quỹ (+)</option>
                  <option value="decrease" @selected(old('direction') === 'decrease')>Giảm quỹ (−)</option>
                </select>
              </div>
              <div class="form-group mb-2">
                <label for="ledger_amount">
                  Số tiền (VND)
                  <span class="text-danger">*</span>
                </label>
                <input
                  type="number"
                  name="amount"
                  id="ledger_amount"
                  class="form-control"
                  min="1"
                  step="1"
                  required
                  value="{{ old('amount') }}"
                />
              </div>
              <div class="form-group mb-2">
                <label for="ledger_reference">Mã giao dịch ngân hàng</label>
                <input
                  type="text"
                  name="reference"
                  id="ledger_reference"
                  class="form-control"
                  maxlength="255"
                  placeholder="VD: VCB FT26111600123"
                  value="{{ old('reference') }}"
                />
              </div>
              <div class="form-group mb-2">
                <label for="ledger_note">Ghi chú</label>
                <textarea name="note" id="ledger_note" rows="2" class="form-control" maxlength="1000">
{{ old('note') }}</textarea
                >
              </div>
              <div class="alert alert-warning py-2 small mb-2">
                <i data-feather="alert-triangle" style="width: 14px"></i>
                Bút toán không thể sửa hoặc xóa. Số dư hiện tại: {{ $money($insurance->amount) }}.
              </div>
              <button type="submit" class="btn btn-submit w-100">Ghi nhận bút toán</button>
            </form>
          </div>
        </div>
      @endcan

      {{-- Lịch sử trạng thái --}}
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Lịch sử trạng thái</h5>
          @forelse ($statusEvents as $event)
            <div class="border-start border-3 ps-3 mb-3">
              <div>
                <strong>{{ $event->type->label() }}</strong>
                <small class="text-muted">&middot; {{ $event->created_at?->format('d/m/Y H:i') }}</small>
              </div>
              @if ($event->reason)
                <div class="small">{{ $event->reason }}</div>
              @endif

              <small class="text-muted">{{ $staffName($event->actor) }}</small>
            </div>
          @empty
            <p class="text-muted small mb-0">Chưa có thay đổi trạng thái nào.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @include('admin.insurances.partials.once-forms-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const type = document.getElementById('ledger_type');
      const direction = document.getElementById('ledger-direction');
      if (!type || !direction) return;
      const sync = () => (direction.style.display = type.value === 'adjustment' ? '' : 'none');
      type.addEventListener('change', sync);
      sync();
    });
  </script>
@endpush
