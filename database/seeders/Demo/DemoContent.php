<?php

namespace Database\Seeders\Demo;

use App\Enums\ScamType;

/**
 * Fictional Vietnamese demo content. Names are made up; phone and bank numbers are generated at random by the seeder.
 */
class DemoContent
{
    /** @return list<array{username: string, name: string, verified: bool}> */
    public static function sellers(): array
    {
        return [
            ['username' => 'shopacc.haidang', 'name' => 'Shop Acc Hải Đăng', 'verified' => true],
            ['username' => 'tung.mmo', 'name' => 'Tùng MMO', 'verified' => true],
            ['username' => 'linh.viastore', 'name' => 'Linh Via Store', 'verified' => false],
            ['username' => 'khoa.toolauto', 'name' => 'Khoa Tool Auto', 'verified' => false],
            ['username' => 'phuong.napgame', 'name' => 'Phương Nạp Game', 'verified' => false],
            ['username' => 'duc.accff', 'name' => 'Đức Acc Free Fire', 'verified' => false],
            ['username' => 'hamy.vidientu', 'name' => 'Hà My Ví Điện Tử', 'verified' => false],
            ['username' => 'quang.social', 'name' => 'Quang Dịch Vụ Social', 'verified' => true],
        ];
    }

    /** @return list<array{username: string, name: string}> */
    public static function buyers(): array
    {
        return [
            ['username' => 'minhtri.ng', 'name' => 'Nguyễn Minh Trí'],
            ['username' => 'ngocanh.tran', 'name' => 'Trần Ngọc Ánh'],
            ['username' => 'baolong.le', 'name' => 'Lê Bảo Long'],
            ['username' => 'thutrang.pham', 'name' => 'Phạm Thu Trang'],
            ['username' => 'giahuy.vo', 'name' => 'Võ Gia Huy'],
            ['username' => 'thanhtam.do', 'name' => 'Đỗ Thanh Tâm'],
        ];
    }

