<?php

use App\Enums\TipeNotifikasi;
use App\Models\Notifikasi;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $filter = 'semua';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

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

        session()->flash('ok', 'Semua notifikasi ditandai dibaca.');
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

    public function with(): array
    {
        $query = Notifikasi::where('user_id', auth()->id());

        if ($this->filter === 'belum') {
            $query->where('is_read', false);
        } elseif ($this->filter === 'sudah') {
            $query->where('is_read', true);
        }

        return [
            'daftar' => $query->orderByDesc('created_at')->paginate(15),
            'jumlahBelum' => Notifikasi::where('user_id', auth()->id())
                ->where('is_read', false)
                ->count(),
        ];
    }
};

?>

<div class="space-y-5" wire:poll.20s>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Notifikasi</h1>
            <p class="text-sm text-gray-500">
                @if ($jumlahBelum > 0)
                    {{ $jumlahBelum }} notifikasi belum dibaca.
                @else
                    Semua notifikasi sudah dibaca.
                @endif
            </p>
        </div>
        @if ($jumlahBelum > 0)
            <button wire:click="tandaiSemuaDibaca"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Tandai semua dibaca
            </button>
        @endif
    </div>

    @if (session('ok'))
        <x-toast :message="session('ok')" />
    @endif

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 p-4">
            <select wire:model.live="filter"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="semua">Semua</option>
                <option value="belum">Belum Dibaca</option>
                <option value="sudah">Sudah Dibaca</option>
            </select>
        </div>

        <ul class="divide-y divide-gray-100">
            @forelse ($daftar as $n)
                <li wire:key="{{ $n->id }}"
                    class="px-4 py-4 transition hover:bg-gray-50 {{ $n->is_read ? '' : 'bg-primary-50/40' }}">
                    <div class="flex gap-3">
                        <div
                            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $n->is_read ? 'bg-gray-200' : 'bg-primary-600' }}">
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-800">{{ $n->judul }}</p>
                                <p class="text-xs text-gray-400">{{ $n->created_at->translatedFormat('d F Y, H:i') }}
                                    &middot; {{ $n->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="mt-1 text-sm leading-relaxed text-gray-700">{{ $n->pesan }}</p>
                            <div class="mt-2 flex gap-3 text-xs">
                                <a href="{{ $this->tujuanUntuk($n) }}"
                                    onclick="event.preventDefault(); @this.call('tandaiDibaca', '{{ $n->id }}'); setTimeout(() => window.location.href = '{{ $this->tujuanUntuk($n) }}', 100);"
                                    class="font-medium text-primary-600 hover:text-primary-700">
                                    Buka permohonan &rarr;
                                </a>
                                @if (!$n->is_read)
                                    <button wire:click="tandaiDibaca('{{ $n->id }}')"
                                        class="font-medium text-gray-500 hover:text-gray-700">
                                        Tandai dibaca
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center">
                    <p class="text-sm text-gray-400">Belum ada notifikasi pada filter ini.</p>
                </li>
            @endforelse
        </ul>

        @if ($daftar->hasPages())
            <div class="border-t border-gray-100 p-4">{{ $daftar->links() }}</div>
        @endif
    </div>
</div>
