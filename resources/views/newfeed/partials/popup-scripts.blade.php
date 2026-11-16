{{-- openPopup()/closePopup() for newfeed/partials/modal: backdrop click, Escape and focus return. --}}
<script>
  (function () {
    let lastTrigger = null;

    window.openPopup = function (id) {
      const popup = document.getElementById(id);
      if (!popup) return;
      lastTrigger = document.activeElement;
      popup.classList.remove('hidden');
      popup.classList.add('flex');
      const focusable = popup.querySelector(
        'textarea, input:not([type="hidden"]):not(.hidden), select, a[href], button:not([disabled])'
      );
      if (focusable) focusable.focus();
    };

    window.closePopup = function (id) {
      const popup = document.getElementById(id);
      if (!popup) return;
      popup.classList.remove('flex');
      popup.classList.add('hidden');
      if (lastTrigger && document.body.contains(lastTrigger)) lastTrigger.focus();
    };

    document.addEventListener('click', function (event) {
      if (event.target.matches('[role="dialog"][id^="popup-"]')) {
        window.closePopup(event.target.id);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      document.querySelectorAll('[role="dialog"][id^="popup-"].flex').forEach(function (popup) {
        window.closePopup(popup.id);
      });
    });
  })();
</script>
