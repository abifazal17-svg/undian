<?php
require_once __DIR__ . '/../config.php';

// Auth Guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = getDB();

// Fetch Active Period & All Periods
$activePeriod = getActivePeriod();
$periodsStmt = $db->query("SELECT * FROM periods ORDER BY id DESC");
$allPeriods = $periodsStmt->fetchAll();

// Fetch Submissions with Period Name
$stmt = $db->query("
    SELECT s.*, p.nama_periode 
    FROM submissions s 
    LEFT JOIN periods p ON s.period_id = p.id 
    ORDER BY s.created_at DESC
");
$submissions = $stmt->fetchAll();

// Statistics
$totalCount = count($submissions);
$winnersCount = 0;
foreach ($submissions as $s) {
    if ($s['is_winner'] == 1) $winnersCount++;
}

// Active Period Info
$activePeriodId = $activePeriod ? $activePeriod['id'] : 1;
$activePeriodName = $activePeriod ? $activePeriod['nama_periode'] : 'Periode A';
$tanggal_undian = $activePeriod ? $activePeriod['tanggal_undian'] : date('Y-m-d H:i:s', strtotime('+7 days'));
$announcement_note = $activePeriod ? $activePeriod['announcement_note'] : '';

// Format for HTML5 datetime-local input (Y-m-d\TH:i)
$inputDateTime = date('Y-m-d\TH:i', strtotime($tanggal_undian));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Undian ShopeeFood</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Admin Navigation -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-icon">⚙️</div>
            <span>Admin Dashboard</span>
        </a>
        <div class="nav-links">
            <a href="../index.php" target="_blank" class="btn-nav">🌐 Lihat Situs Client</a>
            <a href="logout.php" class="btn-nav btn-delete">🚪 Keluar (Logout)</a>
        </div>
    </nav>

    <main class="container">

        <!-- Header Controls -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 800;">Panel Pengelolaan Undian Multi-Periode</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Periode Aktif Saat Ini: <strong style="color: var(--accent);"><?= htmlspecialchars($activePeriodName) ?></strong>
                </p>
            </div>
            
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button onclick="openCreatePeriodModal()" class="btn-submit" style="background: linear-gradient(135deg, #10b981, #059669); font-size: 0.95rem; padding: 0.75rem 1.25rem;">
                    ➕ Mulai Periode Baru
                </button>
                <button onclick="openDrawModal()" class="btn-submit" style="background: linear-gradient(135deg, #f59e0b, #d97706); font-size: 0.95rem; padding: 0.75rem 1.25rem;">
                    🎰 Spin Undi Pemenang
                </button>
                <button onclick="exportCSV()" class="btn-nav btn-nav-primary">
                    📥 Export CSV
                </button>
            </div>
        </div>

        <!-- Stats Counter -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h4>Total Peserta (Semua Periode)</h4>
                    <div class="num"><?= number_format($totalCount) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">🏆</div>
                <div class="stat-info">
                    <h4>Total Pemenang</h4>
                    <div class="num"><?= number_format($winnersCount) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">📅</div>
                <div class="stat-info">
                    <h4>Jadwal Undian Aktif</h4>
                    <div class="num" style="font-size: 1.05rem; font-weight: 700;">
                        <?= date('d M Y H:i', strtotime($tanggal_undian)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi-Period Management Panel -->
        <section class="form-card" style="margin-bottom: 2.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div class="form-title">
                        📌 Pengaturan Daftar Periode Undian
                    </div>
                    <p class="form-subtitle" style="margin-bottom: 0;">
                        Admin dapat menambah periode baru, mengedit jadwal, mengaktifkan periode, atau menghapus periode undian.
                    </p>
                </div>
                <button onclick="openCreatePeriodModal()" class="btn-action btn-wa" style="padding: 0.6rem 1rem;">
                    ✨ + Tambah Periode
                </button>
            </div>

            <!-- Periods List Table -->
            <div class="table-wrapper" style="margin-bottom: 1.5rem;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Nama Periode</th>
                            <th>Tanggal Pengundian</th>
                            <th>Status Periode</th>
                            <th>Catatan Pengumuman</th>
                            <th>Aksi Pengelolaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allPeriods as $p): ?>
                            <tr>
                                <td>
                                    <strong style="font-size: 1rem; color: #fff;"><?= htmlspecialchars($p['nama_periode']) ?></strong>
                                    <?php if ($p['is_active'] == 1): ?>
                                        <span style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); padding: 0.15rem 0.5rem; border-radius: 99px; font-size: 0.75rem; font-weight: 700; margin-left: 0.5rem;">● AKTIF SEKARANG</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= date('d M Y - H:i', strtotime($p['tanggal_undian'])) ?> WIB</code>
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; text-transform: uppercase; padding: 0.2rem 0.6rem; border-radius: 6px; background: rgba(255,255,255,0.05);">
                                        <?= htmlspecialchars($p['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-muted); max-width: 250px;">
                                    <?= htmlspecialchars($p['announcement_note']) ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                        <?php if ($p['is_active'] != 1): ?>
                                            <button onclick="setActivePeriod(<?= $p['id'] ?>)" class="btn-action btn-wa" title="Jadikan Periode Aktif">
                                                ⚡ Aktifkan
                                            </button>
                                        <?php endif; ?>

                                        <button onclick="editPeriod(<?= $p['id'] ?>, <?= htmlspecialchars(json_encode($p['nama_periode']), ENT_QUOTES) ?>, '<?= $p['tanggal_undian'] ?>', <?= htmlspecialchars(json_encode($p['announcement_note']), ENT_QUOTES) ?>, '<?= $p['status'] ?>')" class="btn-action btn-nav-primary">
                                            ✏️ Edit
                                        </button>

                                        <!-- Safe Click Handler for Delete Period -->
                                        <button onclick="confirmDeletePeriod(<?= $p['id'] ?>, <?= htmlspecialchars(json_encode($p['nama_periode']), ENT_QUOTES) ?>)" class="btn-action btn-delete" title="Hapus Periode">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Edit Current Active Period Fast Date Update Form -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                <h4 style="font-size: 1rem; margin-bottom: 1rem; color: var(--accent);">
                    ✏️ Update Cepat Tanggal Periode Aktif (<?= htmlspecialchars($activePeriodName) ?>)
                </h4>
                <form id="updateDateForm">
                    <input type="hidden" name="id" value="<?= $activePeriodId ?>">
                    <input type="hidden" name="nama_periode" value="<?= htmlspecialchars($activePeriodName) ?>">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="tanggal_undian">Tanggal & Waktu Pengundian</label>
                            <div class="input-wrapper">
                                <input type="datetime-local" id="tanggal_undian" name="tanggal_undian" class="form-input" value="<?= $inputDateTime ?>" required>
                                <span class="input-icon">📆</span>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="announcement_note">Catatan / Pesan Pengumuman</label>
                            <div class="input-wrapper">
                                <input type="text" id="announcement_note" name="announcement_note" class="form-input" placeholder="Contoh: Pemenang dihubungi via WA..." value="<?= htmlspecialchars($announcement_note) ?>">
                                <span class="input-icon">💬</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.25rem; text-align: right;">
                        <button type="submit" class="btn-submit" style="width: auto; padding: 0.75rem 2rem; display: inline-flex;">
                            💾 Simpan Perubahan Periode Aktif
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- 2. Tabel Hasil Submit Form Client -->
        <section class="table-wrapper">
            <div class="table-header">
                <div>
                    <h3 class="table-title">Data Submit Form Client</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Daftar seluruh ulasan bintang 5 ShopeeFood yang masuk.</p>
                </div>
                
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <select id="tablePeriodFilter" class="form-input" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem;">
                        <option value="all">Semua Periode</option>
                        <?php foreach ($allPeriods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] == $activePeriodId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama_periode']) ?> <?= $p['is_active'] == 1 ? '(Aktif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" id="tableSearchInput" class="table-search" placeholder="🔍 Cari nama, WA, no pesanan...">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Periode</th>
                            <th>Tanggal Submit</th>
                            <th>Nama Peserta</th>
                            <th>No. WhatsApp</th>
                            <th>No. Pesanan ShopeeFood</th>
                            <th>Bukti Bintang 5</th>
                            <th>Status</th>
                            <th>Aksi (Hapus/WA)</th>
                        </tr>
                    </thead>
                    <tbody id="submissionTableBody">
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                    📥 Belum ada data submit dari client.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($submissions as $row): ?>
                                <tr id="row-<?= $row['id'] ?>" data-period-id="<?= $row['period_id'] ?>">
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <span style="font-size: 0.8rem; background: rgba(99,102,241,0.15); color: #818cf8; padding: 0.2rem 0.5rem; border-radius: 6px; border: 1px solid rgba(99,102,241,0.3);">
                                            <?= htmlspecialchars($row['nama_periode'] ?? 'Periode Active') ?>
                                        </span>
                                    </td>
                                    <td style="white-space: nowrap; font-size: 0.85rem;">
                                        <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['nama']) ?></strong>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($row['no_hp']) ?></code>
                                    </td>
                                    <td>
                                        <code style="color: var(--accent);">#<?= htmlspecialchars(ltrim($row['no_pesanan'], '#')) ?></code>
                                    </td>
                                    <td>
                                        <?php 
                                            $imgPath = '../' . UPLOAD_URL_PATH . $row['screenshot'];
                                        ?>
                                        <img src="<?= htmlspecialchars($imgPath) ?>" 
                                             alt="Screenshot" 
                                             class="img-thumb" 
                                             onclick="viewScreenshot('<?= htmlspecialchars($imgPath) ?>', <?= htmlspecialchars(json_encode($row['nama']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($row['no_pesanan']), ENT_QUOTES) ?>)" 
                                             title="Klik untuk lihat gambar full">
                                    </td>
                                    <td>
                                        <?php if ($row['is_winner'] == 1): ?>
                                            <span class="badge-winner">🏆 PEMENANG</span>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">Peserta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['no_hp']) ?>?text=Halo%20<?= urlencode($row['nama']) ?>!%20Terima%20kasih%20sudah%20mengikuti%20undian%20bintang%205%20ShopeeFood." 
                                           target="_blank" 
                                           class="btn-action btn-wa" 
                                           title="Chat WhatsApp">
                                            💬 WA
                                        </a>

                                        <button onclick="confirmDelete(<?= $row['id'] ?>, <?= htmlspecialchars(json_encode($row['nama']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($row['no_pesanan']), ENT_QUOTES) ?>)" 
                                                class="btn-action btn-delete" 
                                                title="Hapus Input Form">
                                            🗑️ Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Modal Create Period -->
    <div class="modal-overlay" id="createPeriodModal">
        <div class="modal-box" style="max-width: 500px;">
            <button class="modal-close" onclick="closeModal('createPeriodModal')">✕</button>
            <h3 style="font-size: 1.4rem; color: #fff; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                ➕ Mulai Periode Baru
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                Buat periode pengundian baru (misal: Periode Oktober 2026, Periode B, Periode C).
            </p>

            <form id="createPeriodForm">
                <div class="form-group">
                    <label class="form-label" for="new_nama_periode">Nama Periode <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <input type="text" id="new_nama_periode" name="nama_periode" class="form-input" placeholder="Contoh: Periode B / Oktober 2026" required>
                        <span class="input-icon">🏷️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_tanggal_undian">Tanggal & Waktu Pengundian <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <input type="datetime-local" id="new_tanggal_undian" name="tanggal_undian" class="form-input" value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>" required>
                        <span class="input-icon">📆</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_announcement_note">Catatan Pengumuman</label>
                    <div class="input-wrapper">
                        <input type="text" id="new_announcement_note" name="announcement_note" class="form-input" placeholder="Pemenang akan dihubungi via WA..." value="Pemenang dihubungi via WA resmi. Pastikan nomor anda aktif!">
                        <span class="input-icon">💬</span>
                    </div>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.6rem; background: rgba(15,23,42,0.6); padding: 0.8rem; border-radius: 8px;">
                    <input type="checkbox" id="new_set_active" name="set_active" value="1" checked style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="new_set_active" style="font-size: 0.9rem; cursor: pointer; color: #fff;">
                        Jadikan sebagai <strong>Periode Aktif</strong> langsung saat dibuat
                    </label>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeModal('createPeriodModal')" class="btn-nav">Batal</button>
                    <button type="submit" class="btn-submit" style="width: auto; padding: 0.75rem 1.5rem;">
                        ✨ Buat Periode Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Period -->
    <div class="modal-overlay" id="editPeriodModal">
        <div class="modal-box" style="max-width: 500px;">
            <button class="modal-close" onclick="closeModal('editPeriodModal')">✕</button>
            <h3 style="font-size: 1.3rem; color: #fff; margin-bottom: 0.5rem;">
                ✏️ Edit Data Periode
            </h3>
            
            <form id="editPeriodForm">
                <input type="hidden" id="edit_period_id" name="id">

                <div class="form-group">
                    <label class="form-label">Nama Periode</label>
                    <div class="input-wrapper">
                        <input type="text" id="edit_nama_periode" name="nama_periode" class="form-input" required>
                        <span class="input-icon">🏷️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Pengundian</label>
                    <div class="input-wrapper">
                        <input type="datetime-local" id="edit_tanggal_undian" name="tanggal_undian" class="form-input" required>
                        <span class="input-icon">📆</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status Periode</label>
                    <select id="edit_status" name="status" class="form-input" style="padding-left: 1rem;">
                        <option value="berlangsung">Berlangsung</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Pengumuman</label>
                    <div class="input-wrapper">
                        <input type="text" id="edit_announcement_note" name="announcement_note" class="form-input">
                        <span class="input-icon">💬</span>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeModal('editPeriodModal')" class="btn-nav">Batal</button>
                    <button type="button" onclick="executeEditPeriod()" class="btn-submit" style="width: auto; padding: 0.75rem 1.5rem;">
                        💾 Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Delete Period Confirmation (Dedicated Non-Blocking Modal) -->
    <div class="modal-overlay" id="deletePeriodModal">
        <div class="modal-box" style="max-width: 480px;">
            <button class="modal-close" onclick="closeModal('deletePeriodModal')">✕</button>
            <h3 style="font-size: 1.3rem; color: var(--danger); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                🗑️ Konfirmasi Hapus Periode
            </h3>
            <p id="deletePeriodModalInfo" style="color: var(--text-sub); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;"></p>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button onclick="closeModal('deletePeriodModal')" class="btn-nav" style="border: 1px solid var(--border-color);">Batal</button>
                <button id="btnConfirmDeletePeriod" onclick="executeDeletePeriod()" class="btn-action btn-delete" style="padding: 0.65rem 1.35rem; font-size: 0.9rem;">
                    Ya, Hapus Periode & Riwayat
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Delete Submission Entry Confirmation -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box" style="max-width: 450px;">
            <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
            <h3 style="font-size: 1.25rem; color: var(--danger); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                ⚠️ Konfirmasi Hapus Input Peserta
            </h3>
            <p id="deleteModalInfo" style="color: var(--text-sub); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem;"></p>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button onclick="closeModal('deleteModal')" class="btn-nav" style="border: 1px solid var(--border-color);">Batal</button>
                <button id="btnConfirmDelete" onclick="executeDelete()" class="btn-action btn-delete" style="padding: 0.6rem 1.25rem; font-size: 0.9rem;">
                    Ya, Hapus Permanen
                </button>
            </div>
        </div>
    </div>

    <!-- Screenshot Lightbox Modal -->
    <div class="modal-overlay" id="lightboxModal">
        <div class="modal-box" style="max-width: 750px; text-align: center;">
            <button class="modal-close" onclick="closeModal('lightboxModal')">✕</button>
            <h3 id="lightboxTitle" style="font-size: 1.1rem; color: #fff; margin-bottom: 1rem;"></h3>
            <img id="lightboxImg" src="" alt="Full Screenshot" style="max-width: 100%; max-height: 70vh; border-radius: 12px; border: 1px solid var(--border-color); object-fit: contain;">
        </div>
    </div>

    <!-- Lucky Draw Interactive Wheel Modal -->
    <div class="modal-overlay" id="drawModal">
        <div class="modal-box" style="max-width: 550px; text-align: center;">
            <button class="modal-close" onclick="closeModal('drawModal')">✕</button>
            
            <h2 style="font-size: 1.6rem; color: #fff; font-weight: 800; margin-bottom: 0.3rem;">
                🎰 Spin & Draw Pemenang (<?= htmlspecialchars($activePeriodName) ?>)
            </h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                Sistem akan secara acak memutar dan memilih 1 pemenang dari peserta yang valid pada periode aktif!
            </p>

            <div class="spin-wheel-container">
                <div id="spinDisplayName" class="spin-display-name">
                    <div style="font-size: 1.1rem; color: var(--text-muted);">Klik Mulai Undi</div>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <button id="btnSpinStart" onclick="startSpinDraw()" class="btn-submit" style="width: auto; padding: 0.85rem 2rem;">
                    🎲 Mulai Putar Undian!
                </button>
                <button onclick="resetAllWinners()" class="btn-nav btn-delete" style="font-size: 0.85rem;">
                    🔄 Reset Pemenang
                </button>
            </div>

            <div id="drawResultBox"></div>
        </div>
    </div>

    <footer class="footer">
        <p>© <?= date('Y') ?> Undian Bintang 5 ShopeeFood - Admin Control Center.</p>
    </footer>

    <!-- Admin JS -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>
