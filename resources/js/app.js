// Shared UI behaviour for the public layout (plain JS; pages may still use jQuery).
// The initial theme class is applied by an inline script in layouts/partials/head.

const root = document.documentElement;

function setTheme(theme) {
  root.classList.toggle('dark', theme === 'dark');

  try {
    localStorage.setItem('theme', theme);
  } catch (e) {
    // Storage can be unavailable (private mode); the theme still applies for this page.
  }

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
  });
}

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
  button.setAttribute('aria-pressed', root.classList.contains('dark') ? 'true' : 'false');
  button.addEventListener('click', () => {
    setTheme(root.classList.contains('dark') ? 'light' : 'dark');
  });
});

// Disclosure toggles: mobile menu and account dropdown.
// <button data-toggle-target="#id"> shows/hides the target and closes it on outside click or Escape.
const toggles = document.querySelectorAll('[data-toggle-target]');

function closeTarget(button) {
  const target = document.querySelector(button.dataset.toggleTarget);
  if (target) {
    target.classList.add('hidden');
  }
  button.setAttribute('aria-expanded', 'false');
}

toggles.forEach((button) => {
  button.addEventListener('click', (event) => {
    event.stopPropagation();
    const target = document.querySelector(button.dataset.toggleTarget);
    if (!target) {
      return;
    }
    const willOpen = target.classList.contains('hidden');
    target.classList.toggle('hidden', !willOpen);
    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  });
});

document.addEventListener('click', (event) => {
  toggles.forEach((button) => {
    const target = document.querySelector(button.dataset.toggleTarget);
    if (target && !target.classList.contains('hidden') && !target.contains(event.target)) {
      closeTarget(button);
    }
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    toggles.forEach(closeTarget);
  }
});

// Back to top button.
const backToTop = document.getElementById('back-to-top');

if (backToTop) {
  const updateBackToTop = () => {
    const visible = window.scrollY > 300;
    backToTop.classList.toggle('hidden', !visible);
    backToTop.classList.toggle('flex', visible);
  };

  window.addEventListener('scroll', updateBackToTop, { passive: true });
  updateBackToTop();

  backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}
