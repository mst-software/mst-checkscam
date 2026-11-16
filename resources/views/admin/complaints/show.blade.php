@extends('admin.layouts.master')
@section('title', 'Chi tiết Khiếu nại')
@section('content')
  @include(
    'admin.components.page-header',
    [
      'title' => 'Chi tiết khiếu nại #' . $complaint->id,
      'subtitle' => 'Xem và xử lý khiếu nại / phản hồi báo cáo sai',
    ]
  )

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
          <h5 class="card-title">Nội dung khiếu nại</h5>
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-group">
                <label>Lý do</label>
                <p class="mb-0"><strong>{{ $complaint->reason->label() }}</strong></p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Ngày gửi</label>
                <p class="mb-0"><strong>{{ $complaint->created_at->format('d/m/Y H:i') }}</strong></p>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-12">
              <div class="form-group">
                <label>Báo cáo liên quan</label>
                <p class="mb-0">
                  @if ($complaint->report)
                    <a href="{{ route('admin.reports.detail', $complaint->report->id) }}">
                      <strong>#CS-{{ $complaint->report->id }}</strong>
                      &middot; {{ $complaint->report->target_id }}
                      @if ($complaint->report->target_name)
                        ({{ $complaint->report->target_name }})
                      @endif
                    </a>
                  @else
                    <strong class="text-muted">Không gắn với báo cáo cụ thể</strong>
                  @endif
                </p>
              </div>
            </div>
          </div>
          <div class="form-group mb-3">
            <label>Nội dung</label>
            <div class="bg-light rounded p-3" style="white-space: pre-line">{{ $complaint->message }}</div>
          </div>

          {{-- Ảnh bằng chứng --}}
          <h5 class="card-title mt-4">Ảnh bằng chứng ({{ count($complaint->evidence_images ?? []) }})</h5>
          <div class="row">
            @forelse ($complaint->evidence_images ?? [] as $image)
              <div class="col-md-3 mb-3">
                <a href="{{ asset('uploads/' . $image) }}" target="_blank" rel="noopener">
                  <img
                    src="{{ asset('uploads/' . $image) }}"
                    alt="Bằng chứng"
                    class="img-fluid rounded border"
                    style="height: 150px; width: 100%; object-fit: cover"
                  />
                </a>
              </div>
            @empty
              <div class="col-12">
                <p class="text-muted">Không có ảnh bằng chứng.</p>
              </div>
            @endforelse
          </div>

          <h5 class="card-title mt-4">Thông tin người khiếu nại</h5>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Họ tên</label>
                <p class="mb-0"><strong>{{ $complaint->contact_name }}</strong></p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Email</label>
                <p class="mb-0">
                  <strong>
                    <a href="mailto:{{ $complaint->contact_email }}">{{ $complaint->contact_email }}</a>
                  </strong>
                </p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Số điện thoại</label>
                <p class="mb-0"><strong>{{ $complaint->contact_phone ?: 'Không cung cấp' }}</strong></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Cột phải --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Trạng thái hiện tại</h5>
          <p>
            @include('admin.complaints.partials.status-badge', ['status' => $complaint->status])
          </p>

          @if (! $complaint->isPending())
            <div class="alert alert-secondary mb-3 p-2">
              <small>
                Xử lý bởi
                <strong>{{ $complaint->handler?->full_name ?? ($complaint->handler?->username ?? 'N/A') }}</strong>
                lúc {{ $complaint->handled_at?->format('d/m/Y H:i') }}
              </small>
              @if ($complaint->admin_note)
                <hr class="my-2" />
                <small>
                  <strong>Ghi chú:</strong>
                  {{ $complaint->admin_note }}
                </small>
              @endif
            </div>
          @else
            <hr />
            <h5 class="card-title">Xử lý khiếu nại</h5>

            <form action="{{ route('admin.complaints.resolve', $complaint) }}" method="POST" class="mb-4">
              @csrf
              <div class="form-group mb-2">
                <label for="resolve_note">Ghi chú xử lý (không bắt buộc)</label>
                <textarea
                  id="resolve_note"
                  name="admin_note"
                  rows="3"
                  class="form-control"
                  placeholder="VD: Đã gỡ báo cáo / đã sửa thông tin..."
                ></textarea>
              </div>
              <button type="submit" class="btn btn-success w-100 py-2">
                <i data-feather="check" class="me-1"></i>
                Đánh dấu đã xử lý
              </button>
            </form>

            <form action="{{ route('admin.complaints.dismiss', $complaint) }}" method="POST">
              @csrf
              <div class="form-group mb-2">
                <label for="dismiss_note">
                  Lý do bác bỏ
                  <span class="text-danger">*</span>
                </label>
                <textarea
                  id="dismiss_note"
                  name="admin_note"
                  rows="3"
                  class="form-control"
                  required
                  placeholder="VD: Bằng chứng không xác thực..."
                ></textarea>
              </div>
              <button type="submit" class="btn btn-cancel w-100 py-2">
                <i data-feather="x" class="me-1"></i>
                Bác bỏ khiếu nại
              </button>
            </form>
          @endif

          <hr />
          <a href="{{ route('admin.complaints.index') }}" class="btn btn-light w-100">Quay lại danh sách</a>
        </div>
      </div>
    </div>
  </div>
@endsection
