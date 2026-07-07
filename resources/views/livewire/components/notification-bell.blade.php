<?php

use App\Models\Notifikasi;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public function tandaiDibaca(string $id): void
    {
        $notif = Notifikasi::where('user_id', auth()->id())->find($id);

        if ($notif && !$notif->is_read) {
            $notif->update(['is_read' => true]);
        }
    }

    public function tandaiSemuaDibaca(): void
    {
        Notifikasi::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function tujuanUntuk(Notifikasi $n): string
    {
        $user = auth()->user();

        if ($user->isPemohon()) {
            return route('pemohon.detail', $n->permohonan_id);
        }

        if ($user->isVerifikator()) {
            return route('verifikator.detail', $n->permohonan_id);
        }

        return url('/dashboard');
    }

    #[Computed]
    public function unreadCount(): int
    {
        return Notifikasi::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }

    #[Computed]
    public function terbaru()
    {
        // Dropdown hanya menampilkan notifikasi yang belum dibaca.
        // Daftar lengkap tersedia di halaman notifikasi.
        return Notifikasi::where('user_id', auth()->id())
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }
};

?>

<div x-data="{ open: false }" class="relative" wire:poll.15s>
    <button @click="open = !open" type="button" class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100"
        aria-label="Notifikasi">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if ($this->unreadCount > 0)
            <span
                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-transition @click.outside="open = false"
        class="absolute right-0 z-30 mt-2 w-80 origin-top-right rounded-xl border border-gray-200 bg-white shadow-xl"
        style="display: none;">

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5">
            <p class="text-sm font-semibold text-gray-800">Notifikasi</p>
            @if ($this->unreadCount > 0)
                <button wire:click="tandaiSemuaDibaca"
                    class="text-xs font-medium text-primary-600 hover:text-primary-700">
                    Tandai semua dibaca
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($this->terbaru as $n)
                <a href="{{ $this->tujuanUntuk($n) }}" wire:click.prevent="tandaiDibaca('{{ $n->id }}')"
                    onclick="setTimeout(() => window.location.href = '{{ $this->tujuanUntuk($n) }}', 100)"
                    wire:key="{{ $n->id }}"
                    class="flex gap-3 border-b border-gray-50 px-4 py-3 transition hover:bg-gray-50 {{ $n->is_read ? '' : 'bg-primary-50/40' }}">
                    <div
                        class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $n->is_read ? 'bg-transparent' : 'bg-primary-600' }}">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $n->judul }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-gray-600 line-clamp-2">{{ $n->pesan }}</p>
                        <p class="mt-1 text-[11px] text-gray-400">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <p class="text-sm text-gray-400">Tidak ada notifikasi belum dibaca.</p>
                </div>
            @endforelse
        </div>

        <a href="{{ route('notifikasi.daftar') }}" wire:navigate @click="open = false"
            class="block border-t border-gray-100 px-4 py-2.5 text-center text-xs font-medium text-primary-600 hover:bg-gray-50">
            Lihat semua notifikasi
        </a>
    </div>
</div>
