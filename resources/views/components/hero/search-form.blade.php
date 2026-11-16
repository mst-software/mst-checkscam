@props([
  'hints' => true,
  'recentSearches' => collect(),
])

@php
  $defaultHint = 'Hệ thống tự nhận diện loại thông tin khi bạn tra cứu.';
  $hintTypes = [
    ['label' => 'SĐT', 'placeholder' => 'Ví dụ: 0912 345 678', 'hint' => 'Nhập số điện thoại, có thể dùng dạng +84 hoặc 0.'],
    ['label' => 'Số tài khoản', 'placeholder' => 'Ví dụ: 1903 4567 8901', 'hint' => 'Chỉ cần nhập dãy số tài khoản, không cần tên ngân hàng.'],
    ['label' => 'Facebook', 'placeholder' => 'Ví dụ: facebook.com/ten.tai.khoan', 'hint' => 'Dán link trang cá nhân hoặc UID Facebook.'],
    ['label' => 'Link', 'placeholder' => 'Ví dụ: https://ten-mien.com', 'hint' => 'Dán đường link website hoặc trang bán hàng cần kiểm tra.'],
  ];
@endphp

<div {{ $attributes }}>
  <form action="{{ route('search.index') }}" method="GET" role="search" data-search-form>
    <label for="searchInput" class="sr-only">Nhập SĐT, số tài khoản, Facebook hoặc link cần kiểm tra</label>
    <div class="flex flex-col gap-2 sm:flex-row">
      <div class="relative min-w-0 flex-1" id="search-wrapper">
        <i
          class="fa-solid fa-magnifying-glass text-ink-subtle pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm"
          aria-hidden="true"
        ></i>
        <input
          type="text"
          name="q"
          id="searchInput"
          value="{{ request()->query('q') }}"
          autocomplete="off"
          enterkeyhint="search"
          required
          role="combobox"
          aria-autocomplete="list"
          aria-expanded="false"
          aria-controls="searchResults"
          @if ($hints) aria-describedby="search-hint" @endif
          placeholder="Nhập SĐT, số tài khoản, Facebook hoặc link..."
          data-default-placeholder="Nhập SĐT, số tài khoản, Facebook hoặc link..."
          class="border-line bg-surface text-ink placeholder:text-ink-subtle focus:border-accent focus:ring-accent/25 h-11 w-full rounded-xl border pr-4 pl-10 text-base transition-colors outline-none focus:ring-3"
        />

        <ul
          id="searchResults"
          role="listbox"
          aria-label="Gợi ý tra cứu"
          class="border-line bg-surface absolute top-full right-0 left-0 z-40 mt-1 hidden max-h-80 overflow-y-auto rounded-xl border py-1 shadow-lg"
        ></ul>
      </div>

      <x-ucs.button type="submit" size="lg" class="w-full sm:w-auto">Kiểm tra</x-ucs.button>
    </div>
  </form>

  @if ($hints)
    <div class="mt-3 flex flex-wrap items-center gap-2" role="group" aria-label="Loại thông tin cần kiểm tra">
      @foreach ($hintTypes as $hintType)
        <button
          type="button"
          aria-pressed="false"
          data-search-hint="{{ $hintType['hint'] }}"
          data-search-placeholder="{{ $hintType['placeholder'] }}"
          class="border-line text-ink-muted hover:border-line-strong hover:text-ink aria-pressed:border-accent aria-pressed:bg-accent-soft aria-pressed:text-accent focus-visible:outline-accent cursor-pointer rounded-md border px-2.5 py-1 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
        >
          {{ $hintType['label'] }}
        </button>
      @endforeach
    </div>
    <p id="search-hint" class="text-ink-subtle mt-2 text-xs" aria-live="polite" data-default-hint="{{ $defaultHint }}">
      {{ $defaultHint }}
    </p>
  @endif

  @if ($recentSearches->isNotEmpty())
    <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
      <span class="text-ink-subtle">Bạn đã tra cứu:</span>
      @foreach ($recentSearches as $search)
        <a
          href="{{ route('search.index', ['q' => $search]) }}"
          class="text-accent hover:text-accent-hover focus-visible:outline-accent max-w-48 truncate rounded-sm hover:underline focus-visible:outline-2"
        >
          {{ $search }}
        </a>
      @endforeach
    </div>
  @endif
</div>

