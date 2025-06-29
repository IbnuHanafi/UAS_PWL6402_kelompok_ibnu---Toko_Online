/**
 * Custom JavaScript
 * File: assets/js/script.js
 * Toko Online - UAS Pemrograman Web Lanjut
 */

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize DataTables if exists
    if ($('.datatable').length) {
        $('.datatable').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                },
                emptyTable: "Tidak ada data yang tersedia",
                zeroRecords: "Tidak ditemukan data yang sesuai"
            }
        });
    }

    // Back to top button functionality
    var backToTopBtn = $('#backToTopBtn');
    
    $(window).scroll(function() {
        if ($(window).scrollTop() > 300) {
            backToTopBtn.fadeIn();
        } else {
            backToTopBtn.fadeOut();
        }
    });

    backToTopBtn.click(function() {
        $('html, body').animate({scrollTop: 0}, 800);
        return false;
    });

    // Auto-hide alerts after 5 seconds
    $('.alert:not(.alert-permanent)').delay(5000).slideUp(500);

    // Form validation
    $('.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Number formatting for price inputs
    $('.price-input').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(formatNumber(value));
    });

    // Quantity input controls
    $('.qty-minus').click(function() {
        var input = $(this).siblings('.qty-input');
        var currentVal = parseInt(input.val());
        if (currentVal > 1) {
            input.val(currentVal - 1);
            updateCartItem(input);
        }
    });

    $('.qty-plus').click(function() {
        var input = $(this).siblings('.qty-input');
        var currentVal = parseInt(input.val());
        var maxVal = parseInt(input.attr('max'));
        if (currentVal < maxVal) {
            input.val(currentVal + 1);
            updateCartItem(input);
        }
    });

    // Image preview for file uploads
    $('.image-upload').change(function() {
        var input = this;
        var preview = $(input).siblings('.image-preview');
        
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.attr('src', e.target.result).show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.searchable-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Category filter
    $('.category-filter').change(function() {
        var categoryId = $(this).val();
        if (categoryId === '') {
            $('.product-item').show();
        } else {
            $('.product-item').hide();
            $('.product-item[data-category="' + categoryId + '"]').show();
        }
    });

    // Price range filter
    $('#priceRange').on('input', function() {
        var maxPrice = parseInt($(this).val());
        $('#priceValue').text(formatRupiah(maxPrice));
        
        $('.product-item').each(function() {
            var productPrice = parseInt($(this).data('price'));
            if (productPrice <= maxPrice) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Add to cart functionality
    $('.add-to-cart').click(function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        var quantity = $(this).closest('.product-card').find('.quantity-input').val() || 1;
        
        addToCart(productId, quantity);
    });

    // Remove from cart
    $('.remove-from-cart').click(function(e) {
        e.preventDefault();
        var cartId = $(this).data('cart-id');
        removeFromCart(cartId);
    });

    // Update cart item quantity
    $('.cart-quantity').change(function() {
        updateCartItem($(this));
    });

    // Checkout form validation
    $('#checkoutForm').on('submit', function(e) {
        var isValid = true;
        
        // Validate required fields
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            showAlert('error', 'Mohon lengkapi semua field yang diperlukan');
        }
    });

    // Order status update (for admin)
    $('.update-order-status').change(function() {
        var orderId = $(this).data('order-id');
        var newStatus = $(this).val();
        updateOrderStatus(orderId, newStatus);
    });

    // Delete confirmation
    $('.delete-btn').click(function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr('href');
        var itemName = $(this).data('item-name') || 'item ini';
        
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: 'Apakah Anda yakin ingin menghapus ' + itemName + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });

    // Print functionality
    $('.print-btn').click(function() {
        window.print();
    });

    // Export functionality
    $('.export-btn').click(function() {
        var format = $(this).data('format');
        var url = $(this).data('url');
        window.open(url + '?format=' + format, '_blank');
    });
});

// Utility Functions

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatRupiah(amount) {
    return 'Rp ' + formatNumber(amount);
}

function showAlert(type, message) {
    var alertClass = 'alert-info';
    var iconClass = 'fa-info-circle';
    
    switch(type) {
        case 'success':
            alertClass = 'alert-success';
            iconClass = 'fa-check-circle';
            break;
        case 'error':
            alertClass = 'alert-danger';
            iconClass = 'fa-exclamation-circle';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            iconClass = 'fa-exclamation-triangle';
            break;
    }
    
    var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                   '<i class="fas ' + iconClass + ' me-2"></i>' + message +
                   '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                   '</div>';
    
    $('.main-content').prepend('<div class="container mt-3">' + alertHtml + '</div>');
    
    // Auto hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

function addToCart(productId, quantity) {
    $.ajax({
        url: '../customer/add_to_cart.php',
        method: 'POST',
        data: {
            product_id: productId,
            quantity: quantity
        },
        dataType: 'json',
        beforeSend: function() {
            $('.add-to-cart[data-product-id="' + productId + '"]').prop('disabled', true)
                .html('<span class="loading-spinner"></span> Menambahkan...');
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                updateCartBadge();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Terjadi kesalahan saat menambahkan ke keranjang');
        },
        complete: function() {
            $('.add-to-cart[data-product-id="' + productId + '"]').prop('disabled', false)
                .html('<i class="fas fa-cart-plus me-1"></i>Tambah ke Keranjang');
        }
    });
}

function removeFromCart(cartId) {
    Swal.fire({
        title: 'Hapus dari Keranjang?',
        text: 'Item akan dihapus dari keranjang belanja',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../customer/remove_from_cart.php',
                method: 'POST',
                data: { cart_id: cartId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#cart-item-' + cartId).fadeOut(500, function() {
                            $(this).remove();
                            updateCartTotal();
                        });
                        updateCartBadge();
                        showAlert('success', response.message);
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function() {
                    showAlert('error', 'Terjadi kesalahan saat menghapus item');
                }
            });
        }
    });
}

