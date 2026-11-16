@php
  $badgeClass = match ($status) {
    \App\Enums\ComplaintStatus::Pending => 'bg-lightyellow',
    \App\Enums\ComplaintStatus::Resolved => 'bg-lightgreen',
    \App\Enums\ComplaintStatus::Dismissed => 'bg-lightred',
  };
@endphp

<span class="badges {{ $badgeClass }}">{{ $status->label() }}</span>
