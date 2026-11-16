@use('App\Enums\ClaimStatus')
@extends('admin.layouts.master')
@section('title', 'Chi tiết yêu cầu bồi thường')
@section('content')
  @include(
    'admin.components.page-header',
    [
      'title' => 'Yêu cầu bồi thường #' . $claim->id,
      'subtitle' => 'Xem bằng chứng và xử lý yêu cầu chi trả từ quỹ bảo hiểm',
    ]
  )

  @php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' ₫';
    $status = $claim->status;
    $staffName = fn ($user) => $user ? $user->full_name ?? $user->username : 'Hệ thống';
    $evidenceUrl = fn (string $path) => preg_match('~^https?://~i', $path) ? $path : asset('uploads/' . ltrim($path, '/'));
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
    {{-- Cột trái --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Nội dung yêu cầu</h5>
          <div class="row">
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Thành viên bảo hiểm</label>
                <p class="mb-0">
                  <strong>
                    @if ($claim->insurance)
                      <a href="{{ route('admin.insurances.show', $claim->insurance) }}">
                        {{ $claim->insurance->full_name }}
                      </a>
                    @else
                      -
                    @endif
                  </strong>
                </p>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Số tiền yêu cầu</label>
                <p class="mb-0"><strong>{{ $money($claim->amount_claimed) }}</strong></p>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Số tiền duyệt</label>
                <p class="mb-0">
                  <strong>{{ $claim->approved_amount !== null ? $money($claim->approved_amount) : '-' }}</strong>
                </p>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Ngày gửi</label>
                <p class="mb-0"><strong>{{ $claim->created_at->format('d/m/Y H:i') }}</strong></p>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="form-group">
                <label>Quỹ hiện tại của thành viên</label>
                <p class="mb-0"><strong>{{ $claim->insurance ? $money($claim->insurance->amount) : '-' }}</strong></p>
              </div>
            </div>
            @if ($claim->paid_at)
              <div class="col-md-4 col-6">
                <div class="form-group">
                  <label>Ngày chi trả</label>
                  <p class="mb-0"><strong>{{ $claim->paid_at->format('d/m/Y H:i') }}</strong></p>
                </div>
              </div>
            @endif
          </div>

          <div class="form-group mb-3">
            <label>Mô tả của người yêu cầu</label>
            <div class="bg-light rounded p-3" style="white-space: pre-line">{{ $claim->description }}</div>
          </div>

          {{-- Ảnh bằng chứng --}}
          <h5 class="card-title mt-4">Ảnh bằng chứng ({{ count($claim->evidence_paths ?? []) }})</h5>
          <div class="row">
            @forelse ($claim->evidence_paths ?? [] as $path)
              <div class="col-md-3 col-6 mb-3">
                <a
                  href="{{ $evidenceUrl($path) }}"
                  target="_blank"
                  rel="noopener noreferrer"
                  title="Mở ảnh trong tab mới"
                >
                  <img
                    src="{{ $evidenceUrl($path) }}"
                    alt="Bằng chứng {{ $loop->iteration }}"
                    class="img-fluid rounded border"
                    style="height: 150px; width: 100%; object-fit: cover"
                    loading="lazy"
                  />
                </a>
              </div>
            @empty
              <div class="col-12">
                <p class="text-muted">Không có ảnh bằng chứng.</p>
              </div>
            @endforelse
          </div>

          <h5 class="card-title mt-4">
            Thông tin người yêu cầu
            <small class="text-muted fw-normal">(chỉ quản trị viên xem được)</small>
          </h5>
          <div class="row">
            <div class="col-md-3 col-6">
              <div class="form-group">
                <label>Họ tên</label>
                <p class="mb-0"><strong>{{ $claim->claimant_name }}</strong></p>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="form-group">
                <label>Email</label>
                <p class="mb-0 text-break">
                  <strong><a href="mailto:{{ $claim->contact_email }}">{{ $claim->contact_email }}</a></strong>
                </p>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="form-group">
                <label>Số điện thoại</label>
                <p class="mb-0"><strong>{{ $claim->contact_phone ?: 'Không cung cấp' }}</strong></p>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="form-group">
                <label>Địa chỉ IP</label>
                <p class="mb-0"><strong>{{ $claim->ip_address ?: '-' }}</strong></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Tóm tắt công khai & ghi chú --}}
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Tóm tắt công khai & ghi chú quyết định</h5>
          <form action="{{ route('admin.insurance-claims.summary', $claim) }}" method="POST" class="js-once">
            @csrf
            <div class="form-group mb-2">
              <label for="public_summary">Tóm tắt công khai (đã ẩn danh, hiển thị trên trang công khai)</label>
              <textarea id="public_summary" name="public_summary" rows="3" class="form-control" maxlength="2000">
{{ old('public_summary', $claim->public_summary) }}</textarea
              >
            </div>
            <div class="form-group mb-2">
              <label for="decision_note">Ghi chú quyết định (nội bộ, không gửi cho người yêu cầu)</label>
              <textarea id="decision_note" name="decision_note" rows="3" class="form-control" maxlength="2000">
{{ old('decision_note', $claim->decision_note) }}</textarea
              >
            </div>
            <button type="submit" class="btn btn-primary">Lưu tóm tắt</button>
          </form>
        </div>
      </div>

      {{-- Timeline --}}
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Lịch sử xử lý</h5>
          @forelse ($claim->events as $event)
            <div class="border-start border-3 ps-3 mb-3">
              <div>
                @if ($event->from_status)
                  {{ $event->from_status->label() }} →
                @endif

                <strong>{{ $event->to_status->label() }}</strong>
                <small class="text-muted">&middot; {{ $event->created_at?->format('d/m/Y H:i') }}</small>
              </div>
              @if ($event->note)
                <div class="small" style="white-space: pre-line">{{ $event->note }}</div>
              @endif

              <small class="text-muted">{{ $event->actor ? $staffName($event->actor) : 'Người yêu cầu' }}</small>
            </div>
          @empty
            <p class="text-muted small mb-0">Chưa có lịch sử.</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Cột phải --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Trạng thái hiện tại</h5>
          <p>@include('admin.insurance-claims.partials.status-badge', ['status' => $status])</p>

          @if ($claim->decided_at)
            <div class="alert alert-secondary mb-3 p-2">
              <small>
                Quyết định bởi
                <strong>{{ $staffName($claim->decider) }}</strong>
                lúc {{ $claim->decided_at->format('d/m/Y H:i') }}
              </small>
            </div>
          @endif

          @if ($claim->payoutEntry)
            <div class="alert alert-success mb-3 p-2">
              <small>
                Đã ghi sổ quỹ: {{ $money($claim->payoutEntry->amount) }}
                @if ($claim->payoutEntry->reference)
                  &middot; mã GD {{ $claim->payoutEntry->reference }}
                @endif

                <br />
                Số dư sau chi trả: {{ $money($claim->payoutEntry->balance_after) }}
              </small>
            </div>
          @endif

          @if ($status->isFinal())
            <p class="text-muted small mb-0">Yêu cầu đã kết thúc, không còn thao tác nào.</p>
          @else
            <p class="text-muted small">
              Người yêu cầu nhận email khi trạng thái đổi (trừ "Đang xem xét"). Email không bao gồm ghi chú nội bộ.
            </p>
          @endif

          {{-- Bắt đầu / tiếp tục xem xét --}}
          @if ($status->canTransitionTo(ClaimStatus::Reviewing))
            <hr />
            <form action="{{ route('admin.insurance-claims.review', $claim) }}" method="POST" class="js-once">
              @csrf
              <div class="form-group mb-2">
                <label for="review_note">Ghi chú nội bộ (không bắt buộc)</label>
                <textarea id="review_note" name="note" rows="2" class="form-control" maxlength="2000"></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                {{ $status === ClaimStatus::NeedInfo ? 'Tiếp tục xem xét' : 'Bắt đầu xem xét' }}
              </button>
            </form>
          @endif

          {{-- Yêu cầu bổ sung thông tin --}}
          @if ($status->canTransitionTo(ClaimStatus::NeedInfo))
            <hr />
            <form action="{{ route('admin.insurance-claims.request-info', $claim) }}" method="POST" class="js-once">
              @csrf
              <div class="form-group mb-2">
                <label for="need_info_note">
                  Cần bổ sung gì (ghi chú nội bộ)
                  <span class="text-danger">*</span>
                </label>
                <textarea
                  id="need_info_note"
                  name="note"
                  rows="2"
                  class="form-control"
                  maxlength="2000"
                  required
                  placeholder="VD: Cần sao kê chuyển khoản"
                ></textarea>
                <small class="text-muted">Người yêu cầu chỉ nhận email báo cần bổ sung; hãy liên hệ trực tiếp.</small>
              </div>
              <button type="submit" class="btn btn-warning w-100">Yêu cầu bổ sung thông tin</button>
            </form>
          @endif

          {{-- Duyệt --}}
          @if ($status->canTransitionTo(ClaimStatus::Approved))
            <hr />
            @can('approve', $claim)
              <form
                action="{{ route('admin.insurance-claims.approve', $claim) }}"
                method="POST"
                class="js-once"
                data-confirm="Duyệt bồi thường với số tiền đã nhập? Người yêu cầu sẽ nhận email thông báo."
              >
                @csrf
                <div class="form-group mb-2">
                  <label for="approved_amount">
                    Số tiền duyệt (VND, tối đa {{ $money($claim->amount_claimed) }})
                    <span class="text-danger">*</span>
                  </label>
                  <input
                    type="number"
                    id="approved_amount"
                    name="approved_amount"
                    class="form-control"
                    min="1"
                    max="{{ (int) $claim->amount_claimed }}"
                    step="1"
                    required
                    value="{{ old('approved_amount', (int) $claim->amount_claimed) }}"
                  />
                </div>
                <div class="form-group mb-2">
                  <label for="approve_summary">Tóm tắt công khai (không bắt buộc)</label>
                  <textarea id="approve_summary" name="public_summary" rows="2" class="form-control" maxlength="2000">
{{ old('public_summary', $claim->public_summary) }}</textarea
                  >
                </div>
                <div class="form-group mb-2">
                  <label for="approve_note">Ghi chú quyết định (nội bộ)</label>
                  <textarea
                    id="approve_note"
                    name="decision_note"
                    rows="2"
                    class="form-control"
                    maxlength="2000"
                  ></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100">
                  <i data-feather="check" class="me-1" style="width: 16px"></i>
                  Duyệt bồi thường
                </button>
              </form>
            @else
              <p class="text-muted small mb-0">Chỉ quản trị viên mới được duyệt số tiền bồi thường.</p>
            @endcan
          @endif

          {{-- Ghi nhận chi trả --}}
          @if ($status->canTransitionTo(ClaimStatus::Paid))
            <hr />
            @can('markPaid', $claim)
              <form
                action="{{ route('admin.insurance-claims.mark-paid', $claim) }}"
                method="POST"
                class="js-once"
                data-confirm="Ghi nhận đã chi trả? Một bút toán chi trả sẽ được trừ vào quỹ của thành viên và không thể sửa hoặc xóa."
              >
                @csrf
                <div class="form-group mb-2">
                  <label for="paid_amount">
                    Số tiền đã chi trả (VND, tối đa {{ $money($claim->approved_amount) }})
                    <span class="text-danger">*</span>
                  </label>
                  <input
                    type="number"
                    id="paid_amount"
                    name="paid_amount"
                    class="form-control"
                    min="1"
                    max="{{ (int) $claim->approved_amount }}"
                    step="1"
                    required
                    value="{{ old('paid_amount', (int) $claim->approved_amount) }}"
                  />
                </div>
                <div class="form-group mb-2">
                  <label for="paid_reference">
                    Mã giao dịch ngân hàng
                    <span class="text-danger">*</span>
                  </label>
                  <input
                    type="text"
                    id="paid_reference"
                    name="reference"
                    class="form-control"
                    maxlength="255"
                    required
                    value="{{ old('reference') }}"
                  />
                </div>
                <div class="alert alert-warning py-2 small mb-2">Bút toán không thể sửa hoặc xóa.</div>
                <button type="submit" class="btn btn-success w-100">Ghi nhận đã chi trả</button>
              </form>
            @else
              <p class="text-muted small mb-0">Chỉ quản trị viên mới được ghi nhận chi trả.</p>
            @endcan
          @endif

          {{-- Từ chối --}}
          @if ($status->canTransitionTo(ClaimStatus::Rejected))
            <hr />
            <form
              action="{{ route('admin.insurance-claims.reject', $claim) }}"
              method="POST"
              class="js-once"
              data-confirm="Từ chối yêu cầu này? Người yêu cầu sẽ nhận email thông báo."
            >
              @csrf
              <div class="form-group mb-2">
                <label for="reject_reason">
                  Lý do từ chối (nội bộ)
                  <span class="text-danger">*</span>
                </label>
                <textarea
                  id="reject_reason"
                  name="reason"
                  rows="2"
                  class="form-control"
                  maxlength="2000"
                  required
                ></textarea>
              </div>
              <div class="form-group mb-2">
                <label for="reject_summary">Tóm tắt công khai (không bắt buộc)</label>
                <textarea id="reject_summary" name="public_summary" rows="2" class="form-control" maxlength="2000">
{{ $claim->public_summary }}</textarea
                >
              </div>
              <button type="submit" class="btn btn-cancel w-100">
                <i data-feather="x" class="me-1" style="width: 16px"></i>
                Từ chối yêu cầu
              </button>
            </form>
          @endif

          <hr />
          <a href="{{ route('admin.insurance-claims.index') }}" class="btn btn-light w-100">Quay lại danh sách</a>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @include('admin.insurances.partials.once-forms-script')
@endpush
