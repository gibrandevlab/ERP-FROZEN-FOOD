<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Dynamic meta title + description --}}
        <title>
            @hasSection('meta_title')
                @yield('meta_title') — {{ config('app.name', 'Laravel') }}
            @else
                {{ config('app.name', 'Laravel') }}
            @endif
        </title>

        <meta name="description" content="@yield('meta_description', config('app.name', 'Laravel'))" />
        <meta name="robots" content="@yield('meta_robots', 'index,follow')" />

        <!-- Open Graph -->
        <meta property="og:locale" content="{{ str_replace('_','-',app()->getLocale()) }}" />
        <meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}" />
        <meta property="og:title" content="@yield('meta_title', config('app.name', 'Laravel'))" />
        <meta property="og:description" content="@yield('meta_description', config('app.name', 'Laravel'))" />
        <meta property="og:type" content="@yield('og_type', 'website')" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))" />
        <link rel="canonical" href="{{ url()->current() }}" />

                {{-- Allow pages to push additional meta or JSON-LD --}}
                                @stack('meta')

                                {{-- If a view defines a `jsonld` section it will fully replace this default JSON-LD. Use `@section('jsonld')` in product pages. --}}
                                @hasSection('jsonld')
                                        @yield('jsonld')
                                @else
                                        <script type="application/ld+json">
                                        {
                                            "@context": "https://schema.org/",
                                            "@type": "Product",
                                            "name": "@yield('meta_title', config('app.name', 'UMKM'))",
                                            "image": "@yield('og_image', asset('images/og-default.jpg'))",
                                            "description": "@yield('meta_description', 'Deskripsi singkat produk.')",
                                            "offers": {
                                                "@type": "Offer",
                                                "priceCurrency": "@yield('product_currency','IDR')",
                                                "price": "@yield('product_price','0')"
                                            }
                                        }
                                        </script>
                                @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
