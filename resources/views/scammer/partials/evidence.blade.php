{{--
  Evidence gallery with a keyboard-accessible lightbox. Expects: $evidenceImages (collection of URLs), $maskedTarget.
  Images that fail to load are removed instead of showing a broken placeholder.
--}}
<x-ucs.card title="Bằng chứng" :subtitle="$evidenceImages->isNotEmpty() ? 'Bấm vào ảnh để xem cỡ lớn.' : null">
  <div data-evidence-gallery>
    @if ($evidenceImages->isNotEmpty())
      <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3" role="list">
        @foreach ($evidenceImages as $index => $imageUrl)
          <li data-evidence-item>
            <button
              type="button"
              class="rounded-control border-line bg-surface-muted focus-visible:outline-accent group block aspect-[4/3] w-full cursor-zoom-in overflow-hidden border focus-visible:outline-2 focus-visible:outline-offset-2"
              data-evidence-src="{{ $imageUrl }}"
              aria-label="Xem ảnh bằng chứng {{ $index + 1 }} cỡ lớn"
            >
              <img
                src="{{ $imageUrl }}"
                alt="Ảnh bằng chứng {{ $index + 1 }} trong tố cáo {{ $maskedTarget }}"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-cover transition-opacity group-hover:opacity-90"
                onerror="this.closest('[data-evidence-item]').hidden = true"
              />
            </button>
          </li>
        @endforeach
      </ul>
    @endif

    <p class="text-ink-subtle text-sm" data-evidence-empty @if ($evidenceImages->isNotEmpty()) hidden @endif>
      Không có ảnh bằng chứng hiển thị được cho tố cáo này.
    </p>
  </div>
</x-ucs.card>

@if ($evidenceImages->isNotEmpty())
  <dialog
    id="evidence-lightbox"
    class="m-0 h-full max-h-none w-full max-w-none bg-black/90 p-0 backdrop:bg-black/60"
    aria-label="Xem ảnh bằng chứng"
  >
    <div class="flex h-full w-full items-center justify-center p-4 sm:p-12" data-lightbox-stage>
      <img alt="" class="max-h-full max-w-full rounded-lg object-contain select-none" data-lightbox-image />

      <button
        type="button"
        class="rounded-control absolute top-4 right-4 flex h-11 w-11 cursor-pointer items-center justify-center bg-white/10 text-xl text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"
        data-lightbox-close
        aria-label="Đóng"
      >
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
      <button
        type="button"
        class="rounded-control absolute top-1/2 left-3 flex h-11 w-11 -translate-y-1/2 cursor-pointer items-center justify-center bg-white/10 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"
        data-lightbox-prev
        aria-label="Ảnh trước"
      >
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
      </button>
      <button
        type="button"
        class="rounded-control absolute top-1/2 right-3 flex h-11 w-11 -translate-y-1/2 cursor-pointer items-center justify-center bg-white/10 text-white hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-white"
        data-lightbox-next
        aria-label="Ảnh tiếp theo"
      >
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </button>
      <p
        class="absolute bottom-4 left-1/2 -translate-x-1/2 text-sm text-white/80 tabular-nums"
        data-lightbox-counter
        aria-live="polite"
      ></p>
    </div>
  </dialog>

  @push('scripts')
    <script>
      (function () {
        const gallery = document.querySelector('[data-evidence-gallery]');
        const dialog = document.getElementById('evidence-lightbox');
        if (!gallery || !dialog) return;

        const image = dialog.querySelector('[data-lightbox-image]');
        const counter = dialog.querySelector('[data-lightbox-counter]');
        const prev = dialog.querySelector('[data-lightbox-prev]');
        const next = dialog.querySelector('[data-lightbox-next]');
        let items = [];
        let index = 0;
        let opener = null;

        const visibleButtons = () =>
          Array.from(gallery.querySelectorAll('[data-evidence-item]:not([hidden]) [data-evidence-src]'));

        const syncEmptyState = () => {
          gallery.querySelector('[data-evidence-empty]').hidden = visibleButtons().length > 0;
        };

        const render = () => {
          const button = items[index];
          image.src = button.dataset.evidenceSrc;
          image.alt = button.querySelector('img').alt;
          counter.textContent = index + 1 + ' / ' + items.length;
          prev.hidden = next.hidden = items.length < 2;
        };

        const step = (delta) => {
          if (items.length < 2) return;
          index = (index + delta + items.length) % items.length;
          render();
        };

        const close = () => dialog.close();

        gallery.addEventListener(
          'error',
          (event) => {
            const item = event.target.closest && event.target.closest('[data-evidence-item]');
            if (item) {
              item.hidden = true;
              syncEmptyState();
            }
          },
          true
        );

        gallery.addEventListener('click', (event) => {
          const button = event.target.closest('[data-evidence-src]');
          if (!button) return;
          items = visibleButtons();
          index = Math.max(0, items.indexOf(button));
          opener = button;
          render();
          dialog.showModal();
          document.documentElement.classList.add('overflow-hidden');
        });

        dialog.addEventListener('close', () => {
          document.documentElement.classList.remove('overflow-hidden');
          image.removeAttribute('src');
          if (opener) opener.focus();
        });

        dialog.addEventListener('keydown', (event) => {
          if (event.key === 'ArrowLeft') step(-1);
          if (event.key === 'ArrowRight') step(1);
        });

        dialog.querySelector('[data-lightbox-close]').addEventListener('click', close);
        prev.addEventListener('click', () => step(-1));
        next.addEventListener('click', () => step(1));
        dialog.querySelector('[data-lightbox-stage]').addEventListener('click', (event) => {
          if (event.target === event.currentTarget) close();
        });

        syncEmptyState();
      })();
    </script>
  @endpush
@endif
