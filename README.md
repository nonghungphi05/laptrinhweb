# 🚗 Website Thuê Xe Tự Lái Online

Website quản lý cho thuê xe tự lái với đầy đủ chức năng đặt xe, thanh toán online và đánh giá.

## 📋 Tính năng

### 🔐 Hệ thống tài khoản (3 loại)
- **Khách hàng**: Tìm kiếm, đặt xe, thanh toán, đánh giá
- **Chủ xe**: Quản lý xe, xác nhận/từ chối đơn đặt
- **Admin**: Quản trị toàn hệ thống

### 🚙 Quản lý xe (CRUD)
- Thêm/Sửa/Xóa xe
- Upload ảnh xe
- Quản lý trạng thái (available/rented/maintenance)
- Phân loại xe (sedan, SUV, MPV, bán tải)

### 🔍 Tìm kiếm & Lọc
- Tìm kiếm theo tên, mô tả
- Lọc theo loại xe
- Lọc theo khoảng giá

### 📅 Đặt xe
- Chọn ngày thuê (start date - end date)
- Tự động tính tổng tiền
- Kiểm tra trùng lịch
- Lưu booking vào database

### 💳 Thanh toán Online
- Tích hợp **VNPAY Payment Gateway** (Sandbox test)
- Cập nhật trạng thái thanh toán tự động
- Xác nhận đơn đặt sau khi thanh toán

### ⭐ Đánh giá xe
- Rating 1-5 sao
- Viết nhận xét/bình luận
- Hiển thị trên trang chi tiết xe

### 📊 Dashboard
- Thống kê cho Admin
- Thống kê doanh thu cho Chủ xe
- Quản lý đơn đặt cho Khách hàng

## 🛠️ Công nghệ sử dụng

### Frontend
- HTML5
- CSS3 (Pure CSS, không dùng framework)
- JavaScript (Vanilla JS)

### Backend
- PHP 7.4+
- MySQL 5.7+

### Payment Gateway
- VNPAY (Sandbox)

## 📁 Cấu trúc thư mục

```
webthuexe/
├── admin/          # Trang quản trị
├── api/            # API thanh toán VNPAY
├── assets/         # CSS, JavaScript
├── auth/           # Đăng ký, đăng nhập
├── client/         # Chức năng khách hàng
├── config/         # Cấu hình database, session
├── host/           # Chức năng chủ xe
├── includes/       # Header, footer
├── uploads/        # Thư mục chứa ảnh upload
├── index.php       # Trang chủ
└── schema.sql      # Database schema
```

## 🚀 Cài đặt

### Yêu cầu
- XAMPP (hoặc WAMP, Laragon)
- PHP 7.4+
- MySQL 5.7+

### Các bước cài đặt

1. **Clone repository**
   ```bash
   git clone https://github.com/nonghungphi05/laptrinhweb.git
   ```

2. **Copy vào htdocs**
   - Copy folder `webthuexe` vào `C:\xampp\htdocs\`

3. **Import database**
   - Vào phpMyAdmin: http://localhost/phpmyadmin
   - Tạo database mới tên: `car_rental`
   - Import file `schema.sql`

4. **Cấu hình database**
   - Mở file `config/database.php`
   - Kiểm tra thông tin kết nối (mặc định là localhost, root, không password)

5. **Truy cập website**
   - Mở trình duyệt: http://localhost/webthuexe

## 👤 Tài khoản demo

| Username | Password | Role |
|----------|----------|------|
| admin | 123456 | Quản trị viên |
| host1 | 123456 | Chủ xe |
| customer1 | 123456 | Khách hàng |

## 🧪 Test thanh toán VNPAY

Truy cập: http://localhost/webthuexe/api/test-vnpay.php

**Thông tin thẻ test:**
- Ngân hàng: NCB
- Số thẻ: 9704198526191432198
- Tên chủ thẻ: NGUYEN VAN A
- Ngày phát hành: 07/15
- Mã OTP: 123456

## 📖 Hướng dẫn sử dụng

Chi tiết xem file: [INSTALL.txt](INSTALL.txt)

## 🔒 Bảo mật

- SQL Injection Prevention (Prepared Statements)
- XSS Prevention (htmlspecialchars)
- Password Hashing (password_hash/verify)
- Session-based Authentication
- Role-based Access Control

## 📝 Database Schema

### Bảng users
Quản lý người dùng (Admin, Chủ xe, Khách hàng)

### Bảng cars
Quản lý thông tin xe cho thuê

### Bảng bookings
Quản lý đơn đặt xe

### Bảng payments
Quản lý thanh toán

### Bảng reviews
Quản lý đánh giá xe

## 🤝 Đóng góp

Pull requests luôn được chào đón!

## 📄 License

MIT License

## 👨‍💻 Tác giả

**nonghungphi05**
- GitHub: [@nonghungphi05](https://github.com/nonghungphi05)

## 📞 Liên hệ

Nếu có thắc mắc, vui lòng tạo Issue trên GitHub.

---

⭐ Nếu thấy project hữu ích, hãy cho một star nhé!

