<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {};

?>

<div class="space-y-4">
    <h1 class="text-xl font-semibold text-gray-800">Dashboard Administrator</h1>
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <p class="text-sm text-gray-600">
            Selamat datang, <span class="font-medium text-primary-700">{{ auth()->user()->nama_lengkap }}</span>.
        </p>
    </div>
</div>