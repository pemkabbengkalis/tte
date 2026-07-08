<?php

use App\Enums\StatusPermohonan;
use App\Models\Permohonan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'semua')]
    public string $statusFilter = 'semua';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function hapus(string $id): void
    {
        $permohonan = Permohonan::findOrFail($id);

        $this->authorize('delete', $permohonan);

        try {
            $permohonan->delete(); // soft delete
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Gagal menghapus permohonan. Silakan coba lagi.');
            return;
        }

        session()->flash('ok', 'Permohonan berhasil dihapus.');
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Permohonan::query()->whereNotNull('nomor_permohonan')->with('pemohon');

        if ($this->statusFilter !== 'semua') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query
                ->whereHas('pemohon', function ($q) use ($term) {
                    $q->where('nama_lengkap', 'like', $term)->orWhere('nip', 'like', $term);
                })
                ->orWhere('nomor_permohonan', 'like', $term);
        }

        return [
            'daftar' => $query->orderByDesc('tanggal_pengajuan')->paginate(10),
            'ringkasan' => [
                'menunggu' => Permohonan::where('status', StatusPermohonan::MenungguVerifikasi)->count(),
                'diproses' => Permohonan::where('status', StatusPermohonan::Diproses)->count(),
                'diterima' => Permohonan::where('status', StatusPermohonan::Diterima)->count(),
                'ditolak' => Permohonan::where('status', StatusPermohonan::Ditolak)->count(),
                'total' => Permohonan::whereNotNull('nomor_permohonan')->count(),
            ],
        ];
    }
};

?>

<div class="space-y-5">
    @if (session('ok'))
        <x-toast :message="session('ok')" />
    @endif

    @if (session('error'))
        <x-toast :message="session('error')" type="error" />
    @endif

    <div>
        <h1 class="text-xl font-semibold text-gray-800">Dashboard Verifikator</h1>
        <p class="text-sm text-gray-500">Verifikasi permohonan sertifikat elektronik dari ASN.</p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
        <button wire:click="$set('statusFilter', 'menunggu_verifikasi')"
            class="rounded-xl bg-white p-4 text-left shadow-sm ring-1 ring-gray-100 transition hover:ring-yellow-300">
            <p class="text-xs text-gray-400">Menunggu Verifikasi</p>
            <p class="mt-1 text-2xl font-bold text-yellow-600">{{ $ringkasan['menunggu'] }}</p>
        </button>
        <button wire:click="$set('statusFilter', 'diproses')"
            class="rounded-xl bg-white p-4 text-left shadow-sm ring-1 ring-gray-100 transition hover:ring-indigo-300">
            <p class="text-xs text-gray-400">Diproses</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600">{{ $ringkasan['diproses'] }}</p>
        </button>
        <button wire:click="$set('statusFilter', 'diterima')"
            class="rounded-xl bg-white p-4 text-left shadow-sm ring-1 ring-gray-100 transition hover:ring-green-300">
            <p class="text-xs text-gray-400">Diterima</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ $ringkasan['diterima'] }}</p>
        </button>
        <button wire:click="$set('statusFilter', 'ditolak')"
            class="rounded-xl bg-white p-4 text-left shadow-sm ring-1 ring-gray-100 transition hover:ring-red-300">
            <p class="text-xs text-gray-400">Ditolak</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ $ringkasan['ditolak'] }}</p>
        </button>
        <button wire:click="$set('statusFilter', 'semua')"
            class="rounded-xl bg-white p-4 text-left shadow-sm ring-1 ring-gray-100 transition hover:ring-primary-300">
            <p class="text-xs text-gray-400">Total</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ $ringkasan['total'] }}</p>
        </button>
    </div>

    {{-- Tabel --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-4">
            <div class="flex-1 min-w-[200px]">
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Cari nomor, nama, atau NIP..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </div>
            <select wire:model.live="statusFilter"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                <option value="semua">Semua Status</option>
                <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                <option value="diproses">Diproses</option>
                <option value="diterima">Diterima</option>
                <option value="ditolak">Ditolak</option>
                <option value="selesai">Selesai</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Pemohon</th>
                        <th class="px-4 py-3 font-medium">Tanggal Pengajuan</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($daftar as $p)
                        <tr wire:key="{{ $p->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $p->nomor_permohonan }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $p->pemohon->nama_lengkap }}</div>
                                <div class="text-xs text-gray-400">{{ $p->pemohon->nip }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $p->tanggal_pengajuan?->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-4 py-3"><x-badge-status :status="$p->status" /></td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <a href="{{ route('verifikator.detail', $p->id) }}" wire:navigate
                                        class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700">
                                        Periksa
                                    </a>
                                    <button type="button"
                                        @click="$dispatch('open-confirm', { message: 'Hapus permohonan {{ $p->nomor_permohonan }}? Data akan dipindahkan ke arsip terhapus.', callback: () => $wire.hapus('{{ $p->id }}') })"
                                        class="inline-flex items-center rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                                Tidak ada permohonan yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftar->hasPages())
            <div class="border-t border-gray-100 p-4">{{ $daftar->links() }}</div>
        @endif
    </div>
</div>
