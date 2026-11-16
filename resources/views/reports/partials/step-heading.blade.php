<header class="mb-5">
  <p class="text-ink-subtle text-xs font-medium">Bước {{ $number }}/4</p>
  <h2 id="step-{{ $number }}-title" tabindex="-1" class="text-ink mt-0.5 text-lg font-semibold focus:outline-none">
    {{ $steps[$number]['title'] }}
  </h2>
  <p class="text-ink-muted mt-1 text-sm">{{ $steps[$number]['description'] }}</p>
</header>