    /**
     * Marketplace listings. {zalo} is replaced by the seller's generated number.
     * `reports`: how many demo buyers/sellers reported it; 10+ hides it like the real threshold.
     *
     * @return list<array{seller: int, category: string, price: ?int, photos: int, content: string, reports?: int}>
     */
    public static function listings(): array
    {
        $game = 'Tài khoản game';
        $social = 'Tài khoản mạng xã hội';
        $tool = 'Tool / phần mềm MMO';
        $service = 'Dịch vụ MMO';
        $via = 'Via / tài khoản clone';
        $wallet = 'Ví điện tử / thanh toán';
        $other = 'Khác';

        return [
            ['seller' => 0, 'category' => $game, 'price' => 2_800_000, 'photos' => 3, 'content' => "Bán acc Liên Quân 112 tướng, 230 skin, có 6 skin SS hữu hạn (Tulen Thần Tượng Học Đường, Nakroth Siêu Việt...). Rank Cao Thủ mùa trước.\nAcc chính chủ đăng ký bằng SĐT, đổi được hết thông tin. Bao back 30 ngày, ưu tiên giao dịch qua GDV có ký quỹ.\nZalo: {zalo}"],
            ['seller' => 5, 'category' => $game, 'price' => 1_500_000, 'photos' => 2, 'content' => "Acc Free Fire tạo từ 2019, 18 súng nâng cấp, full set Sakura, còn 1.250 kim cương.\nLiên kết Google, đổi email gốc ngay khi giao. Fix nhẹ cho anh em thiện chí. Zalo {zalo}"],
            ['seller' => 0, 'category' => $game, 'price' => 4_200_000, 'photos' => 4, 'content' => "Acc Genshin Impact AR58 server Asia, 9 nhân vật 5 sao (Raiden C2, Nahida, Furina...), 3 vũ khí 5 sao.\nMail gốc chưa đổi lần nào, giao full thông tin, hỗ trợ đổi mail tại chỗ qua video call."],
            ['seller' => 5, 'category' => $game, 'price' => 950_000, 'photos' => 2, 'content' => "Thanh lý nick PUBG Mobile level 72, 3 khẩu M416 nâng cấp, 2 bộ Mythic.\nĐổi được liên kết Twitter + mail. Không bán cho khách đòi đưa thông tin trước khi chuyển khoản."],
            ['seller' => 4, 'category' => $game, 'price' => 1_800_000, 'photos' => 1, 'content' => "Acc Liên Minh Huyền Thoại server VN, 160 tướng, 210 trang phục, rank Bạch Kim IV, có 3 skin hàng hiệu.\nBán gấp cần tiền đóng học, liên hệ Zalo {zalo}."],
            ['seller' => 5, 'category' => $game, 'price' => 3_500_000, 'photos' => 3, 'content' => "Bán acc Valorant Kim Cương 2: Vandal Reaver, Phantom Oni, dao Karambit.\nRiot ID chính chủ, đổi mail trực tiếp khi giao. Giao dịch qua trung gian, phí trung gian mình chịu."],
            ['seller' => 0, 'category' => $game, 'price' => 700_000, 'photos' => 2, 'content' => 'Acc Roblox 2015, đã tiêu khoảng 12.000 Robux, nhiều đồ limited. Bé nhà mình không chơi nữa nên để lại cho bạn nào cần.'],
            ['seller' => 5, 'category' => $game, 'price' => 99_000, 'photos' => 2, 'reports' => 12, 'content' => "SALE SỐC!!! Acc Liên Quân full tướng full skin chỉ 99K. Số lượng có hạn, chuyển khoản trước admin gửi tài khoản ngay trong 1 phút.\nKhông trung gian, không cọc, ai nghi ngờ thì đừng mua."],
            ['seller' => 7, 'category' => $social, 'price' => 6_500_000, 'photos' => 3, 'content' => "Bán kênh TikTok 52K follow, ngách review đồ gia dụng, view trung bình 10-30K/clip, đã bật TikTok Shop.\nCó mail gốc + SĐT chính chủ để đổi. Gửi insight 30 ngày gần nhất khi inbox."],
            ['seller' => 7, 'category' => $social, 'price' => 9_000_000, 'photos' => 2, 'content' => "Fanpage 120K like chủ đề nấu ăn, tương tác tự nhiên, tạo năm 2018, chưa dính hạn chế quảng cáo.\nChuyển quyền quản trị qua trung gian, bàn giao xong mới nhận tiền."],
            ['seller' => 1, 'category' => $social, 'price' => 22_000_000, 'photos' => 2, 'content' => "Kênh YouTube 18K sub đã bật kiếm tiền, nội dung hoạt hình thiếu nhi, doanh thu tháng gần nhất khoảng 180 USD.\nBàn giao Gmail + 2FA + kênh thanh toán AdSense mới."],
            ['seller' => 7, 'category' => $social, 'price' => 4_000_000, 'photos' => 1, 'content' => 'Instagram 35K follow ngách thời trang nữ, phần lớn follow thật từ Việt Nam, sẵn 400 bài đăng. Liên hệ Zalo {zalo} để xem insight.'],
            ['seller' => 2, 'category' => $social, 'price' => 1_200_000, 'photos' => 1, 'reports' => 3, 'content' => 'Nick Facebook cổ 2012, 4.800 bạn bè, đủ 2FA, ảnh đại diện thật. Dùng chạy quảng cáo ổn định, bao login lần đầu.'],
            ['seller' => 3, 'category' => $tool, 'price' => 1_200_000, 'photos' => 3, 'content' => "Tool nuôi nick Facebook tự động v3.2: tự lướt bảng tin, like, bình luận, kết bạn theo lịch.\nChạy tối đa 200 profile cùng lúc, hỗ trợ proxy. Bản quyền 1 máy, hỗ trợ cài đặt qua UltraViewer."],
            ['seller' => 3, 'category' => $tool, 'price' => 800_000, 'photos' => 2, 'content' => 'Phần mềm đăng bài hàng loạt lên group Facebook: trộn nội dung, hẹn giờ, chống spam. Dùng thử miễn phí 3 ngày trước khi mua.'],
            ['seller' => 1, 'category' => $tool, 'price' => 500_000, 'photos' => 2, 'content' => 'Tool tạo Gmail số lượng lớn, tự giải captcha, xuất file theo định dạng mail|pass|recovery. Bản quyền theo tháng, có video hướng dẫn.'],
            ['seller' => 3, 'category' => $tool, 'price' => 50_000, 'photos' => 2, 'reports' => 11, 'content' => "Tool hack kim cương Free Fire bản mới nhất, chỉ cần đăng nhập tài khoản game qua link mình gửi là nhận ngay 10.000 KC.\nĐã test 100% thành công, nhắn Zalo {zalo} để lấy link."],
            ['seller' => 1, 'category' => $tool, 'price' => 2_400_000, 'photos' => 2, 'content' => 'Phần mềm quản lý nhiều shop Shopee: đồng bộ tồn kho, trả lời chat tự động, xuất đơn ra Excel. Bản quyền 1 năm, cập nhật miễn phí.'],
            ['seller' => 7, 'category' => $service, 'price' => 150_000, 'photos' => 2, 'content' => 'Nhận tăng follow TikTok, Instagram người Việt thật, lên đều không tụt. Giá 150K/1.000 follow, bảo hành 30 ngày.'],
            ['seller' => 1, 'category' => $service, 'price' => 3_000_000, 'photos' => 2, 'content' => 'Dịch vụ chạy quảng cáo Facebook cho shop online: dựng tệp khách, tối ưu chi phí, báo cáo hằng tuần. Không ra đơn hoàn phí dịch vụ.'],
            ['seller' => 4, 'category' => $service, 'price' => 450_000, 'photos' => 1, 'content' => 'Nhận cày thuê rank Liên Quân từ Kim Cương lên Cao Thủ trong 3-5 ngày, chơi tay không dùng tool, quay màn hình gửi lại khi xong.'],
            ['seller' => 6, 'category' => $service, 'price' => null, 'photos' => 2, 'reports' => 2, 'content' => 'Nhận nạp tiền Alipay, WeChat tỷ giá tốt, xử lý 5-15 phút, có bill đầy đủ. Hỗ trợ đặt hàng Taobao, 1688. Zalo {zalo}'],
            ['seller' => 2, 'category' => $service, 'price' => 200_000, 'photos' => 3, 'content' => 'Thiết kế ảnh bìa, avatar, banner quảng cáo cho shop online. Giao file trong 24 giờ, sửa đến khi ưng thì thôi.'],
            ['seller' => 7, 'category' => $service, 'price' => 120_000, 'photos' => 1, 'content' => 'Dịch vụ tăng mắt livestream TikTok, mắt ổn định, có bình luận theo kịch bản. Tính phí theo giờ live.'],
            ['seller' => 2, 'category' => $via, 'price' => 350_000, 'photos' => 2, 'content' => 'Via Việt cổ 2015-2018, đã xác minh danh tính, có 2FA, bạn bè thật. Bảo hành đăng nhập lần đầu, đổi nếu checkpoint trong 24 giờ.'],
            ['seller' => 2, 'category' => $via, 'price' => 900_000, 'photos' => 1, 'reports' => 1, 'content' => 'BM2500 đã kháng, tài khoản quảng cáo limit 50$, phù hợp anh em chạy ads số lượng lớn.'],
            ['seller' => 1, 'category' => $via, 'price' => 15_000, 'photos' => 1, 'content' => 'Clone Facebook ngoại tạo bằng Gmail, ngâm 3 tháng, đã có avatar. Dùng nuôi page, seeding. Mua từ 50 nick có giá sỉ.'],
            ['seller' => 2, 'category' => $via, 'price' => 25_000, 'photos' => 2, 'content' => 'Gmail cổ 2016-2019 đã xác minh số điện thoại, dùng đăng ký kênh YouTube, Google Ads. Giao qua file txt.'],
            ['seller' => 6, 'category' => $wallet, 'price' => null, 'photos' => 1, 'reports' => 4, 'content' => 'Nhận đổi USDT sang VND theo giá thị trường, giao dịch nhanh qua GDV có ký quỹ. Chỉ nhận khách đã xác minh danh tính.'],
            ['seller' => 6, 'category' => $wallet, 'price' => 1_600_000, 'photos' => 2, 'content' => 'Bán tài khoản PayPal đã xác minh thẻ, rút tiền về ngân hàng được, kèm hướng dẫn bảo mật sau khi nhận.'],
            ['seller' => 6, 'category' => $wallet, 'price' => 100_000, 'photos' => 2, 'content' => 'Nạp thẻ Garena, Zing, Vcoin chiết khấu 8%, mã thẻ gửi qua Zalo trong 5 phút. Có bill nạp các đơn gần nhất.'],
            ['seller' => 6, 'category' => $wallet, 'price' => null, 'photos' => 1, 'reports' => 10, 'content' => 'Nhận nạp hộ ví điện tử, X2 số dư trong 10 phút, uy tín 100%. Inbox để nhận link nạp, chuyển trước nhận sau.'],
            ['seller' => 4, 'category' => $other, 'price' => 1_100_000, 'photos' => 3, 'content' => 'Pass lại tai nghe gaming HyperX Cloud II, dùng 6 tháng, còn hộp và bảo hành hãng đến tháng 3. Giao hàng toàn quốc, kiểm tra trước khi nhận.'],
            ['seller' => 3, 'category' => $other, 'price' => 7_500_000, 'photos' => 4, 'content' => 'Thanh lý PC chạy tool: i5-10400, RAM 32GB, SSD 512GB, chạy mượt 20 giả lập cùng lúc. Xem máy trực tiếp tại Cầu Giấy, Hà Nội.'],
            ['seller' => 0, 'category' => $other, 'price' => 850_000, 'photos' => 2, 'content' => 'Bán bàn phím cơ Akko 3087 switch Jelly Pink, mới 95%. Ship COD toàn quốc, được kiểm tra hàng.'],
            ['seller' => 5, 'category' => $other, 'price' => 4_300_000, 'photos' => 3, 'content' => 'Pass điện thoại Samsung A54 chuyên chơi game, máy zin, pin 89%, tặng kèm ốp và cường lực. Có thể gặp trực tiếp ở TP.HCM.'],
        ];
    }

