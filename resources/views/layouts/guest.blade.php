<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Riza Frozen Food') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Smooth transitions untuk interaksi input */
            * {
                transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
            }
            body {
                /* Warna background putih gading yang sangat lembut agar mata tidak lelah */
                background-color: #FDFDFD;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        
        <div class="min-h-screen">
            {{-- 
                Slot ini akan merender isi dari component login.
                Kita tidak memberikan container card di sini karena 
                sudah kita konsepkan di dalam file login-nya langsung 
                agar lebih fleksibel (Mobile First).
            --}}
            {{ $slot }}
        </div>

    </body>
</html>