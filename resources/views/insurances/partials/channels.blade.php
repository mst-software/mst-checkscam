{{--
  Official contact channels of a partner from insurance_channels, grouped by type. Bank accounts are listed
  in the payment card instead. Expects: $channels (Collection grouped by ChannelType value).
--}}
@php
  $contactTypes = collect(\App\Enums\ChannelType::cases())
    ->reject(fn ($type) => $type === \App\Enums\ChannelType::BankAccount)
    ->filter(fn ($type) => $channels->has($type->value));
@endphp

<x-ucs.card
  title="Kênh liên hệ chính thức"
  subtitle="Chỉ trao đổi với GDV qua các kênh dưới đây."
  data-official-channels
>
  <div class="space-y-4">
    @foreach ($contactTypes as $type)
      <div>
        <p class="text-ink-subtle flex items-center gap-1.5 text-xs font-medium">
          @include('insurances.partials.channel-icon', ['type' => $type])
          {{ $type->label() }}
        </p>
        <ul class="mt-1.5 space-y-1.5">
          @foreach ($channels[$type->value] as $channel)
            @php
              $href = match (true) {
                $type === \App\Enums\ChannelType::Phone => 'tel:' . preg_replace('/[^\d+]/', '', $channel->value),
                (bool) preg_match('~^https?://~i', $channel->value) => $channel->value,
                default => null,
              };
            @endphp

            <li class="border-line rounded-control flex items-center gap-2 border px-3 py-2">
              <div class="min-w-0 flex-1">
                @if ($href)
                  <a
                    href="{{ $href }}"
                    @if (str_starts_with($href, 'http')) target="_blank" rel="noopener noreferrer" @endif
                    class="text-accent hover:text-accent-hover focus-visible:outline-accent block truncate rounded-sm text-sm font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                  >
                    {{ $channel->value }}
                  </a>
                @else
                  <p class="text-ink truncate text-sm font-medium">{{ $channel->value }}</p>
                @endif
                @if ($channel->verified_at)
                  <p class="text-success mt-0.5 flex items-center gap-1 text-xs">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Xác minh ngày {{ $channel->verified_at->format('d/m/Y') }}
                  </p>
                @endif
              </div>
              <button
                type="button"
                data-copy="{{ $channel->value }}"
                class="text-ink-subtle hover:bg-surface-muted hover:text-ink rounded-control focus-visible:outline-accent flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
              >
                <i class="fa-regular fa-copy text-sm" aria-hidden="true"></i>
                <span class="sr-only" data-copy-label aria-live="polite">Sao chép {{ $type->label() }}</span>
              </button>
            </li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>
</x-ucs.card>
