</main>
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-store me-2"></i>Toko Online
                </h5>
                <p class="text-light"> Platform belanja online terpercaya dengan berbagai pilihan produk berkualitas
                    dan pelayanan terbaik untuk kepuasan pelanggan.
                </p>
                <div class="social-links">
                    <a href="https://www.facebook.com/" class="text-light me-3" target="_blank" aria-label="Facebook">
                        <i class="fab fa-facebook-f fa-lg"></i>
                    </a>
                    <a href="https://www.instagram.com/" class="text-light me-3" target="_blank" aria-label="Instagram">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <a href="https://wa.me/yourphonenumber" class="text-light me-3" target="_blank" aria-label="WhatsApp">
                        <i class="fab fa-whatsapp fa-lg"></i> </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Customer Service</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fab fa-whatsapp fa-lg"></i>
                        <span class="text-light">+62 088802972620 (Ibnu - Ketua project)</span>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        <span class="text-light">112202306994@mhs.dinus.ac.id</span>
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-clock me-2"></i>
                        <span class="text-light">24/7 Online</span>
                    </li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Dibuat Oleh</h6>
                <ul class="list-unstyled">
                    <li><span class="text-light">1. Ibnu Hanafi Assalam - A12.2023.06994</span></li>
                    <li><span class="text-light">2. Muhammad Fuad Aqila - A12.2023.06982</span></li>
                    <li><span class="text-light">3. Dzaki Jamil Makruf - A12.2023.07101</span></li>
                    <li><span class="text-light">4. Rafli Zibrilian Farrel - A12.2023.06973</span></li>
                    <li><span class="text-light">5. Mutiara Acintyacitra N - A12.2023.07059</span></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Newsletter</h6>
                <p class="text-light mb-3">Dapatkan info promo dan produk terbaru</p>
                <div class="input-group">
                    <input type="email" class="form-control" placeholder="Email Anda">
                    <button class="btn btn-primary" type="button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-light mb-0"> &copy; <?php echo date('Y'); ?> Toko Online.
                    UAS Pemrograman Web Lanjut - Sistem Informasi.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <img src="../assets/images/payment-methods.png" alt="Payment Methods"
                    class="img-fluid" style="max-height: 30px;" onerror="this.style.display='none'">
            </div>
        </div>
    </div>
</footer>

<button type="button" class="btn btn-primary btn-floating btn-lg rounded-circle back-to-top"
    id="backToTopBtn" style="display: none;">
    <i class="fas fa-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<?php if (isLoggedIn() && isAdmin()): ?>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<?php endif; ?>

<?php if (isLoggedIn() && isAdmin() && basename($_SERVER['PHP_SELF']) == 'dashboard.php'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="../assets/js/script.js"></script>

<?php if (isset($extra_js)): ?>
    <?php echo $extra_js; ?>
<?php endif; ?>

</body>

</html>