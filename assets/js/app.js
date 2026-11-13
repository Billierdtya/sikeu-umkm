// Script Javascript Pendukung Aplikasi Komputer Bisnis

document.addEventListener('DOMContentLoaded', function () {
    // Format input nominal ke angka dengan pemisah ribuan saat diketik
    const inputNominal = document.querySelectorAll('.format-rupiah');
    inputNominal.forEach(function (input) {
        input.addEventListener('keyup', function (e) {
            let val = this.value.replace(/[^,\d]/g, '').toString();
            let split = val.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            this.value = rupiah;
        });
    });

    // Auto dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Toggle Sidebar di layar mobile / kecil
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }
});

/**
 * Konfirmasi sebelum hapus data
 */
function konfirmasiHapus(url, namaData = 'data ini') {
    if (confirm('Apakah Anda yakin ingin menghapus ' + namaData + '? Tindakan ini tidak dapat dibatalkan.')) {
        window.location.href = url;
    }
}

