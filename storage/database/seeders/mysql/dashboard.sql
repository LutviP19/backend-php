INSERT INTO `activities` (`category_id`, `title`, `member`, `time`, `status`, `icon`, `color`, `created_at`)
VALUES
	('1', 'Sewa Traktor Kubota L4400', 'Sukirman Harjo', '08:00:00', 'Selesai', 'fa-tractor', 'emerald', '2026-01-07 19:48:00'),
	('2', 'Simpanan Pokok Anggota', 'Siti Aminah', '08:15:00', 'Selesai', 'fa-wallet', 'indigo', '2026-01-07 19:48:00'),
	('3', 'Pupuk Urea Subsidi (50kg)', 'Gudang Utama B', '08:45:00', 'Proses', 'fa-box', 'amber', '2026-01-07 19:48:00'),
	('2', 'Angsuran Kredit Mikro', 'Budi Santoso', '09:00:00', 'Selesai', 'fa-hand-holding-dollar', 'indigo', '2026-01-07 19:48:00'),
	('1', 'Sewa Excavator Mini', 'PT. Maju Mundur', '09:20:00', 'Selesai', 'fa-truck-pickup', 'emerald', '2026-01-07 19:48:00'),
	('3', 'Bibit Padi Inpari 32', 'Kelompok Tani Sejati', '09:45:00', 'Selesai', 'fa-seedling', 'emerald', '2026-01-07 19:48:00'),
	('2', 'Pinjaman Darurat', 'Hendra Setiawan', '10:10:00', 'Proses', 'fa-file-invoice-dollar', 'amber', '2026-01-07 19:48:00'),
	('3', 'Pestisida Organik (10L)', 'Gudang Cabang C', '10:30:00', 'Selesai', 'fa-flask', 'rose', '2026-01-07 19:48:00'),
	('1', 'Sewa Drone Pertanian', 'Agus Kuncoro', '11:00:00', 'Selesai', 'fa-plane-up', 'sky', '2026-01-07 19:48:00'),
	('2', 'Simpanan Wajib Bulanan', 'Dewi Sartika', '11:20:00', 'Selesai', 'fa-piggy-bank', 'indigo', '2026-01-07 19:48:00'),
	('3', 'Restock Alat Cangkul (10 unit)', 'Gudang Pusat', '13:00:00', 'Selesai', 'fa-hammer', 'slate', '2026-01-07 19:48:00'),
	('1', 'Sewa Harvester Padi', 'H. Mulyadi', '13:30:00', 'Proses', 'fa-gears', 'amber', '2026-01-07 19:48:00'),
	('2', 'Penarikan Simpanan Sukarela', 'Rina Wijaya', '14:00:00', 'Selesai', 'fa-money-bill-transfer', 'rose', '2026-01-07 19:48:00'),
	('3', 'Bantuan Pupuk NPK', 'Kecamatan Makmur', '14:20:00', 'Selesai', 'fa-leaf', 'emerald', '2026-01-07 19:48:00'),
	('1', 'Sewa Pompa Air Irigasi', 'Tarno Sudirjo', '14:45:00', 'Selesai', 'fa-faucet-drip', 'blue', '2026-01-07 19:48:00'),
	('2', 'Pembayaran Deviden Anggota', 'Koperasi Pusat', '15:10:00', 'Selesai', 'fa-percent', 'indigo', '2026-01-07 19:48:00'),
	('3', 'Stok Pakan Ternak (100kg)', 'Peternakan Jaya', '15:30:00', 'Selesai', 'fa-wheat-awn', 'amber', '2026-01-07 19:48:00'),
	('1', 'Sewa Truk Engkel (Logistik)', 'Anton Kurniawan', '16:00:00', 'Proses', 'fa-truck-moving', 'amber', '2026-01-07 19:48:00'),
	('2', 'Simpanan Berjangka (Deposito)', 'Lestari Indah', '16:20:00', 'Selesai', 'fa-vault', 'violet', '2026-01-07 19:48:00'),
	('3', 'Alat Semprot Elektrik', 'Gudang Cabang A', '16:45:00', 'Selesai', 'fa-spray-can-sparkles', 'emerald', '2026-01-07 19:48:00');