@once
  @push('scripts')
    <script>
      $(function () {
        const $input = $('#searchInput');
        const $results = $('#searchResults');

        if (!$input.length || !$results.length) return;

        const $form = $input.closest('form');
        const $hint = $('#search-hint');
        const typeLabels = {
          account: 'Tài khoản',
          bank: 'Ngân hàng',
          phone: 'SĐT',
          facebook: 'Facebook',
          website: 'Website',
          insurance: 'Bảo hiểm',
        };
        let activeIndex = -1;
        let timeoutId;
        let latestQuery = '';

        function isOpen() {
          return !$results.hasClass('hidden');
        }

        function openDropdown() {
          $results.removeClass('hidden');
          $input.attr('aria-expanded', 'true');
        }

        function closeDropdown() {
          $results.addClass('hidden').empty();
          $input.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
          activeIndex = -1;
        }

        function submitWith(value) {
          $input.val(value);
          closeDropdown();
          $form.get(0).submit();
        }

        function setActive(index) {
          const $options = $results.children('[role="option"]');
          if (!$options.length) return;

          activeIndex = (index + $options.length) % $options.length;
          $options.attr('aria-selected', 'false').removeClass('bg-surface-muted');

          const $active = $options.eq(activeIndex).attr('aria-selected', 'true').addClass('bg-surface-muted');
          $input.attr('aria-activedescendant', $active.attr('id'));
          $active.get(0).scrollIntoView({ block: 'nearest' });
        }

        // Builds an option with .text() so server values are never parsed as HTML.
        function buildOption(index, mainText, subText, badge, value) {
          const $li = $('<li>', {
            id: 'search-option-' + index,
            role: 'option',
            'aria-selected': 'false',
            class: 'hover:bg-surface-muted flex cursor-pointer items-center gap-3 px-4 py-2.5 text-sm',
          });

          $('<i>', { class: 'fa-solid fa-magnifying-glass text-ink-subtle text-xs', 'aria-hidden': 'true' }).appendTo(
            $li
          );

          const $text = $('<span>', { class: 'flex min-w-0 flex-1 items-baseline gap-2' }).appendTo($li);
          $('<span>', { class: 'text-ink truncate font-medium' }).text(mainText).appendTo($text);
          if (subText) {
            $('<span>', { class: 'text-ink-subtle truncate text-xs' }).text(subText).appendTo($text);
          }

          if (badge) {
            const badgeClass =
              badge.type === 'insurance'
                ? 'border-success/25 bg-success-soft text-success'
                : 'border-line bg-surface-muted text-ink-muted';
            $('<span>', { class: 'shrink-0 rounded-md border px-2 py-0.5 text-xs font-medium ' + badgeClass })
              .text(badge.label)
              .appendTo($li);
          }

          $li.on('mousedown', (event) => event.preventDefault()).on('click', () => submitWith(value));

          return $li;
        }

        function renderSuggestions(query, data) {
          $results.empty();
          activeIndex = -1;

          if (Array.isArray(data) && data.length > 0) {
            data.forEach((item, index) => {
              const badge = item.type ? { type: item.type, label: typeLabels[item.type] || item.type } : null;
              const subText = item.type === 'insurance' ? '' : item.target_name;
              $results.append(buildOption(index, item.value, subText, badge, item.value));
            });
          } else {
            $results.append(buildOption(0, 'Tra cứu "' + query + '"', '', null, query));
          }

          openDropdown();
        }

        function fetchSuggestions(query) {
          latestQuery = query;

          $.ajax({
            url: @json(route('search.autocomplete')),
            method: 'GET',
            data: { q: query },
            dataType: 'json',
          })
            .done((data) => {
              if (query === latestQuery && $input.val().trim() === query) {
                renderSuggestions(query, data);
              }
            })
            .fail(closeDropdown);
        }

        $input.on('input', function () {
          clearTimeout(timeoutId);
          const query = $(this).val().trim();

          if (query.length < 2) {
            closeDropdown();
            return;
          }

          timeoutId = setTimeout(() => fetchSuggestions(query), 300);
        });

        $input.on('keydown', function (event) {
          if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (!$results.children().length) return;
            event.preventDefault();
            openDropdown();
            setActive(event.key === 'ArrowDown' ? activeIndex + 1 : activeIndex - 1);
          } else if (event.key === 'Enter' && isOpen() && activeIndex >= 0) {
            event.preventDefault();
            $results.children('[role="option"]').eq(activeIndex).trigger('click');
          } else if (event.key === 'Escape' && isOpen()) {
            event.preventDefault();
            closeDropdown();
          }
        });

        $input.on('focus', function () {
          if ($results.children().length) openDropdown();
        });

        $(document).on('click', function (event) {
          if (!$(event.target).closest('#search-wrapper').length) closeDropdown();
        });

        // Hint chips only change the placeholder and helper text; detection stays server-side.
        $('[data-search-hint]').on('click', function () {
          const $chip = $(this);
          const wasPressed = $chip.attr('aria-pressed') === 'true';

          $('[data-search-hint]').attr('aria-pressed', 'false');

          if (wasPressed) {
            $input.attr('placeholder', $input.data('default-placeholder'));
            $hint.text($hint.data('default-hint'));
          } else {
            $chip.attr('aria-pressed', 'true');
            $input.attr('placeholder', $chip.data('search-placeholder'));
            $hint.text($chip.data('search-hint'));
          }

          $input.trigger('focus');
        });
      });
    </script>
  @endpush
@endonce
