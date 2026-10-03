/**
 * Admin Dashboard JavaScript
 * Undian ShopeeFood Bintang 5
 */

document.addEventListener('DOMContentLoaded', () => {
    initAdminDateForm();
    initAdminSearch();
    initPeriodForms();
});

// Toast notification for Admin
function showToast(title, message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    let icon = type === 'danger' ? '✕' : (type === 'warning' ? '⚠️' : '✓');

    toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
            <h4>${title}</h4>
            <p>${message}</p>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// 1. Update Active Period Form
function initAdminDateForm() {
    const form = document.getElementById('updateDateForm');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('action', 'edit_period');

        fetch('manage_periods.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Jadwal Diperbarui', data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('Gagal Perbarui', data.message, 'danger');
            }
        })
        .catch(() => {
            showToast('Error', 'Gagal memperbarui jadwal undian.', 'danger');
        });
    });
}

// 2. Multi-Period Management Forms (Create, Edit, Set Active, Delete)
function initPeriodForms() {
    const createForm = document.getElementById('createPeriodForm');
    if (createForm) {
        createForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(createForm);
            formData.append('action', 'create_period');

            fetch('manage_periods.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Periode Dibuat', data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Gagal', data.message, 'danger');
                }
            });
        });
    }
}

function openCreatePeriodModal() {
    const modal = document.getElementById('createPeriodModal');
    if (modal) modal.classList.add('active');
}

function setActivePeriod(id) {
    const formData = new FormData();
    formData.append('action', 'set_active');
    formData.append('id', id);

    fetch('manage_periods.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Periode Aktif Berubah', data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Gagal', data.message, 'danger');
        }
    });
}

function editPeriod(id, nama, tanggal, note, status) {
    document.getElementById('edit_period_id').value = id;
    document.getElementById('edit_nama_periode').value = nama;
    document.getElementById('edit_tanggal_undian').value = tanggal.replace(' ', 'T');
    document.getElementById('edit_announcement_note').value = note;
    document.getElementById('edit_status').value = status;

    const modal = document.getElementById('editPeriodModal');
    if (modal) modal.classList.add('active');
}

function executeEditPeriod() {
    const form = document.getElementById('editPeriodForm');
    if (!form) return;
    const formData = new FormData(form);
    formData.append('action', 'edit_period');

    fetch('manage_periods.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Diperbarui', data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Gagal', data.message, 'danger');
        }
    });
}

// Custom Non-Blocking Delete Period Modal Handling
let deletePeriodTargetId = null;

function confirmDeletePeriod(id, nama) {
    deletePeriodTargetId = id;
    const infoEl = document.getElementById('deletePeriodModalInfo');
    if (infoEl) {
        infoEl.innerHTML = `Apakah anda yakin ingin menghapus periode <strong>"${nama}"</strong>?<br><br><span style="color: var(--danger);">⚠️ PERHATIAN: Seluruh data peserta dan RIWAYAT PEMENANG pada periode ini akan ikut dihapus secara permanen!</span>`;
    }
    const modal = document.getElementById('deletePeriodModal');
    if (modal) modal.classList.add('active');
}

function deletePeriod(id, nama) {
    confirmDeletePeriod(id, nama);
}

function executeDeletePeriod() {
    if (!deletePeriodTargetId) return;

    const btn = document.getElementById('btnConfirmDeletePeriod');
    if (btn) btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'delete_period');
    formData.append('id', deletePeriodTargetId);

    fetch('manage_periods.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Terhapus', data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast('Gagal Hapus', data.message, 'danger');
        }
    })
    .catch(() => showToast('Error', 'Gagal menghapus periode.', 'danger'))
    .finally(() => {
        if (btn) btn.disabled = false;
        deletePeriodTargetId = null;
    });
}

