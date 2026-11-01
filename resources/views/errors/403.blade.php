<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[#344950] leading-tight">
            Akses Ditolak
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-none sm:rounded-lg border border-[#e6eae7] p-8 sm:p-16 flex flex-col items-center justify-center text-center">
                
                {{-- Ikon Gembok / Shield --}}
                <div class="w-24 h-24 bg-[#f7eceb] rounded-full flex items-center justify-center mb-6 shadow-none">
                    <svg class="w-12 h-12 text-[#9c5252]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                {{-- Pesan Error --}}
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#172c35] mb-3">
                    Akses Ditolak
                </h1>
                
                <p class="text-[#64747a] mb-8 max-w-md text-sm sm:text-base leading-relaxed">
                    Maaf, Anda tidak memiliki izin (hak akses) untuk melakukan tindakan ini atau melihat halaman ini. Silakan hubungi Administrator jika Anda merasa ini adalah sebuah kesalahan.
                </p>

                {{-- Tombol Kembali --}}
                <a href="{{ route('dashboard') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-6 py-3 bg-[#244e49] hover:bg-[#173d38] text-white font-semibold rounded-lg transition-all shadow-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Beranda
                </a>
                
                {{-- Detail Tambahan --}}
                <div class="mt-12 text-xs text-[#71817f] font-medium">
                    Kode Error: 403 Forbidden
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
