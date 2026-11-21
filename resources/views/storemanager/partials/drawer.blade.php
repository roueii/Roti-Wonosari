<!-- Side Menu Drawer Store Manager (Figma 1099:5907: Beranda, Input Capaian, Approval Tim, Riwayat, Profil) -->
@php
    $smNav = [
        ['route' => 'storemanager.dashboard', 'label' => 'Beranda', 'icon' => 'home-alt-slim-horiz', 'size' => 16, 'active' => request()->routeIs('storemanager.dashboard')],
        ['route' => 'storemanager.penjualan', 'label' => 'Input Capaian', 'icon' => 'page-edit', 'size' => 16, 'active' => request()->is('*isi-capaian*') || request()->routeIs('storemanager.training')],
        ['route' => 'storemanager.approval', 'label' => 'Approval Tim', 'icon' => 'community', 'size' => 20, 'active' => request()->routeIs('storemanager.approval*')],
        ['route' => 'storemanager.riwayat', 'label' => 'Riwayat', 'icon' => 'clock-rotate-right', 'size' => 16, 'active' => request()->routeIs('storemanager.riwayat*')],
        ['route' => 'storemanager.profile', 'label' => 'Profil', 'icon' => 'profile-circle', 'size' => 16, 'active' => request()->routeIs('storemanager.profile*')],
    ];
@endphp
<div class="drawer-overlay" id="drawerOverlay" aria-hidden="true">
    <div class="drawer-backdrop" id="drawerBackdrop"></div>
    <aside class="drawer-sheet sm-drawer" id="drawerSheet" role="dialog" aria-modal="true" aria-label="Menu Navigasi Store Manager">
        <!-- Header -->
        <div class="sm-drawer-header">
            <div class="sm-drawer-brand">
                <strong>CAPAIAN.ID</strong>
                <span>KPI Toko Roti Wonosari</span>
            </div>
            <button class="sm-drawer-close" id="drawerCloseBtn" type="button" aria-label="Tutup menu navigasi">
                <x-icon name="cancel" width="24" height="24" />
            </button>
        </div>

        <!-- Profil singkat -->
        <div class="sm-drawer-section">
            <div class="sm-drawer-user">
                <span class="sm-drawer-avatar" aria-hidden="true"><x-icon name="user" width="24" height="24" /></span>
                <span class="sm-drawer-user-meta">
                    <strong>{{ $user['name'] ?? 'User' }}</strong>
                    <span>Store Manager</span>
                </span>
            </div>
        </div>

        <!-- Navigasi -->
        <nav class="sm-drawer-nav" aria-label="Menu Store Manager">
            @foreach ($smNav as $item)
                <a href="{{ route($item['route']) }}" class="sm-drawer-link {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                    <span class="sm-drawer-link-left">
                        <span class="sm-drawer-link-icon"><x-icon :name="$item['icon']" :width="$item['size']" :height="$item['size']" aria-hidden="true" /></span>
                        <span>{{ $item['label'] }}</span>
                    </span>
                    <x-icon name="nav-arrow-right" width="16" height="16" class="sm-drawer-chevron" aria-hidden="true" />
                </a>
            @endforeach
        </nav>

        <!-- Bawah -->
        <div class="sm-drawer-footer">
            <span>KPI Toko Roti Wonosari</span>
        </div>
    </aside>
</div>
