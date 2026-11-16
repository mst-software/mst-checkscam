@props([
  'image' => '',
  'url' => '#',
  'alt' => '',
])

@php
  $src = Str::startsWith($image, ['http://', 'https://']) ? $image : asset('uploads/' . $image);
  $href = is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
@endphp

<div
  {{ $attributes->merge(['class' => 'border-line bg-surface-muted rounded-card mx-auto aspect-[8/1] w-full max-w-[950px] overflow-hidden border']) }}
>
  @if ($href)
    <a href="{{ $href }}" target="_blank" rel="sponsored noopener" class="block h-full w-full">
      <x-ads-image :src="$src" :alt="$alt" />
    </a>
  @else
    <x-ads-image :src="$src" :alt="$alt" />
  @endif
</div>
