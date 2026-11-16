<div id="global-notification" class="fixed inset-0 z-100 hidden items-center justify-center p-4 sm:p-6">
  <!-- Overlay -->
  <div class="close-notif-btn absolute inset-0 bg-slate-900/50"></div>

  <!-- Modal content -->
  <div
    role="dialog"
    aria-modal="true"
    aria-labelledby="global-notification-title"
    class="border-line bg-surface rounded-card relative w-full max-w-lg overflow-hidden border shadow-lg"
  >
    <button
      type="button"
      aria-label="Đóng"
      class="close-notif-btn text-ink-subtle hover:bg-surface-muted hover:text-ink rounded-control absolute top-3 right-3 z-10 flex h-9 w-9 cursor-pointer items-center justify-center transition-colors"
    >
      <i class="fa-solid fa-xmark"></i>
    </button>

    <div class="p-6 sm:p-8">
      <div class="mb-4 flex items-center gap-3">
        <div class="bg-danger-soft text-danger flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h2 id="global-notification-title" class="text-ink text-lg font-semibold">Cảnh báo giả mạo</h2>
      </div>

      <p class="text-ink-muted text-sm leading-relaxed sm:text-base">
        {!! App\Helpers\ConfigHelper::getConfig('site_notification_text', 'Hiện nay có rất nhiều tài khoản FB, TK ngân hàng, website, group và bot giả mạo <span class="bg-accent-soft text-accent rounded px-1.5 font-semibold">Admin/Gdv CS</span> nhằm lừa đảo người dùng.') !!}
      </p>
    </div>

    <div class="border-line bg-surface-muted flex justify-end border-t px-6 py-4 sm:px-8">
      <button
        type="button"
        id="suppress-btn"
        class="border-line bg-surface text-ink hover:bg-page rounded-control inline-flex h-10 cursor-pointer items-center justify-center gap-2 border px-4 text-sm font-medium transition-colors"
      >
        <i class="fa-regular fa-clock text-xs"></i>
        Ẩn trong 1 giờ
      </button>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    const $notification = $('#global-notification');
    const storageKey = 'cs_notification_suppressed_until';

    function hideNotification() {
      // Smooth fade combined with slight scale down
      $notification.fadeOut(300);
    }

    $('.close-notif-btn').on('click', function () {
      hideNotification();
    });

    $('#suppress-btn').on('click', function () {
      const oneHourLater = new Date().getTime() + 60 * 60 * 1000;
      localStorage.setItem(storageKey, oneHourLater);
      hideNotification();
    });

    const suppressedUntil = localStorage.getItem(storageKey);
    const currentTime = new Date().getTime();

    if (!suppressedUntil || currentTime > parseInt(suppressedUntil)) {
      setTimeout(function () {
        $notification
          .css({
            display: 'flex',
            opacity: 0,
          })
          .hide()
          .fadeIn(400)
          .animate(
            {
              opacity: 1,
            },
            {
              queue: false,
              duration: 400,
            }
          );
      }, 1200);
    }
  });
</script>
