<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Seklov</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f8f9] text-[#202b3d] antialiased">

    {{-- Top Navbar --}}
    <header class="sticky top-0 z-30 border-b border-[#e5eaed] bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="text-[22px] font-extrabold tracking-tight text-[#084734]">
                    Seklov
                </a>
                <span class="rounded-full bg-[#e6f2ee] px-3 py-1 text-[12px] font-semibold text-[#084734]">
                    Admin Panel
                </span>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] font-medium text-[#202b3d] hover:bg-[#f7f9fa]">
                    Lihat Beranda
                </a>
                <span class="hidden text-[13px] text-[#5c6a7a] sm:inline">
                    Halo, <strong class="font-semibold text-[#202b3d]">{{ auth()->user()->name }}</strong>
                </span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="rounded-[10px] bg-[#fee2e2] px-3.5 py-2 text-[13px] font-semibold text-[#b91c1c] hover:bg-[#fecaca]">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">

        {{-- Alert Notifikasi --}}
        @if (session('success'))
            <div class="rounded-[12px] border border-[#bbf7d0] bg-[#f0fdf4] px-4 py-3 text-[13px] font-medium text-[#166534]">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-[12px] border border-[#fecaca] bg-[#fef2f2] px-4 py-3 text-[13px] font-medium text-[#991b1b]">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-[12px] border border-[#fecaca] bg-[#fef2f2] px-4 py-3 text-[13px] text-[#991b1b]">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Ringkasan Statistik --}}
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-[16px] border border-[#e5eaed] bg-white p-5 shadow-xs">
                <p class="text-[12px] font-medium text-[#64748b]">Total Pengguna</p>
                <p class="mt-2 text-[28px] font-bold text-[#0f172a]">{{ $stats['total_users'] }}</p>
                <p class="mt-1 text-[12px] text-[#084734]">
                    {{ $stats['active_users'] }} aktif &middot;
                    <span class="text-[#64748b]">{{ $stats['inactive_users'] }} nonaktif</span>
                </p>
            </div>

            <div class="rounded-[16px] border border-[#e5eaed] bg-white p-5 shadow-xs">
                <p class="text-[12px] font-medium text-[#64748b]">Akun Administrator</p>
                <p class="mt-2 text-[28px] font-bold text-[#0f172a]">{{ $stats['total_admins'] }}</p>
                <p class="mt-1 text-[12px] text-[#64748b]">Role admin aktif</p>
            </div>

            <div class="rounded-[16px] border border-[#e5eaed] bg-white p-5 shadow-xs">
                <p class="text-[12px] font-medium text-[#64748b]">Titik COD Kampus</p>
                <p class="mt-2 text-[28px] font-bold text-[#0f172a]">{{ $stats['total_meeting_points'] }}</p>
                <p class="mt-1 text-[12px] text-[#084734]">{{ $stats['active_meeting_points'] }} titik aktif tersedia</p>
            </div>

            <div class="rounded-[16px] border border-[#e5eaed] bg-white p-5 shadow-xs">
                <p class="text-[12px] font-medium text-[#64748b]">Total Transaksi COD</p>
                <p class="mt-2 text-[28px] font-bold text-[#0f172a]">{{ $stats['total_transactions'] }}</p>
                <p class="mt-1 text-[12px] text-[#64748b]">Seluruh transaksi tercatat</p>
            </div>
        </section>

        {{-- Manajemen Titik COD & Kampus --}}
        <section class="rounded-[16px] border border-[#e5eaed] bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[18px] font-bold text-[#0f172a]">Manajemen Titik COD &amp; Kampus</h2>
                    <p class="text-[13px] text-[#64748b]">Kelola lokasi pertemuan COD resmi untuk kampus UNSIKA dan UBP.</p>
                </div>

                {{-- Filter Kampus + Live Search Titik COD --}}
                <div class="flex flex-wrap items-center gap-2">
                    <input type="text"
                           id="pointSearchInput"
                           value="{{ $pointSearch ?? '' }}"
                           oninput="filterMeetingPoints()"
                           placeholder="Cari titik COD / patokan..."
                           class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] text-[#202b3d] placeholder:text-[#94a3b8] focus:border-[#084734] focus:outline-none sm:w-64">

                    <select id="campusFilterSelect"
                            onchange="filterMeetingPoints()"
                            class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] font-medium text-[#202b3d] focus:border-[#084734] focus:outline-none">
                        <option value="" @selected(($campusFilter ?? '') === '')>Semua Kampus</option>
                        <option value="UNSIKA" @selected(($campusFilter ?? '') === 'UNSIKA')>UNSIKA</option>
                        <option value="UBP" @selected(($campusFilter ?? '') === 'UBP')>UBP</option>
                    </select>
                </div>
            </div>

            {{-- Form Tambah Titik COD --}}
            <form action="{{ route('admin.meeting-points.store') }}" method="POST"
                  class="mt-5 rounded-[14px] border border-[#e5eaed] bg-[#f9fbfc] p-4">
                @csrf
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-[12px] font-semibold text-[#334155]">Kampus</label>
                        <select name="campus" required
                                class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3 py-2 text-[13px] text-[#202b3d] focus:border-[#084734] focus:outline-none">
                            <option value="UNSIKA" @selected(old('campus') === 'UNSIKA')>UNSIKA</option>
                            <option value="UBP" @selected(old('campus') === 'UBP')>UBP</option>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="mb-1 block text-[12px] font-semibold text-[#334155]">Nama Titik COD</label>
                        <input type="text" name="point_name" value="{{ old('point_name') }}" required
                               placeholder="Contoh: Depan Gedung Rektorat"
                               class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3 py-2 text-[13px] text-[#202b3d] placeholder:text-[#94a3b8] focus:border-[#084734] focus:outline-none">
                    </div>

                    <div class="md:col-span-4">
                        <label class="mb-1 block text-[12px] font-semibold text-[#334155]">Deskripsi / Patokan Lokasi</label>
                        <input type="text" name="address_description" value="{{ old('address_description') }}" required
                               placeholder="Contoh: Area tangga lobi utama dekat pos satpam"
                               class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3 py-2 text-[13px] text-[#202b3d] placeholder:text-[#94a3b8] focus:border-[#084734] focus:outline-none">
                    </div>

                    <div class="flex items-center justify-between gap-3 md:col-span-3 md:justify-end">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-[13px] font-medium text-[#202b3d]">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="h-4 w-4 rounded border-[#cbd5e1] text-[#084734] focus:ring-[#084734]">
                            Aktif
                        </label>

                        <button type="submit"
                                class="rounded-[10px] bg-[#084734] px-4 py-2 text-[13px] font-semibold text-white hover:bg-[#063829]">
                            + Tambah Titik
                        </button>
                    </div>
                </div>
            </form>

            {{-- Tabel Titik COD --}}
            <div class="mt-5 overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-[#e5eaed] text-[11px] font-bold uppercase tracking-wider text-[#64748b]">
                            <th class="py-3 pr-4">Kampus</th>
                            <th class="py-3 pr-4">Nama Titik COD</th>
                            <th class="py-3 pr-4">Deskripsi Lokasi</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="meetingPointsTableBody" class="divide-y divide-[#eef2f5] text-[13px]">
                        @forelse ($meetingPoints as $point)
                            <tr class="meeting-point-row hover:bg-[#fafbfc]"
                                data-campus="{{ strtolower($point->campus) }}"
                                data-search="{{ strtolower($point->campus . ' ' . $point->point_name . ' ' . $point->address_description) }}">
                                <td class="py-3.5 pr-4 font-bold text-[#084734]">
                                    {{ $point->campus }}
                                </td>
                                <td class="py-3.5 pr-4 font-semibold text-[#0f172a]">
                                    {{ $point->point_name }}
                                </td>
                                <td class="py-3.5 pr-4 text-[#475569]">
                                    {{ $point->address_description }}
                                </td>
                                <td class="py-3.5 pr-4">
                                    @if ($point->is_active)
                                        <span class="inline-flex rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#15803d]">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-[#f1f5f9] px-2.5 py-0.5 text-[11px] font-semibold text-[#64748b]">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button type="button"
                                                data-id="{{ $point->meeting_point_id }}"
                                                data-campus="{{ $point->campus }}"
                                                data-name="{{ $point->point_name }}"
                                                data-description="{{ $point->address_description }}"
                                                data-active="{{ $point->is_active ? '1' : '0' }}"
                                                onclick="openEditPointModal(this)"
                                                class="rounded-[8px] border border-[#dce3e6] bg-white px-3 py-1.5 text-[12px] font-medium text-[#202b3d] hover:bg-[#f7f9fa]">
                                            Edit
                                        </button>

                                        <form action="{{ route('admin.meeting-points.destroy', $point) }}" method="POST"
                                              onsubmit="return confirm('Hapus titik COD {{ addslashes($point->point_name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-[8px] bg-[#fee2e2] px-3 py-1.5 text-[12px] font-medium text-[#b91c1c] hover:bg-[#fecaca]">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyServerRow">
                                <td colspan="5" class="py-6 text-center text-[13px] text-[#64748b]">
                                    Belum ada titik COD kampus terdaftar.
                                </td>
                            </tr>
                        @endforelse

                        <tr id="emptyFilterRow" style="display: none;">
                            <td colspan="5" class="py-6 text-center text-[13px] text-[#64748b]">
                                Tidak ada titik COD yang cocok dengan kata kunci atau kampus yang dipilih.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Moderasi Produk Penjual --}}
        <section class="rounded-[16px] border border-[#e5eaed] bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[18px] font-bold text-[#0f172a]">Moderasi Produk Penjual</h2>
                    <p class="text-[13px] text-[#64748b]">
                        Total {{ $stats['total_products'] ?? 0 }} produk ({{ $stats['available_products'] ?? 0 }} tersedia). Hapus produk yang melanggar aturan.
                    </p>
                </div>

                <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="product_q" value="{{ $productSearch ?? '' }}"
                           placeholder="Cari nama produk / penjual..."
                           class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] text-[#202b3d] placeholder:text-[#94a3b8] focus:border-[#084734] focus:outline-none">

                    <select name="product_status"
                            class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] text-[#202b3d] focus:border-[#084734] focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="available" @selected(($productStatusFilter ?? '') === 'available')>Tersedia</option>
                        <option value="sold" @selected(($productStatusFilter ?? '') === 'sold')>Terjual</option>
                        <option value="archived" @selected(($productStatusFilter ?? '') === 'archived')>Diarsipkan</option>
                    </select>

                    <button type="submit"
                            class="rounded-[10px] bg-[#084734] px-4 py-2 text-[13px] font-semibold text-white hover:bg-[#063829]">
                        Cari
                    </button>

                    @if (($productSearch ?? '') !== '' || ($productStatusFilter ?? '') !== '')
                        <a href="{{ route('admin.dashboard') }}"
                           class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] font-medium text-[#475569] hover:bg-[#f7f9fa]">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-[#e5eaed] text-[11px] font-bold uppercase tracking-wider text-[#64748b]">
                            <th class="py-3 pr-4">Produk</th>
                            <th class="py-3 pr-4">Penjual</th>
                            <th class="py-3 pr-4">Harga / Stok</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eef2f5] text-[13px]">
                        @forelse ($products ?? [] as $product)
                            <tr class="hover:bg-[#fafbfc]">
                                <td class="py-3.5 pr-4">
                                    <p class="font-semibold text-[#0f172a]">{{ $product->product_name }}</p>
                                    <p class="text-[12px] text-[#64748b]">
                                        {{ $product->category->category_name ?? 'Tanpa Kategori' }} &middot; Kondisi: {{ $product->condition ?? '-' }}
                                    </p>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <p class="font-medium text-[#202b3d]">{{ $product->seller->name ?? '-' }}</p>
                                    <p class="text-[12px] text-[#64748b]">
                                        {{ $product->seller->campus ?? '-' }} &middot; {{ $product->seller->email ?? '-' }}
                                    </p>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <p class="font-semibold text-[#084734]">
                                        Rp {{ number_format((float) $product->price, 0, ',', '.') }}
                                    </p>
                                    <p class="text-[12px] text-[#64748b]">Stok: {{ $product->stock }}</p>
                                </td>
                                <td class="py-3.5 pr-4">
                                    @if ($product->status === 'available')
                                        <span class="inline-flex rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#15803d]">
                                            Tersedia
                                        </span>
                                    @elseif ($product->status === 'sold')
                                        <span class="inline-flex rounded-full bg-[#e0f2fe] px-2.5 py-0.5 text-[11px] font-semibold text-[#0369a1]">
                                            Terjual
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-[#f1f5f9] px-2.5 py-0.5 text-[11px] font-semibold text-[#64748b]">
                                            {{ ucfirst($product->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('products.show', $product) }}" target="_blank"
                                           class="rounded-[8px] border border-[#dce3e6] bg-white px-3 py-1.5 text-[12px] font-medium text-[#202b3d] hover:bg-[#f7f9fa]">
                                            Lihat
                                        </a>

                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
      data-confirm="Yakin ingin menghapus produk {{ $product->product_name }} milik {{ $product->seller->name ?? 'penjual' }}?"
      onsubmit="return confirm(this.dataset.confirm)">
    @csrf
    @method('DELETE')
    <button type="submit"
            class="rounded-[8px] bg-[#fee2e2] px-3 py-1.5 text-[12px] font-medium text-[#b91c1c] hover:bg-[#fecaca]">
        Hapus
    </button>
</form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-[13px] text-[#64748b]">
                                    Belum ada produk yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (isset($products) && method_exists($products, 'links'))
                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            @endif
        </section>

        {{-- Manajemen Akun & Role Pengguna --}}
        <section class="rounded-[16px] border border-[#e5eaed] bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[18px] font-bold text-[#0f172a]">Manajemen Akun &amp; Role Pengguna</h2>
                    <p class="text-[13px] text-[#64748b]">Atur hak akses admin atau nonaktifkan akun bermasalah.</p>
                </div>

                <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="q" value="{{ $search }}"
                           placeholder="Cari nama, email, atau NPM..."
                           class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] text-[#202b3d] placeholder:text-[#94a3b8] focus:border-[#084734] focus:outline-none">

                    <select name="role"
                            class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] text-[#202b3d] focus:border-[#084734] focus:outline-none">
                        <option value="">Semua Role</option>
                        <option value="user" @selected($roleFilter === 'user')>User</option>
                        <option value="admin" @selected($roleFilter === 'admin')>Admin</option>
                    </select>

                    <button type="submit"
                            class="rounded-[10px] bg-[#084734] px-4 py-2 text-[13px] font-semibold text-white hover:bg-[#063829]">
                        Cari
                    </button>

                    @if ($search !== '' || $roleFilter !== '')
                        <a href="{{ route('admin.dashboard') }}"
                           class="rounded-[10px] border border-[#dce3e6] bg-white px-3.5 py-2 text-[13px] font-medium text-[#475569] hover:bg-[#f7f9fa]">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-[#e5eaed] text-[11px] font-bold uppercase tracking-wider text-[#64748b]">
                            <th class="py-3 pr-4">Pengguna</th>
                            <th class="py-3 pr-4">Kampus / NPM</th>
                            <th class="py-3 pr-4">Role</th>
                            <th class="py-3 pr-4">Status Akun</th>
                            <th class="py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#eef2f5] text-[13px]">
                        @forelse ($users as $user)
                            <tr class="hover:bg-[#fafbfc]">
                                <td class="py-3.5 pr-4">
                                    <p class="font-semibold text-[#0f172a]">{{ $user->name }}</p>
                                    <p class="text-[12px] text-[#64748b]">{{ $user->email }}</p>
                                </td>
                                <td class="py-3.5 pr-4 text-[#475569]">
                                    {{ $user->campus ?? '-' }} &middot; {{ $user->npm ?? '-' }}
                                </td>
                                <td class="py-3.5 pr-4">
                                    <form action="{{ route('admin.users.role', $user) }}" method="POST" class="inline-flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" onchange="this.form.submit()"
                                                @disabled($user->user_id === auth()->id())
                                                class="rounded-[8px] border border-[#dce3e6] bg-white px-2.5 py-1 text-[12px] font-medium text-[#202b3d] disabled:bg-[#f1f5f9] disabled:text-[#94a3b8]">
                                            <option value="user" @selected($user->role === 'user')>user</option>
                                            <option value="admin" @selected($user->role === 'admin')>admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="py-3.5 pr-4">
                                    @if ($user->is_active)
                                        <span class="inline-flex rounded-full bg-[#dcfce7] px-2.5 py-0.5 text-[11px] font-semibold text-[#15803d]">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-[#fee2e2] px-2.5 py-0.5 text-[11px] font-semibold text-[#b91c1c]">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 text-right">
                                    @if ($user->user_id === auth()->id())
                                        <span class="text-[12px] italic text-[#94a3b8]">Akun saat ini</span>
                                    @else
                                        <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="rounded-[8px] px-3 py-1.5 text-[12px] font-medium {{ $user->is_active ? 'bg-[#fee2e2] text-[#b91c1c] hover:bg-[#fecaca]' : 'bg-[#dcfce7] text-[#15803d] hover:bg-[#bbf7d0]' }}">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-[13px] text-[#64748b]">
                                    Pengguna tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </section>

    </main>

    {{-- Modal Edit Titik COD --}}
    <div id="editPointModal" style="display: none;"
         class="fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-[16px] bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#0f172a]">Edit Titik COD Kampus</h3>
                <button type="button" onclick="closeEditPointModal()" class="text-[20px] leading-none text-[#64748b] hover:text-[#0f172a]">&times;</button>
            </div>

            <form id="editPointForm" method="POST" class="mt-4 space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1 block text-[12px] font-semibold text-[#334155]">Kampus</label>
                    <select id="editPointCampus" name="campus" required
                            class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3 py-2 text-[13px] text-[#202b3d]">
                        <option value="UNSIKA">UNSIKA</option>
                        <option value="UBP">UBP</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[12px] font-semibold text-[#334155]">Nama Titik COD</label>
                    <input type="text" id="editPointName" name="point_name" required
                           class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3 py-2 text-[13px] text-[#202b3d]">
                </div>

                <div>
                    <label class="mb-1 block text-[12px] font-semibold text-[#334155]">Deskripsi / Patokan Lokasi</label>
                    <textarea id="editPointDescription" name="address_description" rows="3" required
                              class="w-full rounded-[10px] border border-[#dce3e6] bg-white px-3 py-2 text-[13px] text-[#202b3d]"></textarea>
                </div>

                <div>
                    <label class="inline-flex cursor-pointer items-center gap-2 text-[13px] font-medium text-[#202b3d]">
                        <input type="checkbox" id="editPointActive" name="is_active" value="1"
                               class="h-4 w-4 rounded border-[#cbd5e1] text-[#084734]">
                        Titik COD Aktif
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeEditPointModal()"
                            class="rounded-[10px] border border-[#dce3e6] bg-white px-4 py-2 text-[13px] font-medium text-[#475569] hover:bg-[#f7f9fa]">
                        Batal
                    </button>
                    <button type="submit"
                            class="rounded-[10px] bg-[#084734] px-4 py-2 text-[13px] font-semibold text-white hover:bg-[#063829]">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterMeetingPoints() {
            const keyword = (document.getElementById('pointSearchInput')?.value || '').trim().toLowerCase();
            const campus = (document.getElementById('campusFilterSelect')?.value || '').trim().toLowerCase();
            const rows = document.querySelectorAll('.meeting-point-row');
            const emptyFilterRow = document.getElementById('emptyFilterRow');
            let visibleCount = 0;

            rows.forEach((row) => {
                const rowCampus = (row.dataset.campus || '').toLowerCase();
                const rowSearch = (row.dataset.search || '').toLowerCase();

                const matchCampus = campus === '' || rowCampus === campus;
                const matchKeyword = keyword === '' || rowSearch.includes(keyword);

                if (matchCampus && matchKeyword) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyFilterRow) {
                emptyFilterRow.style.display = (rows.length > 0 && visibleCount === 0) ? '' : 'none';
            }
        }

        function openEditPointModal(button) {
            const modal = document.getElementById('editPointModal');
            const form = document.getElementById('editPointForm');

            form.action = `/admin/meeting-points/${button.dataset.id}`;
            document.getElementById('editPointCampus').value = button.dataset.campus;
            document.getElementById('editPointName').value = button.dataset.name;
            document.getElementById('editPointDescription').value = button.dataset.description;
            document.getElementById('editPointActive').checked = button.dataset.active === '1';

            modal.style.display = 'flex';
        }

        function closeEditPointModal() {
            document.getElementById('editPointModal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('pointSearchInput');
            const campusSelect = document.getElementById('campusFilterSelect');

            if (searchInput) searchInput.addEventListener('input', filterMeetingPoints);
            if (campusSelect) campusSelect.addEventListener('change', filterMeetingPoints);

            filterMeetingPoints();
        });
    </script>
</body>
</html>