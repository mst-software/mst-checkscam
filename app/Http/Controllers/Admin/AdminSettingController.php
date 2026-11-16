<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ConfigHelper;
use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'name')->toArray();

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'insurance_min_balance' => ['nullable', 'integer', 'min:0', 'max:9999999999999'],
        ], [
            'insurance_min_balance.integer' => 'Số dư quỹ tối thiểu phải là số nguyên (VND).',
            'insurance_min_balance.min' => 'Số dư quỹ tối thiểu không được âm.',
            'insurance_min_balance.max' => 'Số dư quỹ tối thiểu quá lớn.',
        ]);

        // An emptied field turns the low-balance check off.
        if ($request->has('insurance_min_balance')) {
            $request->merge(['insurance_min_balance' => (string) ($request->input('insurance_min_balance') ?? '0')]);
        }

        $keys = [
            'site_title',
            'site_description',
            'seo_keywords',
            'site_author',
            'hotline',
            'support_email',
            'zalo_link',
            'facebook_link',
            'telegram_link',
            'enable_insurance',
            'enable_comments',
            'maintenance_mode',
            'header_scripts',
            'google_site_verification',
            'bing_site_verification',
            'site_index',
            'og_site_name',
            'twitter_username',
            'schema_organization_name',
            'schema_organization_logo',
            'schema_organization_url',
            'schema_organization_contact',
            'meta_extra',
            'site_notification_text',
            'insurance_min_balance',
        ];

        foreach ($keys as $key) {
            $value = $request->input($key);

            if (in_array($key, ['enable_insurance', 'enable_comments', 'maintenance_mode'])) {
                $value = $request->has($key) ? '1' : '0';
            }

            if ($value !== null) {
                Setting::setValue($key, $value);
            }
        }

        $imageKeys = [
            'logo' => 'logo',
            'logo_header_light' => 'logo_header_light',
            'logo_header_dark' => 'logo_header_dark',
            'logo_footer_light' => 'logo_footer_light',
            'logo_footer_dark' => 'logo_footer_dark',
            'favicon' => 'favicon',
            'og_image' => 'og_image',
        ];

        foreach ($imageKeys as $fileInput => $settingKey) {
            if ($request->hasFile($fileInput)) {
                $mimes = $fileInput == 'favicon' ? 'jpeg,png,jpg,gif,svg,ico' : 'jpeg,png,jpg,gif,svg';
                $max = $fileInput == 'favicon' ? 1024 : 2048;

                $request->validate([$fileInput => "image|mimes:$mimes|max:$max"]);
                $path = FileHelper::uploadImage($request->file($fileInput), 'settings');
                Setting::setValue($settingKey, $path);
            }
        }

        ConfigHelper::clearCache();

        return back()->with('success', 'Đã lưu cài đặt thành công.');
    }
}
