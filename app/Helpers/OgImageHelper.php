<?php

namespace App\Helpers;

use App\Models\Report;
use GdImage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OgImageHelper
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const CACHE_TTL_DAYS = 7;

    private const PADDING = 70;

    /** GD built-in font 5 is 9x15 px per glyph. */
    private const BUILTIN_FONT = 5;

    private const BUILTIN_GLYPH_WIDTH = 9;

    private const BUILTIN_GLYPH_HEIGHT = 15;

    /**
     * Render (or reuse) the share card PNG for an approved report.
     */
    public static function reportCard(Report $report, int $reportsCount, int $commentsCount): string
    {
        $encoded = Cache::remember(
            self::cacheKey($report, $reportsCount, $commentsCount),
            now()->addDays(self::CACHE_TTL_DAYS),
            // Base64 keeps binary bytes safe in the text column of the database cache store.
            fn (): string => base64_encode(self::renderReportCard($report, $reportsCount, $commentsCount)),
        );

        return base64_decode($encoded);
    }

    /**
     * The key changes whenever the report is updated or its counters move, so stale cards are never served.
     */
    public static function cacheKey(Report $report, int $reportsCount, int $commentsCount): string
    {
        $version = implode('|', [
            $report->updated_at?->getTimestamp(),
            $reportsCount,
            $commentsCount,
            self::fontPath() ? 'ttf' : 'builtin',
        ]);

        return 'og_report_'.$report->id.'_'.md5($version);
    }

    public static function typeLabel(Report $report): string
    {
        [$type] = StringHelper::detectQueryType((string) $report->target_id);

        return match ($type) {
            'phone' => 'Số điện thoại',
            'bank' => 'Số tài khoản ngân hàng',
            'facebook' => 'Facebook',
            default => $report->typeLabel(),
        };
    }

    private static function renderReportCard(Report $report, int $reportsCount, int $commentsCount): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = imagecolorallocate($image, 15, 31, 61);
        $slate = imagecolorallocate($image, 100, 116, 139);
        $ink = imagecolorallocate($image, 30, 41, 59);
        $alert = imagecolorallocate($image, 252, 165, 165);
        $light = imagecolorallocate($image, 241, 245, 249);
        $red = imagecolorallocate($image, 220, 38, 38);

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $white);

        // Header band
        imagefilledrectangle($image, 0, 0, self::WIDTH, 120, $navy);
        $siteName = Str::before((string) ConfigHelper::getConfig('site_title', 'Scamproof'), ' - ');
        self::drawText($image, Str::limit($siteName, 28, ''), self::PADDING, 38, 44, $white, 640);
        self::drawText($image, 'CẢNH BÁO LỪA ĐẢO', 780, 46, 30, $alert, 350);

        // Accent bar
        imagefilledrectangle($image, self::PADDING, 175, self::PADDING + 8, 400, $red);
        $left = self::PADDING + 36;
        $contentWidth = self::WIDTH - $left - self::PADDING;

        self::drawText($image, Str::upper(self::typeLabel($report)), $left, 175, 30, $slate, $contentWidth);
        self::drawText($image, StringHelper::mask_id($report->target_id, $report->type), $left, 228, 96, $navy, $contentWidth);

        $name = StringHelper::mask_name($report->target_name);
        if ($report->target_bank) {
            $name .= ' - '.$report->target_bank;
        }
        self::drawText($image, $name, $left, 352, 40, $ink, $contentWidth);

        $stats = number_format($reportsCount).' báo cáo   |   '.number_format($commentsCount).' bình luận';
        self::drawText($image, $stats, self::PADDING, 450, 36, $navy, self::WIDTH - 2 * self::PADDING);

        // Footer band
        imagefilledrectangle($image, 0, 540, self::WIDTH, self::HEIGHT, $light);
        $domain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'scamproof.vn';
        self::drawText($image, 'Kiểm tra tại '.$domain, self::PADDING, 567, 36, $navy, self::WIDTH - 2 * self::PADDING);

        ob_start();
        imagepng($image, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    /**
     * Draw a single line whose glyphs are about $size px tall, shrinking it to fit $maxWidth.
     */
    private static function drawText(GdImage $image, string $text, int $x, int $top, int $size, int $color, int $maxWidth): void
    {
        $font = self::fontPath();

        if ($font) {
            $points = $size * 0.75;
            $box = imagettfbbox($points, 0, $font, $text);
            $width = $box[2] - $box[0];
            if ($width > $maxWidth) {
                $points *= $maxWidth / $width;
            }
            // imagettftext positions the baseline; the cap height is roughly 0.75 of the pixel size.
            imagettftext($image, $points, 0, $x, (int) round($top + $points / 0.75 * 0.8), $color, $font, $text);

            return;
        }

        // The built-in font only covers Latin-1, so Vietnamese diacritics are folded to ASCII.
        $text = Str::ascii($text);
        $length = max(1, strlen($text));
        $scale = max(1, (int) floor($size / self::BUILTIN_GLYPH_HEIGHT));
        $scale = max(1, min($scale, (int) floor($maxWidth / ($length * self::BUILTIN_GLYPH_WIDTH))));

        $glyphs = imagecreatetruecolor($length * self::BUILTIN_GLYPH_WIDTH, self::BUILTIN_GLYPH_HEIGHT);
        imagealphablending($glyphs, false);
        $transparent = imagecolorallocatealpha($glyphs, 0, 0, 0, 127);
        imagefilledrectangle($glyphs, 0, 0, imagesx($glyphs), imagesy($glyphs), $transparent);
        imagesavealpha($glyphs, true);
        imagestring($glyphs, self::BUILTIN_FONT, 0, 0, $text, $color);

        imagecopyresized(
            $image,
            $glyphs,
            $x,
            $top,
            0,
            0,
            imagesx($glyphs) * $scale,
            imagesy($glyphs) * $scale,
            imagesx($glyphs),
            imagesy($glyphs),
        );
        imagedestroy($glyphs);
    }

    private static function fontPath(): ?string
    {
        $font = config('services.og_image.font');

        return is_string($font) && $font !== '' && is_readable($font) ? $font : null;
    }
}
