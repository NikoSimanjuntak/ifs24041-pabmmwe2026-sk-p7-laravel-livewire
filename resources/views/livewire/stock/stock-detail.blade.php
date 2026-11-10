<div>
    @assets
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/3.2.2/toastui-editor.min.css">
    <script src="https://uicdn.toast.com/editor/3.2.2/toastui-editor-all.min.js"></script>
    @endassets

    <a href="{{ route('stocks.index') }}" class="btn btn-sm btn-outline-secondary mb-2">&laquo; Kembali</a>
    @if($message)<div class="alert alert-success">{{ $message }}</div>@endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                @if($cover)
                    <img src="{{ $cover->temporaryUrl() }}" alt="Pratinjau cover" class="img-fluid rounded mb-2">
                @elseif($stock->coverUrl())
                    <img src="{{ $stock->coverUrl() }}" alt="Cover {{ $stock->name }}" class="img-fluid rounded mb-2">
                @else
                    <div class="bg-secondary-subtle text-center text-muted py-5 rounded mb-2">Belum ada cover</div>
                @endif
                <form wire:submit="saveCover">
                    <input type="file" class="form-control mb-2" wire:model="cover" accept="image/*">
                    @error('cover') <div class="text-danger small">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-sm btn-primary">Simpan Cover</button>
                </form>
            </div></div>
        </div>

        <div class="col-md-8">
            <div class="card mb-3"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-1">{{ $stock->name }}</h3>
                        <span class="badge {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                    </div>
                    @unless($editing)
                        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="startEdit">Edit</button>
                    @endunless
                </div>

                <div class="d-flex align-items-center gap-2 my-3">
                    <span class="text-muted">Quantity:</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="adjust(-10)">-10</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="adjust(-1)">-1</button>
                    <strong class="fs-4 mx-2">{{ $stock->quantity }} {{ $stock->unit }}</strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="adjust(1)">+1</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="adjust(10)">+10</button>
                </div>

                @if($editing)
                    <form wire:submit="save" class="row g-2">
                        <div class="col-md-8"><label class="form-label">Nama</label><input type="text" class="form-control" wire:model="name">@error('name') <div class="text-danger small">{{ $message }}</div> @enderror</div>
                        <div class="col-md-4"><label class="form-label">SKU</label><input type="text" class="form-control" wire:model="sku">@error('sku') <div class="text-danger small">{{ $message }}</div> @enderror</div>
                        <div class="col-md-3"><label class="form-label">Quantity</label><input type="number" min="0" class="form-control" wire:model="quantity">@error('quantity') <div class="text-danger small">{{ $message }}</div> @enderror</div>
                        <div class="col-md-3"><label class="form-label">Satuan</label><input type="text" class="form-control" wire:model="unit">@error('unit') <div class="text-danger small">{{ $message }}</div> @enderror</div>
                        <div class="col-md-3"><label class="form-label">Harga</label><input type="number" min="0" step="0.01" class="form-control" wire:model="price">@error('price') <div class="text-danger small">{{ $message }}</div> @enderror</div>
                        <div class="col-md-3"><label class="form-label">Min. stok</label><input type="number" min="0" class="form-control" wire:model="min_stock">@error('min_stock') <div class="text-danger small">{{ $message }}</div> @enderror</div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelEdit">Batal</button>
                        </div>
                    </form>
                @else
                    <dl class="row mb-0">
                        <dt class="col-sm-3">SKU</dt><dd class="col-sm-9">{{ $stock->sku ?? '-' }}</dd>
                        <dt class="col-sm-3">Harga</dt><dd class="col-sm-9">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</dd>
                        <dt class="col-sm-3">Min. stok</dt><dd class="col-sm-9">{{ $stock->min_stock }}</dd>
                    </dl>
                @endif
            </div></div>

            <div class="card"><div class="card-body">
                <h5>Deskripsi</h5>
                <div wire:ignore><div id="stock-description-editor"></div></div>
                @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                <button type="button" class="btn btn-primary btn-sm mt-2" wire:click="saveDescription">Simpan Deskripsi</button>
            </div></div>
        </div>
    </div>
</div>

@script
<script>
    const editor = new toastui.Editor({
        el: document.querySelector('#stock-description-editor'),
        height: '320px',
        initialEditType: 'wysiwyg',
        previewStyle: 'vertical',
        initialValue: $wire.description ?? '',
    });
    editor.on('change', () => { $wire.description = editor.getMarkdown(); });
</script>
@endscript
