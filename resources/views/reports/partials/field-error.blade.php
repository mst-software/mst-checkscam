{{-- Inline error slot for one field; filled by the server on redirect and by the step script on the client. --}}
@php
  $message = $errors->first($field) ?: $errors->first($field . '.*');
@endphp

<p
  id="error-{{ $field }}"
  class="text-danger mt-1.5 text-xs font-medium"
  data-error-for="{{ $field }}"
  @if (! $message) hidden @endif
>
  <i class="fa-solid fa-circle-exclamation mr-1" aria-hidden="true"></i>
  <span data-error-text>{{ $message }}</span>
</p>
