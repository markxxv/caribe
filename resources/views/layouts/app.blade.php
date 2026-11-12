<!DOCTYPE html>
<html lang="@yield('pageLang', 'ru')">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>@yield('metaTitle')</title>
    <meta name="description" content="@yield('metaDescription')" />
    @hasSection('robots')
        <meta name="robots" content="@yield('robots')" />
    @endif
    @hasSection('canonicalUrl')
        <link rel="canonical" href="@yield('canonicalUrl')" />
    @endif

    <meta property="og:site_name" content="Visita Caribe" />
    <meta property="og:locale" content="ru_RU" />
    <meta property="og:type" content="@yield('ogType', 'website')" />
    <meta property="og:title" content="@yield('metaTitle')" />
    <meta property="og:description" content="@yield('metaDescription')" />
    @hasSection('canonicalUrl')
        <meta property="og:url" content="@yield('canonicalUrl')" />
    @endif
    <meta property="og:image" content="@yield('ogImage', asset('img/main_head_background.webp'))" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="@yield('metaTitle')" />
    <meta name="twitter:description" content="@yield('metaDescription')" />

    <meta property="article:tag" content="Путешествия" />

    <meta name="author" content="Visita Caribe">

    <meta name="HandheldFriendly" content="True" />
    <meta name="MobileOptimized" content="width" />

    @yield('og')
    @php
        $siteSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => route('home') . '#website',
                    'url' => route('home'),
                    'name' => 'Visita Caribe',
                    'inLanguage' => 'ru',
                    'publisher' => ['@id' => route('home') . '#organization'],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => route('home') . '#organization',
                    'name' => 'Visita Caribe',
                    'url' => route('home'),
                    'logo' => asset('img/logo.svg'),
                    'sameAs' => [
                        'https://www.instagram.com/helenkavivas',
                        'https://www.facebook.com/helenkavivas',
                        'https://www.youtube.com/@ElenaVivas',
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($siteSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @yield('structuredData')

{{--    <link rel="icon" href="/favicon.ico" sizes="32x32">--}}
    <link rel="shortcut icon" href="/img/favicon.png" type="image/png" />

{{--    <link rel="icon" href="/img/icon.svg" type="image/svg+xml">--}}
    <link rel="apple-touch-icon" href="/img/apple-touch-icon.png">

    <link rel="manifest" href="/manifest.webmanifest">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geologica:wght,CRSV@100..900,0&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')

</head>
<body class="bg-white">
@include('layouts.header')
{{ $slot }}

@include('layouts.footer')

@vite('resources/js/app.js')
</body>
</html>
