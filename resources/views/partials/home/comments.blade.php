<section aria-label="Bình luận mới">
  <x-ucs.section-title title="Bình luận mới" subtitle="Chia sẻ mới nhất từ cộng đồng về các đối tượng bị tố cáo." />

  @if ($comments->isNotEmpty())
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" id="comments-container">
      @include('partials.comment-items', ['comments' => $comments])
    </div>

    @if ($comments->hasMorePages())
      <div class="mt-6 text-center" id="load-more-comments-container">
        <x-ucs.button
          variant="secondary"
          id="btn-load-more-comments"
          data-url="{{ route('home') }}"
          data-next-page="{{ $comments->currentPage() + 1 }}"
          data-total="{{ $comments->total() }}"
        >
          <span data-label>Xem thêm bình luận</span>
        </x-ucs.button>
      </div>
    @endif

    <p id="comments-status" class="sr-only" aria-live="polite"></p>
  @else
    <x-ucs.empty-state title="Chưa có bình luận nào." icon="fa-regular fa-comments" class="bg-surface" />
  @endif
</section>

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const button = document.getElementById('btn-load-more-comments');
      const container = document.getElementById('comments-container');
      if (!button || !container) return;

      const label = button.querySelector('[data-label]');
      const status = document.getElementById('comments-status');
      const total = Number(button.dataset.total);

      button.addEventListener('click', async function () {
        const nextPage = Number(button.dataset.nextPage);
        const url = new URL(button.dataset.url, window.location.origin);
        url.searchParams.set('page', nextPage);

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        label.textContent = 'Đang tải bình luận...';

        try {
          const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
          });
          if (!response.ok) throw new Error('HTTP ' + response.status);

          const template = document.createElement('template');
          template.innerHTML = (await response.text()).trim();
          const added = template.content.querySelectorAll('[data-comment-item]').length;
          container.append(template.content);

          status.textContent = 'Đã tải thêm ' + added + ' bình luận.';

          if (added === 0 || container.querySelectorAll('[data-comment-item]').length >= total) {
            document.getElementById('load-more-comments-container').remove();
            return;
          }

          button.dataset.nextPage = String(nextPage + 1);
          label.textContent = 'Xem thêm bình luận';
        } catch (error) {
          console.error('Load more comments error:', error);
          label.textContent = 'Thử lại';
        } finally {
          button.disabled = false;
          button.removeAttribute('aria-busy');
        }
      });
    });
  </script>
@endpush
