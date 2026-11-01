<x-app-layout>
    <div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">
        {{-- ── Header ─────────────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-extrabold" style="color: #172c35;">Profil Saya</h1>
                <p class="text-xs text-[#64747a] mt-0.5">Atur informasi akun dan keamanan Anda</p>
            </div>
            <div class="w-10 h-10 rounded-md bg-gradient-to-br from-[#527c70] to-[#17343d] flex items-center justify-center text-white font-bold shadow-none shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>

        {{-- ── Forms ──────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg border border-[#e6eae7] overflow-hidden" style="box-shadow: 0 1px 2px rgba(23, 44, 53, 0.04);">
                    <div class="px-6 py-5 border-b border-[#e6eae7]" style="background: linear-gradient(135deg, #f5f6f3, #eef2ee);">
                        <h2 class="text-sm font-bold text-[#344950]">Informasi Profil</h2>
                        <p class="text-xs text-[#64747a] mt-0.5">Perbarui nama lengkap dan alamat email akun Anda.</p>
                    </div>
                    <div class="p-6">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                <div class="bg-white rounded-lg border border-[#e6eae7] overflow-hidden" style="box-shadow: 0 1px 2px rgba(23, 44, 53, 0.04);">
                    <div class="px-6 py-5 border-b border-[#e6eae7]" style="background: linear-gradient(135deg, #f5f6f3, #eef2ee);">
                        <h2 class="text-sm font-bold text-[#344950]">Ubah Password</h2>
                        <p class="text-xs text-[#64747a] mt-0.5">Pastikan akun Anda menggunakan password yang panjang dan acak untuk tetap aman.</p>
                    </div>
                    <div class="p-6">
                        <livewire:profile.update-password-form />
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-lg border border-[#e6eae7] overflow-hidden" style="box-shadow: 0 1px 2px rgba(23, 44, 53, 0.04);">
                    <div class="px-6 py-5 border-b border-[#e6eae7]" style="background: linear-gradient(135deg, #f5f6f3, #eef2ee);">
                        <h2 class="text-sm font-bold text-[#344950]">Sesi Akun</h2>
                        <p class="text-xs text-[#64747a] mt-0.5">Keluar dari perangkat ini.</p>
                    </div>
                    <div class="p-6">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-6 py-2.5 bg-[#f5f6f3] text-[#52646a] rounded-md text-sm font-semibold border border-[#dce1de] hover:bg-[#e6eae7] hover:text-[#344950] transition-colors w-full sm:w-auto">
                                Keluar Akun
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-[#f7eceb]/60 rounded-lg border border-[#ead6d4] overflow-hidden">
                    <div class="px-6 py-5 border-b border-[#ead6d4]/70">
                        <h2 class="text-sm font-bold text-[#7a3d3d]">Hapus Akun</h2>
                        <p class="text-xs text-[#9c5252] mt-0.5">Tindakan ini tidak bisa dibatalkan.</p>
                    </div>
                    <div class="p-6">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
