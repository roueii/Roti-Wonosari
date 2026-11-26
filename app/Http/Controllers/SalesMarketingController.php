<?php

namespace App\Http\Controllers;

/**
 * KPI Karyawan Sales & Marketing (Figma: section "KARYAWAN SALES & MARKETING").
 * Isi capaian mengikuti indikator jabatan Sales di Kelola KPI (KpiFormController).
 */
class SalesMarketingController extends ChecklistRoleController
{
    protected function roleConfig(): array
    {
        return [
            'key' => 'sales_marketing',
            'route' => 'sales',
            'label' => 'Sales & Marketing',
            'badge' => 'SALES & MARKETING',
            'page_title' => 'Sales & Marketing',
            'help_text' => 'Pusat Bantuan Sales & Marketing: Hubungi Store Manager Toko Roti Wonosari.',
        ];
    }
}
