<x-ucs.card>
  <div class="flex items-start gap-3">
    <i class="fa-solid fa-flag text-accent mt-1 w-5 shrink-0 text-center" aria-hidden="true"></i>
    <div class="min-w-0">
      <h2 class="text-ink text-base font-semibold">Bạn vừa bị lừa?</h2>
      <p class="text-ink-muted mt-1 text-sm">
        Gửi tố cáo kèm bằng chứng. Ban quản trị kiểm duyệt trước khi công khai để cảnh báo cộng đồng.
      </p>
      <x-ucs.button :href="route('reports')" variant="secondary" size="sm" class="mt-3">Gửi tố cáo</x-ucs.button>
    </div>
  </div>
</x-ucs.card>