    /** @return list<string> */
    public static function postReportReasons(): array
    {
        return [
            'Có dấu hiệu lừa đảo, yêu cầu chuyển khoản trước',
            'Giá rẻ bất thường so với thị trường',
            'Đã giao dịch và bị chặn liên lạc',
            'Gửi link đăng nhập giả mạo',
            'Nội dung spam, đăng lặp lại nhiều lần',
        ];
    }

    /** @return list<string> */
    public static function familyNames(): array
    {
        return ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
    }

    /** @return list<string> */
    public static function middleNames(): array
    {
        return ['Văn', 'Thị', 'Minh', 'Hoàng', 'Thanh', 'Quốc', 'Ngọc', 'Gia', 'Đức', 'Thu', 'Hữu', 'Bảo'];
    }

    /** @return list<string> */
    public static function givenNames(): array
    {
        return ['Tuấn', 'Hùng', 'Lan', 'Hương', 'Dũng', 'Trang', 'Phong', 'Nhung', 'Khánh', 'Vy', 'Sơn', 'Thảo', 'Cường', 'Yến', 'Hiếu', 'Linh', 'Tài', 'Quyên', 'Nam', 'Hạnh', 'Lộc', 'Duyên', 'Toàn', 'My'];
    }

    /** @return list<string> */
    public static function banks(): array
    {
        return ['Vietcombank', 'Techcombank', 'MB Bank', 'VPBank', 'ACB', 'BIDV', 'VietinBank', 'TPBank', 'Sacombank', 'Agribank', 'VIB', 'MoMo'];
    }

