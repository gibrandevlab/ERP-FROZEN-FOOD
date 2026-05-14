<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - 403</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen font-sans antialiased text-slate-900 p-4">
    <div class="max-w-lg w-full px-8 py-10 text-center bg-white shadow-2xl shadow-slate-200/50 rounded-3xl border border-slate-100 relative overflow-hidden">
        
        <!-- Background Decoration -->
        <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-b from-blue-50/50 to-transparent -z-10"></div>
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl opacity-70"></div>
        <div class="absolute -top-10 -left-10 w-32 h-32 bg-red-100 rounded-full mix-blend-multiply filter blur-xl opacity-70"></div>

        <!-- Vector Illustration -->
        <div class="flex justify-center mb-8 relative">
            <svg class="w-56 h-56 text-red-500 drop-shadow-md" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- A customized nice lock/forbidden illustration -->
                <path d="M16 11V7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7V11M5 11H19C20.1046 11 21 11.8954 21 13V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V13C3 11.8954 3.89543 11 5 11Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-red-500"/>
                <path d="M12 15V18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1" class="text-red-100" fill="rgba(239, 68, 68, 0.05)"/>
                <path d="M4.92896 4.92896L19.0711 19.0711" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="text-red-300"/>
                
                <!-- Extra decorative elements -->
                <circle cx="19" cy="5" r="1.5" fill="#EF4444" opacity="0.5"/>
                <circle cx="5" cy="18" r="1" fill="#EF4444" opacity="0.3"/>
                <circle cx="21" cy="16" r="2" fill="#EF4444" opacity="0.2"/>
            </svg>
        </div>
        
        <!-- Typography -->
        <h1 class="text-7xl font-black text-slate-800 mb-2 tracking-tighter" style="font-family: 'Inter', sans-serif;">403</h1>
        <h2 class="text-2xl font-bold text-slate-700 mb-3">Akses Ditolak</h2>
        
        <!-- Requested Text -->
        <p class="text-slate-500 mb-8 leading-relaxed font-medium">
            Anda tidak mempunyai akses ke halaman ini.
            <br>
            <span class="text-sm font-normal text-slate-400 mt-2 inline-block">Silakan kembali atau hubungi administrator sistem.</span>
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-center gap-3">
            @auth
                <!-- Button if logged in -->
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 shadow-lg shadow-blue-500/30 transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Ke Dashboard
                </a>
            @else
                <!-- Button if NOT logged in (just in case, maybe they shouldn't even see this route if not logged in, but fallback is nice) -->
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 shadow-lg shadow-blue-500/30 transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Login / Beranda
                </a>
            @endauth
            
            <!-- Optional Back Button -->
            <button onclick="window.history.back()" class="inline-flex items-center justify-center px-6 py-3 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-200 transition-all duration-300 shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </button>
        </div>
    </div>
</body>
</html>
