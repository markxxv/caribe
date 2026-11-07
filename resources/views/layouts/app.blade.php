<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>@yield('metaTitle')</title>
    <meta name="description" content="@yield('metaDescription')" />

    <meta property="og:site_name" content="Карибские каникулы" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="@yield('metaTitle')" />
    <meta property="og:description" content="@yield('metaDescription')" />

    <meta property="article:tag" content="Путешествия" />

    <meta name="author" content="Visita Caribe">

    <meta name="HandheldFriendly" content="True" />
    <meta name="MobileOptimized" content="width" />

    @yield('og')

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
