<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />

  <!-- SEO Optimization using SEOTools -->
  {!! \Artesaos\SEOTools\Facades\SEOTools::generate() !!}

  <!-- Preconnect để tăng tốc kết nối CDN -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin />
  <link rel="preconnect" href="https://www.google.com" crossorigin />
  <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin />
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net" />
  <link rel="dns-prefetch" href="https://www.google.com" />

  <script
    src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
    crossorigin="anonymous"
  ></script>

  {{-- Apply the saved theme before first paint to avoid a light flash --}}
  <script>
    (function () {
      try {
        var theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
          document.documentElement.classList.add('dark');
        }
      } catch (e) {}
    })();
  </script>

  @if (! empty($siteConfig['favicon']))
    <link rel="icon" type="image/png" href="{{ asset('uploads/' . $siteConfig['favicon']) }}" />
  @else
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
  @endif
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500&display=swap"
    rel="stylesheet"
  />

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @hasSection('structured_data')
    @yield('structured_data')
  @endif

  @stack('styles')

  {{-- Header Scripts from Admin Settings (Google Analytics, Facebook Pixel, etc.) --}}
  @if (! empty($siteConfig['header_scripts']))
    {!! $siteConfig['header_scripts'] !!}
  @endif
</head>
