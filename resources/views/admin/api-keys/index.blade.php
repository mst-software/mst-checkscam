@extends('admin.layouts.master')
@section('title', 'Quản lý API Key')
@section('content')
  @include(
    'admin.components.page-header',
    [
      'title' => 'Quản lý API Key',
      'subtitle' => 'Cấp và thu hồi khóa truy cập Public API (GET /api/v1/check)',
      'btnText' => 'Tạo API key',
      'btnUrl' => route('admin.api-keys.create'),
    ]
  )

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if (session('plain_api_key'))
    <div class="alert alert-warning" role="alert">
      <p class="fw-bold mb-2">API key mới (chỉ hiển thị một lần duy nhất):</p>
      <div class="d-flex align-items-center flex-wrap gap-2">
        <code id="plain-api-key" class="rounded bg-white p-2" style="word-break: break-all">
          {{ session('plain_api_key') }}
        </code>
        <button type="button" class="btn btn-sm btn-primary" id="btn-copy-api-key">Sao chép</button>
      </div>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table hover-table">
          <thead>
            <tr>
              <th>Tên</th>
              <th>Tiền tố key</th>
              <th>Giới hạn / phút</th>
              <th>Lần dùng gần nhất</th>
              <th>Người tạo</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($apiKeys as $apiKey)
              <tr>
                <td>{{ $apiKey->name }}</td>
                <td><code>{{ $apiKey->key_prefix }}…</code></td>
                <td>{{ $apiKey->rate_limit_per_minute }}</td>
                <td>
                  @if ($apiKey->last_used_at)
                    <small>{{ $apiKey->last_used_at->format('d/m/Y H:i') }}</small>
                  @else
                    <small class="text-muted">Chưa sử dụng</small>
                  @endif
                </td>
                <td>{{ $apiKey->user?->full_name ?? ($apiKey->user?->username ?? '-') }}</td>
                <td>
                  @include('admin.components.status-badge', ['status' => $apiKey->is_active ? 'active' : 'inactive'])
                </td>
                <td>
                  @if ($apiKey->is_active)
                    <form
                      method="POST"
                      action="{{ route('admin.api-keys.revoke', $apiKey) }}"
                      onsubmit="return confirm('Thu hồi API key này? Ứng dụng đang dùng key sẽ bị từ chối ngay.');"
                    >
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn btn-sm btn-danger">Thu hồi</button>
                    </form>
                  @else
                    <small class="text-muted">Đã thu hồi</small>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-muted py-4 text-center">Chưa có API key nào.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $apiKeys->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.getElementById('btn-copy-api-key')?.addEventListener('click', function () {
      const key = document.getElementById('plain-api-key').textContent.trim();
      navigator.clipboard?.writeText(key).then(() => (this.textContent = 'Đã sao chép'));
    });
  </script>
@endpush
