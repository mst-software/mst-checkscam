@props([
  'hints' => false,
  'recentSearches' => collect(),
  'size' => 'lg',
])

{{--
  Search box on the light hero: white rounded box with a red "Tra cứu" action.
  The suggestion list is anchored to the box (#search-wrapper) so it also opens below on mobile.
--}}
@php
  $defaultHint = 'Hệ thống tự nhận diện loại thông tin khi bạn tra cứu.';
  $hintTypes = [
    ['label' => 'SĐT', 'placeholder' => 'Ví dụ: 0912 345 678', 'hint' => 'Nhập số điện thoại, có thể dùng dạng +84 hoặc 0.'],
    ['label' => 'Số tài khoản', 'placeholder' => 'Ví dụ: 1903 4567 8901', 'hint' => 'Chỉ cần nhập dãy số tài khoản, không cần tên ngân hàng.'],
    ['label' => 'Facebook', 'placeholder' => 'Ví dụ: facebook.com/ten.tai.khoan', 'hint' => 'Dán link trang cá nhân hoặc UID Facebook.'],
    ['label' => 'Website', 'placeholder' => 'Ví dụ: https://ten-mien.com', 'hint' => 'Dán đường link website hoặc trang bán hàng cần kiểm tra.'],
  ];
  $isLarge = $size === 'lg';
  $defaultPlaceholder = 'Nhập số tài khoản, SĐT hoặc link...';
@endphp

<div {{ $attributes }}>
  <form action="{{ route('search.index') }}" method="GET" role="search" data-search-form>
    <label for="searchInput" class="sr-only">Nhập SĐT, số tài khoản, Facebook hoặc link cần kiểm tra</label>
    <div
      id="search-wrapper"
      class="{{ $isLarge ? 'rounded-[20px] border p-2 shadow-[0_24px_50px_-24px_color-mix(in_oklab,var(--color-accent)_35%,transparent),0_2px_6px_color-mix(in_oklab,var(--color-ink)_5%,transparent)]' : 'rounded-xl border-2 p-1 shadow-xl shadow-ink/5' }} border-line bg-surface focus-within:border-accent focus-within:ring-accent-soft relative transition-colors focus-within:ring-4"
    >
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative flex min-w-0 flex-1 items-center">
          <i
            class="{{ $isLarge ? 'left-4 sm:left-5' : 'left-4' }} fa-solid fa-magnifying-glass text-ink-subtle pointer-events-none absolute text-lg"
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
            placeholder="{{ $defaultPlaceholder }}"
            data-default-placeholder="{{ $defaultPlaceholder }}"
            class="{{ $isLarge ? 'h-12 pl-11 text-[15px] font-medium sm:h-13.5 sm:pl-14 sm:text-[17px]' : 'h-11 pl-12 text-base font-semibold' }} text-ink placeholder:text-ink-subtle w-full border-0 bg-transparent pr-3 outline-none focus:ring-0 sm:pr-4"
          />
        </div>

        <button
          type="submit"
          class="{{ $isLarge ? 'h-12 gap-2.5 rounded-[14px] px-8 font-extrabold tracking-[0.06em] shadow-[0_8px_18px_-8px_color-mix(in_oklab,var(--color-accent)_70%,transparent)] sm:h-13.5' : 'h-11 rounded-xl px-6 font-bold tracking-widest shadow-sm' }} bg-accent hover:bg-accent-hover text-on-accent focus-visible:outline-accent group inline-flex w-full cursor-pointer items-center justify-center text-sm whitespace-nowrap uppercase transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 sm:w-auto"
        >
          Tra cứu
          @if ($isLarge)
            <i
              class="fa-solid fa-chevron-right text-on-accent/70 text-xs motion-safe:transition-transform motion-safe:group-hover:translate-x-0.5"
              aria-hidden="true"
            ></i>
          @endif
        </button>
      </div>

      <ul
        id="searchResults"
        role="listbox"
        aria-label="Gợi ý tra cứu"
        class="border-line bg-surface absolute top-full right-0 left-0 z-40 mt-2 hidden max-h-80 overflow-y-auto rounded-xl border py-1 text-left shadow-lg"
      ></ul>
    </div>
  </form>

  @if ($hints)
    <div
      class="text-ink-subtle mt-4 flex flex-wrap items-center justify-center gap-2 text-[13px]"
      role="group"
      aria-label="Loại thông tin cần kiểm tra"
    >
      <span>Thử tra cứu:</span>
      @foreach ($hintTypes as $hintType)
        <button
          type="button"
          aria-pressed="false"
          data-search-hint="{{ $hintType['hint'] }}"
          data-search-placeholder="{{ $hintType['placeholder'] }}"
          class="border-line bg-surface text-ink-muted hover:border-line-strong hover:text-ink focus-visible:outline-accent aria-pressed:border-accent aria-pressed:bg-accent-soft aria-pressed:text-accent cursor-pointer rounded-full border px-3 py-1 text-[13px] font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
        >
          {{ $hintType['label'] }}
        </button>
      @endforeach
    </div>
    <p id="search-hint" class="sr-only" aria-live="polite" data-default-hint="{{ $defaultHint }}">
      {{ $defaultHint }}
    </p>
  @endif

  @if ($recentSearches->isNotEmpty())
    <div class="mt-4 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs">
      <span class="text-ink-subtle">Bạn đã tra cứu:</span>
      @foreach ($recentSearches as $search)
        <a
          href="{{ route('search.index', ['q' => $search]) }}"
          class="text-accent focus-visible:outline-accent max-w-48 truncate rounded-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2"
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
        function buildOption(index, mainText, subText, badge, value, url) {
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

          $li
            .on('mousedown', (event) => event.preventDefault())
            .on('click', () => (url ? window.location.assign(url) : submitWith(value)));

          return $li;
        }

        function renderSuggestions(query, data) {
          $results.empty();
          activeIndex = -1;

          if (Array.isArray(data) && data.length > 0) {
            data.forEach((item, index) => {
              const badge = item.type ? { type: item.type, label: typeLabels[item.type] || item.type } : null;
              const subText = item.type === 'insurance' ? '' : item.target_name;
              // The API only returns masked identifiers (display); query/url never expose a full id.
              $results.append(buildOption(index, item.display, subText, badge, item.query, item.url));
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

          if (query.length < 3) {
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