    /**
     * Scam websites (fictional domains) per scam type.
     *
     * @return array<string, list<string>>
     */
    public static function websites(): array
    {
        return [
            ScamType::FakeShop->value => ['https://giaysneaker-sale88.shop', 'https://dienmay-thanhly247.online', 'https://mypham-chinhhang-xa.store'],
            ScamType::Investment->value => ['https://vangso-profit.top', 'https://sancoin-vn68.net', 'https://quydautu-ansinh.vip'],
            ScamType::Impersonation->value => ['https://dichvucong-hotro.online', 'https://nganhang-capnhat-sinhtrac.com'],
            ScamType::LotteryGift->value => ['https://tri-an-khach-hang-2026.site', 'https://quatang-shoppe-vip.xyz'],
            ScamType::JobOffer->value => ['https://tuyendung-ctv-tiktok.work'],
        ];
    }

    /** @return list<array{game: string, nick: string}> */
    public static function soldAccounts(): array
    {
        return [
            ['game' => 'Liên Quân Mobile', 'nick' => 'ThanhLongVIP99'],
            ['game' => 'Free Fire', 'nick' => 'FF.SatThu2k5'],
            ['game' => 'PUBG Mobile', 'nick' => 'Sniper.HaNoi'],
            ['game' => 'Genshin Impact', 'nick' => 'UID 812345678'],
            ['game' => 'Liên Minh Huyền Thoại', 'nick' => 'BongDem#VN2'],
            ['game' => 'Valorant', 'nick' => 'KhongNgu#0207'],
            ['game' => 'Roblox', 'nick' => 'BlockKing_2014'],
            ['game' => 'Play Together', 'nick' => 'meocon.pt'],
            ['game' => 'Tài khoản TikTok', 'nick' => 'tiktok.com/@reviewdogiadung'],
            ['game' => 'Fanpage Facebook', 'nick' => 'facebook.com/bepnhaminh.official'],
        ];
    }

