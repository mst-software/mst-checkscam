<?php

namespace App\Services;

use App\Enums\ChannelType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves a partner's channels from the admin form. Channels are the source of truth; the legacy
 * `contact_info` / `payment_accounts` JSON is rebuilt from the OFFICIAL channels after every save,
 * because the public profile and the search autocomplete still read it. Bank name and account
 * holder only live in that JSON (channels store the account number only).
 */
class InsuranceChannelService
{
    /**
     * Replace the partner's channels with the submitted rows. Rows are matched to existing channels
     * by type + normalized value, so an unchanged channel keeps its id and verified_at.
     *
     * Example: $channels->sync($insurance, [['type' => 'phone', 'value' => '+84 912 345 678', 'is_official' => true, 'verified' => true]]);
     *
     * @param  array<int, array{type: string, value: string, is_official?: mixed, verified?: mixed, bank?: ?string, holder?: ?string}>  $rows
     *
     * @throws ValidationException when a value normalizes to nothing or two rows are the same channel.
     */
    public function sync(Insurance $insurance, array $rows): void
    {
        $prepared = $this->prepare($rows);

        DB::transaction(function () use ($insurance, $prepared) {
            $existing = $insurance->channels()->get()
                ->keyBy(fn (InsuranceChannel $channel) => $this->key($channel->type, $channel->normalized_value));

            $existing->except(array_keys($prepared))->each(fn (InsuranceChannel $channel) => $channel->delete());

            foreach ($prepared as $key => $row) {
                $channel = $existing->get($key) ?? new InsuranceChannel(['insurance_id' => $insurance->id]);

                $channel->fill([
                    'type' => $row['type'],
                    'value' => $row['value'],
                    'is_official' => $row['is_official'],
                    'verified_at' => $row['verified'] ? ($channel->verified_at ?? now()) : null,
                ])->save();
            }

            $insurance->forceFill($this->legacyJson($prepared))->save();
        });
    }

    /**
     * Rows for the admin form: the partner's channels plus the bank name / holder kept in the legacy JSON.
     *
     * @return list<array{type: string, value: string, is_official: bool, verified: bool, bank: string, holder: string}>
     */
    public function formRows(Insurance $insurance): array
    {
        $accounts = collect(is_array($insurance->payment_accounts) ? $insurance->payment_accounts : [])
            ->filter(fn ($account) => is_array($account) && is_scalar($account['number'] ?? null))
            ->keyBy(fn (array $account) => InsuranceChannel::normalize(ChannelType::BankAccount, (string) $account['number']));

        return $insurance->channels()->orderBy('id')->get()->map(function (InsuranceChannel $channel) use ($accounts) {
            $account = $channel->type === ChannelType::BankAccount ? $accounts->get($channel->normalized_value) : null;

            return [
                'type' => $channel->type->value,
                'value' => $channel->value,
                'is_official' => $channel->is_official,
                'verified' => $channel->verified_at !== null,
                'bank' => (string) ($account['bank'] ?? ''),
                'holder' => (string) ($account['name'] ?? ''),
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array{type: ChannelType, value: string, is_official: bool, verified: bool, bank: string, holder: string}>
     *
     * @throws ValidationException
     */
    private function prepare(array $rows): array
    {
        $prepared = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $type = ChannelType::from($row['type']);
            $value = trim((string) $row['value']);
            $normalized = InsuranceChannel::normalize($type, $value);

            if ($normalized === '') {
                $errors["channels.$index.value"] = 'Giá trị kênh "'.$value.'" không hợp lệ với loại '.$type->label().'.';

                continue;
            }

            $key = $this->key($type, $normalized);
            if (isset($prepared[$key])) {
                $errors["channels.$index.value"] = 'Kênh '.$type->label().' "'.$value.'" bị trùng với một dòng khác.';

                continue;
            }

            $prepared[$key] = [
                'type' => $type,
                'value' => $value,
                'is_official' => filter_var($row['is_official'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'verified' => filter_var($row['verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'bank' => trim((string) ($row['bank'] ?? '')),
                'holder' => trim((string) ($row['holder'] ?? '')),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $prepared;
    }

    /**
     * @param  array<string, array{type: ChannelType, value: string, is_official: bool, bank: string, holder: string}>  $prepared
     * @return array{contact_info: ?list<array{platform: string, link: string}>, payment_accounts: ?list<array{bank: string, number: string, name: string}>}
     */
    private function legacyJson(array $prepared): array
    {
        $contacts = [];
        $accounts = [];

        foreach ($prepared as $row) {
            if (! $row['is_official']) {
                continue;
            }

            if ($row['type'] === ChannelType::BankAccount) {
                $accounts[] = ['bank' => $row['bank'], 'number' => $row['value'], 'name' => $row['holder']];
            } else {
                $contacts[] = ['platform' => $row['type']->label(), 'link' => $row['value']];
            }
        }

        return [
            'contact_info' => $contacts ?: null,
            'payment_accounts' => $accounts ?: null,
        ];
    }

    private function key(ChannelType $type, string $normalized): string
    {
        return $type->value.'|'.$normalized;
    }
}
