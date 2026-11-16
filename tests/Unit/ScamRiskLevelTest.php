<?php

use App\Services\ScamLookupService;
use Carbon\CarbonImmutable;

test('risk level depends on approved report count and recency', function (int $count, ?int $daysAgo, string $expected) {
    $latest = $daysAgo === null ? null : CarbonImmutable::now()->subDays($daysAgo);

    expect((new ScamLookupService)->riskLevel($count, $latest))->toBe($expected);
})->with([
    'no reports' => [0, null, ScamLookupService::RISK_LOW],
    'one stale report' => [1, 400, ScamLookupService::RISK_LOW],
    'one recent report' => [1, 3, ScamLookupService::RISK_MEDIUM],
    'two old reports' => [2, 200, ScamLookupService::RISK_MEDIUM],
    'two recent reports' => [2, 10, ScamLookupService::RISK_HIGH],
    'three old reports' => [3, 500, ScamLookupService::RISK_HIGH],
]);
