@extends('admin.layouts.master')
@section('title', 'Yêu cầu bồi thường')
@section('content')
  @include(
    'admin.components.page-header',
    [
      'title' => 'Yêu cầu bồi thường',
      'subtitle' => 'Xử lý yêu cầu chi trả từ quỹ bảo hiểm (' . $openCount . ' đang chờ xử lý)',
    ]
  )

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      {{-- Status pills --}}
      <div class="d-flex flex-wrap gap-2 mb-3">
        <a
          href="{{ route('admin.insurance-claims.index', request()->except(['status', 'page'])) }}"
          class="btn btn-sm {{ $status === null ? 'btn-primary' : 'btn-light' }}"
        >
          Tất cả ({{ $statusCounts->sum() }})
        </a>
        @foreach (\App\Enums\ClaimStatus::cases() as $statusOption)
          <a
            href="{{ route('admin.insurance-claims.index', array_merge(request()->except(['status', 'page']), ['status' => $statusOption->value])) }}"
            class="btn btn-sm {{ $status === $statusOption ? 'btn-primary' : 'btn-light' }}"
          >
            {{ $statusOption->label() }} ({{ $statusCounts[$statusOption->value] ?? 0 }})
          </a>
        @endforeach
      </div>

      {{-- Filter Section --}}
      <form method="GET" action="{{ route('admin.insurance-claims.index') }}" class="mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-lg-3 col-md-6">
            <label for="filter_status" class="form-label fw-bold small text-muted mb-2">Trạng thái</label>
            <select name="status" id="filter_status" class="form-select">
              <option value="">Tất cả trạng thái</option>
              @foreach (\App\Enums\ClaimStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($status === $statusOption)>
                  {{ $statusOption->label() }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-3 col-md-6">
            <label for="filter_insurance" class="form-label fw-bold small text-muted mb-2">Thành viên bảo hiểm</label>
            <select name="insurance_id" id="filter_insurance" class="form-select">
              <option value="">Tất cả thành viên</option>
              @foreach ($partners as $partner)
                <option value="{{ $partner->id }}" @selected($insuranceId === $partner->id)>
                  {{ $partner->full_name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-2 col-md-6 col-6">
            <label for="filter_from" class="form-label fw-bold small text-muted mb-2">Từ ngày</label>
            <input
              type="date"
              name="from"
              id="filter_from"
              class="form-control"
              value="{{ $from?->format('Y-m-d') }}"
            />
          </div>
          <div class="col-lg-2 col-md-6 col-6">
            <label for="filter_to" class="form-label fw-bold small text-muted mb-2">Đến ngày</label>
            <input type="date" name="to" id="filter_to" class="form-control" value="{{ $to?->format('Y-m-d') }}" />
          </div>
          <div class="col-lg-1 col-md-3 col-6">
            <a
              href="{{ route('admin.insurance-claims.index') }}"
              class="btn btn-light d-flex align-items-center justify-content-center w-100 py-2"
              style="height: 40px; border: 1px solid #d1d5db; color: #4b5563"
              title="Xoá bộ lọc"
            >
              <i data-feather="rotate-ccw" style="width: 16px"></i>
            </a>
          </div>
          <div class="col-lg-1 col-md-3 col-6">
            <button
              type="submit"
              class="btn btn-primary d-flex align-items-center justify-content-center w-100 py-2"
              style="height: 40px; background: #ff9f43; border-color: #ff9f43"
              title="Lọc dữ liệu"
            >
              <i data-feather="filter" style="width: 16px"></i>
            </button>
          </div>
        </div>
      </form>

      {{-- Table --}}
      <div class="table-responsive">
        <table class="table hover-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Người yêu cầu</th>
              <th>Thành viên bảo hiểm</th>
              <th class="text-end">Số tiền yêu cầu</th>
              <th class="text-end">Số tiền duyệt</th>
              <th>Trạng thái</th>
              <th>Ngày gửi</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($claims as $claim)
              <tr>
                <td>{{ $claim->id }}</td>
                <td>
                  <strong>{{ $claim->claimant_name }}</strong>
                  <br />
                  <small class="text-muted">{{ $claim->contact_email }}</small>
                </td>
                <td>
                  @if ($claim->insurance)
                    <a href="{{ route('admin.insurances.show', $claim->insurance) }}">
                      {{ $claim->insurance->full_name }}
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td class="text-end text-nowrap">
                  {{ number_format((float) $claim->amount_claimed, 0, ',', '.') }} ₫
                </td>
                <td class="text-end text-nowrap">
                  {{ $claim->approved_amount !== null ? number_format((float) $claim->approved_amount, 0, ',', '.') . ' ₫' : '-' }}
                </td>
                <td>
                  @include('admin.insurance-claims.partials.status-badge', ['status' => $claim->status])
                </td>
                <td class="text-nowrap">{{ $claim->created_at->format('d/m/Y H:i') }}</td>
                <td>
                  <a href="{{ route('admin.insurance-claims.show', $claim) }}" title="Xem & xử lý">
                    <img src="/assets/img/icons/eye.svg" alt="xem" />
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-muted py-4 text-center">Không có yêu cầu bồi thường nào.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="mt-3">
        {{ $claims->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
@endsection
