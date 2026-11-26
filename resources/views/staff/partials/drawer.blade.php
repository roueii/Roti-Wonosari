{{-- Drawer: komponen bersama partials.employee-drawer (Figma Menu) --}}
@include('partials.employee-drawer', [
    'roleLabel' => 'Frontliner',
    'nav' => [
        ['url' => route('frontliner.dashboard'), 'label' => 'Beranda', 'icon' => 'home-alt-slim-horiz', 'size' => 16, 'active' => request()->routeIs('frontliner.dashboard', 'staff.dashboard')],
        ['url' => route('frontliner.kpi'), 'label' => 'Input Capaian', 'icon' => 'page-edit', 'size' => 16, 'active' => request()->is('*isi-capaian*')],
        ['url' => route('frontliner.riwayat'), 'label' => 'Riwayat', 'icon' => 'clock-rotate-right', 'size' => 16, 'active' => request()->routeIs('frontliner.riwayat*')],
        ['url' => route('frontliner.profile'), 'label' => 'Profil', 'icon' => 'profile-circle', 'size' => 16, 'active' => request()->routeIs('frontliner.profile*')],
    ],
])
