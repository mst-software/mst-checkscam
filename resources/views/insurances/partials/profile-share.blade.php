{{--
  "Chia sẻ hồ sơ xác thực": links partners paste on Facebook, Telegram or Zalo as proof of verification.
  Zalo has no public share URL without an Official Account, so it copies the link for pasting into a chat.
  Expects: $insurance, $profileUrl.
--}}
@php
  $shareText = 'Hồ sơ giao dịch viên đã xác thực của ' . $insurance->full_name . ' trên Scamproof';
  $verificationLine = 'Giao dịch viên đã xác thực bởi Scamproof · Mã ' . $insurance->memberCode() . ' · Xem hồ sơ: ' . $profileUrl;
  $shareButton = 'border-line bg-surface text-ink hover:bg-surface-muted rounded-control focus-visible:outline-accent inline-flex h-10 cursor-pointer items-center justify-center gap-2 border px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2';
@endphp

<x-ucs.card data-share-box>
  <x-slot:header>
    <h2 class="text-ink text-base font-semibold">Chia sẻ hồ sơ xác thực</h2>
    <p class="text-ink-subtle mt-0.5 text-sm">Gắn link này lên trang cá nhân, fanpage hoặc nhóm của bạn.</p>
  </x-slot>

  <div class="grid grid-cols-2 gap-2">
    <button type="button" data-copy="{{ $profileUrl }}" class="{{ $shareButton }} col-span-2">
      <i class="fa-solid fa-link" aria-hidden="true"></i>
      <span data-copy-label aria-live="polite">Sao chép link hồ sơ</span>
    </button>
    <a
      href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($profileUrl) }}"
      target="_blank"
      rel="noopener noreferrer"
      class="{{ $shareButton }}"
      data-share="facebook"
    >
      <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
      Facebook
    </a>
    <a
      href="https://t.me/share/url?url={{ urlencode($profileUrl) }}&text={{ urlencode($shareText) }}"
      target="_blank"
      rel="noopener noreferrer"
      class="{{ $shareButton }}"
      data-share="telegram"
    >
      <i class="fa-brands fa-telegram" aria-hidden="true"></i>
      Telegram
    </a>
    <button type="button" data-copy="{{ $profileUrl }}" class="{{ $shareButton }} col-span-2" data-share="zalo">
      <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
      <span data-copy-label aria-live="polite">Sao chép link để gửi qua Zalo</span>
    </button>
  </div>

  <div class="mt-5">
    <p class="text-ink text-sm font-semibold">Dòng xác thực</p>
    <p class="text-ink-subtle mt-0.5 text-xs">Dán vào phần giới thiệu hoặc bài ghim để khách hàng kiểm tra.</p>
    <p
      id="verification-line"
      class="border-line bg-surface-muted text-ink rounded-control mt-2 border px-3 py-2.5 text-sm leading-relaxed break-words"
      data-verification-line
    >
      {{ $verificationLine }}
    </p>
    <button type="button" data-copy="{{ $verificationLine }}" class="{{ $shareButton }} mt-2 w-full">
      <i class="fa-regular fa-copy" aria-hidden="true"></i>
      <span data-copy-label aria-live="polite">Sao chép dòng xác thực</span>
    </button>
  </div>

  <x-slot:footer>
    <p class="text-ink-subtle flex items-start gap-2 text-xs leading-relaxed">
      <i class="fa-solid fa-rotate mt-0.5" aria-hidden="true"></i>
      Trang hồ sơ kiểm tra trạng thái trực tiếp mỗi lần được mở. Nếu thành viên bị tạm đình chỉ hoặc hết hạn, người xem
      sẽ thấy ngay.
    </p>
  </x-slot>
</x-ucs.card>
