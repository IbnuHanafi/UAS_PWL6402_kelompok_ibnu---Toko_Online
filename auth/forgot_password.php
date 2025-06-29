<?php
require_once '../includes/functions.php';
$page_title = 'Bantuan Lupa Password';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Toko Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Poppins', sans-serif;
        }

        .info-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <div class="info-container">
        <div class="card shadow-lg text-center" style="width: 100%; max-width: 500px;">
            <div class="card-body p-5">
                <i class="fas fa-headset fa-3x text-primary mb-4"></i>
                <h3 class="fw-bold">Bantuan Lupa Password</h3>
                <p class="text-muted mt-3">
                    Untuk mereset password Anda, silakan hubungi Administrator kami melalui salah satu kontak di bawah ini:
                </p>

                <div class="list-group text-start my-4">
                    <a href="https://wa.me/6288802972620" target="_blank" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <i class="fab fa-whatsapp fa-2x text-success me-3"></i>
                            <div>
                                <h6 class="mb-0">WhatsApp Admin</h6>
                                <small class="text-muted">+62 888-0297-2620</small>
                            </div>
                        </div>
                    </a>
                    <a href="mailto:112202306994@mhs.dinus.ac.id" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <i class="fas fa-envelope fa-2x text-danger me-3"></i>
                            <div>
                                <h6 class="mb-0">Email Admin</h6>
                                <small class="text-muted">112202306994@mhs.dinus.ac.id</small>
                            </div>
                        </div>
                    </a>
                </div>

                <p class="text-muted small">
                    Tim kami akan membantu Anda untuk mengatur ulang akun Anda secepatnya ataupun memberikan informasi lainnya terkait pertanyaan Anda.
                </p>
                <a href="login.php" class="btn btn-primary w-100 mt-4 py-2">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Halaman Login
                </a>
            </div>
        </div>
    </div>
</body>

</html>