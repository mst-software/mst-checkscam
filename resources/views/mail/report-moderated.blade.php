<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kết quả kiểm duyệt tố cáo</title>
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
        <h2 style="margin: 0; color: #1e3a8a; font-size: 18px">
          {{ $isApproved ? 'Tố cáo của bạn đã được duyệt' : 'Tố cáo của bạn chưa được duyệt' }}
        </h2>
      </div>

      <p>
        @if ($isApproved)
          Cảm ơn bạn đã gửi tố cáo. Đội kiểm duyệt đã xác minh và công khai cảnh báo để cộng đồng cùng phòng tránh.
        @else
            Cảm ơn bạn đã gửi tố cáo. Sau khi xem xét, đội kiểm duyệt chưa thể công khai tố cáo này.
        @endif
      </p>

      <div>
        <div class="label">Đối tượng</div>
        <div class="value" style="font-weight: bold; letter-spacing: 1px">{{ $maskedTarget }}</div>
      </div>

      @if (! $isApproved && $rejectionReason)
        <div style="margin-top: 16px">
          <div class="label">Lý do</div>
          <div class="value">{{ $rejectionReason }}</div>
        </div>
      @endif

      <p style="margin-top: 24px">
        @if ($isApproved)
          <a href="{{ $reportUrl }}" class="button">Xem cảnh báo</a>
        @else
          Bạn có thể bổ sung bằng chứng (bill chuyển khoản, nội dung trò chuyện) và
          <a href="{{ $formUrl }}">gửi lại tố cáo</a>
          .
        @endif
      </p>

      <div class="footer">
        <p>Bạn nhận được email này vì đã gửi tố cáo trên Scamproof.</p>
        <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
      </div>
    </div>
  </body>
</html>
