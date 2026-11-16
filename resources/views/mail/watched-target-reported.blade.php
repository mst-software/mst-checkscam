<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Có báo cáo mới về đối tượng bạn theo dõi</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        background: #f5f5f5;
        margin: 0;
        padding: 20px;
      }
      .container {
        max-width: 600px;
        margin: 0 auto;
        background: #fff;
        border-radius: 8px;
        padding: 32px;
      }
      .header {
        border-bottom: 2px solid #1e3a8a;
        padding-bottom: 16px;
        margin-bottom: 24px;
      }
      .label {
        font-size: 11px;
        font-weight: bold;
        color: #888;
        text-transform: uppercase;
      }
      .value {
        font-size: 14px;
        color: #333;
        margin-top: 4px;
      }
      .button {
        display: inline-block;
        background: #1e3a8a;
        color: #fff !important;
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 6px;
        font-weight: bold;
      }
      .footer {
        margin-top: 32px;
        font-size: 12px;
        color: #888;
        border-top: 1px solid #eee;
        padding-top: 16px;
      }
      a {
        color: #3399ff;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <h2 style="margin: 0; color: #1e3a8a; font-size: 18px">Có báo cáo lừa đảo mới về đối tượng bạn theo dõi</h2>
      </div>

      <p>
        Một báo cáo mới liên quan đến
        <strong>{{ $typeLabel }}</strong>
        bạn đang theo dõi vừa được kiểm duyệt và công khai trên hệ thống.
      </p>

      <div>
        <div class="label">{{ $typeLabel }}</div>
        <div class="value" style="font-weight: bold; letter-spacing: 1px">{{ $maskedTarget }}</div>
      </div>

      <div style="margin-top: 16px">
        <div class="label">Chủ tài khoản</div>
        <div class="value">{{ $maskedName }}</div>
      </div>

      <div style="margin-top: 16px">
        <div class="label">Hình thức</div>
        <div class="value">{{ $report->category ?? 'Không xác định' }}</div>
      </div>

      <p style="margin-top: 24px">
        <a href="{{ $reportUrl }}" class="button">Xem chi tiết báo cáo</a>
      </p>

      <div class="footer">
        <p>
          Bạn nhận được email này vì đã bật theo dõi trên CheckScam.
          <a href="{{ $manageUrl }}">Quản lý danh sách theo dõi</a>
        </p>
        <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
      </div>
    </div>
  </body>
</html>