// 3. Table Realtime Search & Period Filter
function initAdminSearch() {
    const searchInput = document.getElementById('tableSearchInput');
    const periodSelect = document.getElementById('tablePeriodFilter');

    function filterTable() {
        const query = searchInput ? searchInput.value.toLowerCase() : '';
        const periodVal = periodSelect ? periodSelect.value : 'all';
        const rows = document.querySelectorAll('#submissionTableBody tr');

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const rowPeriod = row.dataset.periodId;
            
            const matchesSearch = text.includes(query);
            const matchesPeriod = (periodVal === 'all' || rowPeriod === periodVal);

            row.style.display = (matchesSearch && matchesPeriod) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('keyup', filterTable);
    if (periodSelect) periodSelect.addEventListener('change', filterTable);
}

// 4. Delete Entry Action (Submission row)
let deleteTargetId = null;

function confirmDelete(id, nama, no_pesanan) {
    deleteTargetId = id;
    const infoEl = document.getElementById('deleteModalInfo');
    if (infoEl) {
        infoEl.innerHTML = `Apakah anda yakin ingin menghapus data peserta <strong>${nama}</strong> (No. Pesanan: <strong>#${no_pesanan.replace(/^#/, '')}</strong>)? Gambar screenshot juga akan dihapus.`;
    }
    const modal = document.getElementById('deleteModal');
    if (modal) modal.classList.add('active');
}

function executeDelete() {
    if (!deleteTargetId) return;

    const btn = document.getElementById('btnConfirmDelete');
    if (btn) btn.disabled = true;

    const formData = new FormData();
    formData.append('id', deleteTargetId);

    fetch('delete_entry.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Terhapus', data.message, 'success');
            const row = document.getElementById(`row-${deleteTargetId}`);
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'scale(0.95)';
                row.style.transition = 'all 0.3s ease';
                setTimeout(() => row.remove(), 300);
            }
            closeModal('deleteModal');
        } else {
            showToast('Gagal Hapus', data.message, 'danger');
        }
    })
    .catch(() => showToast('Error', 'Gagal menghapus data.', 'danger'))
    .finally(() => {
        if (btn) btn.disabled = false;
        deleteTargetId = null;
    });
}

// 5. Screenshot Lightbox View
function viewScreenshot(url, nama, no_pesanan) {
    const imgEl = document.getElementById('lightboxImg');
    const titleEl = document.getElementById('lightboxTitle');
    
    if (imgEl) imgEl.src = url;
    if (titleEl) titleEl.innerText = `Bukti Bintang 5 - ${nama} (#${no_pesanan.replace(/^#/, '')})`;

    const modal = document.getElementById('lightboxModal');
    if (modal) modal.classList.add('active');
}

// 6. Interactive Lucky Draw Wheel / Spinner Animation
function openDrawModal() {
    const modal = document.getElementById('drawModal');
    if (modal) modal.classList.add('active');
}

function startSpinDraw() {
    const spinDisplay = document.getElementById('spinDisplayName');
    const btnSpin = document.getElementById('btnSpinStart');
    const resultBox = document.getElementById('drawResultBox');

    if (btnSpin) btnSpin.disabled = true;
    if (resultBox) resultBox.style.display = 'none';

    fetch('../api/get_participants.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success || data.participants.length === 0) {
                showToast('Tidak Ada Peserta', 'Belum ada peserta yang mendaftar undian di periode ini!', 'warning');
                if (btnSpin) btnSpin.disabled = false;
                return;
            }

            const list = data.participants;
            let counter = 0;
            let speed = 50; // milliseconds
            let duration = 5000; // total duration 5 seconds
            let startTime = Date.now();

            playSpinSound();

            function cycle() {
                const p = list[counter % list.length];
                if (spinDisplay) {
                    spinDisplay.innerHTML = `
                        <div style="font-size: 1.1rem; color: #cbd5e1; margin-bottom: 0.2rem;">${p.nama}</div>
                        <div style="font-size: 0.85rem; color: var(--primary); font-family: monospace;">#${p.no_pesanan.replace(/^#/, '')}</div>
                    `;
                }
                counter++;

                const elapsed = Date.now() - startTime;
                if (elapsed < duration) {
                    if (elapsed > duration - 2000) {
                        speed += 15;
                    }
                    setTimeout(cycle, speed);
                } else {
                    const winner = list[Math.floor(Math.random() * list.length)];
                    finishDraw(winner);
                }
            }

            cycle();
        })
        .catch(err => {
            showToast('Error', 'Gagal memuat data peserta undian.', 'danger');
            if (btnSpin) btnSpin.disabled = false;
        });
}

