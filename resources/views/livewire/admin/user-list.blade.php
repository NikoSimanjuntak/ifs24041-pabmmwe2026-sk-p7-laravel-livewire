<div>
    <h3 class="mb-3">Daftar User</h3>
    <input type="search" class="form-control mb-3" placeholder="Cari nama / username..." wire:model.live.debounce.300ms="search" id="user-search">
    <div class="table-responsive">
        <table class="table table-striped align-middle bg-white">
            <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th class="text-end">Jumlah Stok</th><th class="text-end">Total Qty</th><th></th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->name }}</td>
                    <td><span class="badge bg-secondary">{{ $user->role }}</span></td>
                    <td class="text-end">{{ $user->stocks_count }}</td>
                    <td class="text-end">{{ (int) $user->stocks_sum_quantity }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.stocks', $user) }}">Lihat Stok</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">Tidak ada user.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
