# 🚗 Website Thuê Xe Tự Lái Online

Website quản lý cho thuê xe tự lái với đầy đủ chức năng đặt xe, thanh toán online và đánh giá.

## 📋 Tính năng chính

### 🔐 Hệ thống tài khoản
- **Khách hàng**: Tìm kiếm xe, đặt xe, thanh toán, đánh giá
- **Chủ xe**: Quản lý xe, xác nhận/từ chối đơn đặt
- **Admin**: Quản trị toàn hệ thống

### 🚙 Quản lý xe (CRUD)
- Thêm, sửa, xóa xe
- Upload ảnh xe
- Quản lý trạng thái (available/rented/maintenance)
- Phân loại xe (Sedan, SUV, MPV, Bán tải)

### 🔍 Tìm kiếm & Lọc
- Tìm kiếm theo tên, mô tả
- Lọc theo loại xe
- Lọc theo khoảng giá

### 📅 Đặt xe
- Chọn ngày thuê (ngày bắt đầu - ngày kết thúc)
- Tự động tính tổng tiền
- Kiểm tra trùng lịch đặt xe
- Lưu booking vào database

### 💳 Thanh toán Online
- Tích hợp VNPAY Payment Gateway (Sandbox)
- Cập nhật trạng thái thanh toán tự động
- Xác nhận đơn đặt sau khi thanh toán thành công

### ⭐ Đánh giá xe
- Rating 1-5 sao
- Viết nhận xét, bình luận
- Hiển thị đánh giá trên trang chi tiết xe

### 📊 Dashboard
- Thống kê tổng quan cho Admin
- Thống kê doanh thu cho Chủ xe
- Quản lý đơn đặt cho Khách hàng

## 🛠️ Công nghệ sử dụng

- **HTML5** - Cấu trúc trang web
- **CSS3** - Thiết kế giao diện (Pure CSS, không dùng framework)
- **JavaScript** - Xử lý tương tác (Vanilla JS)
- **PHP 7.4+** - Backend, xử lý logic
- **MySQL 5.7+** - Quản lý cơ sở dữ liệu
- **VNPAY** - Cổng thanh toán online (Sandbox)

## 📁 Cấu trúc thư mục

```
webthuexe/
├── admin/          # Trang quản trị (Admin)
├── api/            # API thanh toán VNPAY
├── assets/         # CSS, JavaScript
│   ├── css/
│   └── js/
├── auth/           # Đăng ký, đăng nhập, đăng xuất
├── client/         # Chức năng khách hàng
├── config/         # Cấu hình database, session
├── host/           # Chức năng chủ xe (quản lý xe)
├── includes/       # Header, footer
├── uploads/        # Thư mục chứa ảnh upload
├── index.php       # Trang chủ
└── schema.sql      # Database schema
```

## 🚀 Cài đặt

### Yêu cầu hệ thống
- XAMPP (hoặc WAMP, Laragon)
- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Trình duyệt web hiện đại

### Các bước cài đặt

1. **Clone hoặc tải về dự án**
   ```bash
   git clone https://github.com/nonghungphi05/laptrinhweb.git
   ```

2. **Copy vào thư mục htdocs**
   - Copy folder vào: `C:\xampp\htdocs\webthuexe`

3. **Tạo database**
   - Mở phpMyAdmin: `http://localhost/phpmyadmin`
   - Tạo database mới tên: `car_rental`
   - Import file `schema.sql`

4. **Cấu hình kết nối database**
   - Mở file `config/database.php`
   - Kiểm tra thông tin kết nối (mặc định: localhost, root, không password)

5. **Truy cập website**
   - Mở trình duyệt: `http://localhost/webthuexe`

## 👤 Tài khoản demo

| Username | Password | Vai trò |
|----------|----------|---------|
| admin | 123456 | Quản trị viên |
| host1 | 123456 | Chủ xe |
| customer1 | 123456 | Khách hàng |

## 🧪 Test thanh toán VNPAY

Truy cập: `http://localhost/webthuexe/api/test-vnpay.php`

**Thông tin thẻ test:**
- Ngân hàng: NCB
- Số thẻ: 9704198526191432198
- Tên chủ thẻ: NGUYEN VAN A
- Ngày phát hành: 07/15
- Mã OTP: 123456

## 🔒 Bảo mật

- **SQL Injection Prevention** - Sử dụng Prepared Statements
- **XSS Prevention** - Sử dụng htmlspecialchars()
- **Password Hashing** - Sử dụng password_hash() và password_verify()
- **Session-based Authentication** - Quản lý đăng nhập qua session
- **Role-based Access Control** - Phân quyền theo vai trò người dùng

## 📝 Database Schema

### Bảng `users`
Lưu trữ thông tin người dùng (Admin, Chủ xe, Khách hàng)

### Bảng `cars`
Lưu trữ thông tin xe cho thuê

### Bảng `bookings`
Lưu trữ thông tin đơn đặt xe

### Bảng `payments`
Lưu trữ thông tin thanh toán

### Bảng `reviews`
Lưu trữ đánh giá của khách hàng

## 📖 Hướng dẫn chi tiết

Xem file [INSTALL.txt](INSTALL.txt) để biết thêm chi tiết về cài đặt và sử dụng.

## 📞 Thông tin

**Tác giả:** nonghungphi05  
**Repository:** https://github.com/nonghungphi05/laptrinhweb

---

⭐ Nếu thấy dự án hữu ích, hãy cho một star!