    /**
     * Story templates: {amount} {bank} {platform} {item} {site} {game} {nick} {date} are filled in by the seeder.
     *
     * @return array<string, list<string>>
     */
    public static function stories(): array
    {
        return [
            ScamType::FakeShop->value => [
                'Mình thấy bài đăng bán {item} giá rẻ hơn ngoài cửa hàng gần một nửa trên {platform}. Người bán nhắn tin rất nhiệt tình, gửi cả video quay hàng thật. Ngày {date} mình chuyển khoản {amount} qua {bank} để đặt cọc, sau đó họ đòi thêm phí ship và "phí bảo hiểm hàng hoá". Khi mình hỏi mã vận đơn thì bị chặn Zalo và Facebook. Mọi người cẩn thận với số tài khoản này.',
                'Mình đặt mua {item} của shop này trên {platform}, shop yêu cầu chuyển khoản trước 100% vì "hàng order". Mình chuyển {amount} vào tài khoản {bank} ngày {date}. Đợi 2 tuần không thấy hàng, gọi điện thì thuê bao, trang bán hàng cũng đổi tên. Hỏi trong nhóm thì có thêm vài bạn bị giống mình.',
                'Shop bán {item} chạy quảng cáo rầm rộ trên {platform}, ảnh feedback toàn khách thật. Mình đặt 1 đơn {amount}, chuyển khoản qua {bank}. Hàng nhận về là hộp rỗng chèn giấy báo, nhắn tin thì shop bảo "lỗi kho" rồi chặn luôn.',
            ],
            ScamType::Investment->value => [
                'Mình được kéo vào một nhóm Telegram "đầu tư vàng số" hứa lợi nhuận 3%/ngày. Lần đầu nạp nhỏ thì rút được thật nên mình tin, sau đó nạp tổng cộng {amount} qua {bank}. Đến khi rút thì hệ thống báo phải đóng thêm 20% "thuế thu nhập", đóng xong lại báo "tài khoản bị đóng băng". Admin nhóm đã xoá hết tin nhắn.',
                'Người này tự giới thiệu là chuyên gia tài chính trên {platform}, hướng dẫn mình mở tài khoản trên một sàn coin lạ. Lợi nhuận hiển thị tăng đều mỗi ngày nên mình nạp dần đến {amount}. Ngày {date} sàn sập, không đăng nhập được, người hướng dẫn cũng mất liên lạc.',
                'Mình tham gia "quỹ đầu tư an sinh" theo lời giới thiệu của một người quen trên {platform}, cam kết lãi 15%/tháng, có hợp đồng đóng dấu. Mình chuyển {amount} vào tài khoản {bank} của người đại diện. Sau 2 tháng không nhận được lãi, văn phòng ghi trên hợp đồng cũng không tồn tại.',
            ],
            ScamType::Romance->value => [
                'Mình quen người này qua {platform}, nói chuyện gần 3 tháng, người đó nói đang làm kỹ sư ở nước ngoài và muốn về Việt Nam cưới mình. Họ bảo gửi quà về nhưng bị hải quan giữ, cần đóng phí thông quan. Mình đã chuyển tổng cộng {amount} qua {bank} trước khi nhận ra bị lừa.',
                'Quen qua app hẹn hò, người này rất chu đáo, gọi video thì luôn "camera hỏng". Sau một thời gian thì kể bị tai nạn, cần tiền viện phí gấp. Ngày {date} mình chuyển {amount} vào tài khoản {bank}, sau đó bị chặn ở mọi nơi.',
            ],
            ScamType::JobOffer->value => [
                'Mình nhận được tin nhắn tuyển cộng tác viên làm nhiệm vụ thả tim video {platform}, mỗi nhiệm vụ 30-50 nghìn. Mấy nhiệm vụ đầu được trả tiền thật. Sau đó họ chuyển mình sang "nhiệm vụ VIP" phải nạp tiền trước, mình nạp tổng {amount} qua {bank} nhưng không rút được vì "sai thao tác".',
                'Thấy tin tuyển nhân viên nhập liệu tại nhà trên {platform}, lương 8-12 triệu. Họ yêu cầu đóng {amount} tiền "đồng phục và tài liệu" vào tài khoản {bank}. Đóng xong thì bị kick khỏi nhóm Zalo.',
            ],
            ScamType::Impersonation->value => [
                'Ngày {date} mình nhận cuộc gọi tự xưng là cán bộ công an quận, nói mình liên quan đến một đường dây rửa tiền và đọc đúng số CCCD của mình. Họ yêu cầu chuyển tiền vào "tài khoản tạm giữ" để chứng minh trong sạch. Vì quá sợ mình đã chuyển {amount} qua {bank}. Công an thật không bao giờ làm việc qua điện thoại như vậy!',
                'Người này giả nhân viên ngân hàng gọi hỗ trợ "nâng hạn mức thẻ tín dụng", gửi link qua {platform} để mình cập nhật sinh trắc học. Sau khi nhập thông tin và mã OTP, tài khoản {bank} của mình bị rút {amount} chỉ trong vài phút.',
                'Có người gọi tự xưng nhân viên điện lực, báo nhà mình nợ tiền điện và sắp bị cắt, yêu cầu cài ứng dụng "EVN" qua link gửi trên {platform}. Cài xong điện thoại bị chiếm quyền, tài khoản {bank} mất {amount}.',
            ],
            ScamType::Loan->value => [
                'Mình cần vay gấp nên đăng ký vay online qua quảng cáo trên {platform}. Nhân viên báo hồ sơ đã duyệt nhưng tài khoản bị "sai số" nên bị đóng băng, phải chuyển {amount} để mở khoá giải ngân. Chuyển xong qua {bank} thì họ lại đòi thêm phí bảo hiểm khoản vay.',
                'Người này nhận "hỗ trợ vay vốn không cần thế chấp", yêu cầu đóng trước {amount} phí hồ sơ vào tài khoản {bank}. Sau khi đóng tiền thì không còn liên lạc được, trang {platform} của họ đã xoá.',
            ],
            ScamType::LotteryGift->value => [
                'Mình nhận tin nhắn trúng thưởng xe máy trong chương trình tri ân khách hàng, có cả giấy chứng nhận đóng dấu. Để nhận giải phải đóng thuế và phí vận chuyển {amount} vào tài khoản {bank}. Đóng xong thì họ báo phải đóng thêm phí công chứng.',
                'Trên {platform} có trang tổ chức minigame tặng iPhone, mình được báo trúng giải nhất. Họ yêu cầu chuyển {amount} "phí xác minh người nhận" qua {bank}. Sau đó trang đổi tên và chặn mình.',
            ],
            ScamType::AccountTakeover->value => [
                'Facebook của bạn thân nhắn mượn tiền gấp, gọi video thì chỉ thấy mặt chập chờn vài giây. Mình chuyển {amount} vào tài khoản {bank} theo lời nhắn, hoá ra Facebook của bạn đã bị chiếm từ hôm trước.',
                'Người này nhắn tin qua {platform} nhờ mình bình chọn cho cuộc thi, gửi mã OTP về điện thoại mình rồi xin lại mã. Mình gửi mã xong thì mất luôn tài khoản Zalo, sau đó họ dùng Zalo của mình vay tiền người thân, tổng thiệt hại khoảng {amount}.',
            ],
            ScamType::Other->value => [
                'Người này nhận làm dịch vụ lấy lại tài khoản Facebook bị khoá, yêu cầu chuyển trước {amount} qua {bank}. Sau đó liên tục đòi thêm phí "mở khoá server", cuối cùng chặn liên lạc.',
                'Mình thuê người này thiết kế website bán hàng, đã chuyển cọc {amount} qua {bank} ngày {date}. Đến hạn bàn giao thì không có sản phẩm, gọi điện không nghe.',
            ],
            'website' => [
                'Trang {site} chạy quảng cáo trên {platform}, giao diện y như trang thật. Mình đặt mua và thanh toán {amount} qua chuyển khoản {bank} theo hướng dẫn trên web. Sau đó web báo đơn bị huỷ, yêu cầu chuyển thêm để "kích hoạt hoàn tiền". Mọi người đừng nhập thông tin vào trang này.',
                'Mình được người lạ trên {platform} giới thiệu vào trang {site}. Trang hiển thị số dư tăng mỗi ngày nhưng khi rút thì báo lỗi và đòi nạp thêm phí. Tổng mình đã nạp {amount}.',
            ],
            'back' => [
                'Ngày {date} mình mua acc {game} ({nick}) của người này giá {amount}, chuyển khoản qua {bank}. Người bán giao tài khoản, mình đổi mật khẩu và email đầy đủ. Được khoảng 1 tuần thì acc bị lấy lại bằng thông tin gốc, nhắn tin hỏi thì người bán chặn luôn.',
                'Mình mua {game} ({nick}) qua bài đăng trên {platform}, giá {amount}. Người bán hứa bao back 30 ngày. Chưa đầy 10 ngày acc bị đổi mật khẩu, kiểm tra thì người bán đã dùng giấy tờ gốc để khôi phục rồi bán lại cho người khác.',
                'Người này bán cho mình {game} ({nick}) với giá {amount}, nói là acc chính chủ. Hôm sau acc bị khoá do "bị báo cáo đăng nhập trái phép", người bán lấy lại acc rồi đòi thêm tiền mới trả.',
            ],
        ];
    }

