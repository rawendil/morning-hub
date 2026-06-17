<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.17 0.005 165);
            }
        </style>

        @if(config('services.google.analytics_id'))
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('consent', 'default', {
                    'analytics_storage': 'denied',
                    'ad_storage': 'denied',
                    'ad_user_data': 'denied',
                    'ad_personalization': 'denied',
                    'wait_for_update': 500,
                });
                gtag('js', new Date());
                gtag('config', '{{ config('services.google.analytics_id') }}');
            </script>
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
        @endif

        <title>{{ config('app.name', 'Laravel') }}</title>

        @php
            $ogTitle = 'Morning Hub — Twoja poranna rutyna, uporządkowana';
            $ogDescription = 'Jeden panel do zadań, nawyków, artykułów i kalendarza — zacznij dzień skupiony, nie przytłoczony.';
            $ogImage = asset('images/og-image.png');
        @endphp

        {{-- SEO + Open Graph / Twitter Card meta — server-rendered so crawlers (LinkedIn, Facebook, Slack) get a proper unfurl without executing JS --}}
        <meta name="description" content="{{ $ogDescription }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'Morning Hub') }}">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Morning Hub — panel porannej rutyny">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite('resources/js/app.ts')
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
    </body>
</html>
