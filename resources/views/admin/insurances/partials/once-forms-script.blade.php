{{-- Forms with class "js-once" ask for confirmation (data-confirm) and can only be submitted once. --}}
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.js-once').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (form.dataset.submitted === '1') {
          e.preventDefault();
          return;
        }

        const lock = function () {
          form.dataset.submitted = '1';
          form.querySelectorAll('button[type="submit"]').forEach(function (button) {
            button.disabled = true;
            button.innerHTML =
              '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Đang xử lý...';
          });
        };

        if (!form.dataset.confirm) {
          lock();
          return;
        }

        e.preventDefault();
        const ask =
          typeof Swal !== 'undefined'
            ? Swal.fire({
                title: 'Xác nhận',
                text: form.dataset.confirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff9f43',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy',
              }).then((result) => result.isConfirmed)
            : Promise.resolve(window.confirm(form.dataset.confirm));

        ask.then(function (confirmed) {
          if (confirmed) {
            lock();
            form.submit();
          }
        });
      });
    });
  });
</script>
