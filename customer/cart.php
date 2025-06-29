<?php

/**
 * Customer Cart
 * File: customer/cart.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

$page_title = 'Keranjang Belanja';

global $db;

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = $db->query("
    SELECT c.*, p.name, p.price, p.image, p.stock, c.id as cart_id
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
", [$user_id]);

// Calculate totals
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    $total_items += $item['quantity'];
}

// Shipping cost (simulation)
$shipping_cost = $subtotal > 100000 ? 0 : 15000; // Free shipping above 100k
$total = $subtotal + $shipping_cost;

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient font-weight-bold">Keranjang Belanja</h1>
                    <p class="text-muted">Review dan kelola item di keranjang Anda</p>
                </div>
                <div>
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Lanjut Belanja
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted mb-3">Keranjang Belanja Kosong</h4>
                        <p class="text-muted mb-4">
                            Sepertinya Anda belum menambahkan produk apapun ke keranjang.
                            Yuk mulai berbelanja!
                        </p>
                        <a href="products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Item di Keranjang (<?php echo count($cart_items); ?> produk)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item border rounded p-3 mb-3" id="cart-item-<?php echo $item['cart_id']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <!-- Product Image -->
                                        <?php if ($item['image']): ?>
                                            <img src="../assets/images/products/<?php echo $item['image']; ?>"
                                                class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                style="max-height: 80px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center rounded"
                                                style="height: 80px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-4">
                                        <!-- Product Info -->
                                        <h6 class="font-weight-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-muted mb-1"><?php echo formatRupiah($item['price']); ?> per item</p>
                                        <small class="text-muted">Stok tersedia: <?php echo $item['stock']; ?></small>
                                    </div>

                                    <div class="col-md-3">
                                        <!-- Quantity Controls -->
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-outline-secondary btn-sm qty-minus"
                                                onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm text-center mx-2 cart-quantity"
                                                style="width: 60px;" value="<?php echo $item['quantity']; ?>"
                                                min="1" max="<?php echo $item['stock']; ?>"
                                                data-cart-id="<?php echo $item['cart_id']; ?>"
                                                onchange="updateQuantity(<?php echo $item['cart_id']; ?>, 0, this.value)">
                                            <button class="btn btn-outline-secondary btn-sm qty-plus"
                                                onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <!-- Subtotal -->
                                        <div class="text-end">
                                            <div class="h6 font-weight-bold text-success mb-1"
                                                id="subtotal-<?php echo $item['cart_id']; ?>">
                                                <?php echo formatRupiah($item['price'] * $item['quantity']); ?>
                                            </div>
                                            <button class="btn btn-outline-danger btn-sm"
                                                onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cart Actions -->
                <div class="card shadow mt-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="font-weight-bold mb-3">Kupon Diskon</h6>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Masukkan kode kupon" id="couponCode">
                                    <button class="btn btn-outline-primary" onclick="applyCoupon()">
                                        <i class="fas fa-ticket-alt me-2"></i>Gunakan
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="font-weight-bold mb-3">Estimasi Pengiriman</h6>
                                <select class="form-select" id="shippingOption">
                                    <option value="regular">Reguler (3-5 hari) - Rp 15.000</option>
                                    <option value="express">Express (1-2 hari) - Rp 25.000</option>
                                    <option value="same_day">Same Day - Rp 35.000</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Ringkasan Pesanan</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?php echo $total_items; ?> item)</span>
                            <span id="cart-subtotal"><?php echo formatRupiah($subtotal); ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Ongkos Kirim</span>
                            <span id="shipping-fee">
                                <?php if ($shipping_cost == 0): ?>
                                    <span class="text-success">GRATIS</span>
                                <?php else: ?>
                                    <?php echo formatRupiah($shipping_cost); ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Diskon</span>
                            <span class="text-success" id="discount-amount">- Rp 0</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="h6 font-weight-bold">Total</span>
                            <span class="h6 font-weight-bold text-primary" id="cart-total">
                                <?php echo formatRupiah($total); ?>
                            </span>
                        </div>

                        <?php if ($subtotal < 100000): ?>
                            <div class="alert alert-info small">
                                <i class="fas fa-info-circle me-2"></i>
                                Belanja <?php echo formatRupiah(100000 - $subtotal); ?> lagi untuk mendapat gratis ongkir!
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>Checkout
                            </a>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Lanjut Belanja
                            </a>
                        </div>
                    </div>
                </div>

                        <?php
                        // Get recommended products (simple algorithm: same category)
                        $category_ids = array_unique(array_column($cart_items, 'category_id'));
                        if (!empty($category_ids)) {
                            $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
                            $recommended = $db->query("
                            SELECT * FROM products 
                            WHERE category_id IN ($placeholders) 
                            AND status = 'active' 
                            AND id NOT IN (SELECT product_id FROM cart WHERE user_id = ?)
                            ORDER BY RAND() 
                            LIMIT 3
                        ", array_merge($category_ids, [$user_id]));

                            foreach ($recommended as $product):
                        ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <?php if ($product['image']): ?>
                                            <img src="../assets/images/products/<?php echo $product['image']; ?>"
                                                class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center rounded"
                                                style="width: 50px; height: 50px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 small"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="mb-1 text-primary small font-weight-bold">
                                            <?php echo formatRupiah($product['price']); ?>
                                        </p>
                                        <button class="btn btn-outline-primary btn-sm add-to-cart"
                                            data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                        <?php
                            endforeach;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function updateQuantity(cartId, change, newValue = null) {
        let quantity;

        if (newValue !== null) {
            quantity = parseInt(newValue);
        } else {
            const input = document.querySelector(`input[data-cart-id="${cartId}"]`);
            const currentQuantity = parseInt(input.value);
            quantity = currentQuantity + change;

            if (quantity < 1) quantity = 1;
            if (quantity > parseInt(input.max)) quantity = parseInt(input.max);

            input.value = quantity;
        }

        $.ajax({
            url: 'update_cart.php',
            method: 'POST',
            data: {
                cart_id: cartId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update subtotal for this item
                    $(`#subtotal-${cartId}`).text(formatRupiah(response.item_total));

                    // Update cart totals
                    updateCartTotals();
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
                    url: 'remove_from_cart.php',
                    method: 'POST',
                    data: {
                        cart_id: cartId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $(`#cart-item-${cartId}`).fadeOut(500, function() {
                                $(this).remove();
                                updateCartTotals();
                                updateCartBadge();

                                // Check if cart is empty
                                if ($('.cart-item').length === 0) {
                                    location.reload();
                                }
                            });
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

    function updateCartTotals() {
        $.ajax({
            url: 'get_cart_total.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#cart-subtotal').text(formatRupiah(response.subtotal));
                $('#cart-total').text(formatRupiah(response.total));

                if (response.shipping_cost == 0) {
                    $('#shipping-fee').html('<span class="text-success">GRATIS</span>');
                } else {
                    $('#shipping-fee').text(formatRupiah(response.shipping_cost));
                }
            }
        });
    }

    function applyCoupon() {
        const couponCode = $('#couponCode').val().trim();

        if (!couponCode) {
            showAlert('warning', 'Masukkan kode kupon terlebih dahulu');
            return;
        }

        // Simulate coupon validation
        Swal.fire({
            title: 'Memvalidasi Kupon...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading()
            }
        });

        setTimeout(() => {
            // Simulate random coupon validation
            const isValid = Math.random() > 0.5;

            if (isValid) {
                const discount = 10000; // 10k discount
                $('#discount-amount').text(`- ${formatRupiah(discount)}`);
                updateCartTotals();
                Swal.fire('Berhasil!', 'Kupon berhasil diterapkan', 'success');
            } else {
                Swal.fire('Gagal!', 'Kode kupon tidak valid atau sudah expired', 'error');
            }
        }, 2000);
    }

    function formatRupiah(amount) {
        return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
</script>

<?php include '../includes/footer.php'; ?>