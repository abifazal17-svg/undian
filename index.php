<?php
require_once __DIR__ . '/config.php';

// Get current active period
$activePeriod = getActivePeriod();
$tanggal_undian = $activePeriod ? $activePeriod['tanggal_undian'] : date('Y-m-d H:i:s', strtotime('+7 days'));
$announcement_note = $activePeriod ? $activePeriod['announcement_note'] : 'Pemenang akan dihubungi melalui WhatsApp resmi.';
$nama_periode = $activePeriod ? $activePeriod['nama_periode'] : 'Periode Aktif';

// Fetch current active period winners
$db = getDB();
$periodId = $activePeriod ? $activePeriod['id'] : 1;
$winnerStmt = $db->prepare("SELECT * FROM submissions WHERE is_winner = 1 AND period_id = ? ORDER BY created_at DESC");
$winnerStmt->execute([$periodId]);
$winners = $winnerStmt->fetchAll();

// Fetch ALL Winner History across periods (Riwayat Pemenang Periode)
$historyStmt = $db->query("
    SELECT s.*, p.nama_periode, p.tanggal_undian as p_tanggal 
    FROM submissions s 
    INNER JOIN periods p ON s.period_id = p.id 
    WHERE s.is_winner = 1 
    ORDER BY s.period_id DESC, s.created_at DESC
");
$winnerHistory = $historyStmt->fetchAll();

// Count total participants for active period
$totalStmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE period_id = ?");
$totalStmt->execute([$periodId]);
$totalParticipants = $totalStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undian Berhadiah Bintang 5 ShopeeFood - <?= htmlspecialchars($nama_periode) ?></title>
    <meta name="description" content="Upload bukti ulasan bintang 5 ShopeeFood anda dan menangkan hadiah menarik dalam pengundian resmi!">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navigation Header -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-icon">🥘</div>
            <span>Kedai Wajan</span>
        </a>
        <div class="nav-links" style="display:none;">
            <a href="#formSection" class="btn-nav btn-nav-primary">🎯 Ikut Undian</a>
            <a href="admin/login.php" class="btn-nav">🔑 Portal Admin</a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="container">

        <!-- Hero Section -->
        <section class="hero">
            <!--<div class="badge-tag">
                ✨ Selamat Mengikuti Undian &bull; <?= htmlspecialchars($nama_periode) ?> Semoga Beruntung!
            </div>-->
            <h1>Selamat Mengikuti Undian <?= htmlspecialchars($nama_periode) ?><br>Semoga Beruntung!</h1>
            <p>
                Berikan ulasan <strong>Bintang 5</strong>, upload buktinya, dan raih kesempatan memenangkan hadiah menarik!
            </p>
        </section>

        <!-- Countdown & Draw Period Section -->
        <section class="countdown-card">
            <div class="countdown-header">
                <span id="statusBadge" class="badge-tag" style="background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.4); color: #f59e0b;">
                    ⏳ <?= htmlspecialchars($nama_periode) ?> Sedang Berlangsung
                </span>
                <h3>📅 Jadwal Pengumuman Pemenang</h3>
            </div>

            <div class="countdown-timer" id="countdownTimer" data-target="<?= htmlspecialchars($tanggal_undian) ?>">
                <div class="timer-box">
                    <div class="timer-value" id="timerDays">00</div>
                    <div class="timer-label">Hari</div>
                </div>
                <div class="timer-box">
                    <div class="timer-value" id="timerHours">00</div>
                    <div class="timer-label">Jam</div>
                </div>
                <div class="timer-box">
                    <div class="timer-value" id="timerMinutes">00</div>
                    <div class="timer-label">Menit</div>
                </div>
                <div class="timer-box">
                    <div class="timer-value" id="timerSeconds">00</div>
                    <div class="timer-label">Detik</div>
                </div>
            </div>

            <div class="announcement-note">
                <strong>📌 Tanggal Pengundian:</strong> <?= date('d F Y - H:i', strtotime($tanggal_undian)) ?> WIB<br>
                <em><?= htmlspecialchars($announcement_note) ?></em>
            </div>
        </section>

        <!-- Winners Showcase (If active period winners exist) -->
        <!--<?php if (!empty($winners)): ?>
        <section class="form-card" style="border-color: rgba(245, 158, 11, 0.4); background: rgba(30, 41, 59, 0.85); margin-bottom: 3rem;">
            <div class="form-title" style="color: #f59e0b;">
                🏆 Pemenang Terpilih - <?= htmlspecialchars($nama_periode) ?>
            </div>
            <p class="form-subtitle">Selamat kepada pemenang ulasan bintang 5 ShopeeFood pada periode ini!</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                <?php foreach ($winners as $w): ?>
                <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2.2rem;">🎁</div>
                    <div>
                        <div style="font-weight: 800; font-size: 1.1rem; color: #fff;"><?= htmlspecialchars($w['nama']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">No. Pesanan: #<?= htmlspecialchars(ltrim($w['no_pesanan'], '#')) ?></div>
                        <div style="font-size: 0.8rem; color: #34d399; margin-top: 0.2rem;">✓ Pemenang Terpilih</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>-->

        <!-- Form Submission Section -->
        <section id="formSection" class="form-card">
            <div class="form-title">
                📝 Form Pendaftaran Peserta (<?= htmlspecialchars($nama_periode) ?>)
            </div>
            <p class="form-subtitle">
                Isi data diri dan nomor pesanan ShopeeFood anda di bawah ini.<br><br>(Total Peserta Terdaftar: <strong><?= number_format($totalParticipants) ?></strong>)
            </p>

            <form id="submissionForm" enctype="multipart/form-data">
                
                <!-- Nama Lengkap -->
                <div class="form-group">
                    <label class="form-label" for="nama">
                        Nama Lengkap <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="nama" name="nama" class="form-input" placeholder="Masukkan nama lengkap anda" required>
                        <span class="input-icon">👤</span>
                    </div>
                </div>

                <!-- Nomor HP / WhatsApp -->
                <div class="form-group">
                    <label class="form-label" for="no_hp">
                        Nomor HP / WhatsApp Aktif <span class="required">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input type="tel" id="no_hp" name="no_hp" class="form-input" placeholder="Contoh: 081234567890" required>
                        <span class="input-icon">📱</span>
                    </div>
                    <div class="form-hint">
                        💡 <em>Pastikan nomor WhatsApp aktif untuk dihubungi jika anda keluar sebagai pemenang.</em>
                    </div>
                </div>

                <!-- Nomor Pesanan ShopeeFood (# Prefix & Example Button) -->
                <div class="form-group">
                    <div class="form-label-row">
                        <label class="form-label" for="no_pesanan">
                            Nomor Pesanan ShopeeFood : <span style="color: var(--primary); font-weight: 800;"># xxx</span> <span class="required">*</span>
                        </label>
                        <!-- Button Contoh Nomor Pesanan -->
                        <button type="button" class="btn-example-order" onclick="openExampleModal()">
                            ❓ Contoh Nomor Pesanan
                        </button>
                    </div>

                    <div class="input-wrapper">
                        <span class="input-prefix-badge">#</span>
                        <input type="text" id="no_pesanan" name="no_pesanan" class="form-input form-input-prefixed" placeholder="  100" required>
                        <span class="input-icon">🧾</span>
                    </div>
                    <div id="orderCheckStatus"></div>
                    <div class="form-hint">
                        ⚠️ 1 Nomor Pesanan hanya dapat digunakan 1 kali pendaftaran.
                    </div>
                </div>

                <!-- Screenshot Bintang 5 Upload -->
                <div class="form-group">
                    <label class="form-label">
                        Screenshot Ulasan Bintang 5 ShopeeFood <span class="required">*</span>
                    </label>
                    
                    <div class="dropzone" id="screenshotDropzone">
                        <div class="dropzone-icon">📸</div>
                        <div class="dropzone-text">Klik atau seret file gambar screenshot di sini</div>
                        <div class="dropzone-subtext">Format: JPG, PNG, WEBP (Maksimal 5MB)</div>
                        <input type="file" id="screenshotInput" name="screenshot" accept="image/jpeg,image/png,image/webp" class="file-input-hidden" required>
                    </div>

                    <!-- Image Preview Container -->
                    <div class="preview-container" id="previewContainer">
                        <img id="previewImg" src="" alt="Screenshot Preview" class="preview-img">
                        <button type="button" id="removePreview" class="btn-remove-preview" title="Hapus Gambar">✕</button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="btnSubmit" class="btn-submit">
                    🎁 Kirim Pendaftaran Undian
                </button>
            </form>
        </section>

        <!-- Tabel Riwayat Pemenang Periode (Penambahan Poin #3) -->
        <section class="winner-history-card">
            <div class="winner-history-title">
                🏆 Riwayat Pemenang Periode
            </div>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                Daftar pemenang pengundian dari periode yang telah berlalu maupun berjalan. (Nama disensor demi privasi).
            </p>

            <div class="table-wrapper" style="margin-bottom: 0;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Periode Undian</th>
                            <th>Tanggal Pengundian</th>
                            <th>Nama Pemenang (Disensor)</th>
                            <th>No. Pesanan ShopeeFood</th>
                            <th>Status Pemenang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($winnerHistory)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                                    📢 Belum ada riwayat pemenang yang ditetapkan. Pengundian akan diumumkan sesuai jadwal!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($winnerHistory as $h): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #fff;"><?= htmlspecialchars($h['nama_periode'] ?? 'Periode Active') ?></strong>
                                    </td>
                                    <td style="white-space: nowrap; font-size: 0.85rem;">
                                        <?= !empty($h['p_tanggal']) ? date('d M Y - H:i', strtotime($h['p_tanggal'])) : date('d M Y', strtotime($h['created_at'])) ?> WIB
                                    </td>
                                    <td>
                                        <!-- Censored / Masked Name Example: Abiyyu Dhonan -> A***** *****N -->
                                        <span class="masked-name"><?= htmlspecialchars(maskWinnerName($h['nama'])) ?></span>
                                    </td>
                                    <td>
                                        <code>#<?= htmlspecialchars(ltrim($h['no_pesanan'], '#')) ?></code>
                                    </td>
                                    <td>
                                        <span class="badge-winner">🏆 PEMENANG SAH</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Modal Popup Contoh Nomor Pesanan (Penambahan Poin #2) -->
    <div class="modal-overlay" id="exampleOrderModal">
        <div class="modal-box" style="max-width: 650px; text-align: center;">
            <button class="modal-close" onclick="closeModal('exampleOrderModal')">✕</button>
            <h3 style="font-size: 1.3rem; color: #fff; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                🧾 Contoh Letak Nomor Pesanan ShopeeFood
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                Temukan nomor pesanan (berawalan <strong>#</strong>) pada resi atau detail pesanan ShopeeFood anda seperti gambar di bawah ini:
            </p>
            <div style="background: rgba(15,23,42,0.9); padding: 0.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                <img src="assets/example.png" alt="Contoh Nomor Pesanan ShopeeFood" style="max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 0 auto;" onerror="this.src='assets/img/example.png'">
            </div>
            <div style="margin-top: 1.25rem;">
                <button type="button" class="btn-submit" onclick="closeModal('exampleOrderModal')">Paham & Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Success Popup -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal('successModal')">✕</button>
            <div id="successModalBody"></div>
            <div style="text-align: center; margin-top: 1.5rem;">
                <button class="btn-submit" onclick="closeModal('successModal')">Tutup & Selesai</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>© <?= date('Y') ?> Undian Bintang 5 ShopeeFood. Semua Hak Cipta Dilindungi.</p>
    </footer>

    <!-- Main JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
