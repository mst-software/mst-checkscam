@extends('layouts.app')

@php
  $partners = [
    ['name' => 'Vietcombank', 'logo' => 'https://i.ibb.co/TMXxGRhw/vcb.webp'],
    ['name' => 'Momo', 'logo' => 'https://i.ibb.co/KjJmJqYL/momo.webp'],
    ['name' => 'Telegram Global', 'logo' => 'https://i.ibb.co/8n74BrYX/tele.webp'],
    ['name' => 'Facebook Safety', 'logo' => 'https://i.ibb.co/rGtr2pbZ/fb.webp'],
  ];
  $benefits = ['Chia sẻ dữ liệu API hai chiều', 'Truyền thông cảnh báo lừa đảo', 'Xác minh danh tính thành viên'];
@endphp

@section('content')
  <main class="flex-1 pb-20">
    <x-ucs.page-header
      title="Đối tác uy tín"
      description="Hợp tác cùng các đơn vị để xây dựng môi trường internet an toàn."
      :breadcrumbs="[['name' => 'Đối tác uy tín', 'url' => url('/doi-tac-uy-tin')]]"
    />

    <x-ucs.container>
      <section aria-label="Mạng lưới tin cậy">
        <x-ucs.section-title title="Mạng lưới tin cậy" />
        <ul class="grid grid-cols-2 gap-3 md:grid-cols-4">
          @foreach ($partners as $partner)
            <li class="border-line bg-surface rounded-card flex h-28 items-center justify-center border p-6">
              <img
                src="{{ $partner['logo'] }}"
                alt="{{ $partner['name'] }}"
                loading="lazy"
                class="max-h-12 w-auto object-contain md:max-h-14"
              />
            </li>
          @endforeach
        </ul>
      </section>

      <section class="mt-12 grid grid-cols-1 items-center gap-8 lg:grid-cols-2" aria-labelledby="become-partner-title">
        <div>
          <h2 id="become-partner-title" class="text-ink text-xl font-semibold sm:text-2xl">
            Trở thành đối tác của Scamproof
          </h2>
          <p class="text-ink-muted mt-3 text-base leading-relaxed">
            Chúng tôi luôn mở rộng hợp tác với các cộng đồng MMO, đơn vị trung gian và nền tảng thanh toán để cùng chia
            sẻ dữ liệu cảnh báo lừa đảo.
          </p>
          <ul class="mt-5 space-y-3">
            @foreach ($benefits as $benefit)
              <li class="text-ink flex items-center gap-3 text-sm">
                <i class="fa-solid fa-circle-check text-success" aria-hidden="true"></i>
                {{ $benefit }}
              </li>
            @endforeach
          </ul>
          <div class="mt-6">
            <x-ucs.button :href="url('/lien-he-admin')" icon="fa-solid fa-handshake">Liên hệ hợp tác</x-ucs.button>
          </div>
        </div>
        <img
          src="https://i.ibb.co/kgwtn4vF/fpayment.jpg"
          alt="Minh hoạ thanh toán an toàn cùng đối tác"
          loading="lazy"
          class="border-line rounded-card h-auto w-full border"
        />
      </section>
    </x-ucs.container>
  </main>
@endsection
