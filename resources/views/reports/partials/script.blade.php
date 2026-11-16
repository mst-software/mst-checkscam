<script>
  $(function () {
    const $form = $('#report-form');
    if (!$form.length) return;

    const form = $form.get(0);
    const TOTAL_STEPS = 4;
    const MAX_FILES = 10;
    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    const FIELD_STEPS = {
      type: 1,
      target_id: 1,
      target_name: 1,
      target_bank: 1,
      scam_type: 2,
      category: 2,
      description: 3,
      damage_amount: 3,
      evidence_images: 3,
      reporter_name: 4,
      reporter_contact: 4,
      confirm: 4,
    };
    const TYPE_LABELS = { account: 'Tài khoản / Số điện thoại', website: 'Website' };

    const $stepButtons = $form.find('[data-step-target]').not('[data-edit-link]');
    const $alert = $('#report-form-alert');
    const $fileInput = $('#evidence_images');
    const $preview = $form.find('[data-evidence-preview]');
    const $submit = $form.find('[data-submit-btn]');

    let currentStep = Number($form.attr('data-current-step')) || 1;
    let files = [];

    form.noValidate = true;

    // ---------- helpers ----------
    function value(name) {
      const $el = $form.find('[name="' + name + '"]').not(':radio');
      return $el.length ? String($el.val() || '').trim() : '';
    }

    function selectedType() {
      return $form.find('[name="type"]:checked').val() || 'account';
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

    function clearStepErrors(step) {
      $.each(FIELD_STEPS, function (field, fieldStep) {
        if (fieldStep === step) setError(field, '');
      });
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

    function focusFirstInvalid(step) {
      const $invalid = $form.find('[data-step-panel="' + step + '"] [aria-invalid="true"]').first();
      if ($invalid.length) {
        $invalid.trigger('focus');
        return true;
      }
      return false;
    }

    // ---------- validation ----------
    function validateStep(step) {
      const errors = {};

      if (step === 1) {
        const targetId = value('target_id');
        if (!targetId) {
          errors.target_id =
            selectedType() === 'website'
              ? 'Vui lòng nhập link website lừa đảo.'
              : 'Vui lòng nhập số tài khoản hoặc số điện thoại của đối tượng.';
        } else if (targetId.length > 255) {
          errors.target_id = 'Thông tin không được dài quá 255 ký tự.';
        } else if (selectedType() === 'website' && !/^\S+\.\S+$/.test(targetId)) {
          errors.target_id = 'Link website chưa đúng, ví dụ: https://ten-mien.com';
        }
      }

      if (step === 3) {
        const descriptionLength = value('description').length;
        if (descriptionLength < 50) {
          errors.description = 'Vui lòng mô tả sự việc tối thiểu 50 ký tự (hiện có ' + descriptionLength + ').';
        }

        const amountInput = document.getElementById('damage_amount');
        if (amountInput.validity.badInput || Number(amountInput.value) < 0) {
          errors.damage_amount = 'Số tiền thiệt hại phải là số và không nhỏ hơn 0.';
        }

        if (files.length === 0) {
          errors.evidence_images = 'Vui lòng tải lên ít nhất 1 ảnh bằng chứng.';
        }
      }

      if (step === 4) {
        if (!value('reporter_name')) errors.reporter_name = 'Vui lòng nhập họ và tên của bạn.';
        if (!value('reporter_contact')) errors.reporter_contact = 'Vui lòng nhập số Zalo hoặc cách liên hệ.';
        if (!$('#confirm_truth').is(':checked')) errors.confirm = 'Vui lòng xác nhận cam kết trước khi gửi.';
      }

      clearStepErrors(step);
      $.each(errors, setError);

      return $.isEmptyObject(errors);
    }

    // ---------- steps ----------
    function showStep(step, options) {
      const settings = $.extend({ focus: true }, options);
      currentStep = Math.min(Math.max(step, 1), TOTAL_STEPS);
      $form.attr('data-current-step', currentStep);

      $stepButtons.each(function () {
        const number = Number($(this).attr('data-step-target'));
        const state = number < currentStep ? 'done' : number === currentStep ? 'current' : 'upcoming';
        $(this).attr('data-state', state);
        if (state === 'current') {
          $(this).attr('aria-current', 'step');
        } else {
          $(this).removeAttr('aria-current');
        }
      });

      if (currentStep === TOTAL_STEPS) renderReview();

      if (settings.focus) {
        const card = document.getElementById('report-form-card');
        if (card.getBoundingClientRect().top < 0) card.scrollIntoView({ block: 'start' });
        if (!focusFirstInvalid(currentStep)) $('#step-' + currentStep + '-title').trigger('focus');
      }
    }

    function goToStep(target) {
      if (target <= currentStep) {
        showStep(target);
        return;
      }
      for (let step = currentStep; step < target; step++) {
        if (!validateStep(step)) {
          showStep(step);
          return;
        }
      }
      showStep(target);
    }

    $form.on('click', '[data-step-target]', function () {
      goToStep(Number($(this).attr('data-step-target')));
    });

    $form.find('[data-next-btn]').on('click', function () {
      goToStep(currentStep + 1);
    });

    $form.find('[data-back-btn]').on('click', function () {
      showStep(currentStep - 1);
    });

    // Enter in a single-line field moves to the next step instead of submitting early.
    $form.on('keydown', 'input', function (event) {
      if (event.key !== 'Enter' || currentStep === TOTAL_STEPS) return;
      if ($(this).is(':radio, :checkbox, [type="file"]')) return;
      event.preventDefault();
      goToStep(currentStep + 1);
    });

    // Editing a flagged field clears its message; the step is checked again on Next/submit.
    $form.on('input change', '[data-field][aria-invalid]', function () {
      const field = $(this).attr('data-field');
      if (field !== 'evidence_images') setError(field, '');
    });

    // ---------- step 1: target type ----------
    function syncType() {
      const type = selectedType();
      $form.find('[data-type-text]').each(function () {
        $(this).text($(this).attr('data-' + type));
      });
      $form.find('[data-placeholder-' + type + ']').each(function () {
        $(this).attr('placeholder', $(this).attr('data-placeholder-' + type));
      });
      const isAccount = type === 'account';
      $form.find('[data-account-only]').prop('hidden', !isAccount).find('input').prop('disabled', !isAccount);
    }

    $form.on('change', '[name="type"]', syncType);

    // ---------- step 2: scam type ----------
    function syncCategory() {
      const $checked = $form.find('[name="scam_type"]:checked');
      $form.find('[data-category-input]').val($checked.attr('data-label') || '');
    }

    $form.on('change', '[name="scam_type"]', syncCategory);

    // ---------- step 3: description counter ----------
    const $descriptionCount = $form.find('[data-description-count]');
    $('#description').on('input', function () {
      $descriptionCount.text($(this).val().trim().length + '/50');
    });

    // ---------- step 3: evidence images ----------
    function addFiles(fileList) {
      const problems = [];

      Array.from(fileList).forEach(function (file) {
        if (files.some((item) => item.file.name === file.name && item.file.size === file.size)) return;
        if (!ACCEPTED_TYPES.includes(file.type)) {
          problems.push('Ảnh "' + file.name + '" không đúng định dạng (chỉ nhận JPG, PNG, GIF).');
          return;
        }
        if (file.size > MAX_FILE_SIZE) {
          problems.push('Ảnh "' + file.name + '" lớn hơn 5MB.');
          return;
        }
        if (files.length >= MAX_FILES) {
          problems.push('Chỉ được tải lên tối đa 10 ảnh.');
          return;
        }
        files.push({ file: file, url: URL.createObjectURL(file) });
      });

      syncFiles();
      setError('evidence_images', problems[0] || '');
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
        .text(files.length ? '· Đã chọn ' + files.length + '/' + MAX_FILES + ' ảnh.' : '');
    }

    $fileInput.on('change', function () {
      // The input is rebuilt from `files`, so only the newly picked files are read here.
      const picked = Array.from(this.files).filter((file) => !files.some((item) => item.file === file));
      addFiles(picked);
    });

    const $dropzone = $form.find('[data-evidence-dropzone]');
    $dropzone
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

    // ---------- review ----------
    function renderReview() {
      const amount = value('damage_amount');
      const description = value('description');
      const scamLabel = $form.find('[name="scam_type"]:checked').attr('data-label');
      const isAccount = selectedType() === 'account';
      const rows = {
        type: TYPE_LABELS[selectedType()],
        target_id: value('target_id'),
        target_name: value('target_name'),
        target_bank: isAccount ? value('target_bank') : '',
        scam_type: scamLabel || 'Chưa rõ',
        damage_amount: amount ? Number(amount).toLocaleString('vi-VN') + ' đ' : '',
        description: description.length > 180 ? description.slice(0, 180) + '…' : description,
        evidence_images: files.length ? files.length + ' ảnh' : '',
      };

      $.each(rows, function (key, text) {
        $form.find('[data-review="' + key + '"]').text(text || '—');
      });
      $form.find('[data-review="target_bank"]').closest('div').prop('hidden', !isAccount);
    }

    // ---------- submit ----------
    function setSubmitting(isSubmitting) {
      $submit.prop('disabled', isSubmitting).attr('aria-busy', isSubmitting ? 'true' : null);
      $submit.find('[data-submit-label]').text(isSubmitting ? 'Đang gửi...' : 'Gửi tố cáo');
    }

    function applyServerErrors(errors) {
      let firstStep = null;
      const general = [];

      $.each(errors, function (key, messages) {
        const field = key.split('.')[0];
        const message = Array.isArray(messages) ? messages[0] : messages;
        const step = FIELD_STEPS[field];

        if (step && field !== 'category') {
          if ($('#error-' + field).prop('hidden')) setError(field, message);
          firstStep = firstStep === null ? step : Math.min(firstStep, step);
        } else {
          general.push(message);
        }
      });

      if (errors.general) {
        showAlert(general[0], ['Mỗi địa chỉ IP được gửi tối đa 3 tố cáo trong 24 giờ, vui lòng quay lại sau.']);
      } else if (general.length) {
        showAlert(general[0], general.slice(1));
      } else {
        showAlert('Vui lòng kiểm tra lại các mục được đánh dấu.', []);
      }

      if (firstStep !== null) {
        showStep(firstStep);
      } else {
        $alert.trigger('focus');
      }
    }

    $form.on('submit', function (event) {
      event.preventDefault();
      hideAlert();

      for (let step = 1; step <= TOTAL_STEPS; step++) {
        if (!validateStep(step)) {
          showStep(step);
          return;
        }
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
          } else if (xhr.status === 419) {
            showAlert('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và gửi lại.', []);
            $alert.trigger('focus');
          } else if (xhr.status === 429) {
            showAlert('Bạn đã gửi quá nhiều lần. Vui lòng thử lại sau.', []);
            $alert.trigger('focus');
          } else {
            showAlert('Không gửi được tố cáo do lỗi kết nối hoặc máy chủ. Vui lòng thử lại.', []);
            $alert.trigger('focus');
          }
        });
    });

    // ---------- init ----------
    syncType();
    if (!$form.find('[data-category-input]').val()) syncCategory();

    if ($form.attr('data-has-errors') === 'true') {
      showStep(currentStep);
    } else {
      showStep(currentStep, { focus: false });
    }
  });
</script>
