<?php

namespace App\Helpers;

use Artesaos\SEOTools\Facades\SEOTools;

class SeoHelper
{
    public const BRAND = 'Scamproof';

    /**
     * Sets the "<Page> - Scamproof" title and the description for meta, OpenGraph, Twitter and JSON-LD.
     */
    public static function setPage(string $page, string $description): void
    {
        SEOTools::setTitle($page.' - '.self::BRAND);
        SEOTools::setDescription($description);
    }
}
