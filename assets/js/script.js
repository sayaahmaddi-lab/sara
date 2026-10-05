/**
 * File: assets/js/script.js
 * Deskripsi: Custom JavaScript untuk aplikasi
 */

// Document Ready
$(document).ready(function() {
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Konfirmasi hapus
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            e.preventDefault();
        }
    });
    
    // Format input rupiah
    $('.input-rupiah').on('keyup', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(formatRupiah(value));
    });
    
    // Auto hide alert
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
});

/**
 * Format angka ke Rupiah
 */
function formatRupiah(angka) {
    let number_string = angka.toString().replace(/[^,\d]/g, '');
    let split = number_string.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return 'Rp ' + rupiah;
}

/**
 * Show loading spinner
 */
function showLoading() {
    $('.spinner-overlay').addClass('active');
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    $('.spinner-overlay').removeClass('active');
}

/**
 * Print element
 */
function printElement(elementId) {
    let printContents = document.getElementById(elementId).innerHTML;
    let originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}

/**
 * Konfirmasi pembayaran
 */
function konfirmasiPembayaran(tagihan_id) {
    if (confirm('Apakah Anda yakin ingin melanjutkan pembayaran?')) {
        window.location.href = 'bayar.php?id=' + tagihan_id;
    }
}