# 🛒 Toko Online - E-commerce Web Application

**UAS Pemrograman Web Lanjut - Sistem Informasi**

📝 Deskripsi Webiste
Project UAS website Toko Online ini adalah aplikasi web e-commerce lengkap yang dibangun dengan PHP, MySQL, Bootstrap, dan JavaScript. Sistem website ini menyediakan platform belanja online dengan fitur admin dan customer yang komprehensif. Aplikasi ini dilengkapi dengan sistem autentikasi role-based, manajemen produk dan kategori, keranjang belanja real-time, sistem checkout yang lengkap, dan panel admin untuk mengelola seluruh aspek toko online termasuk laporan penjualan dan analitik.

## 📋 Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Struktur Database](#struktur-database)
- [Struktur Folder](#struktur-folder)
- [Panduan Penggunaan](#panduan-penggunaan)
- [Akun Demo](#akun-demo)
- [Screenshots](#screenshots)
- [Kontribusi](#kontribusi)

## ✨ Fitur Utama

### 🔐 Sistem Autentikasi
- **Login/Register** dengan validasi keamanan
- **Role-based access control** (Admin & Customer)
- **Session management** yang aman
- **Password hashing** dengan algoritma yang kuat

### 👤 Panel Admin
- **Dashboard** dengan statistik dan grafik real-time
- **Manajemen Produk** (CRUD lengkap dengan upload gambar)
- **Manajemen Kategori** produk
- **Manajemen Pesanan** dengan update status
- **Laporan Penjualan** dan analitik
- **Interface responsif** dan user-friendly

### 🛍️ Panel Customer
- **Dashboard** dengan ringkasan aktivitas
- **Katalog Produk** dengan filter dan pencarian
- **Keranjang Belanja** dengan update quantity real-time
- **Sistem Checkout** yang lengkap
- **Riwayat Pesanan** dan tracking status
- **Manajemen Profil** dan keamanan akun

### 🎨 UI/UX Features
- **Responsive Design** untuk semua device
- **Modern Bootstrap 5** interface
- **Interactive JavaScript** features
- **Real-time notifications** dan alerts
- **Loading states** dan animasi smooth

## 🛠️ Teknologi yang Digunakan

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Database management
- **PDO** - Database abstraction layer
- **Session-based** authentication

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Modern styling dengan custom properties
- **Bootstrap 5.3** - Responsive CSS framework
- **JavaScript (ES6+)** - Interactive features
- **jQuery 3.7** - DOM manipulation
- **Font Awesome 6.4** - Icon library
- **Google Fonts** - Typography (Poppins)

### Libraries & Tools
- **Chart.js** - Data visualization untuk admin
- **DataTables** - Advanced table features
- **SweetAlert2** - Beautiful alerts dan confirmations
- **Animate.css** - CSS animations
- **Vendor composer** - Export Package manager

## 💻 Persyaratan Sistem

- **Web Server**: Apache 2.4+ atau Nginx
- **PHP**: 7.4 atau lebih tinggi
- **MySQL**: 5.7 atau lebih tinggi (atau MariaDB 10.2+)
- **Extensions**: PDO, PDO_MySQL, GD (untuk image handling)
- **Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

## ⚙️ Instalasi

### 1. Persiapan Environment

**Menggunakan XAMPP (Recommended):**
1. Download dan install [XAMPP](https://www.apachefriends.org/)
2. Start **Apache** dan **MySQL** dari XAMPP Control Panel
3. Pastikan port 80 (Apache) dan 3306 (MySQL) tidak digunakan aplikasi lain

### 2. Setup Project

```bash
# 1. Clone atau download project
# Ekstrak file project ke folder htdocs XAMPP
C:\xampp\htdocs\toko_online\

# 2. Struktur folder harus seperti ini:
C:\xampp\htdocs\toko_online\
├── admin/
├── auth/
├── customer/
├── config/
├── includes/
├── assets/
├── index.php
└── README.md
```

### 3. Setup Database

1. **Akses phpMyAdmin**: http://localhost/phpmyadmin
2. **Buat database baru**: `toko_online`
3. **Import database**:
   - Pilih database `toko_online`
   - Klik tab "Import"
   - Upload file database.sql atau copy-paste script SQL dari file `config/database.sql`
   - Klik "Go" untuk menjalankan

### 4. Konfigurasi Database

Edit file `config/database.php` jika diperlukan:

```php
// Sesuaikan dengan konfigurasi MySQL Anda
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Default XAMPP kosong
define('DB_NAME', 'toko_online');
```

### 5. Setup Folder Permissions

Buat folder untuk upload gambar:
```bash
# Pastikan folder ini ada dan memiliki permission write
toko_online/assets/images/products/
```

### 6. Testing Installation

1. **Akses aplikasi**: http://localhost/toko_online
2. **Cek koneksi database**: Halaman seharusnya load tanpa error
3. **Test login** dengan akun demo (lihat bagian Akun Demo)

## 🗄️ Struktur Database

### Tabel Utama

#### `users` - Data User
- **id** (PK, AUTO_INCREMENT)
- **username** (UNIQUE, NOT NULL)
- **email** (UNIQUE, NOT NULL)
- **password** (Hashed, NOT NULL)
- **role** (ENUM: 'admin', 'customer')
- **full_name** (NOT NULL)
- **phone, address** (TEXT)
- **created_at** (TIMESTAMP)

#### `categories` - Kategori Produk
- **id** (PK, AUTO_INCREMENT)
- **name** (NOT NULL)
- **description** (TEXT)
- **created_at** (TIMESTAMP)

#### `products` - Data Produk
- **id** (PK, AUTO_INCREMENT)
- **name** (NOT NULL)
- **description** (TEXT)
- **price** (DECIMAL(10,2))
- **stock** (INT)
- **category_id** (FK → categories.id)
- **image** (VARCHAR)
- **status** (ENUM: 'active', 'inactive')
- **created_at, updated_at** (TIMESTAMP)

#### `orders` - Data Pesanan
- **id** (PK, AUTO_INCREMENT)
- **user_id** (FK → users.id)
- **order_number** (UNIQUE)
- **total_amount** (DECIMAL(10,2))
- **status** (ENUM: 'pending', 'processing', 'completed', 'cancelled')
- **shipping_address** (TEXT)
- **notes** (TEXT)
- **created_at, updated_at** (TIMESTAMP)

#### `order_items` - Detail Item Pesanan
- **id** (PK, AUTO_INCREMENT)
- **order_id** (FK → orders.id)
- **product_id** (FK → products.id)
- **quantity** (INT)
- **price** (DECIMAL(10,2))
- **subtotal** (DECIMAL(10,2))

#### `cart` - Keranjang Belanja
- **id** (PK, AUTO_INCREMENT)
- **user_id** (FK → users.id)
- **product_id** (FK → products.id)
- **quantity** (INT)
- **created_at** (TIMESTAMP)

### Relasi Database
- **One-to-Many**: users → orders
- **One-to-Many**: users → cart
- **One-to-Many**: categories → products
- **One-to-Many**: orders → order_items
- **Many-to-One**: cart → products

## 📁 Struktur Folder

```
toko_online_kelompok_ibnu/
├── 📁 admin/                    # Panel Admin
|   ├── categories              # Menampilkan menambahkan kategories
|   ├── customers.php           # melihat customers
│   ├── dashboard.php           # Dashboard admin
│   ├── export_customers.php    # Export daftar konsumen
│   ├── export_orders.php       # Export daftar pesanan
│   ├── export_products.php     # Export daftar produk
│   ├── products.php            # Manajemen produk
│   ├── categories.php          # Manajemen kategori
│   ├── orders.php              # Manajemen pesanan
│   ├── get_product.php         # AJAX endpoint
│   ├── get_category.php        # AJAX endpoint
│   ├── update_order_status.php # Update status pesanan
│   ├── reportss.php            # Menampilkan report
│   ├── update_order_status.php # Update status pesanan
│   └── logout.php              # Logout admin
│
├── 📁 auth/                     # Autentikasi
|   ├── forgot_passwordd.php    # lupa password
│   ├── login.php               # Halaman login
│   └── register.php            # Halaman registrasi
│
├── 📁 customer/                 # Panel Customer
|    ├── add_to_cart.php        # tambah ke keranjang
│   ├── dashboard.php           # Dashboard customer
│   ├── products.php            # Katalog produk
│   ├── cart.php                # Keranjang belanja
│   ├── checkout.php            # Proses checkout
│   ├── orders.php              # Riwayat pesanan
│   ├── profile.php             # Manajemen profil
│   ├── quick_view.php          # Quick view produk
│   ├── add_to_cart.php         # Tambah ke keranjang
│   ├── update_cart.php         # Update keranjang
│   ├── remove_from_cart.php    # Hapus dari keranjang
│   ├── get_cart_total.php      # Total keranjang
│   └── logout.php              # Logout customer
│   
├── 📁 config/                   # Konfigurasi
│   └── database.php            # Koneksi database
│
├── 📁 includes/                 # File Include
│   ├── functions.php           # Helper functions
│   ├── header.php              # Template header
│   └── footer.php              # Template footer
│
├── 📁 assets/                   # Asset Files
│   ├── 📁 css/
│   │   └── style.css           # Custom CSS
│   ├── 📁 js/
│   │   └── script.js           # Custom JavaScript
│   └── 📁 images/
│       └── 📁 products/        # Upload gambar produk
|       └── 📁 Screenshoot/     # Screenshoot tampilan website untuk README.md
│
├── index.php                   # Landing page
└── README.md                   # Dokumentasi
└── toko_online.sql             # File Database yang harus import untuk menjalankan program
```

## 📖 Panduan Penggunaan

### Untuk Admin
1. **Login**: Akses auth/login.php dengan akun admin untuk masuk ke panel admin
2. **Dashboard (admin/dashboard.php)**: Lihat statistik penjualan, grafik real-time, dan ringkasan aktivitas toko
3. **Manajemen Produk (admin/products.php)**:
   - Tambah produk baru dengan upload gambar
   - Edit informasi produk menggunakan get_product.php
   - Kelola stok dan status produk
   - Export data produk via export_products.php
4. **Manajemen Kategori (admin/categories.php)**:
   - Buat dan kelola kategori produk
   - Gunakan get_category.php untuk edit kategori
5. **Manajemen Pesanan (admin/orders.php)**:
   - Lihat semua pesanan masuk
   - Update status pesanan via update_order_status.php
   - Export laporan pesanan dengan export_orders.php
6. **Manajemen Customer (admin/customers.php)**:
   - Lihat data customer
   - Export data customer dengan export_customers.php
7. **Laporan (admin/reports.php)**: Akses laporan penjualan dan analitik lengkap

### Untuk Customer
1. **Registrasi/Login**: Akses auth/register.php untuk daftar atau auth/login.php untuk masuk
   - Fitur lupa password tersedia di auth/forgot_password.php
2. **Dashboard (customer/dashboard.php)**: Lihat ringkasan aktivitas dan profil
3. **Browse Produk (customer/products.php)**:
   - Lihat katalog produk dengan filter dan pencarian
   - Quick view detail produk via quick_view.php
4. **Keranjang Belanja (customer/cart.php)**:
   - Tambah produk via add_to_cart.php
   - Update quantity dengan update_cart.php
   - Hapus item menggunakan remove_from_cart.php
   - Cek total belanja dengan get_cart_total.php
5. **Checkout (customer/checkout.php)**:
   - Isi informasi pengiriman
   - Konfirmasi pesanan dan pembayaran
6. **Riwayat Pesanan (customer/orders.php)**: Pantau status dan tracking pesanan
7. **Profil (customer/profile.php)**: Kelola informasi personal dan keamanan akun

## 🔑 Akun Demo

### Admin
- **Username**: `admin`
- **Password**: `password`
- **Akses**: Panel admin lengkap

### Customer
- **Username**: `customer1`
- **Password**: `password`
- **Akses**: Panel customer

> **Note**: Ganti password default setelah instalasi untuk keamanan!

## 📱 Screenshots

### Landing Page 
![Landing Page](first_look_landing_page.png)
- Hero section dengan CTA yang menarik
- Featured products dan statistik
- Responsive design untuk mobile

### Admin Dashboard
![Admin Dashboard](admin_dashboard.png)
- Real-time statistics dan charts
- Quick actions dan shortcuts
- Clean dan intuitive interface

### Customer Dashboard
![Customer Dashboard](customers_dashboard.png)
- Quick access ke features
- Recent orders dan recommendations

### Product Management
![Product Management](product_management.png)
![Add Product](add_product.png)
![Delete_product](delete_product.png)
![Reports](reports.png)
- Advanced filtering dan search
- Bulk operations
- Image upload dengan preview


### Shopping Cart
![Shopping cart](cart_shopping.png)
- Real-time quantity updates
- Shipping calculator

### History Orders
![History Orders](history_orders.png)

## Customers view product
![Customer view product](customer_view_product.png)

## Checkout
![Checkout](checkout.png)

## 🚀 Fitur Lanjutan

### Security Features
- **Password Hashing**: Menggunakan PHP `password_hash()`
- **SQL Injection Protection**: Prepared statements dengan PDO
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Session-based validation
- **File Upload Security**: Type dan size validation

### Performance Optimizations
- **Lazy Loading**: Gambar dimuat sesuai kebutuhan
- **Database Indexing**: Index pada kolom yang sering diquery
- **CSS/JS Minification**: Asset optimization
- **Image Optimization**: Resize dan compress gambar upload

### User Experience
- **Real-time Updates**: AJAX untuk interaksi smooth
- **Form Validation**: Client-side dan server-side
- **Loading States**: Feedback visual untuk operasi async
- **Error Handling**: User-friendly error messages
- **Responsive Design**: Optimal di semua device

## 🔧 Customization

### Mengubah Tema
Edit `assets/css/style.css`:
```css
:root {
    --primary-color: #your-color;
    --secondary-color: #your-color;
    /* Customize color scheme */
}
```

### Menambah Fitur Payment Gateway
1. Buat file `payment/gateway.php`
2. Implementasi API payment provider
3. Update checkout process
4. Add configuration di `config/payment.php`

### Menambah Fitur Email Notification
1. Install PHPMailer atau SwiftMailer
2. Setup SMTP configuration
3. Create email templates
4. Trigger notifications di order events

## 🐛 Troubleshooting

### Error Database Connection
```
Solution: 
1. Pastikan MySQL service running
2. Check username/password di config/database.php
3. Verify database 'toko_online' sudah dibuat
```

### Upload Gambar Gagal
```
Solution:
1. Check folder assets/images/products/ exists
2. Verify folder permissions (755 or 777)
3. Check file size limits di php.ini
```

### Session Issues
```
Solution:
1. Pastikan session_start() dipanggil
2. Check browser cookies enabled
3. Verify server session configuration
```

### AJAX Requests Failing
```
Solution:
1. Check browser console untuk errors
2. Verify endpoint URLs correct
3. Ensure proper JSON response format
```

## 📝 Development Notes

### Coding Standards
- **PSR-4** autoloading structure
- **Camel case** untuk variabel JavaScript
- **Snake case** untuk variabel PHP
- **Semantic HTML** markup
- **Mobile-first** responsive approach

### Database Conventions
- **Singular** table names
- **Primary key** selalu 'id'
- **Foreign key** format: 'table_id'
- **Timestamps**: created_at, updated_at
- **Soft deletes** untuk data penting

### Security Best Practices
- **Never** store plaintext passwords
- **Always** validate and sanitize input
- **Use** prepared statements untuk SQL
- **Implement** proper error handling
- **Regular** security updates

## 📄 License

Project ini dibuat untuk keperluan edukasi UAS Pemrograman Web Lanjut.

## 👥 Kontribusi

Project ini dikembangkan sebagai bagian dari UAS Pemrograman Web Lanjut - Sistem Informasi.

### Tim Pengembang
- **Backend Development**: PHP, MySQL, Security
- **Frontend Development**: HTML, CSS, JavaScript, UI/UX
- **Database**: Rancangan database
- **Testing & QA**: Functionality, Security, Performance

---

## 📞 Support - 088802972620

Jika mengalami kesulitan dalam instalasi atau penggunaan:

1. **Check dokumentasi** ini terlebih dahulu
2. **Review error logs** di browser console
3. **Verify** semua requirements terpenuhi
4. **Test** dengan akun demo yang disediakan

---

**Happy Coding! 🚀**

*Dibuat dengan ❤️ untuk UAS Pemrograman Web Lanjut*

## Dibuat oleh:
1. **Ibnu Hanafi Assalam - A12.2023.06994** 
2. **Muhammad Fuad Aqila - A12.2023.06982**
3. **Dzaki Jamil Makruf - A12.2023.07101**
4. **Rafli Zibrilian Farrel - A12.2023.06973**
5. **Mutiara Acintyacitra N - A12.2023.07059**