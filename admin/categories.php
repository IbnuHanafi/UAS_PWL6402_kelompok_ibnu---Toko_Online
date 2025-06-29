<?php

/**
 * Admin Categories Management
 * File: admin/categories.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

$page_title = 'Kelola Kategori';

global $db;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = cleanInput($_POST['name']);
        $description = cleanInput($_POST['description']);

        if (empty($name)) {
            setFlashMessage('error', 'Nama kategori harus diisi');
        } else {
            // Check if category already exists
            $existing = $db->fetch("SELECT id FROM categories WHERE name = ?", [$name]);
            if ($existing) {
                setFlashMessage('error', 'Kategori dengan nama tersebut sudah ada');
            } else {
                $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
                if ($db->execute($sql, [$name, $description])) {
                    setFlashMessage('success', 'Kategori berhasil ditambahkan');
                } else {
                    setFlashMessage('error', 'Gagal menambahkan kategori');
                }
            }
        }
        redirect('categories.php');
    } elseif (isset($_POST['edit_category'])) {
        // Edit category
        $id = (int)$_POST['id'];
        $name = cleanInput($_POST['name']);
        $description = cleanInput($_POST['description']);

        if (empty($name)) {
            setFlashMessage('error', 'Nama kategori harus diisi');
        } else {
            // Check if category name already exists (except current)
            $existing = $db->fetch("SELECT id FROM categories WHERE name = ? AND id != ?", [$name, $id]);
            if ($existing) {
                setFlashMessage('error', 'Kategori dengan nama tersebut sudah ada');
            } else {
                $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
                if ($db->execute($sql, [$name, $description, $id])) {
                    setFlashMessage('success', 'Kategori berhasil diupdate');
                } else {
                    setFlashMessage('error', 'Gagal mengupdate kategori');
                }
            }
        }
        redirect('categories.php');
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Check if category has products
    $product_count = $db->fetch("SELECT COUNT(*) as count FROM products WHERE category_id = ?", [$delete_id])['count'];

    if ($product_count > 0) {
        setFlashMessage('error', "Tidak dapat menghapus kategori. Masih ada $product_count produk yang menggunakan kategori ini.");
    } else {
        if ($db->execute("DELETE FROM categories WHERE id = ?", [$delete_id])) {
            setFlashMessage('success', 'Kategori berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus kategori');
        }
    }
    redirect('categories.php');
}

// Get categories with product count
$categories = $db->query("
    SELECT c.*, 
           COUNT(p.id) as product_count,
           c.created_at
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name
");

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient fw-bold">Kelola Kategori</h1>
                    <p class="text-muted">Manajemen kategori produk</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-2"></i>Tambah Kategori
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="h5 mb-0"><?php echo count($categories); ?></div>
                            <div class="small">Total Kategori</div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-tags fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="h5 mb-0"><?php echo array_sum(array_column($categories, 'product_count')); ?></div>
                            <div class="small">Total Produk</div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-info text-white shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="h5 mb-0">
                                <?php echo count(array_filter($categories, function ($cat) {
                                    return $cat['product_count'] > 0;
                                })); ?>
                            </div>
                            <div class="small">Kategori Aktif</div>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-pie fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="row">
        <?php if (empty($categories)): ?>
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada kategori</h5>
                        <p class="text-muted">Klik tombol "Tambah Kategori" untuk membuat kategori pertama</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Tambah Kategori Pertama
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-gradient-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold"><?php echo htmlspecialchars($category['name']); ?></h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger delete-btn"
                                                href="?delete=<?php echo $category['id']; ?>"
                                                data-item-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                <i class="fas fa-trash me-2"></i>Hapus
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($category['description']): ?>
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </p>
                            <?php else: ?>
                                <p class="card-text text-muted fst-italic">Tidak ada deskripsi</p>
                            <?php endif; ?>

                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h4 text-primary mb-0"><?php echo $category['product_count']; ?></div>
                                    <small class="text-muted">Produk</small>
                                </div>
                                <div class="col-6">
                                    <div class="h6 text-muted mb-0"><?php echo formatDate($category['created_at']); ?></div>
                                    <small class="text-muted">Dibuat</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="../admin/products.php?category=<?php echo $category['id']; ?>"
                                    class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-eye me-1"></i>Lihat Produk
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" name="name" required maxlength="100">
                        <div class="invalid-feedback">Nama kategori harus diisi</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="3"
                            placeholder="Deskripsi kategori (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required maxlength="100">
                        <div class="invalid-feedback">Nama kategori harus diisi</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editCategory(id) {
        // Fetch category data via AJAX
        $.ajax({
            url: 'get_category.php',
            method: 'GET',
            data: {
                id: id
            },
            dataType: 'json',
            success: function(category) {
                $('#edit_id').val(category.id);
                $('#edit_name').val(category.name);
                $('#edit_description').val(category.description);

                $('#editCategoryModal').modal('show');
            },
            error: function() {
                showAlert('error', 'Gagal mengambil data kategori');
            }
        });
    }

    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>

<?php include '../includes/footer.php'; ?>