INSERT INTO `categories` (`slug`, `display_name`, `default_icon`, `default_color`)
VALUES
	('assets', 'Aset & Alat', 'fa-tractor', 'emerald'),
	('finance', 'Keuangan', 'fa-wallet', 'indigo'),
	('inventory', 'Inventaris', 'fa-box', 'amber');

INSERT INTO `products` (`nama`, `kategori`, `stok`, `harga`, `status_kritis`, `created_at`, `updated_at`)
VALUES
	('Pupuk Cair EM4 Kuning', 'pupuk', '150', '12000.00', '1', '2026-01-01 01:04:16', '2026-01-12 18:20:21'),
	('Pupuk Cair EM4 Merah', 'pupuk', '100', '18000.00', '1', '2026-01-01 01:04:39', '2026-01-01 17:21:48'),
	('Pupuk Mutiara', 'pupuk', '300', '10000.00', '1', '2026-01-01 01:50:47', '2026-01-07 12:59:22'),
	('Benih Padi Super A+', 'benih', '28', '250000.00', '1', '2026-01-01 01:53:11', '2026-01-12 17:39:31'),
	('Pupuk Urea Nitrea 50kg', 'pupuk', '5', '250000.00', '1', '2026-01-01 01:53:50', '2026-01-01 02:17:25'),
	('Benih Jagung Pioner P35', 'benih', '15', '115000.00', '1', '2026-01-01 01:54:50', '2026-01-13 13:42:47'),
	('Rubigan 120 EC', 'pestisida', '45', '95000.00', '1', '2026-01-01 01:55:20', '2026-01-07 12:47:37'),
	('Pupuk NPK Mutiara 16-16-16', 'pupuk', '250', '115000.00', '1', '2026-01-01 01:55:59', '2026-01-07 12:46:20'),
	('Raydock 28 EC', 'pestisida', '120', '30000.00', '1', '2026-01-08 19:17:20', '2026-01-12 16:49:00'),
	('Fenval 200 EC', 'pestisida', '100', '70000.00', '1', '2026-01-08 19:17:54', '2026-01-08 19:17:54'),
	('Varitas 3 GR', 'pestisida', '5', '67000.00', '1', '2026-01-08 19:18:21', '2026-01-08 19:22:03'),
	('Meteor 25 EC', 'pestisida', '50', '125000.00', '1', '2026-01-08 19:18:51', '2026-01-18 18:43:42');


INSERT INTO `asset_categories` (`category_name`, `slug`)
VALUES
	('Alat Berat', 'heavy-equipment'),
	('Teknologi', 'technology'),
	('Pendukung', 'support'),
	('Peralatan Gudang', 'warehouse'),
	('Logistik', 'logistics');

