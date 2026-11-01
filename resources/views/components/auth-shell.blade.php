@props(['title', 'subtitle' => null])

<main class="min-h-screen bg-[#f5f6f3] px-4 py-8 font-sans text-[#172c35] sm:px-6 sm:py-12">
    <div class="mx-auto w-full max-w-xl">
        <a href="/" class="inline-flex items-center gap-3 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#527c70]" aria-label="KULKAS, kembali ke halaman utama">
            <span class="flex h-10 w-10 items-center justify-center border border-[#315b50]/30 bg-[#e8efeb] text-[#244e49]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="square" stroke-linejoin="round" d="M12 2.75v18.5M4 7.25l16 9.5M4 16.75l16-9.5M9.5 4.2 12 6.7l2.5-2.5M9.5 19.8l2.5-2.5 2.5 2.5M3.8 10.3l3.4.9-.9 3.4m14-4.3-3.4.9.9 3.4" />
                </svg>
            </span>
            <span>
                <span class="block text-sm font-semibold tracking-[0.2em] text-[#17343d]">KULKAS</span>
                <span class="mt-0.5 block text-[10px] uppercase tracking-[0.16em] text-[#64747a]">Operasional frozen food</span>
            </span>
        </a>

        <div class="mt-8 overflow-hidden rounded-lg border border-[#dce1de] bg-white">
            <div class="border-b border-[#e6eae7] bg-gradient-to-r from-[#f5f6f3] to-[#eef2ee] px-6 py-5 sm:px-8">
                <h1 class="text-2xl font-semibold tracking-tight text-[#172c35]">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1.5 text-sm leading-6 text-[#64747a]">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="px-6 py-6 sm:px-8 sm:py-7">
                {{ $slot }}
            </div>
        </div>

        <p class="mt-6 text-center text-xs tracking-wide text-[#71817f]">
            KULKAS <span class="px-1.5 text-[#a9beb5]">/</span> Operasional frozen food
        </p>
    </div>
</main>
