{{--
    Comment form and list. Works without JavaScript (normal POST + redirect back) and is enhanced with fetch.
    Expects: $report, $comments (each with a redacted `display_content`).
--}}
@php
  $commentCount = $comments->count();
  $hasFormErrors = $errors->has("full_name") || $errors->has("content");
@endphp

<section id="comment-section" aria-labelledby="comment-heading">
  <x-ucs.card>
    <x-slot:header>
      <h2 id="comment-heading" class="text-ink text-base font-semibold">Bình luận cộng đồng</h2>
      <p class="text-ink-subtle mt-0.5 text-sm">
        <span data-comment-count>{{ number_format($commentCount, 0, ",", ".") }}</span>
        bình luận
      </p>
    </x-slot>

    <form
      action="{{ route('comment.store', $report->id) }}"
      method="POST"
      class="space-y-3"
      data-comment-form
      novalidate
    >
      @csrf

      <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1" data-comment-name-field>
          <label for="comment-full-name" class="text-ink mb-1 block text-sm font-medium">Tên hiển thị</label>
          <input
            type="text"
            name="full_name"
            id="comment-full-name"
            value="{{ old('full_name') }}"
            maxlength="100"
            autocomplete="nickname"
            placeholder="Ví dụ: Minh Anh"
            @error("full_name")
                aria-invalid="true"
            @enderror
            class="rounded-control border-line bg-surface text-ink placeholder:text-ink-subtle focus:border-accent focus:ring-accent h-10 w-full border px-3 text-sm outline-none focus:ring-1 disabled:opacity-50"
          />
        </div>

        <label
          for="comment-anonymous"
          class="rounded-control border-line text-ink flex h-10 cursor-pointer items-center gap-2 border px-3 text-sm select-none"
        >
          <input
            type="checkbox"
            name="is_anonymous"
            id="comment-anonymous"
            value="1"
            @checked(old("is_anonymous"))
            class="accent-accent h-4 w-4"
          />
          Ẩn danh
        </label>
      </div>

      <div>
        <label for="comment-content" class="text-ink mb-1 block text-sm font-medium">Nội dung</label>
        <textarea
          name="content"
          id="comment-content"
          rows="4"
          minlength="10"
          maxlength="1000"
          placeholder="Chia sẻ thêm thông tin về đối tượng này (tối thiểu 10 ký tự)..."
          @error("content")
              aria-invalid="true"
          @enderror
          class="rounded-control border-line bg-surface text-ink placeholder:text-ink-subtle focus:border-accent focus:ring-accent w-full resize-y border p-3 text-sm outline-none focus:ring-1"
        >
{{ old("content") }}</textarea
        >
      </div>

      <div
        class="rounded-control border-danger/25 bg-danger-soft text-danger border px-3 py-2 text-sm"
        role="alert"
        data-comment-error
        @if (! session('error') && ! $hasFormErrors) hidden @endif
      >
        @if (session("error"))
          {{ session("error") }}
        @elseif ($hasFormErrors)
          {{ $errors->first("full_name") ?: $errors->first("content") }}
        @endif
      </div>
      <div
        class="rounded-control border-success/25 bg-success-soft text-success border px-3 py-2 text-sm"
        role="status"
        data-comment-success
        @if (! session('success')) hidden @endif
      >
        {{ session("success") }}
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-ink-subtle text-xs">
          <span data-comment-chars>0</span>
          / 1000 ký tự · Tối đa 5 bình luận mỗi ngày. Không đăng thông tin cá nhân của bạn.
        </p>
        <x-ucs.button type="submit" icon="fa-regular fa-paper-plane" class="w-full sm:w-auto" data-comment-submit>
          Gửi bình luận
        </x-ucs.button>
      </div>
    </form>

    <div class="border-line mt-6 border-t pt-6">
      <ul class="space-y-5" data-comment-list @if ($commentCount === 0) hidden @endif>
        @foreach ($comments as $comment)
          @include(
              "scammer.partials.comment-item",
              [
                  "commentId" => $comment->id,
                  "authorName" => $comment->display_name,
                  "content" => $comment->display_content,
                  "timeLabel" => $comment->created_at->locale("vi")->diffForHumans(),
                  "isAnonymous" => $comment->is_anonymous,
                  "canModify" => $comment->canModify(request()->ip()),
              ]
          )
        @endforeach
      </ul>

      <x-ucs.empty-state
        title="Chưa có bình luận nào"
        description="Hãy là người đầu tiên chia sẻ thông tin về đối tượng này."
        icon="fa-regular fa-comments"
        data-comment-empty
        :hidden="$commentCount > 0"
      />
    </div>
  </x-ucs.card>
</section>

<template id="comment-item-template">
  @include(
      "scammer.partials.comment-item",
      [
          "commentId" => "",
          "authorName" => "",
          "content" => "",
          "timeLabel" => "",
          "isAnonymous" => false,
          "canModify" => true,
      ]
  )
</template>

