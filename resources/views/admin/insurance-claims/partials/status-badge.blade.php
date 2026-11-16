@php
  $badgeClass = match ($status) {
    \App\Enums\ClaimStatus::Submitted, \App\Enums\ClaimStatus::Reviewing, \App\Enums\ClaimStatus::NeedInfo => 'bg-lightyellow',
    \App\Enums\ClaimStatus::Approved, \App\Enums\ClaimStatus::Paid => 'bg-lightgreen',
    \App\Enums\ClaimStatus::Rejected => 'bg-lightred',
  };
@endphp

<span class="badges {{ $badgeClass }}">{{ $status->label() }}</span>
