{{-- Drawer: komponen bersama partials.employee-drawer (Figma Menu) --}}
@php $r = $cfg['route']; @endphp
@include('partials.employee-drawer', [
    'roleLabel' => $cfg['label'],
    'nav' => [
        ['url' => route($r . '.dashboard'), 'label' => 'Beranda', 'icon' => 'home-alt-slim-horiz', 'size' => 16, 'active' => request()->routeIs($r . '.dashboard')],
        ['url' => route($r . '.step', array_key_first($cfg['steps'])), 'label' => 'Input Capaian', 'icon' => 'page-edit', 'size' => 16, 'active' => request()->routeIs($r . '.step')],
        ['url' => route($r . '.riwayat'), 'label' => 'Riwayat', 'icon' => 'clock-rotate-right', 'size' => 16, 'active' => request()->routeIs($r . '.riwayat*')],
        ['url' => route($r . '.profile'), 'label' => 'Profil', 'icon' => 'profile-circle', 'size' => 16, 'active' => request()->routeIs($r . '.profile')],
    ],
])
