document.addEventListener('DOMContentLoaded', () => {
    let allData = [];
    const dataGrid = document.getElementById('dataGrid');
    const searchInput = document.getElementById('searchInput');
    const totalCountEl = document.getElementById('totalCount');
    const lastUpdateEl = document.getElementById('lastUpdate');
    const exportBtn = document.getElementById('exportBtn');

    // Fetch Initial Data
    async function fetchData() {
        showLoader();
        try {
            const response = await fetch('/api/pelanggans');
            if (!response.ok) throw new Error('Gagal mengambil data');
            const data = await response.json();
            allData = data;
            renderData(allData);
            updateStats(allData);
        } catch (error) {
            console.error(error);
            showToast('Error: ' + error.message, 'error');
            dataGrid.innerHTML = '<div class="error-msg">Gagal memuat data. Pastikan server berjalan.</div>';
        }
    }

    function renderData(data) {
        if (data.length === 0) {
            dataGrid.innerHTML = '<div class="empty-msg">Tidak ada data ditemukan.</div>';
            return;
        }

        dataGrid.innerHTML = data.map(item => {
            const fotoUrl = getPhotoUrl(item.foto_path);
            const date = new Date(item.created_at).toLocaleString('id-ID');
            
            return `
                <div class="data-card" data-id="${item.id}">
                    <div class="card-image">
                        ${fotoUrl ? `<img src="${fotoUrl}" alt="${escapeHtml(item.nama)}" onerror="showImagePlaceholder(this)">` : '<i class="fas fa-image"></i>'}
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">${escapeHtml(item.nama)}</h3>
                        <div class="info-row">
                            <span class="info-label">Alamat:</span>
                            <span class="info-value">${escapeHtml(item.alamat)}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">No. Meter:</span>
                            <span class="info-value">${escapeHtml(item.no_meter)}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Daya:</span>
                            <span class="info-value">${escapeHtml(item.daya_listrik || '-')} VA</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">No. HP:</span>
                            <span class="info-value">${escapeHtml(item.no_hp || '-')}</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="timestamp"><i class="far fa-clock"></i> ${date}</span>
                        <button class="btn btn-danger btn-sm delete-btn" onclick="deleteItem(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateStats(data) {
        totalCountEl.textContent = data.length;
        if (data.length > 0) {
            const lastDate = new Date(data[0].created_at);
            lastUpdateEl.textContent = lastDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }
    }

    function getPhotoUrl(fotoPath) {
        if (!fotoPath || fotoPath.startsWith('/data/') || fotoPath.startsWith('file:')) {
            return null;
        }

        if (fotoPath.startsWith('http://') || fotoPath.startsWith('https://')) {
            return fotoPath;
        }

        const cleanPath = fotoPath.replace(/^\/+/, '').replace(/^storage\//, '');
        const encodedPath = cleanPath.split('/').map(encodeURIComponent).join('/');

        return `/api/foto/${encodedPath}`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char]));
    }

    window.showImagePlaceholder = (image) => {
        image.parentElement.innerHTML = '<i class="fas fa-image"></i>';
    };

    // Search Logic
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtered = allData.filter(item => 
            item.nama.toLowerCase().includes(query) || 
            item.alamat.toLowerCase().includes(query) || 
            item.no_meter.toString().includes(query)
        );
        renderData(filtered);
    });

    // Delete Logic
    window.deleteItem = async (id) => {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return;

        try {
            const response = await fetch(`/api/pelanggans/${id}`, {
                method: 'DELETE',
            });
            const result = await response.json();

            if (result.status === 'success') {
                showToast('Data berhasil dihapus');
                allData = allData.filter(item => item.id !== id);
                renderData(allData);
                updateStats(allData);
            } else {
                showToast('Gagal menghapus data: ' + result.message, 'error');
            }
        } catch (error) {
            showToast('Error: ' + error.message, 'error');
        }
    };

    // Export Logic
    exportBtn.addEventListener('click', () => {
        if (allData.length === 0) {
            showToast('Tidak ada data untuk diexport', 'error');
            return;
        }

        const headers = ['ID', 'Nama', 'Alamat', 'No Meter', 'Daya', 'No HP', 'Waktu Sinkron'];
        const csvRows = [headers.join(',')];

        allData.forEach(item => {
            const row = [
                item.id,
                `"${item.nama}"`,
                `"${item.alamat}"`,
                item.no_meter,
                item.daya_listrik,
                item.no_hp,
                item.created_at
            ];
            csvRows.push(row.join(','));
        });

        const csvContent = "data:text/csv;charset=utf-8," + csvRows.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `PLN_Sync_Data_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('Data berhasil diexport ke CSV');
    });

    // Utilities
    function showLoader() {
        dataGrid.innerHTML = '<div class="loader-container"><div class="loader"></div></div>';
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.style.display = 'block';
        toast.style.backgroundColor = type === 'error' ? '#ef4444' : '#10b981';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    // Init
    fetchData();
});
