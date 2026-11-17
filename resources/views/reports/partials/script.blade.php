<script>
  $(function () {
    const $form = $('#report-form');
    if (!$form.length) return;

    const form = $form.get(0);
    const TYPES = ['account', 'website', 'back'];
    const MAX_FILES = 10;
    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    const MIN_DESCRIPTION = 20;
    const MAX_DESCRIPTION = 5000;
    // Capacity of the decimal(10,2) column, mirrored from ReportController::MAX_DAMAGE_AMOUNT.
    const MAX_AMOUNT = 99999999;
    const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const DRAFT_KEY = 'report-form-draft-v1';
    const DRAFT_TEXT_FIELDS = [
      'target_id',
      'target_name',
      'target_bank',
      'target_socials',
      'sold_account',
      'damage_amount',
      'description',
    ];
    // Reporter email, contact and name are personal data and never stored in the browser draft.
    const DRAFT_RADIO_FIELDS = ['type', 'scam_type', 'reporter_role'];
    // Order used to pick the first invalid field to focus.
    const FIELD_ORDER = [
      'type',
      'target_id',
      'target_name',
      'target_bank',
      'target_socials',
      'sold_account',
      'scam_type',
      'damage_amount',
      'description',
      'evidence_images',
      'reporter_email',
      'reporter_contact',
      'reporter_name',
      'reporter_role',
      'confirm',
    ];

    const $alert = $('#report-form-alert');
    const $fileInput = $('#evidence_images');
    const $preview = $form.find('[data-evidence-preview]');
    const $submit = $form.find('[data-submit-btn]');
    const $tabs = $form.find('[data-type-tab]');

    let files = [];
    const dirty = {};

    form.noValidate = true;

    // ---------- helpers ----------
    function value(name) {
      const $el = $form.find('[name="' + name + '"]').not(':radio');
      return $el.length ? String($el.val() || '').trim() : '';
    }

    function selectedType() {
      return $form.find('[name="type"]:checked').val() || 'account';
    }

    function digits(text) {
      return String(text || '').replace(/\D/g, '');
    }

    function setError(field, message) {
      const $error = $('#error-' + field);
      $error.find('[data-error-text]').text(message || '');
      $error.prop('hidden', !message);
      const $controls = $form.find('[data-field="' + field + '"]');
      if (message) {
        $controls.attr('aria-invalid', 'true');
      } else {
        $controls.removeAttr('aria-invalid');
      }
    }

    function showAlert(title, messages) {
      $alert.find('[data-alert-title]').text(title);
      const $list = $alert.find('[data-alert-list]').empty();
      (messages || []).forEach(function (message) {
        $('<li>').text(message).appendTo($list);
      });
      $list.prop('hidden', !(messages && messages.length));
      $alert.prop('hidden', false);
    }

    function hideAlert() {
      $alert.prop('hidden', true);
    }

    function focusFirstInvalid() {
      for (const field of FIELD_ORDER) {
        const $control = $form.find('[data-field="' + field + '"][aria-invalid="true"]').first();
        if ($control.length && !$control.prop('disabled')) {
          // Visually hidden inputs (file, chips) scroll their visible wrapper into view instead.
          const target = $control.is('.sr-only') ? $control.closest('label, fieldset').get(0) : $control.get(0);
          target.scrollIntoView({ block: 'center', behavior: 'smooth' });
          $control.get(0).focus({ preventScroll: true });
          return true;
        }
      }
      return false;
    }

    // ---------- validation ----------
    const validators = {
      target_id: function () {
        const targetId = value('target_id');
        const type = selectedType();
        if (!targetId) {
          return {
            account: 'Vui lòng nhập số tài khoản, SĐT hoặc link của đối tượng.',
            website: 'Vui lòng nhập link website lừa đảo.',
            back: 'Vui lòng nhập SĐT, Zalo hoặc link Facebook của người bán.',
          }[type];
        }
        if (type === 'website' && !/^\S+\.\S+$/.test(targetId)) {
          return 'Link website chưa đúng, ví dụ: https://ten-mien.com';
        }
        return '';
      },
      damage_amount: function () {
        const amount = value('damage_amount');
        if (!amount) return 'Vui lòng nhập số tiền thiệt hại (nhập 0 nếu chưa mất tiền).';
        if (!/^[\d.,\s]+$/.test(amount)) return 'Số tiền thiệt hại phải là chữ số.';
        if (Number(digits(amount)) > MAX_AMOUNT) {
          return 'Số tiền tối đa là 99.999.999 VNĐ. Nếu thiệt hại lớn hơn, hãy ghi rõ số tiền trong phần kể lại sự việc.';
        }
        return '';
      },
      description: function () {
        const length = value('description').length;
        if (!length) return 'Vui lòng kể lại sự việc.';
        if (length < MIN_DESCRIPTION) {
          return 'Vui lòng mô tả sự việc với ít nhất ' + MIN_DESCRIPTION + ' ký tự (hiện có ' + length + ').';
        }
        if (length > MAX_DESCRIPTION) return 'Phần kể lại sự việc không được dài quá ' + MAX_DESCRIPTION + ' ký tự.';
        return '';
      },
      evidence_images: function () {
        return files.length ? '' : 'Vui lòng tải lên ít nhất 1 ảnh bằng chứng (bill chuyển tiền, đoạn chat).';
      },
      reporter_email: function () {
        const email = value('reporter_email');
        if (!email) return 'Vui lòng nhập email để nhận kết quả kiểm duyệt.';
        if (!/^[^\s@]+@[^\s@]+\.[^\s@.]+$/.test(email)) return 'Email không đúng định dạng, ví dụ: ten@gmail.com';
        return '';
      },
      reporter_contact: function () {
        return value('reporter_contact') ? '' : 'Vui lòng nhập số Zalo hoặc SĐT để kiểm duyệt viên liên hệ khi cần.';
      },
      reporter_role: function () {
        return $form.find('[name="reporter_role"]:checked').length ? '' : 'Vui lòng chọn vai trò của bạn.';
      },
      confirm: function () {
        return $('#confirm_truth').is(':checked') ? '' : 'Vui lòng xác nhận cam kết trước khi gửi.';
      },
    };

    function validateField(field) {
      const message = validators[field] ? validators[field]() : '';
      setError(field, message);
      return message;
    }

    function validateAll() {
      return Object.keys(validators).map(validateField).filter(Boolean);
    }

    // Validate on blur once the field was edited; re-check live while it is flagged.
    $form.on('input change', '[data-field]', function () {
      const field = $(this).attr('data-field');
      dirty[field] = true;
      // addFiles() owns the evidence error, so its specific reason (type, size, count) is not overwritten.
      if (field === 'evidence_images') return;
      if ($(this).attr('aria-invalid') === 'true' && validators[field]) validateField(field);
    });

    $form.on('blur', 'input[data-field], textarea[data-field]', function () {
      const field = $(this).attr('data-field');
      if (dirty[field] && validators[field] && field !== 'evidence_images') validateField(field);
    });

    // ---------- type tabs ----------
    function setType(type, options) {
      const settings = $.extend({ focus: false }, options);
      if (!TYPES.includes(type)) type = 'account';

      $('#type-' + type).prop('checked', true);
      $tabs.each(function () {
        const isActive = $(this).attr('data-type-tab') === type;
        $(this)
          .attr('aria-selected', isActive ? 'true' : 'false')
          .attr('tabindex', isActive ? '0' : '-1');
        if (isActive && settings.focus) $(this).trigger('focus');
      });
      $('#report-panel').attr('aria-labelledby', 'report-tab-' + type);

      $form.find('[data-placeholders]').each(function () {
        const placeholders = JSON.parse($(this).attr('data-placeholders'));
        if (placeholders[type]) $(this).attr('placeholder', placeholders[type]);
      });

      // Fields of other tabs are not sent, so a stale value never reaches the server.
      $form.find('[data-show-for]').each(function () {
        const shown = $(this).attr('data-show-for').split(' ').includes(type);
        $(this).find('input, select, textarea').prop('disabled', !shown);
      });

      if (dirty.target_id) validateField('target_id');
    }

    $tabs.on('click', function () {
      setType($(this).attr('data-type-tab'));
      saveDraft();
    });

    $tabs.on('keydown', function (event) {
      const index = $tabs.index(this);
      const last = $tabs.length - 1;
      const next = {
        ArrowRight: index === last ? 0 : index + 1,
        ArrowLeft: index === 0 ? last : index - 1,
        Home: 0,
        End: last,
      }[event.key];
      if (next === undefined) return;
      event.preventDefault();
      setType($tabs.eq(next).attr('data-type-tab'), { focus: true });
      saveDraft();
    });

    $form.on('change', '[name="type"]', function () {
      setType(selectedType());
    });

    // ---------- scam type ----------
    function syncCategory() {
      const $checked = $form.find('[name="scam_type"]:checked');
      $form.find('[data-category-input]').val($checked.attr('data-label') || '');
    }

    $form.on('change', '[name="scam_type"]', syncCategory);

    // ---------- amount: live thousand separators ----------
    const $amount = $('#damage_amount');

    function formatAmount() {
      const input = $amount.get(0);
      const allDigits = digits(input.value);
      const raw = allDigits.replace(/^0+(?=\d)/, '');
      // Keep the caret after the same digit it followed, so editing in the middle does not jump to the end.
      const caret = input.selectionStart === null ? input.value.length : input.selectionStart;
      const digitsBefore = Math.max(0, digits(input.value.slice(0, caret)).length - (allDigits.length - raw.length));

      input.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

      if (document.activeElement !== input) return;
      let position = 0;
      for (let seen = 0; position < input.value.length && seen < digitsBefore; position++) {
        if (/\d/.test(input.value[position])) seen++;
      }
      input.setSelectionRange(position, position);
    }

    $amount.on('input', formatAmount);

    // ---------- description counter ----------
    const $descriptionCount = $form.find('[data-description-count]');

    function updateDescriptionCount() {
      $descriptionCount.text(value('description').length + '/' + MIN_DESCRIPTION);
    }

    $('#description').on('input', updateDescriptionCount);

    // ---------- evidence images ----------
    function addFiles(fileList) {
      const problems = [];

      Array.from(fileList).forEach(function (file) {
        if (files.some((item) => item.file.name === file.name && item.file.size === file.size)) return;
        if (!ACCEPTED_TYPES.includes(file.type)) {
          problems.push('Ảnh "' + file.name + '" không đúng định dạng (chỉ nhận JPG, PNG, GIF, WEBP).');
          return;
        }
        if (file.size > MAX_FILE_SIZE) {
          problems.push('Ảnh "' + file.name + '" lớn hơn 5MB. Thử gửi qua Zalo rồi tải về để ảnh nhẹ hơn.');
          return;
        }
        if (files.length >= MAX_FILES) {
          problems.push('Chỉ được tải lên tối đa ' + MAX_FILES + ' ảnh.');
          return;
        }
        files.push({ file: file, url: URL.createObjectURL(file) });
      });

      syncFiles();
      setError('evidence_images', [...new Set(problems)].join(' '));
    }

    function removeFile(index) {
      URL.revokeObjectURL(files[index].url);
      files.splice(index, 1);
      syncFiles();
      const $remaining = $preview.find('[data-remove-index]');
      if ($remaining.length) {
        $remaining.eq(Math.min(index, $remaining.length - 1)).trigger('focus');
      } else {
        $fileInput.trigger('focus');
      }
    }

    function syncFiles() {
      const transfer = new DataTransfer();
      files.forEach((item) => transfer.items.add(item.file));
      $fileInput.get(0).files = transfer.files;

      $preview.empty();
      files.forEach(function (item, index) {
        const $item = $('<li>', { class: 'relative' });
        const $open = $('<button>', {
          type: 'button',
          class:
            'border-line block aspect-square w-full cursor-zoom-in overflow-hidden rounded-lg border focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
          'aria-label': 'Xem ảnh ' + (index + 1) + ': ' + item.file.name,
        }).on('click', () => openLightbox(index));
        $('<img>', { src: item.url, alt: '', class: 'h-full w-full object-cover' }).appendTo($open);

        const $remove = $('<button>', {
          type: 'button',
          class:
            'border-line bg-surface text-ink-muted hover:text-danger absolute -top-2 -right-2 flex size-7 cursor-pointer items-center justify-center rounded-full border text-xs focus-visible:outline-2 focus-visible:outline-accent',
          'aria-label': 'Xóa ảnh ' + (index + 1),
          'data-remove-index': index,
        }).on('click', () => removeFile(index));
        $('<i>', { class: 'fa-solid fa-xmark', 'aria-hidden': 'true' }).appendTo($remove);

        $item.append($open, $remove).appendTo($preview);
      });

      $form
        .find('[data-evidence-count]')
        .text(files.length ? 'Đã chọn ' + files.length + '/' + MAX_FILES + ' ảnh.' : '');
    }

    $fileInput.on('change', function () {
      // The input is rebuilt from `files`, so only the newly picked files are read here.
      const picked = Array.from(this.files).filter((file) => !files.some((item) => item.file === file));
      addFiles(picked);
    });

    $form
      .find('[data-evidence-dropzone]')
      .on('dragover', function (event) {
        event.preventDefault();
        $(this).attr('data-dragover', 'true');
      })
      .on('dragleave drop', function () {
        $(this).removeAttr('data-dragover');
      })
      .on('drop', function (event) {
        event.preventDefault();
        addFiles(event.originalEvent.dataTransfer.files);
      });

    // Screenshots pasted anywhere on the page are added as evidence.
    $(document).on('paste', function (event) {
      const clipboard = event.originalEvent.clipboardData;
      const pasted = Array.from((clipboard && clipboard.files) || []).filter((file) => file.type.startsWith('image/'));
      if (!pasted.length) return;
      event.preventDefault();
      addFiles(
        pasted.map((file, index) =>
          file.name && file.name !== 'image.png'
            ? file
            : new File([file], 'anh-dan-' + Date.now() + '-' + index + '.png', { type: file.type })
        )
      );
    });

    // ---------- lightbox ----------
    const lightbox = document.getElementById('evidence-lightbox');
    let lightboxIndex = 0;

    function renderLightbox() {
      const item = files[lightboxIndex];
      $(lightbox).find('[data-lightbox-image]').attr({ src: item.url, alt: item.file.name });
      $(lightbox)
        .find('[data-lightbox-counter]')
        .text(lightboxIndex + 1 + ' / ' + files.length);
      $(lightbox)
        .find('[data-lightbox-prev], [data-lightbox-next]')
        .prop('hidden', files.length < 2);
    }

    function openLightbox(index) {
      if (!lightbox || !files.length) return;
      lightboxIndex = index;
      renderLightbox();
      lightbox.showModal();
    }

    function stepLightbox(delta) {
      lightboxIndex = (lightboxIndex + delta + files.length) % files.length;
      renderLightbox();
    }

    if (lightbox) {
      $(lightbox)
        .find('[data-lightbox-close]')
        .on('click', () => lightbox.close());
      $(lightbox)
        .find('[data-lightbox-prev]')
        .on('click', () => stepLightbox(-1));
      $(lightbox)
        .find('[data-lightbox-next]')
        .on('click', () => stepLightbox(1));
      $(lightbox)
        .find('[data-lightbox-backdrop]')
        .on('click', function (event) {
          if (event.target === this) lightbox.close();
        });
      $(lightbox).on('keydown', function (event) {
        if (event.key === 'ArrowLeft') stepLightbox(-1);
        if (event.key === 'ArrowRight') stepLightbox(1);
      });
    }

    // ---------- draft autosave (text only, never files) ----------
    let draftTimer;

    function saveDraft() {
      clearTimeout(draftTimer);
      draftTimer = setTimeout(function () {
        const draft = {};
        DRAFT_TEXT_FIELDS.forEach((name) => (draft[name] = $form.find('[name="' + name + '"]').val() || ''));
        DRAFT_RADIO_FIELDS.forEach((name) => (draft[name] = $form.find('[name="' + name + '"]:checked').val() || ''));
        try {
          localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        } catch (error) {
          // Storage can be full or blocked (private mode); the form works without a draft.
        }
      }, 400);
    }

    function clearDraft() {
      clearTimeout(draftTimer);
      try {
        localStorage.removeItem(DRAFT_KEY);
      } catch (error) {
        // Nothing to clear when storage is blocked.
      }
    }

    function restoreDraft() {
      let draft = null;
      try {
        draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null');
      } catch (error) {
        return false;
      }
      if (!draft || typeof draft !== 'object') return false;

      let restored = false;
      DRAFT_TEXT_FIELDS.forEach(function (name) {
        if (typeof draft[name] === 'string' && draft[name] !== '') {
          $form.find('[name="' + name + '"]').val(draft[name]);
          restored = true;
        }
      });
      DRAFT_RADIO_FIELDS.forEach(function (name) {
        if (typeof draft[name] !== 'string') return;
        // A ?type= link picks the tab, even when a draft exists.
        if (name === 'type' && new URLSearchParams(window.location.search).has('type')) return;
        $form
          .find('[name="' + name + '"]')
          .filter((_, el) => el.value === draft[name])
          .prop('checked', true);
      });
      return restored;
    }

    $form.on('input change', 'input:not([type="file"]):not([type="checkbox"]), textarea', saveDraft);

    const $draftNote = $form.find('[data-draft-note]');
    $form.find('[data-draft-clear]').on('click', function () {
      clearDraft();
      DRAFT_TEXT_FIELDS.forEach((name) => $form.find('[name="' + name + '"]').val(''));
      $form.find('[name="scam_type"][value=""]').prop('checked', true);
      $form.find('[name="reporter_role"][value="victim"]').prop('checked', true);
      syncCategory();
      updateDescriptionCount();
      $draftNote.prop('hidden', true);
      $('#target_id').trigger('focus');
    });

    // ---------- submit ----------
    function setSubmitting(isSubmitting) {
      $submit.prop('disabled', isSubmitting).attr('aria-busy', isSubmitting ? 'true' : null);
      $submit.find('[data-submit-icon]').prop('hidden', isSubmitting);
      $submit.find('[data-submit-spinner]').prop('hidden', !isSubmitting);
      $submit.find('[data-submit-label]').text(isSubmitting ? 'Đang gửi...' : 'Gửi tố cáo');
    }

    function applyServerErrors(errors) {
      const fieldMessages = [];
      const general = [];

      $.each(errors, function (key, messages) {
        const field = key.split('.')[0];
        const message = Array.isArray(messages) ? messages[0] : messages;

        if (field !== 'general' && field !== 'category' && $('#error-' + field).length) {
          setError(field, message);
          fieldMessages.push(message);
        } else {
          general.push(message);
        }
      });

      if (errors.general) {
        showAlert(general[0], ['Mỗi địa chỉ IP được gửi tối đa 3 tố cáo trong 24 giờ, vui lòng quay lại sau.']);
      } else {
        showAlert('Vui lòng kiểm tra lại các mục được đánh dấu bên dưới.', general.concat(fieldMessages));
      }

      if (!focusFirstInvalid()) $alert.trigger('focus');
    }

    $form.on('submit', function (event) {
      event.preventDefault();
      hideAlert();

      const problems = validateAll();
      if (problems.length) {
        showAlert('Còn ' + problems.length + ' mục cần bổ sung trước khi gửi:', problems);
        focusFirstInvalid();
        return;
      }

      setSubmitting(true);

      $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: new FormData(form),
        processData: false,
        contentType: false,
        dataType: 'json',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
        .done(function (response) {
          clearDraft();
          const redirect = (response && response.redirect) || $form.attr('data-home-url');
          const dialog = document.getElementById('report-success-dialog');
          $(dialog).find('[data-success-home]').attr('href', redirect);
          // Navigate away when the dialog closes so the same report is not sent twice.
          dialog.addEventListener('close', () => (window.location.href = redirect), { once: true });
          dialog.showModal();
        })
        .fail(function (xhr) {
          setSubmitting(false);

          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            applyServerErrors(xhr.responseJSON.errors);
            return;
          }

          if (xhr.status === 419) {
            showAlert('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và gửi lại (nội dung đã được lưu nháp).', []);
          } else if (xhr.status === 429) {
            showAlert('Bạn đã gửi quá nhiều lần. Vui lòng thử lại sau.', []);
          } else {
            showAlert('Không gửi được tố cáo do lỗi kết nối hoặc máy chủ. Vui lòng thử lại.', []);
          }
          $alert.trigger('focus');
        });
    });

    // ---------- init ----------
    // After a failed no-JS post the server already refilled the form, so the draft is not applied.
    if ($form.attr('data-has-errors') !== 'true' && restoreDraft()) {
      $draftNote.prop('hidden', false);
    }

    setType(selectedType());
    if ($amount.val()) formatAmount();
    updateDescriptionCount();
    if (!$form.find('[data-category-input]').val()) syncCategory();

    if ($form.attr('data-has-errors') === 'true') focusFirstInvalid();
  });
</script>
