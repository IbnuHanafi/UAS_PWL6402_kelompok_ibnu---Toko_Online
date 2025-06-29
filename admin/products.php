<?php

/**
 * Admin Products Management
 * File: admin/products.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

$page_title = 'Kelola Produk';

global $db;

// Handle actions
$action = $_GET['action'] ?? 'list';
$product_id = $_GET['id'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $name = cleanInput($_POST['name']);
        $description = cleanInput($_POST['description']);
        $price = (float)str_replace('.', '', $_POST['price']);
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        $status = $_POST['status'];

        // Handle file upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_result = uploadImage($_FILES['image']);
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
            } else {
                setFlashMessage('error', $upload_result['message']);
            }
        }

        if (empty($name) || empty($price) || empty($category_id)) {
            setFlashMessage('error', 'Nama, harga, dan kategori harus diisi');
        } else {
            $sql = "INSERT INTO products (name, description, price, stock, category_id, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if ($db->execute($sql, [$name, $description, $price, $stock, $category_id, $image, $status])) {
                setFlashMessage('success', 'Produk berhasil ditambahkan');
                redirect('products.php');
            } else {
                setFlashMessage('error', 'Gagal menambahkan produk');
            }
        }
    } elseif (isset($_POST['edit_product'])) {
        // Edit product
        $id = (int)$_POST['id'];
        $name = cleanInput($_POST['name']);
        $description = cleanInput($_POST['description']);
        $price = (float)str_replace('.', '', $_POST['price']);
        $stock = (int)$_POST['stock'];
        $category_id = (int)$_POST['category_id'];
        $status = $_POST['status'];

        // Handle file upload
        $image_update = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_result = uploadImage($_FILES['image']);
            if ($upload_result['success']) {
                $image_update = ", image = '" . $upload_result['filename'] . "'";
            } else {
                setFlashMessage('error', $upload_result['message']);
            }
        }

        if (empty($name) || empty($price) || empty($category_id)) {
            setFlashMessage('error', 'Nama, harga, dan kategori harus diisi');
        } else {
            $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, status = ? $image_update WHERE id = ?";
            if ($db->execute($sql, [$name, $description, $price, $stock, $category_id, $status, $id])) {
                setFlashMessage('success', 'Produk berhasil diupdate');
                redirect('products.php');
            } else {
                setFlashMessage('error', 'Gagal mengupdate produk');
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    if ($db->execute("DELETE FROM products WHERE id = ?", [$delete_id])) {
        setFlashMessage('success', 'Produk berhasil dihapus');
    } else {
        setFlashMessage('error', 'Gagal menghapus produk');
    }
    redirect('products.php');
}

// Get categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name");

// Get single product for edit
$edit_product = null;
if ($action == 'edit' && $product_id) {
    $edit_product = $db->fetch("SELECT * FROM products WHERE id = ?", [$product_id]);
    if (!$edit_product) {
        redirect('products.php');
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
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

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products p $where_sql";
$total_products = $db->fetch($count_sql, $params)['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$products_sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $where_sql 
    ORDER BY p.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$products = $db->query($products_sql, $params);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient fw-bold">Kelola Produk</h1>
                    <p class="text-muted">Manajemen produk toko online</p>
                </div>
                <div>
                    <?php if ($action == 'list'): ?>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportProducts('excel')">
                                        <i class="fas fa-file-excel me-2 text-success"></i>Export Excel
                                    </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportProducts('pdf')">
                                        <i class="fas fa-file-pdf me-2 text-danger"></i>Export PDF
                                    </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportProducts('csv')">
                                        <i class="fas fa-file-csv me-2 text-info"></i>Export CSV
                                    </a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Tambah Produk
                        </button>
                    <?php elseif ($action == 'add' || $action == 'edit'): ?>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($action == 'list'): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Cari Produk</label>
                                <input type="text" class="form-control" name="search" placeholder="Nama atau deskripsi..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kategori</label>
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
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                    <a href="products.php" class="btn btn-outline-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">
                            Daftar Produk (<?php echo number_format($total_products); ?> produk)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">Gambar</th>
                                        <th>Nama Produk</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th width="120">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fas fa-box fa-2x mb-2"></i>
                                                <br>Tidak ada produk ditemukan
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($product['image']): ?>
                                                        <img src="../assets/images/products/<?php echo $product['image']; ?>"
                                                            class="img-thumbnail" width="50" height="50" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center rounded"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-bold text-success">
                                                    <?php echo formatRupiah($product['price']); ?>
                                                </td>
                                                <td>
                                                    <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            <?php echo $product['stock']; ?>
                                                        </span>
                                                    <?php elseif ($product['stock'] == 0): ?>
                                                        <span class="badge bg-danger">Habis</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><?php echo $product['stock']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo getStatusBadge($product['status']); ?></td>
                                                <td>
                                                    <small><?php echo formatDate($product['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            onclick="editProduct(<?php echo $product['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $product['id']; ?>"
                                                            class="btn btn-sm btn-outline-danger delete-btn"
                                                            data-item-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <?php echo getPagination($page, $total_pages, 'products.php', ['search' => $search, 'category' => $category_filter, 'status' => $status_filter]); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Produk Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Nama Produk *</label>
                                <input type="text" class="form-control" name="name" required>
                                <div class="invalid-feedback">Nama produk harus diisi</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="description" rows="4"
                                    placeholder="Deskripsi produk..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Harga *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control price-input" name="price" required>
                                        </div>
                                        <div class="invalid-feedback">Harga harus diisi</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Stok</label>
                                        <input type="number" class="form-control" name="stock" min="0" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori *</label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">Pilih kategori...</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Kategori harus dipilih</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="active">Aktif</option>
                                            <option value="inactive">Nonaktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Gambar Produk</label>
                                <input type="file" class="form-control image-upload" name="image" accept="image/*">
                                <div class="mt-2">
                                    <img src="#" class="img-thumbnail image-preview" style="max-width: 100%; display: none;">
                                </div>
                                <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Nama Produk *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                                <div class="invalid-feedback">Nama produk harus diisi</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="4"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Harga *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control price-input" name="price" id="edit_price" required>
                                        </div>
                                        <div class="invalid-feedback">Harga harus diisi</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Stok</label>
                                        <input type="number" class="form-control" name="stock" id="edit_stock" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori *</label>
                                        <select class="form-select" name="category_id" id="edit_category_id" required>
                                            <option value="">Pilih kategori...</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Kategori harus dipilih</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" id="edit_status">
                                            <option value="active">Aktif</option>
                                            <option value="inactive">Nonaktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Gambar Produk</label>
                                <input type="file" class="form-control image-upload" name="image" accept="image/*">
                                <div class="mt-2">
                                    <img src="#" class="img-thumbnail image-preview" id="edit_current_image" style="max-width: 100%;">
                                </div>
                                <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_product" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ===== FUNGSI EDIT PRODUK =====
    function editProduct(id) {
        $.ajax({
            url: 'get_product.php',
            method: 'GET',
            data: {
                id: id
            },
            dataType: 'json',
            success: function(product) {
                $('#edit_id').val(product.id);
                $('#edit_name').val(product.name);
                $('#edit_description').val(product.description);
                $('#edit_price').val(formatNumber(product.price));
                $('#edit_stock').val(product.stock);
                $('#edit_category_id').val(product.category_id);
                $('#edit_status').val(product.status);
                if (product.image) {
                    $('#edit_current_image').attr('src', '../assets/images/products/' + product.image).show();
                } else {
                    $('#edit_current_image').hide();
                }
                $('#editProductModal').modal('show');
            },
            error: function() {
                showAlert('error', 'Gagal mengambil data produk');
            }
        });
    }

    // ===== FUNGSI EXPORT PRODUK =====
    function exportProducts(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('format', format);
        params.delete('page'); // Remove page parameter for export
        const url = `export_products.php?${params.toString()}`;
        window.location.href = url;
    }

    // ===== HELPER FUNCTIONS =====
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    $('.price-input').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(formatNumber(value));
    });

    // Image preview functionality
    $('.image-upload').on('change', function() {
        const file = this.files[0];
        const preview = $(this).siblings('.mt-2').find('.image-preview');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        } else {
            preview.hide();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>