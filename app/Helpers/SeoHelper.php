<?php

namespace App\Helpers;

use Artesaos\SEOTools\Facades\SEOTools;

class SeoHelper
{
    public const BRAND = 'Scamproof';

    /** Request attribute marking that the page set its own share image. */
    private const SHARE_IMAGE_ATTRIBUTE = 'seo.share_image';

    /**
     * Sets the "<Page> - Scamproof" title and the description for meta, OpenGraph, Twitter and JSON-LD.
     */
    public static function setPage(string $page, string $description): void
    {
        SEOTools::setTitle($page.' - '.self::BRAND);
        SEOTools::setDescription($description);
    }

    /**
     * Sets the og:image (with alt and, when known, size) and twitter:image of the page.
     * The site-wide default image is then not added (see AppServiceProvider), so crawlers pick this one.
     */
    public static function setShareImage(string $url, string $alt, ?int $width = null, ?int $height = null): void
    {
        SEOTools::opengraph()->addImage($url, array_filter([
            'alt' => $alt,
            'width' => $width,
            'height' => $height,
        ]));
        SEOTools::twitter()->setImage($url);
        SEOTools::twitter()->addValue('image:alt', $alt);

        request()->attributes->set(self::SHARE_IMAGE_ATTRIBUTE, true);
    }

    public static function hasShareImage(): bool
    {
        return (bool) request()->attributes->get(self::SHARE_IMAGE_ATTRIBUTE, false);
    }
}
