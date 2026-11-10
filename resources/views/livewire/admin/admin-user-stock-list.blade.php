<div>
    <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-secondary mb-2">&laquo; Kembali</a>
    <h3 class="mb-1">Stok milik {{ $user->name }} <small class="text-muted">({{ $user->username }})</small></h3>
    <p class="text-muted">Mode baca saja. Admin tidak dapat mengubah atau menghapus stok.</p>

    <div class="row g-2 mb-3">
        <div class="col-md-8"><input type="search" class="form-control" placeholder="Cari nama / SKU..." wire:model.live.debounce.300ms="search"></div>
        <div class="col-md-4">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">Semua status</option>
                @foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle bg-white">
            <thead><tr><th>Nama</th><th>SKU</th><th class="text-end">Qty</th><th>Satuan</th><th class="text-end">Harga</th><th class="text-end">Min</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($stocks as $stock)
                <tr wire:key="stock-{{ $stock->id }}">
                    <td>{{ $stock->name }}</td>
                    <td>{{ $stock->sku }}</td>
                    <td class="text-end">{{ $stock->quantity }}</td>
                    <td>{{ $stock->unit }}</td>
                    <td class="text-end">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $stock->min_stock }}</td>
                    <td><span class="badge {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Belum ada stok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $stocks->links() }}
</div>
