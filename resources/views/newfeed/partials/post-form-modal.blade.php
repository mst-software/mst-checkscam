{{-- New post form; submitted by submitPost() to POST /api/newfeed/posts. Expects: $categories. --}}
@php
  $fieldClass =
    'border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent w-full border px-3 py-2.5 text-base sm:text-sm focus-visible:outline-2 focus-visible:outline-offset-1';
  $toolClass =
    'text-ink-muted hover:bg-surface-muted hover:text-ink rounded-control h-8 min-w-8 cursor-pointer px-2 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent';
@endphp

@component('newfeed.partials.modal', ['id' => 'popup-post-form', 'title' => 'Đăng bài mới', 'size' => 'lg'])
  <div class="space-y-4">
    <div>
      <label for="nf-content" class="text-ink mb-1.5 block text-sm font-medium">
        Nội dung
        <span class="text-danger" aria-hidden="true">*</span>
      </label>
      <div class="mb-1 flex gap-1" role="toolbar" aria-label="Định dạng văn bản">
        <button
          type="button"
          onclick="formatText('bold')"
          class="{{ $toolClass }} font-bold"
          title="In đậm đoạn đang chọn"
          aria-label="In đậm"
        >
          B
        </button>
        <button
          type="button"
          onclick="formatText('italic')"
          class="{{ $toolClass }} italic"
          title="In nghiêng đoạn đang chọn"
          aria-label="In nghiêng"
        >
          I
        </button>
        <button
          type="button"
          onclick="formatText('underline')"
          class="{{ $toolClass }} underline"
          title="Gạch chân đoạn đang chọn"
          aria-label="Gạch chân"
        >
          U
        </button>
      </div>
      <textarea
        id="nf-content"
        rows="5"
        maxlength="5000"
        placeholder="Mô tả sản phẩm, dịch vụ và cách liên hệ (tối thiểu 10 ký tự)"
        aria-describedby="nf-content-hint"
        class="{{ $fieldClass }} resize-y leading-relaxed"
      ></textarea>
      <p id="nf-content-hint" class="text-ink-subtle mt-1 flex justify-end text-xs tabular-nums">
        <span id="nf-content-count">0</span>
        <span>/5000</span>
      </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label for="nf-price" class="text-ink mb-1.5 block text-sm font-medium">
          Giá (đồng)
          <span class="text-ink-subtle font-normal">(tuỳ chọn)</span>
        </label>
        <input
          id="nf-price"
          type="number"
          inputmode="numeric"
          min="0"
          max="999999999"
          placeholder="0"
          class="{{ $fieldClass }}"
        />
      </div>

      <div>
        <label for="nf-category" class="text-ink mb-1.5 block text-sm font-medium">
          Danh mục
          <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <input
          id="nf-category"
          type="text"
          list="nf-category-options"
          maxlength="100"
          placeholder="Chọn hoặc nhập danh mục"
          autocomplete="off"
          class="{{ $fieldClass }}"
        />
        <datalist id="nf-category-options">
          @foreach ($categories as $cat)
            <option value="{{ $cat }}"></option>
          @endforeach
        </datalist>
      </div>
    </div>

    <div>
      <p class="text-ink mb-1.5 text-sm font-medium" id="nf-images-label">
        Ảnh
        <span class="text-ink-subtle font-normal">(tuỳ chọn, tối đa 10 ảnh, mỗi ảnh 5MB)</span>
      </p>
      <input
        id="nf-images"
        type="file"
        accept="image/jpg,image/jpeg,image/png,image/webp"
        multiple
        class="hidden"
        aria-labelledby="nf-images-label"
      />
      <button
        type="button"
        id="nf-image-drop"
        onclick="document.getElementById('nf-images').click()"
        class="border-line text-ink-muted hover:border-line-strong hover:text-ink rounded-control flex w-full cursor-pointer flex-col items-center justify-center border border-dashed py-6 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      >
        <i class="fa-regular fa-images mb-2 text-xl" aria-hidden="true"></i>
        Chọn ảnh (tối đa 10)
      </button>
      <div id="nf-image-preview" class="mt-2 hidden">
        <div id="nf-preview-grid" class="grid grid-cols-3 gap-2"></div>
        <p class="text-ink-subtle mt-2 text-xs">Kéo để sắp xếp thứ tự ảnh.</p>
        <x-ucs.button variant="secondary" size="sm" icon="fa-solid fa-trash-can" class="mt-2" onclick="clearImages()">
          Xoá tất cả ảnh
        </x-ucs.button>
      </div>
    </div>

    <div
      class="border-line text-ink-muted rounded-control flex items-center gap-2 border border-dashed px-3 py-2 text-sm"
    >
      <i class="fa-solid fa-wand-magic-sparkles text-ink-subtle" aria-hidden="true"></i>
      AI tạo nội dung
      <x-ucs.badge class="ml-auto">Sắp ra mắt</x-ucs.badge>
    </div>
  </div>

  @slot('footer')
    <div class="bg-surface-muted text-ink-muted rounded-control mb-3 px-3 py-2 text-xs leading-relaxed">
      <strong class="text-ink font-semibold">Mẹo:</strong>
      bài đăng có thông tin liên hệ rõ ràng sẽ được tiếp cận nhanh hơn.
      <strong class="text-ink font-semibold">Lưu ý:</strong>
      không thể chỉnh sửa bài sau khi đăng, bài sẽ bị ẩn nếu nhận đủ báo cáo.
    </div>
    <x-ucs.button id="btn-submit-post" class="w-full" onclick="submitPost()" disabled>Đăng bài</x-ucs.button>
  @endslot
@endcomponent
