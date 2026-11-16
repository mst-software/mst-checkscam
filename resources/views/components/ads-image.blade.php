@props([
  'src' => '',
  'alt' => '',
])

{{--
  Third-party banner images often fail (403, hotlink protection). On error the whole ad slot is
  hidden, and the banner-ads container too once none of its slots is left, so no blank gap or
  broken-image icon is shown.
--}}
<img
  src="{{ $src }}"
  alt="{{ $alt }}"
  loading="lazy"
  decoding="async"
  onerror="
    var s = this.closest('[data-ad-slot]') || this.parentNode;
    s.hidden = true;
    var c = s.closest('[data-ad-container]');
    if (c && !c.querySelector('[data-ad-slot]:not([hidden])')) {
      c.hidden = true;
    }
  "
  class="h-full w-full object-contain"
/>
