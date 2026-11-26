<?php

namespace App\Http\Controllers;

/**
 * KPI Karyawan Supervisor (Figma: section "KARYAWAN SUPERVISOR").
 * Isi capaian mengikuti indikator jabatan SPV di Kelola KPI (KpiFormController).
 */
class SupervisorController extends ChecklistRoleController
{
    protected function roleConfig(): array
    {
        return [
            'key' => 'supervisor',
            'route' => 'supervisor',
            'label' => 'Supervisor',
            'badge' => 'SUPERVISOR',
            'page_title' => 'Supervisor',
            'help_text' => 'Pusat Bantuan Supervisor: Hubungi Store Manager atau Admin Toko Roti Wonosari.',
        ];
    }
}
