{{-- Asks guests to sign in with Google before posting or reporting. Optional: $title. --}}
@component('newfeed.partials.modal', ['id' => 'popup-login', 'title' => $title ?? 'Bạn cần đăng nhập'])
  <p class="text-ink-muted text-sm">Đăng nhập bằng tài khoản Google để đăng tin và báo cáo bài vi phạm.</p>
  <a
    href="{{ route('auth.google') }}"
    class="border-line bg-surface text-ink hover:bg-surface-muted rounded-control mt-4 flex h-11 w-full items-center justify-center gap-3 border text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
  >
    <i class="fa-brands fa-google" aria-hidden="true"></i>
    Đăng nhập với Google
  </a>
  <x-ucs.button variant="ghost" class="mt-2 w-full" onclick="closePopup('popup-login')">Để sau</x-ucs.button>
@endcomponent
