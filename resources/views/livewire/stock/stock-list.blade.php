<div>
    @if($message)<div class="alert alert-success">{{ $message }}</div>@endif

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h3 class="m-0">Stok Barang</h3>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary" wire:click="create" id="stock-create">+ Tambah</button>
            <button type="button" class="btn btn-outline-success" wire:click="export" id="stock-export">Export Excel</button>
            <button type="button" class="btn btn-outline-secondary" wire:click="$toggle('showImport')">Import Excel</button>
            <button type="button" class="btn btn-outline-dark" wire:click="$toggle('showAi')">Generate AI</button>
        </div>
    </div>

    @if($showImport)
        <div class="card mb-3"><div class="card-body">
            <h6>Import Excel</h6>
            <p class="small text-muted mb-2">Baris pertama adalah header: name, sku, qty, unit, price, min_stock, status, description. Status dihitung otomatis.</p>
            <form wire:submit="import" class="d-flex gap-2">
                <input type="file" class="form-control" wire:model="importFile" accept=".xlsx,.xls">
                <button type="submit" class="btn btn-success">Import</button>
            </form>
            @error('importFile') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div></div>
    @endif

    @if($showAi)
        <div class="card mb-3"><div class="card-body">
            <h6>Generate Item Stok dengan AI</h6>
            <form wire:submit="generateAi" class="row g-2">
                <div class="col-md-8"><input type="text" class="form-control" placeholder="mis. perlengkapan alat tulis kantor" wire:model="aiPrompt"></div>
                <div class="col-md-2"><input type="number" class="form-control" min="1" max="10" wire:model="aiCount"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-dark w-100">Generate</button></div>
            </form>
            @error('aiPrompt') <div class="text-danger small">{{ $message }}</div> @enderror
            @error('aiCount') <div class="text-danger small">{{ $message }}</div> @enderror
            @if($aiError)<div class="alert alert-danger mt-2 mb-0">{{ $aiError }}</div>@endif

            @if(count($aiItems) > 0)
                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle">
                        <thead><tr><th></th><th>Nama</th><th>SKU</th><th>Qty</th><th>Satuan</th><th>Harga</th><th>Min</th></tr></thead>
                        <tbody>
                        @foreach($aiItems as $i => $item)
                            <tr wire:key="ai-{{ $i }}">
                                <td><input type="checkbox" class="form-check-input" value="{{ $i }}" wire:model="aiSelected"></td>
                                <td>{{ $item['name'] }}</td><td>{{ $item['sku'] }}</td><td>{{ $item['quantity'] }}</td>
                                <td>{{ $item['unit'] }}</td><td>{{ $item['price'] !== null ? number_format((float) $item['price'], 0, ',', '.') : '-' }}</td><td>{{ $item['min_stock'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @error('aiSelected') <div class="text-danger small">{{ $message }}</div> @enderror
                <button type="button" class="btn btn-primary" wire:click="saveAi">Simpan item terpilih</button>
            @endif
        </div></div>
    @endif

    <div class="row g-2 mb-3">
        <div class="col-md-8"><input type="search" class="form-control" placeholder="Cari nama / SKU..." wire:model.live.debounce.300ms="search" id="stock-search"></div>
        <div class="col-md-4">
            <select class="form-select" wire:model.live="statusFilter" id="stock-status-filter">
                <option value="">Semua status</option>
                @foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle bg-white">
            <thead><tr><th></th><th>Nama</th><th>SKU</th><th class="text-center">Qty</th><th>Satuan</th><th class="text-end">Harga</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($stocks as $stock)
                <tr wire:key="stock-{{ $stock->id }}">
                    <td style="width:56px">@if($stock->coverUrl())<img src="{{ $stock->coverUrl() }}" alt="Cover" width="48" height="48" style="object-fit:cover" class="rounded">@endif</td>
                    <td><a href="{{ route('stocks.show', $stock) }}">{{ $stock->name }}</a></td>
                    <td>{{ $stock->sku }}</td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="adjust({{ $stock->id }}, -1)" aria-label="Kurangi">&minus;</button>
                        <strong class="mx-2">{{ $stock->quantity }}</strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="adjust({{ $stock->id }}, 1)" aria-label="Tambah">+</button>
                    </td>
                    <td>{{ $stock->unit }}</td>
                    <td class="text-end">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                    <td><span class="badge {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span></td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="edit({{ $stock->id }})">Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete({{ $stock->id }})" wire:confirm="Hapus stok ini?">Hapus</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">Belum ada stok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $stocks->links() }}

    @if($showForm)
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" wire:submit="save">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingId ? 'Edit Stok' : 'Tambah Stok' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeForm" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="f-name">Nama</label>
                            <input type="text" id="f-name" class="form-control" wire:model="name">
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="f-sku">SKU</label>
                            <input type="text" id="f-sku" class="form-control" wire:model="sku">
                            @error('sku') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="f-qty">Quantity</label>
                            <input type="number" min="0" id="f-qty" class="form-control" wire:model="quantity">
                            @error('quantity') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="f-unit">Satuan</label>
                            <input type="text" id="f-unit" class="form-control" wire:model="unit">
                            @error('unit') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="f-price">Harga</label>
                            <input type="number" min="0" step="0.01" id="f-price" class="form-control" wire:model="price">
                            @error('price') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="f-min">Min. stok</label>
                            <input type="number" min="0" id="f-min" class="form-control" wire:model="min_stock">
                            @error('min_stock') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="f-desc">Deskripsi (Markdown)</label>
                            <textarea id="f-desc" rows="4" class="form-control" wire:model="description"></textarea>
                            @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="f-cover">Cover</label>
                            <input type="file" id="f-cover" class="form-control" wire:model="cover" accept="image/*">
                            @error('cover') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 small text-muted">Status (tersedia / menipis / habis) dihitung otomatis dari quantity dan min. stok.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeForm">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
