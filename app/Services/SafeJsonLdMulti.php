<?php

namespace App\Services;

use Artesaos\SEOTools\JsonLd;
use Artesaos\SEOTools\JsonLdMulti;

/**
 * SEOTools' JSON-LD with "<" and ">" escaped, so user text (a report story, a post excerpt)
 * can never close the <script> block. The package encodes with JSON_UNESCAPED_SLASHES only.
 */
class SafeJsonLdMulti extends JsonLdMulti
{
    public function generate($minify = false)
    {
        return array_reduce($this->list, function (string $output, JsonLd $jsonLd): string {
            if ($jsonLd->isEmpty()) {
                return $output;
            }

            $data = array_merge(['@context' => 'https://schema.org'], $jsonLd->convertToArray());

            return $output.'<script type="application/ld+json">'
                .json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)
                .'</script>';
        }, '');
    }
}
