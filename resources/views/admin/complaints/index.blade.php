@extends('admin.layouts.master')
@section('title', 'Danh sách Khiếu nại')
@section('content')
  @include(
    'admin.components.page-header',
    [
      'title' => 'Quản lý khiếu nại',
      'subtitle' => 'Phản hồi báo cáo sai và yêu cầu gỡ bài (' . $pendingCount . ' đang chờ xử lý)',
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
      {{-- Filter Section --}}
      <form method="GET" action="{{ route('admin.complaints.index') }}" class="mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-lg-3 col-md-6">
            <label class="form-label fw-bold small text-muted mb-2">Trạng thái khiếu nại</label>
            <select name="status" class="form-select">
              <option value="">Tất cả trạng thái</option>
              @foreach (\App\Enums\ComplaintStatus::cases() as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($status === $statusOption)>
                  {{ $statusOption->label() }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-lg-1 col-md-3">
            <a
              href="{{ route('admin.complaints.index') }}"
              class="btn btn-light d-flex align-items-center justify-content-center w-100 py-2"
              style="height: 40px; border: 1px solid #d1d5db; color: #4b5563"
              title="Xoá bộ lọc"
            >
              <i data-feather="rotate-ccw" style="width: 16px"></i>
            </a>
          </div>
          <div class="col-lg-1 col-md-3">
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
              <th>Người khiếu nại</th>
              <th>Lý do</th>
              <th>Báo cáo liên quan</th>
              <th>Trạng thái</th>
              <th>Ngày gửi</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($complaints as $complaint)
              <tr>
                <td>{{ $complaint->id }}</td>
                <td>
                  <strong>{{ $complaint->contact_name }}</strong>
                  <br />
                  <small class="text-muted">{{ $complaint->contact_email }}</small>
                </td>
                <td>{{ $complaint->reason->label() }}</td>
                <td>
                  @if ($complaint->report)
                    <a href="{{ route('admin.reports.detail', $complaint->report->id) }}">
                      #CS-{{ $complaint->report->id }} &middot; {{ $complaint->report->target_id }}
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @include('admin.complaints.partials.status-badge', ['status' => $complaint->status])
                </td>
                <td>{{ $complaint->created_at->format('d/m/Y H:i') }}</td>
                <td>
                  <a href="{{ route('admin.complaints.show', $complaint) }}">
                    <img src="/assets/img/icons/eye.svg" alt="xem" />
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-muted py-4 text-center">Không có khiếu nại nào.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="mt-3">
        {{ $complaints->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
@endsection
