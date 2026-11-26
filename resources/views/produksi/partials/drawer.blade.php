{{-- Drawer: komponen bersama partials.employee-drawer (Figma Menu) --}}
@include('partials.employee-drawer', [
    'roleLabel' => 'Produksi',
    'nav' => [
        ['url' => route('produksi.dashboard'), 'label' => 'Beranda', 'icon' => 'home-alt-slim-horiz', 'size' => 16, 'active' => request()->routeIs('produksi.dashboard')],
        ['url' => route('produksi.kpi'), 'label' => 'Input Capaian', 'icon' => 'page-edit', 'size' => 16, 'active' => request()->is('*produksi/isi-capaian*')],
        ['url' => route('produksi.riwayat'), 'label' => 'Riwayat', 'icon' => 'clock-rotate-right', 'size' => 16, 'active' => request()->routeIs('produksi.riwayat*')],
        ['url' => route('produksi.profile'), 'label' => 'Profil', 'icon' => 'profile-circle', 'size' => 16, 'active' => request()->routeIs('produksi.profile*')],
    ],
])
