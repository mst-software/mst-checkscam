{{--
  Marketplace page behaviour: infinite scroll and filters over GET /api/newfeed/posts, post form (POST),
  delete (DELETE) and report (POST .../report). Expects: $posts, $userReportedIds.
--}}
@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css" />
@endpush

@push('scripts')
  <script>
    window._authUserId = @json(auth()->id());
    window._csrfToken = @json(csrf_token());
  </script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js"></script>
  @include('newfeed.partials.popup-scripts')
  <script>
    Fancybox.bind('[data-fancybox]', {
      animated: false,
      Toolbar: { display: { left: [], middle: [], right: ['close'] } },
    });

    window.openCardGallery = function (images, startIndex) {
      Fancybox.show(
        images.map(function (src) {
          return { src: src, type: 'image' };
        }),
        {
          startIndex: startIndex || 0,
          animated: false,
          Toolbar: { display: { left: [], middle: [], right: ['close'] } },
        }
      );
    };

    $(function () {
      // ===== State =====
      let currentPage = {{ $posts->currentPage() }};
      const lastPage = {{ $posts->lastPage() }};
      let loading = false;
      let currentCategory = '';
      let currentQ = '';
      let searchTimer = null;
      const userReportedIds = @json($userReportedIds);

      const $feed = $('#nf-feed');
      const $sentinel = $('#nf-sentinel');
      const $loader = $('#nf-loader');
      const $nfContent = $('#nf-content');
      const $catInput = $('#nf-category');
      const $btnSubmit = $('#btn-submit-post');

      // ===== Category filter chips =====
      $('.nf-chip').on('click', function () {
        currentCategory = $(this).data('category') || '';
        currentPage = 1;
        $('.nf-chip').attr('aria-pressed', 'false');
        $(this).attr('aria-pressed', 'true');
        reloadFeed(true);
      });

      // ===== Search =====
      $('#nf-search').on('input', function () {
        clearTimeout(searchTimer);
        const val = $(this).val().trim();
        searchTimer = setTimeout(function () {
          currentQ = val;
          currentPage = 1;
          reloadFeed(true);
        }, 400);
      });

      function emptyStateHtml(text) {
        return (
          '<div class="nf-empty-state border-line text-ink-subtle rounded-card flex w-full flex-col items-center justify-center border border-dashed py-16 text-center text-sm [column-span:all]">' +
          '<i class="fa-regular fa-folder-open mb-3 text-2xl" aria-hidden="true"></i>' +
          escapeHtml(text) +
          '</div>'
        );
      }

      // ===== Load posts from API =====
      function loadPosts(page, replace) {
        if (loading) return;
        loading = true;
        $loader.removeClass('hidden');

        const params = { page: page, per_page: 12 };
        if (currentCategory) params.category = currentCategory;
        if (currentQ) params.q = currentQ;

        $.get('/api/newfeed/posts', params)
          .done(function (data) {
            if (replace) $feed.empty();
            if (data.data.length === 0 && replace) {
              $feed.html(emptyStateHtml('Không tìm thấy bài đăng nào.'));
            } else {
              $.each(data.data, function (i, post) {
                $feed.append(renderCard(post));
              });
            }
            currentPage = data.current_page;
            if (!data.has_more) {
              $sentinel.html('<p class="text-ink-subtle text-sm">Đã xem hết bài</p>');
            }
          })
          .fail(function (e) {
            console.error('Load posts error:', e);
          })
          .always(function () {
            loading = false;
            $loader.addClass('hidden');
          });
      }

      function reloadFeed(replace) {
        loadPosts(1, replace !== false);
      }
      window.reloadFeed = reloadFeed;

      // ===== HTML escape + inline markdown renderer (mirrors StringHelper::formatPostContent) =====
      function escapeHtml(s) {
        return $('<div>').text(s).html().replace(/"/g, '&quot;');
      }

      function formatPostContent(text) {
        let s = escapeHtml(text);
        s = s.replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>');
        s = s.replace(/__(.+?)__/gs, '<u>$1</u>');
        s = s.replace(/\*(.+?)\*/gs, '<em>$1</em>');
        return s.replace(/\n/g, '<br>');
      }

      // ===== Image grid builder (mirrors newfeed/partials/card) =====
      const SHOW_MAX = 4;
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

      // ===== Truncate content (card click opens the detail page) =====
      const CONTENT_LIMIT = 200;
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

      const ACTION_CLASS =
        'rounded-control absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent';
      const REPORTED_CLASS = ACTION_CLASS + ' text-ink-subtle cursor-not-allowed opacity-70';

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
        if (userReportedIds.includes(post.id) || post.is_reported) {
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

      // ===== Render card from API JSON (mirrors newfeed/partials/card) =====
      function renderCard(post) {
        const postUrl = '/newfeed/' + post.id;
        const tick = post.user.is_verified
          ? '<i class="fa-solid fa-circle-check text-success text-xs" title="Tài khoản đã được CheckScam xác minh uy tín" aria-hidden="true"></i><span class="sr-only">(đã xác minh)</span>'
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

      // ===== Infinite scroll =====
      if (lastPage > 1) {
        const observer = new IntersectionObserver(
          function (entries) {
            if (entries[0].isIntersecting && currentPage < lastPage) {
              loadPosts(currentPage + 1, false);
            }
          },
          { threshold: 0.1 }
        );
        observer.observe($sentinel[0]);
      }

      // ===== Card click → detail page =====
      $feed.on('click', '.nf-card', function (e) {
        if ($(e.target).closest('[data-fancybox], [data-no-nav], button, a').length) return;
        window.location.href = $(this).data('post-url');
      });

      // ===== Open post form (auth check) =====
      $('#btn-open-post-form, #btn-fab').on('click', function () {
        window._authUserId ? openPopup('popup-post-form') : openPopup('popup-login');
      });

      // ===== Content counter + form validation =====
      $catInput.on('input', validateForm);
      $nfContent.on('input', function () {
        $('#nf-content-count').text(this.value.length);
        validateForm();
      });
      function validateForm() {
        $btnSubmit.prop('disabled', $nfContent.val().trim().length < 10 || $catInput.val().trim().length === 0);
      }

      // ===== Multi-image upload (drag reorder + per-image delete) =====
      let selectedFiles = [];

      Sortable.create(document.getElementById('nf-preview-grid'), {
        animation: 150,
        ghostClass: 'opacity-40',
        onEnd: function (evt) {
          const moved = selectedFiles.splice(evt.oldIndex, 1)[0];
          selectedFiles.splice(evt.newIndex, 0, moved);
        },
      });

      function renderPreviews() {
        const $grid = $('#nf-preview-grid').empty();
        selectedFiles.forEach(function (file, idx) {
          const reader = new FileReader();
          reader.onload = function (e) {
            $grid
              .find('[data-pending="' + idx + '"]')
              .find('img')
              .attr('src', e.target.result);
          };
          $grid.append(
            $(
              '<div data-pending="' +
                idx +
                '" class="border-line rounded-control relative cursor-grab overflow-hidden border">' +
                '<img src="" alt="Ảnh ' +
                (idx + 1) +
                '" class="h-20 w-full object-cover" />' +
                '<button type="button" data-no-nav onclick="removeUploadFile(' +
                idx +
                ')" class="bg-danger absolute top-1 right-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full text-xs text-white focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent" aria-label="Xóa ảnh ' +
                (idx + 1) +
                '"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
                '</div>'
            )
          );
          reader.readAsDataURL(file);
        });

        const hasFiles = selectedFiles.length > 0;
        $('#nf-image-preview').toggleClass('hidden', !hasFiles);
        $('#nf-image-drop').toggleClass('hidden', hasFiles);
      }

      $('#nf-images').on('change', function () {
        Array.from(this.files || []).forEach(function (f) {
          if (selectedFiles.length < 10) selectedFiles.push(f);
        });
        this.value = '';
        renderPreviews();
      });

      window.removeUploadFile = function (idx) {
        selectedFiles.splice(idx, 1);
        renderPreviews();
      };

      window.clearImages = function () {
        selectedFiles = [];
        renderPreviews();
      };

      // ===== Format text (wrap textarea selection with markdown markers) =====
      window.formatText = function (type) {
        const marker = { bold: '**', italic: '*', underline: '__' }[type];
        const ta = $nfContent[0];
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        const val = ta.value;
        const selected = val.slice(start, end) || 'văn bản';
        ta.value = val.slice(0, start) + marker + selected + marker + val.slice(end);
        const cursor = start + marker.length * 2 + selected.length;
        ta.focus();
        ta.setSelectionRange(cursor, cursor);
        $nfContent.trigger('input');
      };

      // ===== Submit new post =====
      window.submitPost = function () {
        $btnSubmit.prop('disabled', true).text('Đang đăng...');
        const fd = new FormData();
        fd.append('content', $nfContent.val().trim());
        fd.append('category', $catInput.val().trim());
        const price = $('#nf-price').val();
        if (price) fd.append('price', price);
        selectedFiles.forEach(function (f) {
          fd.append('images[]', f);
        });
        fd.append('_token', window._csrfToken);

        $.ajax({ url: '/api/newfeed/posts', method: 'POST', data: fd, processData: false, contentType: false })
          .done(function (data) {
            $feed.find('.nf-empty-state').remove();
            $feed.prepend(renderCard(data.post));
            closePopup('popup-post-form');
            resetPostForm();
          })
          .fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'Có lỗi xảy ra khi đăng bài.');
          })
          .always(function () {
            validateForm();
            $btnSubmit.text('Đăng bài');
          });
      };

      function resetPostForm() {
        $nfContent.val('');
        $('#nf-content-count').text('0');
        $('#nf-price').val('');
        $catInput.val('');
        clearImages();
        validateForm();
      }

      // ===== Delete post =====
      window.deletePost = function (postId, btn) {
        if (!confirm('Bạn có chắc muốn xóa bài đăng này?')) return;
        $.ajax({
          url: '/api/newfeed/posts/' + postId,
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': window._csrfToken, Accept: 'application/json' },
        })
          .done(function () {
            $(btn).closest('.nf-card').remove();
          })
          .fail(function () {
            alert('Không thể xóa bài này.');
          });
      };

      // ===== Report =====
      window.openReport = function (postId) {
        $('#report-post-id').val(postId);
        $('input[name="report-reason"]').prop('checked', false);
        $('#btn-submit-report').prop('disabled', true);
        openPopup('popup-report');
      };
      $(document).on('change', 'input[name="report-reason"]', function () {
        $('#btn-submit-report').prop('disabled', false);
      });
      window.submitReport = function () {
        const postId = $('#report-post-id').val();
        const reason = $('input[name="report-reason"]:checked').val();
        if (!reason) {
          alert('Vui lòng chọn lý do báo cáo.');
          return;
        }

        $.ajax({
          url: '/api/newfeed/posts/' + postId + '/report',
          method: 'POST',
          contentType: 'application/json',
          headers: { 'X-CSRF-TOKEN': window._csrfToken, Accept: 'application/json' },
          data: JSON.stringify({ reason: reason }),
        })
          .done(function () {
            closePopup('popup-report');
            userReportedIds.push(parseInt(postId));
            $('.nf-card[data-id="' + postId + '"]')
              .find('[title="Báo cáo"]')
              .attr({ title: 'Đã báo cáo', 'aria-label': 'Bạn đã báo cáo bài đăng này' })
              .removeAttr('onclick')
              .prop('disabled', true)
              .attr('class', REPORTED_CLASS)
              .html('<i class="fa-solid fa-flag" aria-hidden="true"></i>');
          })
          .fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.error) || 'Không thể gửi báo cáo.');
          });
      };
    });
  </script>
@endpush
