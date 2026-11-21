<?php

/*
 * Definisi item ceklis Isi Capaian per role (teks dari Figma).
 * Semua item mulai TIDAK tercentang; status tersimpan diambil dari Backend saat halaman dibuka.
 * Pemetaan item -> kode indikator Backend ada di konstanta BACKEND_MAP tiap controller.
 */

return [
    'frontliner' => [
        'penjualan' => [
            [
                'id' => 'target_omzet',
                'title' => 'Mencapai target omzet harian/bulanan',
                'desc' => 'Omset toko hari ini sudah mencapai atau melampaui target yang ditetapkan',
                'checked' => false,
            ],
            [
                'id' => 'rata_rata_belanja',
                'title' => 'Meningkatkan nilai rata-rata belanja pelanggan',
                'desc' => 'Nilai transaksi per pelanggan hari ini lebih tinggi dari rata-rata bulan lalu',
                'checked' => false,
            ],
            [
                'id' => 'tawarkan_promo',
                'title' => 'Aktif menawarkan produk promo',
                'desc' => 'Kamu sudah menawarkan produk promo kepada pelanggan yang datang hari ini',
                'checked' => false,
            ],
        ],
        'pelayanan' => [
            [
                'id' => 'menyapa_pelanggan',
                'title' => 'Menyapa pelanggan',
                'desc' => 'Setiap pelanggan yang masuk disambut dengan salam dan senyum',
                'checked' => false,
            ],
            [
                'id' => 'ramah_komunikatif',
                'title' => 'Ramah dan komunikatif',
                'desc' => 'Melayani dengan sikap positif dan komunikasi yang jelas sepanjang shift',
                'checked' => false,
            ],
            [
                'id' => 'cepat_melayani',
                'title' => 'Cepat melayani',
                'desc' => 'Waktu tunggu pelanggan tidak terlalu lama dan antrian tidak menumpuk',
                'checked' => false,
            ],
            [
                'id' => 'menangani_komplain',
                'title' => 'Menangani komplain dengan baik',
                'desc' => 'Setiap komplain pelanggan ditanggapi dengan tenang dan diselesaikan hari ini',
                'checked' => false,
            ],
        ],
        'product_knowledge' => [
            [
                'id' => 'fungsi_bahan_kue',
                'title' => 'Menguasai fungsi bahan kue',
                'desc' => 'Bisa menjelaskan bahan-bahan yang digunakan dalam produk roti kepada pelanggan',
                'checked' => false,
            ],
            [
                'id' => 'stok_lokasi_produk',
                'title' => 'Mengetahui stok dan lokasi produk',
                'desc' => 'Tahu produk apa yang tersedia hari ini dan di mana letaknya di toko',
                'checked' => false,
            ],
            [
                'id' => 'rekomendasi_pelanggan',
                'title' => 'Mampu memberi rekomendasi kepada pelanggan',
                'desc' => 'Aktif menyarankan produk yang sesuai kebutuhan atau selera pelanggan',
                'checked' => false,
            ],
        ],
        'akurasi_kasir' => [
            [
                'id' => 'tidak_ada_selisih',
                'title' => 'Tidak ada selisih kas',
                'desc' => 'Jumlah uang fisik di laci kasir sesuai dengan total transaksi tercatat',
                'checked' => false,
            ],
            [
                'id' => 'tidak_salah_harga',
                'title' => 'Tidak salah input harga',
                'desc' => 'Semua harga produk diinput sesuai harga yang berlaku, tidak ada koreksi transaksi',
                'checked' => false,
            ],
            [
                'id' => 'nota_sesuai',
                'title' => 'Nota sesuai transaksi',
                'desc' => 'Struk atau nota yang diberikan ke pelanggan sesuai dengan pesanan dan pembayaran',
                'checked' => false,
            ],
        ],
        'display_kebersihan' => [
            [
                'id' => 'rak_selalu_rapi',
                'title' => 'Rak selalu rapi',
                'desc' => 'Produk di rak tertata rapi dan tidak ada yang jatuh atau berantakan sepanjang shift',
                'checked' => false,
            ],
            [
                'id' => 'produk_fifo',
                'title' => 'Produk FIFO',
                'desc' => 'Produk yang lebih lama dipajang di depan, produk baru di belakang',
                'checked' => false,
            ],
            [
                'id' => 'label_harga_lengkap',
                'title' => 'Label harga lengkap',
                'desc' => 'Semua produk di rak sudah terpasang label harga yang jelas dan benar',
                'checked' => false,
            ],
            [
                'id' => 'area_kerja_bersih',
                'title' => 'Area kerja bersih',
                'desc' => 'Meja kasir, area display, dan sekitar tempat kerja bersih dari sampah dan kotoran',
                'checked' => false,
            ],
        ],
        'kepatuhan_sop' => [
            'sop' => [
                [
                    'id' => 'seragam_lengkap',
                    'title' => 'Menggunakan seragam lengkap',
                    'desc' => 'Hadir dengan seragam lengkap sesuai ketentuan toko hari ini',
                    'checked' => false,
                ],
                [
                    'id' => 'sop_buka_tutup',
                    'title' => 'Mengikuti SOP buka/tutup toko',
                    'desc' => 'Semua prosedur pembukaan atau penutupan toko sudah dijalankan sesuai urutan',
                    'checked' => false,
                ],
                [
                    'id' => 'prosedur_keamanan',
                    'title' => 'Menjalankan prosedur pelayanan dan keamanan',
                    'desc' => 'Prosedur layanan pelanggan dan keamanan toko diikuti sepanjang shift',
                    'checked' => false,
                ],
            ],
            'disiplin' => [
                [
                    'id' => 'tidak_terlambat',
                    'title' => 'Tidak terlambat',
                    'desc' => 'Hadir di lokasi dan siap memproduksi roti tepat waktu sesuai jadwal shift hari ini',
                    'checked' => false,
                ],
                [
                    'id' => 'jadwal_kerja',
                    'title' => 'Mematuhi Jadwal kerja',
                    'desc' => 'Semua langkah perawatan alat harian sudah dilakukan dan tercatat',
                    'checked' => false,
                ],
            ],
        ],
    ],
    'store_manager' => [
        'penjualan' => [
            [
                'id' => 'target_omset',
                'title' => 'Pencapaian target omset ≥100%',
                'desc' => 'Total omset hari ini sudah mencapai atau melampaui target harian toko',
                'checked' => false,
            ],
            [
                'id' => 'pertumbuhan_omset',
                'title' => 'Pertumbuhan omset vs bulan lalu ≥5%',
                'desc' => 'Tren penjualan minggu ini lebih tinggi dibanding periode yang sama bulan lalu',
                'checked' => false,
            ],
            [
                'id' => 'average_basket',
                'title' => 'Rata-rata belanja (Average Basket) naik ≥10%',
                'desc' => 'Nilai transaksi per pelanggan hari ini lebih tinggi dari rata-rata bulan lalu',
                'checked' => false,
            ],
        ],
        'pelayanan' => [
            [
                'id' => 'kepuasan_pelanggan',
                'title' => 'Kepuasan pelanggan ≥90%',
                'desc' => 'Rating toko hari ini berada di atas 90% berdasarkan penilaian dan feedback pelanggan',
                'checked' => false,
            ],
            [
                'id' => 'komplain_selesai',
                'title' => 'Komplain terselesaikan 100% dalam 1x24 jam',
                'desc' => 'Semua komplain yang masuk hari ini sudah ditangani dan diselesaikan sebelum 24 jam',
                'checked' => false,
            ],
        ],
        'promosi_event' => [
            [
                'id' => 'promo_terjadwal',
                'title' => 'Ada promo terjadwal hari ini?',
                'desc' => 'Contoh: Bundling Roti Kopi Sore / Diskon Akhir Pekan',
                'checked' => false,
            ],
            [
                'id' => 'promo_dieksekusi',
                'title' => 'Promo dieksekusi sesuai jadwal',
                'desc' => 'Materi POP terpasang, kasir menerapkan diskon & display siap',
                'checked' => false,
            ],
        ],
        'laba_persediaan' => [
            'laba_biaya' => [
                [
                    'id' => 'food_cost',
                    'title' => 'Food Cost sesuai standar ≤ target perusahaan',
                    'desc' => 'Biaya bahan baku terhadap harga jual minggu ini tidak melebihi target yang ditetapkan perusahaan',
                    'checked' => false,
                ],
                [
                    'id' => 'waste_expired',
                    'title' => 'Waste/produk rusak & expired ≤1% dari omset',
                    'desc' => 'Nilai roti yang dibuang, rusak, atau kedaluwarsa hari ini tidak lebih dari 1% omset',
                    'checked' => false,
                ],
            ],
            'persediaan' => [
                [
                    'id' => 'akurasi_stok',
                    'title' => 'Akurasi stok ≥98%',
                    'desc' => 'Selisih antara stok fisik di toko dan catatan sistem tidak lebih dari 2%',
                    'checked' => false,
                ],
                [
                    'id' => 'stock_opname',
                    'title' => 'Stock opname tepat waktu 100%',
                    'desc' => 'Penghitungan stok fisik hari ini sudah dilakukan sesuai jadwal yang ditetapkan',
                    'checked' => false,
                ],
            ],
        ],
        'operasional_sdm' => [
            'operasional' => [
                [
                    'id' => 'display_lengkap',
                    'title' => 'Display produk lengkap saat jam operasional',
                    'desc' => 'Etalase roti terisi penuh & label harga terpasang rapi',
                    'checked' => false,
                ],
                [
                    'id' => 'sop_buka',
                    'title' => 'SOP buka toko dijalankan',
                    'desc' => 'Sanitasi, pencatatan kas modal awal, dan uji mesin POS',
                    'checked' => false,
                ],
                [
                    'id' => 'sop_tutup',
                    'title' => 'SOP tutup toko dijalankan',
                    'desc' => 'Rekapitulasi sisa produk, kunci chiller, dan pemadaman lampu',
                    'checked' => false,
                ],
            ],
            'sdm' => [
                [
                    'id' => 'briefing_harian',
                    'title' => 'Briefing harian sudah dilaksanakan',
                    'desc' => 'Penyampaian target penjualan harian dan fokus produk promo',
                    'checked' => false,
                ],
                [
                    'id' => 'training_karyawan',
                    'title' => 'Training karyawan Minimal 1 kali/bulan',
                    'desc' => 'Pelatihan standar sanitasi, pencatatan kas, dan operasional sistem POS.',
                    'checked' => false,
                ],
            ],
        ],
    ],
];
