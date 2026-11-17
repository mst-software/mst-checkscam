{{--
  Long-form document layout: table of contents (sticky left column on desktop, collapsible on mobile) next to the article.
  Usage: <x-ucs.document :sections="[['id' => 'data', 'title' => '...']]"> <x-ucs.document-section ... /> </x-ucs.document>
  The TOC links are plain anchors; the script only marks the section in view with aria-current.
--}}

@props([
  'sections',
])

@php
  $linkClasses =
    'text-ink-muted hover:bg-surface-muted hover:text-ink aria-[current=location]:bg-accent-soft aria-[current=location]:text-accent focus-visible:outline-accent rounded-control flex gap-2 px-3 py-2 text-sm leading-snug transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 aria-[current=location]:font-medium';
@endphp

<div
  {{ $attributes->class(['lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12 xl:gap-16']) }}
  data-document
>
  <details
    class="border-line bg-surface rounded-card group mb-8 border lg:hidden print:hidden"
    data-document-toc-mobile
  >
    <summary
      class="text-ink rounded-card focus-visible:outline-accent flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 [&::-webkit-details-marker]:hidden"
    >
      <span class="flex items-center gap-2">
        <i class="fa-solid fa-list-ol text-ink-subtle text-xs" aria-hidden="true"></i>
        Mục lục
      </span>
      <i
        class="fa-solid fa-chevron-down text-ink-subtle text-xs transition-transform group-open:rotate-180"
        aria-hidden="true"
      ></i>
    </summary>
    <nav class="border-line border-t p-2" aria-label="Mục lục">
      <ol class="space-y-0.5">
        @foreach ($sections as $section)
          <li>
            <a href="#{{ $section['id'] }}" class="{{ $linkClasses }}" data-toc-link>
              <span class="text-ink-subtle tabular-nums">{{ $loop->iteration }}.</span>
              <span>{{ $section['title'] }}</span>
            </a>
          </li>
        @endforeach
      </ol>
    </nav>
  </details>

  <aside class="hidden lg:block print:hidden">
    <nav class="sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto pb-4" aria-labelledby="document-toc-title">
      <p id="document-toc-title" class="text-ink-subtle px-3 text-xs font-semibold tracking-wider uppercase">Mục lục</p>
      <ol class="mt-3 space-y-0.5">
        @foreach ($sections as $section)
          <li>
            <a href="#{{ $section['id'] }}" class="{{ $linkClasses }}" data-toc-link>
              <span class="text-ink-subtle tabular-nums">{{ $loop->iteration }}.</span>
              <span>{{ $section['title'] }}</span>
            </a>
          </li>
        @endforeach
      </ol>
    </nav>
  </aside>

  <article class="max-w-3xl min-w-0 space-y-10">
    {{ $slot }}
  </article>
</div>

@once
  @push('scripts')
    <script>
      (() => {
        const root = document.querySelector('[data-document]');
        if (!root) return;

        const links = [...root.querySelectorAll('[data-toc-link]')];
        const sections = [...new Set(links.map((link) => link.hash.slice(1)))]
          .map((id) => document.getElementById(id))
          .filter(Boolean);
        const mobileToc = root.querySelector('[data-document-toc-mobile]');
        let current = null;

        const update = () => {
          // A section counts as current once its top passes just below the sticky site header.
          let active = sections[0];
          for (const section of sections) {
            if (section.getBoundingClientRect().top <= 140) active = section;
          }
          if (!active || active === current) return;
          current = active;
          links.forEach((link) =>
            link.hash === '#' + active.id
              ? link.setAttribute('aria-current', 'location')
              : link.removeAttribute('aria-current')
          );
        };

        let ticking = false;
        window.addEventListener(
          'scroll',
          () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
              ticking = false;
              update();
            });
          },
          { passive: true }
        );
        window.addEventListener('resize', update);
        links.forEach((link) => link.addEventListener('click', () => mobileToc && (mobileToc.open = false)));
        update();
      })();
    </script>
  @endpush
@endonce
