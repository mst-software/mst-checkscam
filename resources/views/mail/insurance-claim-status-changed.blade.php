<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cập nhật yêu cầu bồi thường #{{ $claimId }}</title>
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
      .footer {
        margin-top: 32px;
        font-size: 12px;
        color: #888;
        border-top: 1px solid #eee;
        padding-top: 16px;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <h2 style="margin: 0; color: #1e3a8a; font-size: 18px">
          Yêu cầu bồi thường #{{ $claimId }}: {{ $statusLabel }}
        </h2>
      </div>

      <p>Chào {{ $claimantName }},</p>
      <p>{{ $statusMessage }}</p>

      @if ($partnerName)
        <div style="margin-top: 16px">
          <div class="label">Đối tác bảo hiểm</div>
          <div class="value">{{ $partnerName }}</div>
        </div>
      @endif

      <div style="margin-top: 16px">
        <div class="label">Số tiền yêu cầu</div>
        <div class="value">{{ $amountClaimed }}</div>
      </div>

      @if ($approvedAmount)
        <div style="margin-top: 16px">
          <div class="label">Số tiền được duyệt</div>
          <div class="value" style="font-weight: bold">{{ $approvedAmount }}</div>
        </div>
      @endif

      <div class="footer">
        <p>Bạn nhận được email này vì đã gửi yêu cầu bồi thường trên Scamproof.</p>
        <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
      </div>
    </div>
  </body>
</html>
