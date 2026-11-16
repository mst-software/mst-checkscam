<?php

namespace App\Console\Commands;

use App\Enums\InsuranceStatusEventType;
use App\Exceptions\InsuranceLedgerException;
use App\Helpers\ConfigHelper;
use App\Models\Insurance;
use App\Services\InsuranceLedgerService;
use App\Services\InsuranceSuspensionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class SyncInsuranceStatus extends Command
{
    public const MIN_BALANCE_SETTING = 'insurance_min_balance';

    protected $signature = 'insurance:sync-status';

    protected $description = 'Suspend insurance partners below the minimum deposit and record expired guarantees';

    public function handle(InsuranceSuspensionService $suspensions): int
    {
        $suspended = $this->suspendLowBalancePartners($suspensions);
        $expired = $this->recordExpiredPartners();

        $this->info("Suspended for low balance: {$suspended}. New expiry notices: {$expired}.");

        return self::SUCCESS;
    }

    /**
     * Setting `insurance_min_balance` (VND); "0" or empty disables the check.
     */
    private function suspendLowBalancePartners(InsuranceSuspensionService $suspensions): int
    {
        $setting = (string) ConfigHelper::getConfig(self::MIN_BALANCE_SETTING, '0');

        try {
            $minCents = $setting === '' ? 0 : InsuranceLedgerService::toCents($setting);
        } catch (InsuranceLedgerException) {
            $this->warn('Invalid '.self::MIN_BALANCE_SETTING.' setting: '.$setting);

            return 0;
        }

        if ($minCents <= 0) {
            return 0;
        }

        $count = 0;
        Insurance::query()
            ->trusted()
            ->where('amount', '<', InsuranceLedgerService::fromCents($minCents))
            ->each(function (Insurance $insurance) use ($suspensions, &$count) {
                if ($suspensions->suspend($insurance, InsuranceSuspensionService::REASON_LOW_BALANCE)) {
                    $count++;
                    Log::info('Insurance partner suspended for low balance', ['insurance_id' => $insurance->id, 'amount' => (string) $insurance->amount]);
                }
            });

        return $count;
    }

    /**
     * Trusted() already hides expired partners; this only leaves one audit row per expiry.
     */
    private function recordExpiredPartners(): int
    {
        $count = 0;
        Insurance::query()
            ->where('status', 1)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->whereDoesntHave('statusEvents', function (Builder $query) {
                $query->where('type', InsuranceStatusEventType::ExpiredNotice)
                    ->whereColumn('insurance_status_events.created_at', '>=', 'insurances.expired_at');
            })
            ->each(function (Insurance $insurance) use (&$count) {
                $insurance->statusEvents()->create([
                    'type' => InsuranceStatusEventType::ExpiredNotice,
                    'reason' => 'Hết hạn bảo hiểm ngày '.$insurance->expired_at->format('d/m/Y'),
                ]);
                Log::info('Insurance partner guarantee expired', ['insurance_id' => $insurance->id]);
                $count++;
            });

        return $count;
    }
}
