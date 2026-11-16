{{--
  Escrowed partners as cards: verified name, deposit, validity and services. Expects: $trustedInsurances.
--}}
@if ($trustedInsurances->isNotEmpty())
  <section aria-label="Trung gian đã ký quỹ">
    <x-ucs.section-title
      title="Trung gian đã ký quỹ"
      subtitle="Danh tính đã xác thực, mức ký quỹ và hạn hiệu lực hiển thị công khai."
      action-label="Xem tất cả"
      :action-url="route('insurances.frontend.index')"
    />

    <ul class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      @foreach ($trustedInsurances as $insurance)
        @php
          $services = collect(is_array($insurance->services) ? $insurance->services : [])
            ->pluck('title')
            ->filter()
            ->implode(', ');
          $initials = collect(preg_split('/\s+/', trim($insurance->full_name)))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->pipe(fn ($letters) => $letters->count() > 1 ? $letters->first() . $letters->last() : $letters->first());
        @endphp

        <li class="border-line bg-surface rounded-card flex flex-col border p-5" data-partner-card>
          <div class="flex items-center gap-3">
            @if ($insurance->avatar_url)
              <img
                src="{{ $insurance->avatar_url }}"
                alt=""
                width="48"
                height="48"
                loading="lazy"
                class="border-line bg-surface-muted size-12 shrink-0 rounded-full border object-cover"
              />
            @else
              <span
                class="bg-surface-muted text-ink-muted flex size-12 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                aria-hidden="true"
              >
                {{ $initials }}
              </span>
            @endif

            <div class="min-w-0">
              <p class="text-ink flex items-center gap-1.5 text-[15px] font-bold">
                <span class="truncate">{{ $insurance->full_name }}</span>
                <i class="fa-solid fa-circle-check text-success text-sm" aria-hidden="true"></i>
              </p>
              <x-ucs.badge variant="success" class="mt-1">Đã xác thực</x-ucs.badge>
            </div>
          </div>

          <dl class="mt-4 mb-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-ink-subtle">Quỹ bảo hiểm</dt>
              <dd class="text-ink font-semibold tabular-nums">
                {{ number_format((int) $insurance->amount, 0, ',', '.') }} đ
              </dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-ink-subtle">Hiệu lực đến</dt>
              <dd class="text-ink font-semibold tabular-nums">
                {{ $insurance->expired_at?->format('d/m/Y') ?? 'Không thời hạn' }}
              </dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-ink-subtle shrink-0">Dịch vụ</dt>
              <dd class="text-ink min-w-0 truncate text-right font-semibold">
                {{ $services !== '' ? $services : '—' }}
              </dd>
            </div>
          </dl>

          <div
            class="border-line text-ink-muted mt-auto flex items-center justify-between gap-3 border-t border-dashed pt-3 text-xs"
          >
            <span>
              @if ($insurance->insurance_date)
                Xác thực từ {{ $insurance->insurance_date->format('d/m/Y') }}
              @endif
            </span>
            <a
              href="{{ route('insurances.frontend.show', $insurance->slug) }}"
              class="text-accent hover:text-accent-hover focus-visible:outline-accent rounded-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2"
            >
              Xem hồ sơ →
              <span class="sr-only">{{ $insurance->full_name }}</span>
            </a>
          </div>
        </li>
      @endforeach
    </ul>
  </section>
@endif
