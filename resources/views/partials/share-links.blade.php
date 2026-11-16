{{-- Usage: @include('partials.share-links', ['url' => url()->current(), 'title' => '...']) --}}
@php
  $shareUrl = $url ?? url()->current();
  $shareTitle = $title ?? '';
@endphp

<div class="flex gap-2.5">
  <button
    type="button"
    data-copy="{{ $shareUrl }}"
    onclick="
      {
        const text = this.getAttribute('data-copy');
        navigator.clipboard.writeText(text).then(() => alert('Đã sao chép liên kết'));
      }
    "
    class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-100 bg-white py-3 text-xs font-bold tracking-tighter text-gray-500 uppercase transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-slate-800 dark:hover:bg-slate-700"
  >
    <i class="fa-regular fa-copy text-sm"></i>
    Chia sẻ
  </button>
  <a
    href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Chia sẻ lên Facebook"
    class="flex w-12 items-center justify-center rounded-2xl border border-gray-100 bg-white text-gray-500 transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-slate-800 dark:hover:bg-slate-700"
  >
    <i class="fa-brands fa-facebook-f text-sm"></i>
  </a>
  <a
    href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareTitle) }}"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Chia sẻ qua Telegram"
    class="flex w-12 items-center justify-center rounded-2xl border border-gray-100 bg-white text-gray-500 transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-slate-800 dark:hover:bg-slate-700"
  >
    <i class="fa-brands fa-telegram text-sm"></i>
  </a>
</div>
