<?php

namespace App\Http\Controllers;

use App\Helpers\SeoHelper;
use Illuminate\Contracts\View\View;

/**
 * Static public pages; each one sets its own title and description through SEOTools.
 */
class PageController extends Controller
{
    public function reportForm(): View
    {
        SeoHelper::setPage(
            'Tố cáo lừa đảo',
            'Gửi tố cáo tài khoản ngân hàng, số điện thoại, Facebook hoặc website lừa đảo kèm bằng chứng để cảnh báo cộng đồng.',
        );

        return view('reports.index');
    }

    public function apiDocs(): View
    {
        SeoHelper::setPage(
            'Public API kiểm tra lừa đảo',
            'Tài liệu Public API tra cứu số tài khoản, số điện thoại và Facebook trong dữ liệu cảnh báo lừa đảo đã kiểm duyệt.',
        );

        return view('system.api');
    }

    public function partners(): View
    {
        SeoHelper::setPage(
            'Đối tác uy tín',
            'Danh sách đối tác và đơn vị đồng hành uy tín cùng cộng đồng phòng chống lừa đảo trực tuyến.',
        );

        return view('system.partners');
    }

    public function guide(): View
    {
        SeoHelper::setPage(
            'Hướng dẫn tố cáo',
            'Hướng dẫn từng bước cách tố cáo đối tượng lừa đảo, chuẩn bị bằng chứng và theo dõi kết quả kiểm duyệt.',
        );

        return view('support.guide');
    }

    public function contact(): View
    {
        SeoHelper::setPage(
            'Liên hệ Admin',
            'Liên hệ ban quản trị để được hỗ trợ, giải đáp thắc mắc hoặc góp ý về dữ liệu cảnh báo lừa đảo.',
        );

        return view('support.contact');
    }

    public function terms(): View
    {
        SeoHelper::setPage(
            'Điều khoản sử dụng',
            'Điều khoản sử dụng dịch vụ tra cứu và tố cáo lừa đảo, quyền và trách nhiệm của người dùng.',
        );

        return view('legal.terms');
    }

    public function moderationPolicy(): View
    {
        SeoHelper::setPage(
            'Quy trình kiểm duyệt',
            'Cách Scamproof kiểm duyệt tố cáo lừa đảo: yêu cầu bằng chứng, đối chiếu, duyệt hoặc từ chối, che thông tin nhạy cảm và quyền khiếu nại của bên bị tố cáo.',
        );

        return view('legal.moderation-policy');
    }
}
