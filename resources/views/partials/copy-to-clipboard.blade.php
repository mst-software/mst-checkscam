{{--
  Copy-to-clipboard for any <button data-copy="text"> (or data-copy-target="#id" to copy an element's text).
  The button's [data-copy-label] child switches to "Đã sao chép" for 2s. Include once per page.
--}}
@once
  @push('scripts')
    <script>
      document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-copy], [data-copy-target]');
        if (!button) {
          return;
        }

        const target = button.dataset.copyTarget ? document.querySelector(button.dataset.copyTarget) : null;
        const text = target ? target.textContent.trim() : button.dataset.copy;
        const label = button.querySelector('[data-copy-label]');

        const done = function (message) {
          if (!label) {
            return;
          }
          const original = label.dataset.original || label.textContent;
          label.dataset.original = original;
          label.textContent = message;
          setTimeout(function () {
            label.textContent = original;
          }, 2000);
        };

        if (!navigator.clipboard) {
          done('Không sao chép được');
          return;
        }

        navigator.clipboard.writeText(text).then(
          function () {
            done('Đã sao chép');
          },
          function () {
            done('Không sao chép được');
          }
        );
      });
    </script>
  @endpush
@endonce