@push("scripts")
  <script>
    (function () {
      const form = document.querySelector('[data-comment-form]');
      if (!form) return;

      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const list = document.querySelector('[data-comment-list]');
      const empty = document.querySelector('[data-comment-empty]');
      const countLabel = document.querySelector('[data-comment-count]');
      const template = document.getElementById('comment-item-template');
      const errorBox = form.querySelector('[data-comment-error]');
      const successBox = form.querySelector('[data-comment-success]');
      const nameInput = form.querySelector('#comment-full-name');
      const anonymous = form.querySelector('#comment-anonymous');
      const content = form.querySelector('#comment-content');
      const chars = form.querySelector('[data-comment-chars]');
      const submit = form.querySelector('[data-comment-submit]');
      const commentUrl = @json(route("comment.update", ["id" => "__ID__"]));

      const showMessage = (box, message) => {
        errorBox.hidden = successBox.hidden = true;
        box.textContent = message;
        box.hidden = false;
      };

      const firstError = (data, fallback) => {
        const errors = data && data.errors;
        if (!errors) return fallback;
        const first = errors.general || errors[Object.keys(errors)[0]];
        return (first && first[0]) || fallback;
      };

      const request = async (url, body) => {
        const response = await fetch(url, {
          method: 'POST',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
          body,
        });
        const data = await response.json().catch(() => ({}));
        return { ok: response.ok, data };
      };

      const syncCount = () => {
        const count = list.querySelectorAll('[data-comment-item]').length;
        countLabel.textContent = count.toLocaleString('vi-VN');
        list.hidden = count === 0;
        empty.hidden = count > 0;
      };

      const syncName = () => {
        nameInput.disabled = anonymous.checked;
        if (anonymous.checked) nameInput.value = '';
      };

      const updateChars = () => {
        chars.textContent = content.value.length;
      };

      const buildItem = (comment) => {
        const item = template.content.firstElementChild.cloneNode(true);
        item.dataset.commentId = comment.id;
        item.querySelector('[data-comment-author]').textContent = comment.display_name;
        item.querySelector('[data-comment-time]').textContent = comment.created_at;
        item.querySelector('[data-comment-content]').textContent = comment.content;
        item.querySelector('textarea').value = comment.content;
        item.querySelector('textarea').id = 'comment-edit-' + comment.id;
        item.querySelector('label[for]').htmlFor = 'comment-edit-' + comment.id;

        const avatar = item.querySelector('[data-comment-avatar]');
        if (comment.is_anon) {
          avatar.innerHTML = '<i class="fa-solid fa-user-secret"></i>';
        } else {
          avatar.textContent = comment.initials;
        }

        return item;
      };

      anonymous.addEventListener('change', syncName);
      content.addEventListener('input', updateChars);
      syncName();
      updateChars();

      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const text = content.value.trim();

        if (!text) return showMessage(errorBox, 'Nội dung bình luận không được để trống.');
        if (text.length < 10) return showMessage(errorBox, 'Bình luận cần ít nhất 10 ký tự.');
        if (!anonymous.checked && !nameInput.value.trim()) {
          return showMessage(errorBox, 'Vui lòng nhập tên hiển thị hoặc chọn ẩn danh.');
        }

        submit.disabled = true;
        try {
          const { ok, data } = await request(form.action, new FormData(form));
          if (!ok) return showMessage(errorBox, firstError(data, 'Có lỗi xảy ra, vui lòng thử lại.'));

          list.prepend(buildItem(data.comment));
          syncCount();
          form.reset();
          syncName();
          updateChars();
          showMessage(successBox, data.message);
        } catch (e) {
          showMessage(errorBox, 'Không thể kết nối máy chủ, vui lòng thử lại.');
        } finally {
          submit.disabled = false;
        }
      });

      list.addEventListener('click', async (event) => {
        const item = event.target.closest('[data-comment-item]');
        if (!item) return;

        const editForm = item.querySelector('[data-comment-edit-form]');
        const body = item.querySelector('[data-comment-content]');
        const tools = item.querySelector('[data-comment-tools]');
        const url = commentUrl.replace('__ID__', item.dataset.commentId);

        if (event.target.closest('[data-comment-edit]')) {
          editForm.hidden = false;
          body.hidden = tools.hidden = true;
          editForm.querySelector('textarea').focus();
        }

        if (event.target.closest('[data-comment-edit-cancel]')) {
          editForm.hidden = true;
          body.hidden = tools.hidden = false;
        }

        if (event.target.closest('[data-comment-edit-save]')) {
          const value = editForm.querySelector('textarea').value.trim();
          if (value.length < 10) return alert('Bình luận cần ít nhất 10 ký tự.');

          const payload = new FormData();
          payload.append('_method', 'PATCH');
          payload.append('content', value);
          const { ok, data } = await request(url, payload);
          if (!ok) return alert(firstError(data, 'Không thể chỉnh sửa. Có thể đã quá 15 phút.'));

          body.textContent = data.content;
          editForm.hidden = true;
          body.hidden = tools.hidden = false;
        }

        if (event.target.closest('[data-comment-delete]')) {
          if (!confirm('Bạn có chắc muốn xoá bình luận này không?')) return;

          const payload = new FormData();
          payload.append('_method', 'DELETE');
          const { ok, data } = await request(url, payload);
          if (!ok) return alert(firstError(data, 'Không thể xoá. Có thể đã quá 15 phút.'));

          item.remove();
          syncCount();
        }
      });
    })();
  </script>
@endpush
