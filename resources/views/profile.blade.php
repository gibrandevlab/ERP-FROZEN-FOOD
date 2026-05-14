<x-app-layout>
    <div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">
        {{-- ── Header ─────────────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-extrabold" style="color: #1E293B;">Profil Saya</h1>
                <p class="text-xs text-slate-500 mt-0.5">Atur informasi akun dan keamanan Anda</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>

        {{-- ── Forms ──────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
                    <div class="px-6 py-5 border-b border-slate-50" style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                        <h2 class="text-sm font-bold text-slate-700">Informasi Profil</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Perbarui nama lengkap dan alamat email akun Anda.</p>
                    </div>
                    <div class="p-6">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
                    <div class="px-6 py-5 border-b border-slate-50" style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                        <h2 class="text-sm font-bold text-slate-700">Ubah Password</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pastikan akun Anda menggunakan password yang panjang dan acak untuk tetap aman.</p>
                    </div>
                    <div class="p-6">
                        <livewire:profile.update-password-form />
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
                    <div class="px-6 py-5 border-b border-slate-50" style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                        <h2 class="text-sm font-bold text-slate-700">Sesi Akun</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Keluar dari perangkat ini.</p>
                    </div>
                    <div class="p-6">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-6 py-2.5 bg-slate-50 text-slate-600 rounded-xl text-sm font-semibold border border-slate-200 hover:bg-slate-100 hover:text-slate-800 transition-colors w-full sm:w-auto">
                                Keluar Akun
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-red-50/50 rounded-2xl border border-red-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-red-100/50">
                        <h2 class="text-sm font-bold text-red-700">Hapus Akun</h2>
                        <p class="text-xs text-red-500/80 mt-0.5">Tindakan ini tidak bisa dibatalkan.</p>
                    </div>
                    <div class="p-6">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
