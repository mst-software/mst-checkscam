{{--
  Client-side card renderer for posts loaded from GET /api/newfeed/posts (mirrors newfeed/partials/card).
  Exposes window.NewfeedCard. Expects: $userReportedIds.
--}}
<script>
  window.NewfeedCard = (function () {
    const reportedIds = @json($userReportedIds);
    const SHOW_MAX = 4;
    const CONTENT_LIMIT = 200;
    const ACTION_CLASS =
      'rounded-control absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent';
    const REPORTED_CLASS = ACTION_CLASS + ' text-ink-subtle cursor-not-allowed opacity-70';

    function escapeHtml(s) {
      return $('<div>').text(s).html().replace(/"/g, '&quot;');
    }

    // Inline markdown renderer (mirrors StringHelper::formatPostContent)
    function formatPostContent(text) {
      let s = escapeHtml(text);
      s = s.replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>');
      s = s.replace(/__(.+?)__/gs, '<u>$1</u>');
      s = s.replace(/\*(.+?)\*/gs, '<em>$1</em>');
      return s.replace(/\n/g, '<br>');
    }

    function emptyStateHtml(text) {
      return (
        '<div class="nf-empty-state border-line text-ink-subtle rounded-card flex w-full flex-col items-center justify-center border border-dashed py-16 text-center text-sm [column-span:all]">' +
        '<i class="fa-regular fa-folder-open mb-3 text-2xl" aria-hidden="true"></i>' +
        escapeHtml(text) +
        '</div>'
      );
    }

    function buildImageGrid(images) {
      if (!images || images.length === 0) return '';
      const total = images.length;
      const show = Math.min(total, SHOW_MAX);
      const extra = total - show;
      const imagesJson = JSON.stringify(images).replace(/"/g, '&quot;');
      let html = '<div class="mt-3 grid gap-1 ' + (show === 1 ? 'grid-cols-1' : 'grid-cols-2') + '">';
      for (let i = 0; i < show; i++) {
        const spanCls = show === 3 && i === 0 ? ' col-span-2' : '';
        const hCls = show === 1 ? ' max-h-72' : ' h-36';
        html +=
          '<button type="button" onclick="event.stopPropagation();openCardGallery(' +
          imagesJson +
          ',' +
          i +
          ')" aria-label="Xem ảnh ' +
          (i + 1) +
          '/' +
          total +
          '" class="bg-surface-muted relative cursor-pointer overflow-hidden rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent' +
          spanCls +
          hCls +
          '">' +
          '<img src="' +
          escapeHtml(images[i]) +
          '" alt="" class="h-full w-full object-cover" loading="lazy">';
        if (i === show - 1 && extra > 0) {
          html +=
            '<span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/50 text-xl font-semibold text-white">+' +
            extra +
            '</span>';
        }
        html += '</button>';
      }
      return html + '</div>';
    }

    // Long content is cut; clicking the card opens the detail page.
    function truncateContent(text) {
      const isLong = text.length > CONTENT_LIMIT;
      const body = formatPostContent(isLong ? text.substring(0, CONTENT_LIMIT) : text);
      return (
        '<div class="text-ink mt-3 text-sm leading-relaxed break-words">' +
        body +
        (isLong ? '<span class="text-ink-subtle">...</span>' : '') +
        '</div>'
      );
    }

    function actionButton(post) {
      if (!window._authUserId) return '';
      if (post.is_mine) {
        return (
          '<button type="button" data-no-nav onclick="deletePost(' +
          post.id +
          ',this)" class="' +
          ACTION_CLASS +
          ' text-danger hover:bg-danger-soft cursor-pointer" title="Xóa bài" aria-label="Xóa bài đăng"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>'
        );
      }
      if (reportedIds.includes(post.id) || post.is_reported) {
        return (
          '<button type="button" data-no-nav class="' +
          REPORTED_CLASS +
          '" title="Đã báo cáo" aria-label="Bạn đã báo cáo bài đăng này" disabled><i class="fa-solid fa-flag" aria-hidden="true"></i></button>'
        );
      }
      return (
        '<button type="button" data-no-nav onclick="openReport(' +
        post.id +
        ',this)" class="' +
        ACTION_CLASS +
        ' text-ink-subtle hover:bg-surface-muted hover:text-ink cursor-pointer" title="Báo cáo" aria-label="Báo cáo bài đăng"><i class="fa-regular fa-flag" aria-hidden="true"></i></button>'
      );
    }

    function render(post) {
      const postUrl = '/newfeed/' + post.id;
      const tick = post.user.is_verified
        ? '<i class="fa-solid fa-circle-check text-success text-xs" title="Tài khoản đã được Scamproof xác minh uy tín" aria-hidden="true"></i><span class="sr-only">(đã xác minh)</span>'
        : '';
      const priceHtml =
        post.price && post.price > 0
          ? '<p class="text-ink mt-2 text-sm font-semibold tabular-nums">' +
            Number(post.price).toLocaleString('vi-VN') +
            ' đ</p>'
          : '';

      return $(
        '<article class="nf-card border-line bg-surface rounded-card hover:border-line-strong relative mb-4 cursor-pointer break-inside-avoid border p-4 transition-colors" data-id="' +
          post.id +
          '" data-post-url="' +
          postUrl +
          '">' +
          actionButton(post) +
          '<div class="flex items-center gap-3 pr-10">' +
          '<img src="' +
          escapeHtml(post.user.avatar_url || '') +
          '" alt="" width="36" height="36" class="h-9 w-9 rounded-full object-cover">' +
          '<div class="min-w-0"><p class="text-ink flex items-center gap-1 text-sm font-medium"><span class="truncate">' +
          escapeHtml(post.user.name) +
          '</span>' +
          tick +
          '</p><p class="text-ink-subtle text-xs"><a href="' +
          postUrl +
          '" class="hover:text-ink rounded-sm hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">' +
          escapeHtml(post.created_at) +
          '</a> · ' +
          escapeHtml(post.category) +
          '</p></div></div>' +
          truncateContent(post.content) +
          priceHtml +
          buildImageGrid(post.image_urls) +
          '</article>'
      );
    }

    return {
      reportedIds: reportedIds,
      REPORTED_CLASS: REPORTED_CLASS,
      emptyStateHtml: emptyStateHtml,
      render: render,
    };
  })();
</script>