INSERT INTO `assets` (`asset_id`, `category_id`, `name`, `status`, `health`, `icon`, `color`, `created_at`, `updated_at`)
VALUES
	('DRN-01', '2', 'DJI Agras T40 (Sprayer)', 'ready', '75', 'fa-plane-up', 'sky', '2026-01-07 20:39:47', '2026-01-12 12:40:08'),
	('DRN-02', '2', 'DJI Mavic 3 (Mapping)', 'working', '98', 'fa-helicopter', 'emerald', '2026-01-07 20:39:47', '2026-01-12 16:47:04'),
	('DRN-03', '2', 'Drone Sprayer V2', 'working', '86', 'fa-plane', 'rose', '2026-01-07 20:39:47', '2026-01-08 17:49:57'),
	('EXC-01', '1', 'Excavator Mini Hitachi', 'ready', '85', 'fa-truck-pickup', 'cyan', '2026-01-07 20:39:47', '2026-01-13 13:23:54'),
	('EXC-02', '1', 'Excavator PC200', 'working', '80', 'fa-truck-monster', 'emerald', '2026-01-07 20:39:47', '2026-01-08 17:39:02'),
	('FOR-01', '4', 'Forklift Toyota 3-Ton Edit2', 'maintenance', '50', 'fa-dolly', 'orange', '2026-01-07 20:39:47', '2026-01-13 13:42:28'),
	('FOR-02', '4', 'Forklift Toyota 3-Ton', 'ready', '82', 'fa-dolly', 'emerald', '2026-01-07 20:39:47', '2026-01-07 20:39:47'),
	('GEN-01', '4', 'Genset Cummins 50KVA', 'ready', '95', 'fa-plug', 'slate', '2026-01-07 20:39:47', '2026-01-18 18:43:52'),
	('HVS-01', '1', 'Harvester Padi Yanmar', 'ready', '78', 'fa-gear', 'indigo', '2026-01-07 20:39:47', '2026-01-07 20:39:47'),
	('KBT-01', '1', 'Traktor Kubota L4400', 'ready', '85', 'fa-tractor', 'emerald', '2026-01-07 20:39:47', '2026-01-07 20:39:47'),
	('KBT-02', '1', 'Traktor Kubota L4400', 'maintenance', '32', 'fa-tractor', 'rose', '2026-01-07 20:39:47', '2026-01-07 20:39:47'),
	('KBT-03', '1', 'Traktor Kubota L4400', 'ready', '100', 'fa-tractor', 'emerald', '2026-01-08 12:44:33', '2026-01-13 12:55:55'),
	('KBT-04', '1', 'Traktor Kubota L4400', 'maintenance', '45', 'fa-tractor', 'emerald', '2026-01-08 12:46:33', '2026-01-13 13:28:13'),
	('KBT-05', '1', 'Traktor Kubota L4400', 'ready', '100', 'fa-tractor', 'emerald', '2026-01-08 12:48:29', '2026-01-08 12:48:29'),
	('KBT-06', '1', 'Traktor Kubota L4400', 'working', '100', 'fa-tractor', 'emerald', '2026-01-08 12:50:46', '2026-01-12 16:46:54'),
	('KBT-07', '1', 'Traktor Kubota L4400', 'ready', '100', 'fa-tractor', 'emerald', '2026-01-08 12:51:54', '2026-01-08 12:51:54'),
	('KBT-08', '1', 'Traktor Kubota L4400', 'working', '100', 'fa-tractor', 'emerald', '2026-01-08 12:53:02', '2026-01-08 17:48:33'),
	('PMP-01', '3', 'Pompa Irigasi Diesel', 'ready', '90', 'fa-faucet-drip', 'blue', '2026-01-07 20:39:47', '2026-01-07 20:39:47'),
	('PMP-02', '3', 'Pompa Irigasi Diesel', 'ready', '85', 'fa-faucet-drip', 'amber', '2026-01-07 20:39:47', '2026-01-13 12:57:12'),
	('TRK-01', '3', 'Truk Engkel Logistik', 'working', '76', 'fa-truck', 'amber', '2026-01-07 20:39:47', '2026-01-08 17:43:44'),
	('TRK-02', '3', 'Truk Mitsubishi Canter', 'ready', '88', 'fa-truck-moving', 'emerald', '2026-01-07 20:39:47', '2026-01-07 20:39:47'),
	('KBT-011', '1', 'Traktor Kubota L4400', 'working', '100', 'fa-tractor', 'emerald', '2026-01-08 13:02:42', '2026-01-13 12:56:14'),
	('KBT-012', '1', 'Traktor Kubota L4400', 'ready', '80', 'fa-tractor', 'emerald', '2026-01-08 13:03:15', '2026-01-13 19:18:01'),
	('KBT-013', '1', 'Traktor Kubota L4400', 'working', '100', 'fa-tractor', 'emerald', '2026-01-08 13:03:41', '2026-01-08 17:48:23'),
	('KBT-014', '1', 'Traktor Kubota L4400', 'maintenance', '46', 'fa-tractor', 'emerald', '2026-01-08 13:29:54', '2026-01-08 16:54:35'),
	('DRN-011', '2', 'DJI Agras T40 (Sprayer)', 'working', '85', 'fa-tractor', 'blue', '2026-01-08 13:34:27', '2026-01-08 16:58:22'),
	('DRN-012', '2', 'DJI Agras T40 (Sprayer)', 'ready', '100', 'fa-plane-up', 'blue', '2026-01-08 13:35:35', '2026-01-08 19:07:52'),
	('KBT-030', '1', 'Traktor Kubota L4400', 'working', '100', 'fa-tractor', 'emerald', '2026-01-08 13:38:08', '2026-01-08 17:37:58'),
	('PCKUP-1', '5', 'Pickup L300', 'working', '100', 'fa-truck-pickup', 'emerald', '2026-01-08 14:08:37', '2026-01-08 17:11:43'),
	('PCKUP-2', '5', 'Pickup L300 Super 2025', 'working', '88', 'fa-truck-pickup', 'emerald', '2026-01-08 14:15:14', '2026-01-08 16:47:50'),
	('DRN-04', '2', 'DJI Mavic 3 (Mapping)', 'working', '100', 'fa-helicopter', 'sky', '2026-01-08 14:59:23', '2026-01-08 16:48:08');