function updateCartItem(input) {
    var cartId = input.data('cart-id');
    var quantity = input.val();
    
    $.ajax({
        url: '../customer/update_cart.php',
        method: 'POST',
        data: {
            cart_id: cartId,
            quantity: quantity
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateCartTotal();
                updateCartBadge();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Terjadi kesalahan saat mengupdate keranjang');
        }
    });
}

function updateCartBadge() {
    $.ajax({
        url: '../customer/get_cart_count.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.count > 0) {
                $('.cart-badge').text(response.count).show();
            } else {
                $('.cart-badge').hide();
            }
        }
    });
}

function updateCartTotal() {
    $.ajax({
        url: '../customer/get_cart_total.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#cart-total').text(formatRupiah(response.total));
        }
    });
}

function updateOrderStatus(orderId, newStatus) {
    $.ajax({
        url: '../admin/update_order_status.php',
        method: 'POST',
        data: {
            order_id: orderId,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Status pesanan berhasil diupdate');
                location.reload();
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Terjadi kesalahan saat mengupdate status');
        }
    });
}

// Dashboard Chart Initialization (for admin)
function initDashboardCharts() {
    // Sales Chart
    if ($('#salesChart').length) {
        var ctx = document.getElementById('salesChart').getContext('2d');
        var salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesData.labels,
                datasets: [{
                    label: 'Penjualan',
                    data: salesData.data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Category Chart
    if ($('#categoryChart').length) {
        var ctx = document.getElementById('categoryChart').getContext('2d');
        var categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoryData.labels,
                datasets: [{
                    data: categoryData.data,
                    backgroundColor: [
                        '#0d6efd',
                        '#198754',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Initialize charts when page loads
$(window).on('load', function() {
    if (typeof salesData !== 'undefined' || typeof categoryData !== 'undefined') {
        initDashboardCharts();
    }
});

// Real-time notifications (WebSocket simulation with polling)
function checkNotifications() {
    if (typeof isAdmin !== 'undefined' && isAdmin) {
        $.ajax({
            url: '../admin/get_notifications.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.new_orders > 0) {
                    showNotification('Pesanan baru: ' + response.new_orders + ' pesanan menunggu konfirmasi');
                }
            }
        });
    }
}

function showNotification(message) {
    // Create notification toast
    var toastHtml = '<div class="toast align-items-center text-bg-primary border-0 position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">' +
                   '<div class="d-flex">' +
                   '<div class="toast-body">' + message + '</div>' +
                   '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
                   '</div></div>';
    
    $('body').append(toastHtml);
    var toast = new bootstrap.Toast($('.toast').last());
    toast.show();
}

// Check notifications every 30 seconds
setInterval(checkNotifications, 30000);

// Form auto-save (draft functionality)
function autoSaveForm(formId) {
    var form = $('#' + formId);
    var formData = form.serialize();
    var savedData = localStorage.getItem(formId + '_draft');
    
    if (formData !== savedData) {
        localStorage.setItem(formId + '_draft', formData);
        console.log('Form auto-saved');
    }
}

// Restore form from auto-save
function restoreForm(formId) {
    var savedData = localStorage.getItem(formId + '_draft');
    if (savedData) {
        var form = $('#' + formId);
        $.each(savedData.split('&'), function(index, elem) {
            var vals = elem.split('=');
            var key = decodeURIComponent(vals[0]);
            var val = decodeURIComponent(vals[1]);
            form.find('[name="' + key + '"]').val(val);
        });
        
        showAlert('info', 'Data draft telah dipulihkan');
    }
}

// Clear auto-save data
function clearAutoSave(formId) {
    localStorage.removeItem(formId + '_draft');
}

// Auto-save forms every 10 seconds
setInterval(function() {
    $('form[data-autosave]').each(function() {
        autoSaveForm($(this).attr('id'));
    });
}, 10000);