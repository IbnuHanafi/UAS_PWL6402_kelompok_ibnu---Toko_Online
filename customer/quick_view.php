<?php

/**
 * Customer Quick View
 * File: customer/quick_view.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Product ID required</div>';
    exit;
}

$product_id = (int)$_GET['id'];

global $db;

$product = $db->fetch("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 'active'
", [$product_id]);

if (!$product) {
    echo '<div class="alert alert-danger">Produk tidak ditemukan</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <!-- Product Image -->
        <div class="text-center">
            <?php if ($product['image']): ?>
                <img src="../assets/images/products/<?php echo $product['image']; ?>"
                    class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>"
                    style="max-height: 300px; object-fit: cover;">
            <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center rounded"
                    style="height: 300px;">
                    <i class="fas fa-image fa-4x text-muted"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Product Info -->
        <div class="mb-2">
            <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
        </div>

        <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h4>

        <div class="mb-3">
            <span class="h4 text-primary fw-bold"><?php echo formatRupiah($product['price']); ?></span>
        </div>

        <div class="mb-3">
            <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>

        <div class="mb-3">
            <div class="row">
                <div class="col-6">
                    <strong>Stok:</strong>
                    <?php if ($product['stock'] > 5): ?>
                        <span class="badge bg-success"><?php echo $product['stock']; ?> tersedia</span>
                    <?php elseif ($product['stock'] > 0): ?>
                        <span class="badge bg-warning text-dark"><?php echo $product['stock']; ?> tersisa</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Stok habis</span>
                    <?php endif; ?>
                </div>
                <div class="col-6">
                    <strong>Status:</strong>
                    <span class="badge bg-success">Aktif</span>
                </div>
            </div>
        </div>

        <?php if ($product['stock'] > 0): ?>
            <!-- Quantity and Add to Cart -->
            <div class="mb-3">
                <label class="form-label fw-bold">Jumlah:</label>
                <div class="input-group" style="max-width: 150px;">
                    <button class="btn btn-outline-secondary qty-minus" type="button">-</button>
                    <input type="number" class="form-control text-center quantity-input" value="1"
                        min="1" max="<?php echo $product['stock']; ?>">
                    <button class="btn btn-outline-secondary qty-plus" type="button">+</button>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary btn-lg add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang
                </button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Maaf, produk ini sedang tidak tersedia
            </div>
            <div class="d-grid">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Quantity controls
        $('.qty-minus').click(function() {
            var input = $(this).siblings('.quantity-input');
            var currentVal = parseInt(input.val());
            if (currentVal > 1) {
                input.val(currentVal - 1);
            }
        });

        $('.qty-plus').click(function() {
            var input = $(this).siblings('.quantity-input');
            var currentVal = parseInt(input.val());
            var maxVal = parseInt(input.attr('max'));
            if (currentVal < maxVal) {
                input.val(currentVal + 1);
            }
        });

        // Add to cart from quick view
        $('.add-to-cart').click(function() {
            var productId = $(this).data('product-id');
            var quantity = $('.quantity-input').val();

            $.ajax({
                url: 'add_to_cart.php',
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity
                },
                dataType: 'json',
                beforeSend: function() {
                    $('.add-to-cart').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menambahkan...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#quickViewModal').modal('hide');
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
                    $('.add-to-cart').prop('disabled', false).html('<i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang');
                }
            });
        });
    });
</script>