INSERT INTO `asset_maintenance_logs` (`asset_id`, `maintenance_date`, `task`, `status`, `technician_name`, `cost`, `created_at`, `updated_at`)
VALUES
	('1', '2025-11-05', 'Ganti Oli Mesin & Filter', 'Selesai', 'Budi Santoso', '450000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('1', '2025-11-20', 'Pengecekan Sistem Hidrolik', 'Selesai', 'Agus Prayogo', '250000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('1', '2025-12-05', 'Kalibrasi Sensor Teknologi', 'Selesai', 'Dani Tech', '1200000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('1', '2025-12-28', 'Penggantian Ban Belakang', 'Selesai', 'Budi Santoso', '3500000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('1', '2026-01-05', 'Pemeriksaan Rutin Mingguan', 'Selesai', 'Agus Prayogo', '200000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('2', '2025-10-15', 'Servis Berkala 500 Jam', 'Selesai', 'Eko Wijaya', '2800000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('2', '2025-11-10', 'Ganti Aki N70', 'Selesai', 'Budi Santoso', '145000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('2', '2025-12-01', 'Perbaikan Jalur Kabel Utama', 'Selesai', 'Dani Tech', '600000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('2', '2025-12-20', 'Penambahan Grease/Gemuk', 'Selesai', 'Agus Prayogo', '150000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('2', '2026-01-07', 'Pengecekan Kebocoran Bahan Bakar', 'Selesai', 'Eko Wijaya', '100000.00', '2026-01-08 13:52:31', '2026-01-08 13:52:31'),
	('5', '2025-11-05', 'Ganti Oli Mesin & Filter', 'Selesai', 'Budi Santoso', '450000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('5', '2025-11-20', 'Pengecekan Sistem Hidrolik', 'Selesai', 'Agus Prayogo', '250000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('5', '2025-12-05', 'Kalibrasi Sensor Teknologi', 'Selesai', 'Dani Tech', '1200000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('5', '2025-12-28', 'Penggantian Ban Belakang', 'Selesai', 'Budi Santoso', '3500000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('5', '2026-01-05', 'Pemeriksaan Rutin Mingguan', 'Selesai', 'Agus Prayogo', '200000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('6', '2025-10-15', 'Servis Berkala 500 Jam', 'Selesai', 'Eko Wijaya', '2800000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('6', '2025-11-10', 'Ganti Aki N70', 'Selesai', 'Budi Santoso', '145000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('6', '2025-12-01', 'Perbaikan Jalur Kabel Utama', 'Selesai', 'Dani Tech', '600000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('6', '2025-12-20', 'Penambahan Grease/Gemuk', 'Selesai', 'Agus Prayogo', '150000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('6', '2026-01-07', 'Pengecekan Kebocoran Bahan Bakar', 'Selesai', 'Eko Wijaya', '100000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('19', '2025-10-15', 'Servis Berkala 500 Jam', 'Selesai', 'Eko Wijaya', '2800000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('19', '2025-11-10', 'Ganti Aki N70', 'Selesai', 'Budi Santoso', '145000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('19', '2025-12-01', 'Perbaikan Jalur Kabel Utama', 'Selesai', 'Dani Tech', '600000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('19', '2025-12-20', 'Penambahan Grease/Gemuk', 'Selesai', 'Agus Prayogo', '150000.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11'),
	('19', '2026-01-07', 'Pengecekan Kebocoran Bahan Bakar', 'Proses', 'Eko Wijaya', '0.00', '2026-01-08 13:56:11', '2026-01-08 13:56:11');


