{{-- Font Awesome icon for an official channel type. Expects: $type (App\Enums\ChannelType|null); optional $class. --}}
@php
  $iconClass = match ($type) {
    \App\Enums\ChannelType::Phone => 'fa-solid fa-phone',
    \App\Enums\ChannelType::Facebook => 'fa-brands fa-facebook-f',
    \App\Enums\ChannelType::Telegram => 'fa-brands fa-telegram',
    \App\Enums\ChannelType::Zalo => 'fa-solid fa-comment-dots',
    \App\Enums\ChannelType::BankAccount => 'fa-solid fa-building-columns',
    \App\Enums\ChannelType::Website => 'fa-solid fa-globe',
    default => 'fa-solid fa-link',
  };
@endphp

<i class="{{ $iconClass }} {{ $class ?? '' }}" aria-hidden="true"></i>
