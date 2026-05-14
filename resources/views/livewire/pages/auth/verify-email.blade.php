<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Halaman verifikasi email tidak digunakan.
     * Sistem ini tidak menggunakan email verification.
     * Redirect ke dashboard jika sudah login.
     */
    public function mount(): void
    {
        $this->redirectRoute('dashboard', navigate: true);
    }
}; ?>

<div>
    <p class="text-sm text-gray-500 text-center">Mengalihkan ke dashboard...</p>
</div>
