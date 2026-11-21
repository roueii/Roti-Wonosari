{{-- Drawer: komponen bersama partials.employee-drawer (Figma Menu) --}}
@include('partials.employee-drawer', [
    'roleLabel' => 'Driver',
    'nav' => [
        ['url' => route('driver.dashboard'), 'label' => 'Beranda', 'icon' => 'home-alt-slim-horiz', 'size' => 16, 'active' => request()->routeIs('driver.dashboard')],
        ['url' => route('driver.pengiriman'), 'label' => 'Input Capaian', 'icon' => 'page-edit', 'size' => 16, 'active' => request()->is('*driver/isi-capaian*')],
        ['url' => route('driver.riwayat'), 'label' => 'Riwayat', 'icon' => 'clock-rotate-right', 'size' => 16, 'active' => request()->routeIs('driver.riwayat*')],
        ['url' => route('driver.profile'), 'label' => 'Profil', 'icon' => 'profile-circle', 'size' => 16, 'active' => request()->routeIs('driver.profile*')],
    ],
])