    /** @return array<string, list<string>> */
    public static function items(): array
    {
        return [
            'items' => ['iPhone 15 Pro Max', 'nồi chiên không dầu', 'giày sneaker', 'đồng hồ thông minh', 'máy lọc không khí', 'bộ mỹ phẩm Hàn Quốc', 'xe đạp điện', 'laptop gaming', 'túi xách hàng hiệu', 'máy hút bụi robot'],
            'platforms' => ['Facebook', 'Zalo', 'TikTok', 'Telegram', 'Instagram', 'Shopee Live'],
        ];
    }

    /** @return list<array{name: ?string, content: string}> */
    public static function comments(): array
    {
        return [
            ['name' => 'Minh Tuấn', 'content' => 'Mình cũng bị người này lừa tháng trước, cùng một kịch bản y hệt. Đã báo công an phường.'],
            ['name' => null, 'content' => 'Cảm ơn bạn đã chia sẻ, suýt nữa mình chuyển khoản cho số này.'],
            ['name' => 'Thu Hà', 'content' => 'Số tài khoản này còn đang chạy quảng cáo trên Facebook, mọi người report giúp với.'],
            ['name' => 'Hoàng Nam', 'content' => 'Mình vừa tra thì ra 3 bài tố cáo, may quá chưa giao dịch.'],
            ['name' => null, 'content' => 'Bạn nên làm đơn trình báo kèm sao kê ngân hàng, mình làm vậy và được hỗ trợ phong toả tài khoản.'],
            ['name' => 'Ngọc Lan', 'content' => 'Người này đổi tên Facebook liên tục, nhưng số điện thoại thì vẫn vậy.'],
            ['name' => 'Quốc Bảo', 'content' => 'Hôm qua nó còn nhắn tin cho mình chào bán y chang, đã chặn.'],
            ['name' => null, 'content' => 'Mọi người nhớ giao dịch qua trung gian có ký quỹ nhé, đừng chuyển trước.'],
            ['name' => 'Phương Thảo', 'content' => 'Mất 2 triệu với đối tượng này hồi đầu năm, giờ mới biết có trang tra cứu.'],
            ['name' => 'Đức Anh', 'content' => 'Chia sẻ để nhiều người biết hơn, thời buổi này lừa đảo tinh vi quá.'],
            ['name' => null, 'content' => 'Ngân hàng có hỗ trợ tra soát nếu báo sớm, bạn thử gọi tổng đài xem.'],
            ['name' => 'Gia Bảo', 'content' => 'Trang web của nó giống trang thật đến 90%, chỉ khác tên miền. Cẩn thận!'],
            ['name' => 'Mai Anh', 'content' => 'Mình là người bị lừa thứ 2 trong bài này, đã bổ sung bằng chứng ở bài tố cáo khác.'],
            ['name' => 'Trọng Hiếu', 'content' => 'Acc back kiểu này nhiều lắm, mua acc nhớ đổi hết thông tin liên kết ngay.'],
        ];
    }