function finishDraw(winner) {
    const spinDisplay = document.getElementById('spinDisplayName');
    const btnSpin = document.getElementById('btnSpinStart');
    const resultBox = document.getElementById('drawResultBox');

    playWinSound();

    if (spinDisplay) {
        spinDisplay.innerHTML = `
            <div style="font-size: 1.6rem; color: var(--accent); font-weight: 900;">🏆 WINNER!</div>
            <div style="font-size: 1.25rem; color: #fff; margin-top: 0.3rem;">${winner.nama}</div>
        `;
    }

    if (resultBox) {
        resultBox.style.display = 'block';
        resultBox.innerHTML = `
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 12px; padding: 1.25rem; text-align: center; margin-top: 1rem;">
                <h3 style="color: #34d399; margin-bottom: 0.5rem;">🎉 Selamat Kepada Pemenang!</h3>
                <p style="color: #fff; font-size: 0.95rem;"><strong>Nama:</strong> ${winner.nama}</p>
                <p style="color: var(--text-sub); font-size: 0.9rem;"><strong>No. Pesanan:</strong> #${winner.no_pesanan.replace(/^#/, '')}</p>
                <p style="color: var(--text-sub); font-size: 0.9rem;"><strong>WhatsApp:</strong> ${winner.no_hp}</p>
                
                <div style="display: flex; gap: 0.75rem; justify-content: center; margin-top: 1rem;">
                    <button class="btn-action btn-wa" onclick="window.open('https://wa.me/${winner.no_hp}?text=Selamat!%20Anda%20memenangkan%20undian%20ShopeeFood%20bintang%205!', '_blank')">
                        💬 Hubungi Pemenang di WA
                    </button>
                    <button class="btn-action btn-nav-primary" onclick="markAsWinner(${winner.id})">
                        ⭐ Simpan Sebagai Pemenang
                    </button>
                </div>
            </div>
        `;
    }

    if (btnSpin) btnSpin.disabled = false;
}

function markAsWinner(id) {
    const formData = new FormData();
    formData.append('action', 'set_winner');
    formData.append('id', id);

    fetch('pick_winner.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Pemenang Disimpan', data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Gagal', data.message, 'danger');
        }
    });
}

function resetAllWinners() {
    if (!confirm('Apakah anda yakin ingin mengundi ulang dan mereset status pemenang yang ada?')) return;

    const formData = new FormData();
    formData.append('action', 'reset_winners');

    fetch('pick_winner.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showToast('Reset Selesai', data.message, 'info');
        setTimeout(() => location.reload(), 1000);
    });
}

// Web Audio API Synth Sounds
function playSpinSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(440, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.05, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.1);
    } catch(e) {}
}

function playWinSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [523.25, 659.25, 783.99, 1046.50].forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.1, now + i * 0.1);
            gain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.1 + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now + i * 0.1);
            osc.stop(now + i * 0.1 + 0.3);
        });
    } catch(e) {}
}

// Export Submissions to CSV
function exportCSV() {
    const table = document.querySelector(".custom-table");
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length - 1; j++) {
            let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
            row.push('"' + text.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(","));
    }

    const csvFile = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    const downloadLink = document.createElement("a");
    downloadLink.download = `Data_Undian_ShopeeFood_${new Date().toISOString().slice(0,10)}.csv`;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    downloadLink.remove();
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}
