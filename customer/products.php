<?php

/**
 * Customer Products
 * File: customer/products.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

$page_title = 'Produk';

global $db;

// Get filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build where conditions
$where_conditions = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($min_price)) {
    $where_conditions[] = "p.price >= ?";
    $params[] = (float)str_replace('.', '', $min_price);
}

if (!empty($max_price)) {
    $where_conditions[] = "p.price <= ?";
    $params[] = (float)str_replace('.', '', $max_price);
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// Sort options
$order_sql = "ORDER BY ";
switch ($sort) {
    case 'price_low':
        $order_sql .= "p.price ASC";
        break;
    case 'price_high':
        $order_sql .= "p.price DESC";
        break;
    case 'name':
        $order_sql .= "p.name ASC";
        break;
    case 'oldest':
        $order_sql .= "p.created_at ASC";
        break;
    case 'newest':
    default:
        $order_sql .= "p.created_at DESC";
        break;
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_sql";
$total_products = $db->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$products_sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $where_sql 
    $order_sql 
    LIMIT $limit OFFSET $offset
";
$products = $db->query($products_sql, $params);

// Get categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name");

// Get price range
$price_range = $db->fetch("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'");

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient font-weight-bold">Produk</h1>
                    <p class="text-muted">Temukan produk favorit Anda</p>
                </div>
                <div>
                    <span class="badge bg-primary fs-6">
                        <?php echo number_format($total_products); ?> produk ditemukan
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow sticky-top" style="top: 100px;">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-white">Filter Produk</h6>
                </div>
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <!-- Search -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Cari Produk</label>
                            <input type="text" class="form-control" name="search" placeholder="Nama produk..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Kategori</label>
                            <select class="form-select" name="category">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Rentang Harga</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="text" class="form-control price-input" name="min_price"
                                        placeholder="Min" value="<?php echo $min_price; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control price-input" name="max_price"
                                        placeholder="Max" value="<?php echo $max_price; ?>">
                                </div>
                            </div>
                            <small class="text-muted">
                                Range: <?php echo formatRupiah($price_range['min_price']); ?> -
                                <?php echo formatRupiah($price_range['max_price']); ?>
                            </small>
                        </div>

                        <!-- Sort -->
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Urutkan</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Terlama</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Harga Terendah</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Nama A-Z</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Cari Produk
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-2"></i>Reset Filter
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Sort Bar -->
            <div class="card shadow mb-4">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">
                                Menampilkan <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $total_products); ?>
                                dari <?php echo number_format($total_products); ?> produk
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <label class="form-label me-2 mb-0">Urutkan:</label>
                            <select class="form-select form-select-sm" style="width: auto;" onchange="changeSort(this.value)">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Harga ↑</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Harga ↓</option>
                                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Nama A-Z</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <?php if (empty($products)): ?>
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Produk tidak ditemukan</h5>
                        <p class="text-muted">Coba ubah filter pencarian atau kata kunci Anda</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-undo me-2"></i>Reset Pencarian
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4 product-item"
                            data-category="<?php echo $product['category_id']; ?>"
                            data-price="<?php echo $product['price']; ?>">
                            <div class="card h-100 shadow-sm product-card border-0">
                                <!-- Product Image Container -->
                                <div class="product-image-container position-relative overflow-hidden">
                                    <?php if ($product['image']): ?>
                                        <img src="../assets/images/products/<?php echo $product['image']; ?>"
                                            class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-img-placeholder d-flex align-items-center justify-content-center">
                                            <div class="text-center">
                                                <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                                <div class="small text-muted">No Image</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Overlay Actions -->
                                    <div class="product-overlay">
                                        <div class="product-actions">
                                            <button class="btn btn-white btn-sm rounded-circle me-2"
                                                onclick="quickView(<?php echo $product['id']; ?>)"
                                                data-bs-toggle="tooltip" title="Quick View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($product['stock'] > 0): ?>
                                                <button class="btn btn-primary btn-sm rounded-circle add-to-cart"
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    data-bs-toggle="tooltip" title="Add to Cart">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Badges -->
                                    <div class="product-badges">
                                        <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                            <span class="badge bg-warning text-dark">Limited Stock</span>
                                        <?php elseif ($product['stock'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Product Info -->
                                <div class="card-body p-3">
                                    <!-- Category Badge -->
                                    <div class="mb-2">
                                        <span class="badge bg-light text-primary border border-primary">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </div>

                                    <!-- Product Title -->
                                    <h6 class="card-title product-title mb-2" title="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h6>

                                    <!-- Product Description -->
                                    <p class="card-text text-muted small mb-3 product-desc">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 70)) . (strlen($product['description']) > 70 ? '...' : ''); ?>
                                    </p>

                                    <!-- Price and Stock Info -->
                                    <div class="product-info-bottom">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="product-price">
                                                <span class="h6 text-primary fw-bold mb-0">
                                                    <?php echo formatRupiah($product['price']); ?>
                                                </span>
                                            </div>
                                            <div class="product-stock">
                                                <small class="text-muted">
                                                    <i class="fas fa-box me-1"></i><?php echo $product['stock']; ?> left
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-grid">
                                            <?php if ($product['stock'] > 0): ?>
                                                <button class="btn btn-primary btn-add-cart add-to-cart"
                                                    data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-times me-2"></i>Out of Stock
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <?php echo getPagination($page, $total_pages, 'products.php'); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick View Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="quickViewContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function changeSort(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        window.location.href = url.toString();
    }

    function quickView(productId) {
        $('#quickViewModal').modal('show');

        $.ajax({
            url: 'quick_view.php',
            method: 'GET',
            data: {
                id: productId
            },
            success: function(response) {
                $('#quickViewContent').html(response);
            },
            error: function() {
                $('#quickViewContent').html('<div class="alert alert-danger">Gagal memuat data produk</div>');
            }
        });
    }

    // Auto submit form when filter changes
    $('#filterForm select').change(function() {
        $('#filterForm').submit();
    });

    // Price input formatting
    $('.price-input').on('input', function() {
        let value = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(formatNumber(value));
    });

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Initialize tooltips
    $(document).ready(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>

<style>
    /* Product Card Styling */
    .product-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        border-color: #007bff;
    }

    /* Product Image Container */
    .product-image-container {
        position: relative;
        height: 220px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-img {
        transform: scale(1.05);
    }

    .product-img-placeholder {
        height: 100%;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    /* Product Overlay */
    .product-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .product-card:hover .product-overlay {
        opacity: 1;
    }

    .product-actions {
        display: flex;
        gap: 10px;
    }

    .product-actions .btn {
        transition: all 0.3s ease;
    }

    .product-actions .btn:hover {
        transform: scale(1.1);
    }

    /* Product Badges */
    .product-badges {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
    }

    .product-badges .badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.6rem;
        border-radius: 6px;
    }

    /* Product Info */
    .product-title {
        font-weight: 600;
        font-size: 1rem;
        line-height: 1.3;
        color: #2c3e50;
        min-height: 2.6rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-desc {
        font-size: 0.85rem;
        line-height: 1.4;
        color: #6c757d;
        min-height: 3rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: #28a745;
    }

    .product-stock {
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* Action Buttons */
    .btn-add-cart {
        font-weight: 600;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        transition: all 0.3s ease;
    }

    .btn-add-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
    }

    /* Category Badge */
    .badge {
        font-size: 0.7rem;
        padding: 0.35rem 0.6rem;
        border-radius: 6px;
        font-weight: 500;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .product-image-container {
            height: 180px;
        }

        .product-title {
            font-size: 0.9rem;
            min-height: 2.4rem;
        }

        .product-desc {
            font-size: 0.8rem;
            min-height: 2.5rem;
        }

        .product-price {
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .product-card {
            margin-bottom: 1rem;
        }

        .product-image-container {
            height: 160px;
        }
    }

    /* Grid Layout */
    .row {
        margin-left: -8px;
        margin-right: -8px;
    }

    .row>[class*="col-"] {
        padding-left: 8px;
        padding-right: 8px;
    }

    /* Card Body Padding */
    .product-card .card-body {
        padding: 1rem;
    }

    .product-info-bottom {
        margin-top: auto;
    }

    /* Loading Animation */
    .product-card.loading {
        opacity: 0.7;
    }

    .product-card.loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    /* Hover Effects */
    .btn-white {
        background: white;
        border: 1px solid #dee2e6;
        color: #495057;
    }

    .btn-white:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
        color: #495057;
    }
</style>

<?php include '../includes/footer.php'; ?>