    /**
     * Blog posts (HTML content, like the admin editor produces).
     *
     * @return list<array{title: string, description: string, hashtags: string, featured: bool, content: string}>
     */
    public static function blogPosts(): array
    {
        return [
            [
                'title' => '5 dấu hiệu nhận biết shop online lừa đảo trước khi chuyển khoản',
                'description' => 'Giá rẻ bất thường, chỉ nhận chuyển khoản trước, trang mới lập... Đây là những dấu hiệu bạn nên kiểm tra trước khi đặt hàng online.',
                'hashtags' => 'mua-sam-online, canh-bao, shop-gia',
                'featured' => true,
                'content' => '<h2>Mua hàng online an toàn bắt đầu từ việc kiểm tra người bán</h2><p>Phần lớn các vụ lừa đảo bán hàng online đều có chung một kịch bản: giá rẻ hơn thị trường, người bán rất nhiệt tình và luôn yêu cầu chuyển khoản trước.</p><ol><li><strong>Giá rẻ bất thường:</strong> sản phẩm chính hãng hiếm khi rẻ hơn 30-50% so với cửa hàng.</li><li><strong>Chỉ nhận chuyển khoản trước:</strong> từ chối COD hoặc trung gian là dấu hiệu đáng ngờ.</li><li><strong>Trang mới lập, đổi tên nhiều lần:</strong> xem mục "Minh bạch trang" trên Facebook.</li><li><strong>Feedback toàn ảnh chụp màn hình:</strong> rất dễ làm giả.</li><li><strong>Hối thúc:</strong> "chỉ còn 2 suất", "giá này hết hôm nay".</li></ol><p>Trước khi chuyển tiền, hãy tra cứu số tài khoản và số điện thoại của người bán trên Scamproof.</p>',
            ],
            [
                'title' => 'Cuộc gọi giả danh công an: kịch bản và cách xử lý',
                'description' => 'Đối tượng đọc đúng số CCCD, dọa liên quan đường dây rửa tiền và yêu cầu chuyển tiền vào "tài khoản tạm giữ". Đây là cách nhận biết và xử lý.',
                'hashtags' => 'gia-danh, cong-an, cuoc-goi-lua-dao',
                'featured' => true,
                'content' => '<h2>Công an không làm việc qua điện thoại</h2><p>Cơ quan công an chỉ mời làm việc bằng giấy triệu tập gửi trực tiếp, không bao giờ yêu cầu chuyển tiền vào tài khoản cá nhân hay "tài khoản tạm giữ".</p><h3>Kịch bản thường gặp</h3><ul><li>Gọi từ số lạ hoặc số có đầu +84 giả mạo tổng đài.</li><li>Đọc đúng họ tên, số CCCD để tạo lòng tin.</li><li>Đe doạ bắt giam, yêu cầu giữ bí mật với người thân.</li><li>Gửi link cài ứng dụng "Bộ Công an" giả.</li></ul><h3>Nên làm gì?</h3><p>Cúp máy, không cài ứng dụng lạ, gọi ngay cho người thân và đến trụ sở công an gần nhất để xác minh.</p>',
            ],
            [
                'title' => 'Mua acc game thế nào để không bị "back acc"?',
                'description' => 'Back acc là chiêu bán tài khoản rồi lấy lại bằng thông tin gốc. Checklist những việc phải làm ngay sau khi mua acc game.',
                'hashtags' => 'back-acc, game, tai-khoan',
                'featured' => false,
                'content' => '<h2>Back acc là gì?</h2><p>Người bán giao tài khoản cho bạn, nhưng vẫn giữ email gốc, số điện thoại hoặc giấy tờ đăng ký. Vài ngày sau họ khôi phục tài khoản và bán lại cho người khác.</p><h3>Checklist sau khi nhận acc</h3><ul><li>Đổi mật khẩu, email và số điện thoại liên kết ngay khi nhận.</li><li>Gỡ toàn bộ liên kết Facebook, Google, Apple cũ.</li><li>Yêu cầu người bán cung cấp thông tin đăng ký gốc.</li><li>Giao dịch qua giao dịch viên có ký quỹ để được bảo vệ.</li></ul><p>Nếu bị back acc, hãy gửi tố cáo kèm bằng chứng chuyển khoản và tin nhắn để cảnh báo cộng đồng.</p>',
            ],
            [
                'title' => 'Việc nhẹ lương cao trên TikTok: bẫy "nhiệm vụ VIP"',
                'description' => 'Thả tim video được trả tiền thật ở vài nhiệm vụ đầu, sau đó bị dụ nạp tiền làm nhiệm vụ VIP. Cách nhận biết trước khi mất tiền.',
                'hashtags' => 'viec-lam, ctv, tiktok',
                'featured' => false,
                'content' => '<h2>Mồi nhử bằng tiền thật</h2><p>Đối tượng trả cho bạn vài chục nghìn đồng ở các nhiệm vụ đầu để tạo lòng tin. Khi bạn đã tin, chúng chuyển sang "nhiệm vụ VIP" yêu cầu nạp tiền trước với hoa hồng 20-30%.</p><p>Khi số tiền nạp đủ lớn, hệ thống báo "sai thao tác", "cần nạp thêm để mở khoá". Không có công việc hợp pháp nào bắt người lao động đóng tiền trước.</p><p><strong>Lời khuyên:</strong> rời nhóm ngay khi được yêu cầu nạp tiền, và tra cứu số tài khoản nhận tiền trên Scamproof.</p>',
            ],
            [
                'title' => 'Bị lừa chuyển khoản: 4 việc cần làm trong 24 giờ đầu',
                'description' => 'Càng báo sớm, cơ hội phong toả tiền càng cao. Hướng dẫn từng bước sau khi phát hiện mình bị lừa.',
                'hashtags' => 'huong-dan, ngan-hang, trinh-bao',
                'featured' => false,
                'content' => '<h2>Bình tĩnh và hành động nhanh</h2><ol><li><strong>Gọi tổng đài ngân hàng</strong> của bạn để yêu cầu tra soát giao dịch.</li><li><strong>Lưu lại bằng chứng:</strong> ảnh chụp tin nhắn, sao kê, số tài khoản nhận.</li><li><strong>Trình báo công an</strong> nơi cư trú, mang theo bằng chứng.</li><li><strong>Gửi tố cáo lên Scamproof</strong> để cảnh báo những người khác.</li></ol><p>Tuyệt đối không tin các "dịch vụ lấy lại tiền bị lừa" vì đây thường là lừa đảo lần hai.</p>',
            ],
            [
                'title' => 'Tin nhắn trúng thưởng: vì sao bạn không bao giờ phải đóng phí nhận giải',
                'description' => 'Chương trình khuyến mại hợp pháp không thu phí nhận giải qua chuyển khoản cá nhân. Nhận diện tin nhắn trúng thưởng giả.',
                'hashtags' => 'trung-thuong, sms, canh-bao',
                'featured' => false,
                'content' => '<h2>Đóng phí để nhận quà là dấu hiệu lừa đảo</h2><p>Các doanh nghiệp tổ chức khuyến mại phải đăng ký với cơ quan quản lý và không thu tiền của người trúng giải qua tài khoản cá nhân.</p><ul><li>Kiểm tra tên miền: trang thật dùng tên miền chính thức của doanh nghiệp.</li><li>Gọi tổng đài chính thức in trên website hoặc sản phẩm.</li><li>Không cung cấp CCCD, mã OTP cho người "trao giải".</li></ul>',
            ],
            [
                'title' => 'Giao dịch viên ký quỹ là gì và vì sao nên dùng?',
                'description' => 'Giao dịch viên (GDV) có tiền ký quỹ với Scamproof giúp hai bên mua bán online an toàn hơn. Tìm hiểu cách hoạt động.',
                'hashtags' => 'gdv, ky-quy, trung-gian',
                'featured' => false,
                'content' => '<h2>Trung gian có trách nhiệm</h2><p>Giao dịch viên giữ tiền của người mua cho đến khi người mua nhận đủ hàng hoặc tài khoản. Mỗi GDV có một khoản ký quỹ công khai trên Scamproof.</p><p>Trước khi giao dịch, hãy kiểm tra số GDV, trạng thái xác thực và đúng kênh liên hệ chính thức trên trang Bảo hiểm CS. Kẻ gian thường giả danh GDV bằng Facebook hoặc Zalo gần giống.</p>',
            ],
            [
                'title' => 'Giả mạo ứng dụng ngân hàng để cập nhật sinh trắc học',
                'description' => 'Thủ đoạn gửi link cài ứng dụng giả để "cập nhật sinh trắc học" đang gia tăng. Cách bảo vệ tài khoản ngân hàng của bạn.',
                'hashtags' => 'ngan-hang, sinh-trac-hoc, ma-doc',
                'featured' => false,
                'content' => '<h2>Ngân hàng không gửi link cài ứng dụng qua tin nhắn</h2><p>Việc cập nhật sinh trắc học chỉ thực hiện trong ứng dụng chính thức tải từ App Store hoặc Google Play, hoặc tại quầy giao dịch.</p><p>Ứng dụng giả có thể đọc tin nhắn OTP và điều khiển điện thoại từ xa. Nếu lỡ cài, hãy ngắt mạng, gọi ngay cho ngân hàng để khoá tài khoản và mang máy đến nơi bảo hành.</p>',
            ],
        ];
    }

