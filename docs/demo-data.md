# Dữ liệu demo

Bộ dữ liệu mẫu để demo toàn bộ site (trang chủ, Khu mua bán, cảnh báo, trang tố cáo, Bảo hiểm CS, bài viết, trang quản trị) với ảnh chụp thật. **Chỉ dùng trên database dev/demo, không chạy trên production.**

## Chạy

```bash
php artisan migrate
php artisan storage:link        # ảnh Khu mua bán nằm ở storage/app/public/posts
php artisan db:seed --class=DemoDataSeeder
```

- Mất khoảng vài phút vì tải khoảng 190 ảnh từ picsum.photos (mỗi ảnh tải một lần, nghỉ ngắn giữa các lần tải). Lệnh in số dòng đã tạo, số ảnh đã tải và thời gian chạy.
- Không có mạng: ảnh không tải được sẽ được vẽ tại chỗ (nền gradient kèm nhãn), lệnh vẫn chạy xong và ghi cảnh báo vào log.
- Chạy lại khi đã có dữ liệu demo thì lệnh bỏ qua, không nhân đôi dữ liệu. Muốn seed lại: `php artisan demo:clear` rồi chạy lại.
- `DatabaseSeeder` không gọi seeder này.
- Nếu database chưa có admin, seeder tạo `admin@demo.scamproof.test` / `demo123456` (bị xoá cùng dữ liệu demo). Admin đã có thì không bị thay đổi.

## Nội dung

| Phần | Số lượng |
|------|----------|
| Người dùng demo (Google) | 8 người bán, 6 người mua, 1 tài khoản "Ban biên tập Scamproof" ký duyệt bài |
| Khu mua bán | 36 tin trong 7 danh mục, 1 đến 4 ảnh mỗi tin, trải đều 30 ngày; 3 tin bị ẩn do đủ 10 lượt báo cáo, 4 tin có vài lượt báo cáo |
| Tố cáo | 90 bài (STK scam, Web scam, Acc bị back), đủ các hình thức lừa đảo, 76 đã duyệt, 9 chờ duyệt, 5 bị từ chối; 14 đối tượng bị tố cáo nhiều lần; thiệt hại từ 200.000đ đến 99.999.999.999đ; 1 đến 4 ảnh bằng chứng |
| Bình luận | 40 (có tên và ẩn danh) |
| Khiếu nại | 3 đang chờ xử lý |
| Theo dõi đối tượng | 5 |
| Lượt tìm kiếm | khoảng 130 trong 7 ngày (8 đối tượng cho mục "Tìm kiếm nhiều trong tuần", tên GDV, tìm không thấy) |
| Bài viết | 8, có ảnh bìa |
| Bảo hiểm CS (GDV) | 8: 4 đang hoạt động, 1 sắp hết hạn, 1 tạm đình chỉ, 2 đã hết hạn; đủ kênh liên hệ và bút toán ký quỹ |

Toàn bộ tên người, tên shop, tên miền là **hư cấu**. Số điện thoại, số tài khoản ngân hàng, mã giao dịch được **sinh ngẫu nhiên**, không thuộc về ai; nếu trùng với số thật thì chỉ là ngẫu nhiên.

## Xoá

```bash
php artisan demo:clear
```

Lệnh chỉ xoá dòng demo và ảnh của chúng, dựa vào các dấu hiệu:

- người dùng có email `@demo.scamproof.test`;
- tố cáo, bình luận, khiếu nại, lượt tìm kiếm có IP `192.0.2.x` (dải TEST-NET, không bao giờ là IP thật);
- tin Khu mua bán và bài viết thuộc người dùng demo;
- GDV có bút toán ký quỹ do người dùng demo ghi.

Dữ liệu khác không bị đụng tới. Lưu ý: số GDV đã cấp cho GDV demo không được dùng lại (đúng quy tắc của hệ thống), nên GDV thật tạo sau đó sẽ có số lớn hơn.
