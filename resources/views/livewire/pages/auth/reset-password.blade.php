<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Halaman ini tidak lagi digunakan.
     * Sistem reset password via token email sudah diganti
     * dengan sistem kata rahasia (mnemonic recovery phrase).
     *
     * Redirect otomatis ke halaman Pulihkan Akun.
     */
    public function mount(): void
    {
        $this->redirectRoute('password.request', navigate: true);
    }
}; ?>

<div>
    {{-- Halaman ini redirect otomatis ke /pulihkan-akun --}}
    <p class="text-sm text-gray-500 text-center">Mengalihkan...</p>
</div>
