@extends('admin.layouts.master')
@section('title', 'Tạo API Key')
@section('content')
  @include(
    'admin.components.page-header',
    [
      'title' => 'Tạo API key mới',
      'subtitle' => 'Key chỉ hiển thị một lần sau khi tạo, hệ thống chỉ lưu bản băm (SHA-256)',
    ]
  )

  @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.api-keys.store') }}">
    @csrf

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Thông tin API key</h5>
        <div class="row">
          <div class="col-lg-6 col-12">
            <div class="form-group">
              <label>
                Tên đối tác / ứng dụng
                <span class="text-danger">*</span>
              </label>
              <input
                type="text"
                name="name"
                class="form-control"
                placeholder="VD: Shop ABC - kiểm tra người bán"
                value="{{ old('name') }}"
                required
              />
            </div>
          </div>
          <div class="col-lg-6 col-12">
            <div class="form-group">
              <label>
                Giới hạn truy vấn mỗi phút
                <span class="text-danger">*</span>
              </label>
              <input
                type="number"
                name="rate_limit_per_minute"
                class="form-control"
                value="{{ old('rate_limit_per_minute', 30) }}"
                min="1"
                max="1000"
                required
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <button type="submit" class="btn btn-submit me-2">Tạo API key</button>
        <a href="{{ route('admin.api-keys.index') }}" class="btn btn-cancel">Hủy</a>
      </div>
    </div>
  </form>
@endsection
