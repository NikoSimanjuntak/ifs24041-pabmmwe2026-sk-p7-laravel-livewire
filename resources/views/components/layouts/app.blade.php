<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Stock Barang' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/">Stock Barang</a>
        @auth
            <div class="navbar-nav me-auto">
                @if(auth()->user()->isAdmin())
                    <a class="nav-link" href="{{ route('admin.users') }}">Users</a>
                @else
                    <a class="nav-link" href="{{ route('stocks.index') }}">Stok</a>
                @endif
                <a class="nav-link" href="{{ route('profile') }}">Profil</a>
            </div>
            <div class="d-flex align-items-center gap-2 text-white">
                @if(auth()->user()->photoUrl())
                    <img src="{{ auth()->user()->photoUrl() }}" alt="Foto profil" class="rounded-circle" width="32" height="32" style="object-fit:cover">
                @endif
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light" id="logout-submit">Logout</button>
                </form>
            </div>
        @endauth
    </div>
</nav>
<main class="container py-4">
    {{ $slot }}
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
