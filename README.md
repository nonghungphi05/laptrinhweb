# 🚗 CarRental - Website Thuê Xe Tự Lái

> **Đồ án môn Lập Trình Web**  
> Website cho thuê xe tự lái – Mỗi user có thể vừa thuê xe vừa đăng xe cho thuê trên cùng một tài khoản.

---

## 📋 Mục lục

- [Tính năng chính](#-tính-năng-chính)
- [Công nghệ sử dụng](#️-công-nghệ-sử-dụng)
- [Cấu trúc thư mục](#-cấu-trúc-thư-mục)
- [Cài đặt](#-cài-đặt)
- [Tài khoản demo](#-tài-khoản-demo)
- [Hướng dẫn sử dụng](#-hướng-dẫn-sử-dụng)
- [Database Schema](#-database-schema)
- [Bảo mật](#-bảo-mật)

---

## ✨ Tính năng chính

### 🚙 Người dùng (User)
| Tính năng | Mô tả |
|-----------|-------|
| Tìm kiếm xe | Lọc theo địa điểm, loại xe, khoảng giá |
| Xem chi tiết xe | Gallery nhiều ảnh, thông tin đầy đủ, đánh giá từ khách hàng |
| Đặt xe | Chọn ngày, kiểm tra trùng lịch realtime, tính tiền tự động |
| Giao xe tận nơi | Chọn địa chỉ đã lưu hoặc thêm mới |
| Thanh toán | VNPAY Sandbox, tự động hủy sau 15 phút nếu chưa thanh toán |
| Đánh giá | Đánh giá xe sau khi hoàn thành chuyến |
| Quản lý tài khoản | Thông tin cá nhân, địa chỉ, lịch sử thanh toán |

### 🚘 Chủ xe (Host)
| Tính năng | Mô tả |
|-----------|-------|
| Dashboard | Thống kê doanh thu, biểu đồ (Chart.js) |
| Quản lý xe | Thêm/sửa/xóa xe, upload nhiều ảnh |
| Lịch đặt xe | Xem lịch trực quan (FullCalendar.js) |
| Quản lý đơn | Xác nhận/từ chối đơn đặt xe |
| Thu nhập | Xem doanh thu, yêu cầu rút tiền |
| Đánh giá | Phản hồi và báo cáo đánh giá không phù hợp |

### 👨‍💼 Quản trị viên (Admin)
| Tính năng | Mô tả |
|-----------|-------|
| Tổng quan | Dashboard với thống kê toàn hệ thống |
| Người dùng | Quản lý tài khoản, phân quyền |
| Xe | Quản lý tất cả xe trong hệ thống |
| Đơn đặt | Quản lý đơn đặt, thanh toán |
| Đánh giá | Xử lý báo cáo đánh giá vi phạm |
| Rút tiền | Duyệt yêu cầu rút tiền từ chủ xe |

### ⚙️ Tính năng hệ thống
- ✅ Tự động hủy đơn chưa thanh toán sau 15 phút
- ✅ Kiểm tra trùng lịch đặt xe realtime
- ✅ Upload và quản lý nhiều ảnh cho mỗi xe
- ✅ Responsive design (Tailwind CSS)

---

## 🛠️ Công nghệ sử dụng

| Loại | Công nghệ |
|------|-----------|
| **Frontend** | HTML5, CSS3, Tailwind CSS, JavaScript (Vanilla) |
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ |
| **Payment** | VNPAY Sandbox |
| **Server** | Apache (XAMPP) |
| **Libraries** | FullCalendar.js (lịch), Chart.js (biểu đồ) |

---

## 📁 Cấu trúc thư mục

```
laptrinhweb/
│
├── 📂 admin/                    # Quản trị (Admin only)
│   ├── dashboard.php            # Trang tổng quan
│   ├── users.php                # Quản lý người dùng
│   ├── cars.php                 # Quản lý xe
│   ├── bookings.php             # Quản lý đơn đặt
│   ├── reviews.php              # Quản lý đánh giá & báo cáo
│   └── payouts.php              # Quản lý yêu cầu rút tiền
│
├── 📂 api/                      # API thanh toán
│   ├── config.php               # Cấu hình VNPAY
│   ├── vnpay-payment.php        # Tạo link thanh toán
│   ├── vnpay_return.php         # Xử lý kết quả trả về
│   └── vnpay_ipn.php            # IPN callback
│
├── 📂 auth/                     # Xác thực
│   ├── login.php                # Đăng nhập
│   ├── register.php             # Đăng ký
│   └── logout.php               # Đăng xuất
│
├── 📂 cars/                     # Danh sách xe
│   └── index.php                # Trang lọc & tìm kiếm xe
│
├── 📂 client/                   # Chức năng người dùng
│   ├── car-detail.php           # Chi tiết xe
│   ├── booking.php              # Form đặt xe
│   ├── payment.php              # Thanh toán
│   ├── my-bookings.php          # Đơn đặt của tôi
│   ├── review.php               # Đánh giá xe
│   ├── profile.php              # Thông tin cá nhân
│   ├── addresses.php            # Quản lý địa chỉ
│   ├── payment-history.php      # Lịch sử thanh toán
│   └── account-sidebar.php      # Sidebar tài khoản
│
├── 📂 config/                   # Cấu hình
│   ├── database.php             # Kết nối database
│   ├── session.php              # Quản lý session
│   ├── helpers.php              # Các hàm helper
│   ├── constants.php            # Hằng số cấu hình
│   └── base_url.php             # URL cơ sở
│
├── 📂 host/                     # Chức năng chủ xe
│   ├── dashboard.php            # Dashboard (5 tab)
│   ├── add-car.php              # Thêm xe mới
│   ├── edit-car.php             # Sửa thông tin xe
│   ├── delete-car.php           # Xóa xe
│   └── car-bookings.php         # Đơn đặt xe của tôi
│
├── 📂 includes/                 # Components dùng chung
│   ├── header.php               # Header với navigation
│   └── footer.php               # Footer
│
│
├── 📂 uploads/                  # Thư mục upload ảnh
│
├── index.php                    # Trang chủ
├── about.php                    # Giới thiệu
├── schema.sql                   # Database schema
└── README.md                    # Tài liệu này
```

---

## 🚀 Cài đặt

### Yêu cầu hệ thống
- XAMPP (PHP 7.4+, MySQL 5.7+)
- Trình duyệt web hiện đại (Chrome, Firefox, Edge)

### Các bước cài đặt

**Bước 1: Copy project vào XAMPP**
```
C:\xampp\htdocs\laptrinhweb
```

**Bước 2: Start XAMPP**
- Mở XAMPP Control Panel
- Start **Apache** và **MySQL**

**Bước 3: Import database**
1. Mở phpMyAdmin: `http://localhost/phpmyadmin`
2. Click tab **SQL** hoặc **Import**
3. Copy toàn bộ nội dung file `schema.sql` và dán vào
4. Click **Go** để chạy

> ⚠️ **Lưu ý**: File `schema.sql` sẽ xóa database cũ (nếu có) và tạo mới!

**Bước 4: Kiểm tra cấu hình database**

File `config/database.php` (mặc định đã đúng cho XAMPP):
```php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'carrental';
```

**Bước 5: Truy cập website**
```
http://localhost/laptrinhweb
```

---

## 👤 Tài khoản demo

> 🔐 **Mật khẩu chung cho tất cả tài khoản:** `123456`

| Email | Role | Tên | Mô tả |
|-------|------|-----|-------|
| `admin@carrental.vn` | Admin | Quản Trị Viên | Quản trị toàn bộ hệ thống |
| `hoangminh@gmail.com` | Host | Nguyễn Hoàng Minh | Chủ xe sang (Mercedes, Audi, BMW...) - 11 xe |
| `thanhnga@gmail.com` | Host | Trần Thanh Nga | Chủ xe gia đình (SUV, MPV...) - 16 xe |
| `duclong@gmail.com` | Host | Lê Đức Long | Chủ xe điện & bán tải (VinFast, Ford Ranger...) - 7 xe |
| `khachhang@gmail.com` | User | Phạm Quốc Bảo | Khách hàng thuê xe |

> 💡 **Lưu ý**: User có thể nâng cấp thành Host bằng cách đăng xe cho thuê.

---

## 📖 Hướng dẫn sử dụng

### Người dùng muốn thuê xe
1. Đăng nhập hoặc đăng ký tài khoản
2. Tìm xe tại trang chủ hoặc "Danh sách xe"
3. Click vào xe để xem chi tiết
4. Chọn ngày thuê và nhấn "Đặt xe"
5. Chọn hình thức nhận xe (tự lấy/giao tận nơi)
6. Thanh toán qua VNPAY
7. Theo dõi đơn đặt tại "Đơn đặt của tôi"
8. Đánh giá xe sau khi hoàn thành

### Người dùng muốn cho thuê xe
1. Đăng nhập vào tài khoản
2. Vào "Quản lý xe" từ menu
3. Click "Thêm xe mới"
4. Điền thông tin và upload ảnh
5. Xe sẽ xuất hiện trong danh sách cho thuê
6. Quản lý đơn đặt tại Dashboard chủ xe
7. Yêu cầu rút tiền khi có doanh thu

### Test thanh toán VNPAY

| Thông tin | Giá trị |
|-----------|---------|
| Ngân hàng | NCB |
| Số thẻ | 9704198526191432198 |
| Tên chủ thẻ | NGUYEN VAN A |
| Ngày phát hành | 07/15 |
| Mã OTP | 123456 |

---

## 📊 Database Schema

### Sơ đồ quan hệ

```
users (1) ──────< cars (N)
  │                 │
  │                 └──< car_images (N)
  │                 │
  │                 └──< reviews (N) ──< review_replies (N)
  │                         │
  │                         └──< review_flags (N)
  │
  └──────< bookings (N) ──< payments (N)
  │
  └──────< user_addresses (N)
  │
  └──────< payout_requests (N)
```

### Chi tiết các bảng

| Bảng | Mô tả | Quan hệ |
|------|-------|---------|
| `users` | Người dùng (role: user/host/admin) | — |
| `cars` | Thông tin xe | → users (owner_id) |
| `car_images` | Ảnh xe (nhiều ảnh/xe) | → cars |
| `bookings` | Đơn đặt xe | → cars, users |
| `payments` | Thanh toán | → bookings |
| `user_addresses` | Địa chỉ nhận/trả xe | → users |
| `reviews` | Đánh giá xe | → cars, users, bookings |
| `review_replies` | Phản hồi đánh giá | → reviews, users |
| `review_flags` | Báo cáo đánh giá | → reviews, users |
| `payout_requests` | Yêu cầu rút tiền | → users |

---

## 🔒 Bảo mật

| Biện pháp | Mô tả |
|-----------|-------|
| SQL Injection | Prepared Statements với MySQLi |
| XSS | htmlspecialchars() cho output |
| Password | Mã hóa với password_hash() |
| Session | Session-based authentication |
| File Upload | Kiểm tra type, size, rename file |
| Access Control | Role-based (user/host/admin) |

---

## 🐛 Xử lý lỗi thường gặp

| Lỗi | Nguyên nhân | Giải pháp |
|-----|-------------|-----------|
| Không kết nối database | MySQL chưa start | Start MySQL trong XAMPP |
| 404 Not Found | Sai đường dẫn | Kiểm tra folder `laptrinhweb` |
| Upload ảnh thất bại | Thiếu quyền | Chmod 755 cho folder `uploads/` |
| VNPAY không hoạt động | Sai config | Kiểm tra `api/config.php` |

---

## 📞 Liên hệ hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra log lỗi PHP trong XAMPP
2. Kiểm tra Console trình duyệt (F12)
3. Đảm bảo đã import đúng `schema.sql`
4. Kiểm tra quyền thư mục `uploads/`

---

**© 2025 CarRental - Đồ án Lập Trình Web**