    /**
     * Insurance partners; `state`: active, expiring, expired or suspended.
     *
     * @return list<array{name: string, state: string, deposit: int, topups: list<int>, services: list<string>}>
     */
    public static function insurances(): array
    {
        return [
            ['name' => 'Trần Minh Khôi', 'state' => 'active', 'deposit' => 200_000_000, 'topups' => [50_000_000, 30_000_000], 'services' => ['Trung gian mua bán tài khoản game', 'Trung gian giao dịch MMO 24/7']],
            ['name' => 'Lê Thị Bảo Ngọc', 'state' => 'active', 'deposit' => 150_000_000, 'topups' => [20_000_000], 'services' => ['Trung gian mua bán fanpage, kênh TikTok', 'Hỗ trợ kiểm tra tài khoản trước khi giao']],
            ['name' => 'Phạm Quốc Việt', 'state' => 'active', 'deposit' => 100_000_000, 'topups' => [], 'services' => ['Đổi USDT, nạp ví điện tử', 'Trung gian giao dịch tiền số']],
            ['name' => 'Nguyễn Hoàng Long', 'state' => 'active', 'deposit' => 80_000_000, 'topups' => [10_000_000, 10_000_000], 'services' => ['Trung gian mua bán acc Liên Quân, Free Fire']],
            ['name' => 'Đỗ Thanh Hương', 'state' => 'expiring', 'deposit' => 50_000_000, 'topups' => [], 'services' => ['Trung gian mua bán đồ công nghệ cũ', 'Kiểm tra máy trước khi giao']],
            ['name' => 'Vũ Đức Anh', 'state' => 'suspended', 'deposit' => 60_000_000, 'topups' => [], 'services' => ['Trung gian mua bán via, BM quảng cáo']],
            ['name' => 'Hồ Gia Bảo', 'state' => 'expired', 'deposit' => 40_000_000, 'topups' => [], 'services' => ['Trung gian giao dịch tài khoản mạng xã hội']],
            ['name' => 'Bùi Thảo Nhi', 'state' => 'expired', 'deposit' => 30_000_000, 'topups' => [], 'services' => ['Nạp thẻ game, thẻ điện thoại chiết khấu']],
        ];
    }
}
