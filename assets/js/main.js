/**
 * Main Client JavaScript
 * Undian ShopeeFood Bintang 5
 */

document.addEventListener('DOMContentLoaded', () => {
    initCountdown();
    initFormHandlers();
    initDropzone();
});

// Toast Notification Helper
function showToast(title, message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = '✓';
    if (type === 'danger') icon = '✕';
    if (type === 'warning') icon = '⚠️';

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
    }, 4000);
}

// Open Example Order Modal Popup
function openExampleModal() {
    const modal = document.getElementById('exampleOrderModal');
    if (modal) modal.classList.add('active');
}

// 1. Live Countdown Timer
function initCountdown() {
    const timerElement = document.getElementById('countdownTimer');
    if (!timerElement) return;

    const targetDateStr = timerElement.dataset.target;
    if (!targetDateStr) return;

    const targetDate = new Date(targetDateStr.replace(' ', 'T')).getTime();

    function update() {
        const now = new Date().getTime();
        const distance = targetDate - now;

        const daysEl = document.getElementById('timerDays');
        const hoursEl = document.getElementById('timerHours');
        const minutesEl = document.getElementById('timerMinutes');
        const secondsEl = document.getElementById('timerSeconds');

        if (distance < 0) {
            if (daysEl) daysEl.innerText = '00';
            if (hoursEl) hoursEl.innerText = '00';
            if (minutesEl) minutesEl.innerText = '00';
            if (secondsEl) secondsEl.innerText = '00';
            
            const badge = document.getElementById('statusBadge');
            if (badge) {
                badge.innerText = 'Pengundian Sedang / Sudah Berlangsung!';
                badge.style.borderColor = '#10b981';
                badge.style.color = '#34d399';
            }
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        if (daysEl) daysEl.innerText = String(days).padStart(2, '0');
        if (hoursEl) hoursEl.innerText = String(hours).padStart(2, '0');
        if (minutesEl) minutesEl.innerText = String(minutes).padStart(2, '0');
        if (secondsEl) secondsEl.innerText = String(seconds).padStart(2, '0');
    }

    update();
    setInterval(update, 1000);
}

// 2. Dropzone & Image Preview
function initDropzone() {
    const dropzone = document.getElementById('screenshotDropzone');
    const fileInput = document.getElementById('screenshotInput');
    const previewContainer = document.getElementById('previewContainer');
    const previewImg = document.getElementById('previewImg');
    const removeBtn = document.getElementById('removePreview');

    if (!dropzone || !fileInput) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('dragover'), false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFiles(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (fileInput.files.length > 0) {
            handleFiles(fileInput.files[0]);
        }
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.value = '';
            previewContainer.style.display = 'none';
            dropzone.style.display = 'block';
        });
    }

    function handleFiles(file) {
        const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!validTypes.includes(file.type)) {
            showToast('Format Tidak Didukung', 'Format gambar harus JPG, PNG, atau WEBP', 'danger');
            fileInput.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            showToast('Ukuran Terlalu Besar', 'Maksimal ukuran screenshot adalah 5MB', 'danger');
            fileInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
            dropzone.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

// 3. Duplicate Order AJAX Validation & Form Submit
function initFormHandlers() {
    const orderInput = document.getElementById('no_pesanan');
    const orderStatus = document.getElementById('orderCheckStatus');
    const submitForm = document.getElementById('submissionForm');
    const submitBtn = document.getElementById('btnSubmit');

    let orderCheckTimeout = null;

    if (orderInput && orderStatus) {
        orderInput.addEventListener('input', () => {
            clearTimeout(orderCheckTimeout);
            const val = orderInput.value.trim().replace(/^#\s*/, '');
            
            if (val.length < 3) {
                orderStatus.innerHTML = '';
                return;
            }

            orderStatus.innerHTML = '<span style="color: var(--text-muted)">🔎 Memeriksa nomor pesanan...</span>';

            orderCheckTimeout = setTimeout(() => {
                fetch(`api/check_order.php?no_pesanan=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.exists) {
                            orderStatus.className = 'order-check-status invalid';
                            orderStatus.innerHTML = `✕ Nomor pesanan #${val} SUDAH TERDAFTAR! Silakan periksa kembali.`;
                            if (submitBtn) submitBtn.disabled = true;
                        } else {
                            orderStatus.className = 'order-check-status valid';
                            orderStatus.innerHTML = `✓ Nomor pesanan #${val} tersedia untuk diikutsertakan.`;
                            if (submitBtn) submitBtn.disabled = false;
                        }
                    })
                    .catch(() => {
                        orderStatus.innerHTML = '';
                    });
            }, 400);
        });
    }

    if (submitForm) {
        submitForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const nama = document.getElementById('nama').value.trim();
            const no_hp = document.getElementById('no_hp').value.trim();
            const no_pesanan = document.getElementById('no_pesanan').value.trim();
            const screenshot = document.getElementById('screenshotInput').files[0];

            if (!nama || !no_hp || !no_pesanan) {
                showToast('Form Belum Lengkap', 'Harap isi semua bidang data yang diperlukan.', 'danger');
                return;
            }

            if (!screenshot) {
                showToast('Screenshot Wajib', 'Harap unggah bukti screenshot bintang 5 ShopeeFood anda.', 'danger');
                return;
            }

            const formData = new FormData(submitForm);
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>⏳ Memproses Pendaftaran...</span>';
            }

            fetch('process_submit.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal(data.message, data.details);
                    submitForm.reset();
                    const previewContainer = document.getElementById('previewContainer');
                    const dropzone = document.getElementById('screenshotDropzone');
                    if (previewContainer) previewContainer.style.display = 'none';
                    if (dropzone) dropzone.style.display = 'block';
                    if (orderStatus) orderStatus.innerHTML = '';
                } else {
                    showToast('Gagal Terdaftar', data.message, 'danger');
                }
            })
            .catch(err => {
                showToast('Error Server', 'Terjadi kesalahan saat menghubungkan ke server.', 'danger');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '🎁 Kirim Pendaftaran Undian';
                }
            });
        });
    }
}

// Success Modal Display
function showSuccessModal(title, details) {
    const modal = document.getElementById('successModal');
    if (!modal) {
        showToast('Berhasil!', title, 'success');
        return;
    }

    const modalBody = document.getElementById('successModalBody');
    if (modalBody) {
        modalBody.innerHTML = `
            <div style="text-align:center; padding: 1rem 0;">
                <div style="font-size: 3.5rem; margin-bottom: 1rem;">🎉</div>
                <h3 style="font-size: 1.4rem; color: #fff; margin-bottom: 0.5rem;">${title}</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                    Data anda berhasil dicatat untuk <strong>${details.periode}</strong>. Semoga beruntung!
                </p>
                <div style="background: rgba(15,23,42,0.8); border: 1px solid var(--border-color); padding: 1rem; border-radius: 12px; text-align: left; font-size: 0.9rem; line-height: 1.6;">
                    <strong>Nama:</strong> ${details.nama}<br>
                    <strong>No. WhatsApp:</strong> ${details.no_hp}<br>
                    <strong>No. Pesanan:</strong> ${details.no_pesanan}<br>
                    <strong>Periode Undian:</strong> ${details.periode}<br>
                    <strong>Waktu Submit:</strong> ${details.created_at}
                </div>
            </div>
        `;
    }

    modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}
