<?php

use App\Enums\StatusPermohonan;
use App\Models\Permohonan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {

    use WithPagination;

    public function hapusDraft(string $id): void
    {
        $permohonan = Permohonan::where('pemohon_id', auth()->id())
            ->with('dokumen')
            ->findOrFail($id);

        $this->authorize('forceDelete', $permohonan);

        try {
            foreach ($permohonan->dokumen as $dokumen) {
                Storage::disk('local')->delete($dokumen->path_file);
            }

            $permohonan->forceDelete();

        } catch (\Throwable $e) {
            report($e);

            $this->dispatch('toast', message: 'Gagal menghapus draft.', type: 'error');
            return;
        }

        $this->dispatch('toast', message: 'Draft berhasil dihapus.', type: 'success');
        $this->resetPage();
    }

    public function with(): array
    {
        $userId = auth()->id();

        return [
            'daftar' => Permohonan::with(['pemohon'])
                ->where('pemohon_id', $userId)
                ->orderByDesc('created_at')
                ->paginate(10),

            'ringkasan' => [
                'draft' => Permohonan::where('pemohon_id', $userId)
                    ->where('status', StatusPermohonan::Draft)->count(),

                'menunggu' => Permohonan::where('pemohon_id', $userId)
                    ->where('status', StatusPermohonan::MenungguVerifikasi)->count(),

                'diterima' => Permohonan::where('pemohon_id', $userId)
                    ->where('status', StatusPermohonan::Diterima)->count(),

                'ditolak' => Permohonan::where('pemohon_id', $userId)
                    ->where('status', StatusPermohonan::Ditolak)->count(),
            ],
        ];
    }
};
?>

<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold">Dashboard Pemohon</h1>
            <p class="text-sm text-gray-500">
                Selamat datang, {{ auth()->user()->nama_lengkap }}
            </p>
        </div>

        <a href="{{ route('pemohon.buat') }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">
            + Buat Permohonan
        </a>
    </div>

    {{-- RINGKASAN --}}
    <div class="grid grid-cols-4 gap-3">

        <div class="p-4 bg-white shadow rounded-xl">
            <p class="text-xs text-gray-400">Draft</p>
            <p class="text-2xl font-bold">{{ $ringkasan['draft'] }}</p>
        </div>

        <div class="p-4 bg-white shadow rounded-xl">
            <p class="text-xs text-gray-400">Menunggu</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $ringkasan['menunggu'] }}</p>
        </div>

        <div class="p-4 bg-white shadow rounded-xl">
            <p class="text-xs text-gray-400">Diterima</p>
            <p class="text-2xl font-bold text-green-600">{{ $ringkasan['diterima'] }}</p>
        </div>

        <div class="p-4 bg-white shadow rounded-xl">
            <p class="text-xs text-gray-400">Ditolak</p>
            <p class="text-2xl font-bold text-red-600">{{ $ringkasan['ditolak'] }}</p>
        </div>

    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="p-4 border-b">
            <h2 class="text-sm font-semibold text-gray-500 uppercase">
                Riwayat Permohonan
            </h2>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Nomor</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Jenis</th>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                    @forelse ($daftar as $p)

                        <tr wire:key="{{ $p->id }}" class="hover:bg-gray-50">

                            {{-- Nomor --}}
                            <td class="px-4 py-3 font-medium">
                                {{ $p->nomor_permohonan ?? '—' }}
                            </td>

                            {{-- Nama --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ $p->pemohon->nama_lengkap ?? '—' }}
                            </td>

                            {{-- Jenis --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ $p->jenis_permohonan ?? '—' }}
                            </td>

                            {{-- Tanggal --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ $p->created_at?->translatedFormat('d M Y, H:i') }}
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <x-badge-status :status="$p->status" />
                            </td>

                            {{-- Aksi --}}
                            <td class="px-4 py-3 text-right">

                                @if ($p->status === StatusPermohonan::Draft)

                                    <div class="flex gap-2 justify-end">

                                        <a href="{{ route('pemohon.buat', $p->id) }}"
                                            class="px-3 py-1 text-xs bg-primary-600 text-white rounded">
                                            Lanjutkan
                                        </a>

                                        <button @click="$dispatch('open-confirm', {
                                                        message: 'Hapus draft?',
                                                        callback: () => $wire.hapusDraft('{{ $p->id }}')
                                                    })" class="px-3 py-1 text-xs border text-red-600 rounded">
                                            Hapus
                                        </button>

                                    </div>

                                @elseif ($p->status === StatusPermohonan::Ditolak)

                                    <div class="flex gap-2 justify-end">

                                        <a href="{{ route('pemohon.detail', $p->id) }}"
                                            class="px-3 py-1 text-xs border rounded">
                                            Detail
                                        </a>

                                        <a href="{{ route('pemohon.perbaiki', $p->id) }}"
                                            class="px-3 py-1 text-xs bg-blue-600 text-white rounded">
                                            Perbaiki
                                        </a>

                                    </div>

                                @else

                                    <a href="{{ route('pemohon.detail', $p->id) }}" class="px-3 py-1 text-xs border rounded">
                                        Detail
                                    </a>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-400">
                                Belum ada permohonan
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="p-4 border-t">
            {{ $daftar->links() }}
        </div>

    </div>

</div>