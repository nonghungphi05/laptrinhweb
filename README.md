# 🚗 Diễn đàn Thuê Xe Tự Lái

Website diễn đàn cho thuê xe tự lái - Nơi bạn có thể vừa thuê xe vừa đăng bài cho thuê xe trên cùng một tài khoản.

## ✨ Tính năng chính

### 🚙 Thuê xe
- Xem thông tin xe trong bài viết
- Đặt xe trực tiếp từ bài viết
- Chọn ngày thuê, tự động tính tổng tiền
- Kiểm tra trùng lịch đặt xe
- Thanh toán online qua VNPAY
- Đánh giá xe sau khi thuê
- 💬 **Chat trực tiếp**: Nhắn tin trao đổi giữa người thuê và chủ xe (Real-time)

### 👤 Tài khoản
- Mỗi user có thể vừa thuê xe vừa đăng bài cho thuê
- Không phân biệt khách hàng/chủ xe
- Chỉ có 2 role: **user** và **admin**
- Trung tâm tài khoản: quản lý thông tin cá nhân, địa chỉ, lịch sử thanh toán, thông báo

## 🛠️ Công nghệ

- **Frontend**: HTML5, CSS3 (Pure CSS & Tailwind CSS), JavaScript (Vanilla JS)
- **Backend**: PHP 7.4+, MySQL 5.7+
- **Real-time**: Pusher (Chat feature)
- **Payment**: VNPAY Sandbox
- **Server**: Apache (mod_rewrite)

## 📁 Cấu trúc thư mục

```
laptrinhweb/
├── cars/               # Danh sách xe cho thuê
│   └── index.php       # Trang lọc & tìm kiếm xe
├── admin/              # Quản trị (Admin only)
├── api/                # API thanh toán VNPAY
├── assets/             # CSS, JS, Fonts
├── auth/               # Đăng nhập, đăng ký
├── chat/               # API xử lý tin nhắn (send, get history)
├── client/             # Đặt xe, thanh toán, đánh giá, hồ sơ
│   ├── booking.php              # Form đặt xe
│   ├── payment.php              # Thanh toán
│   ├── my-bookings.php          # Đơn đặt của tôi
│   ├── profile.php              # Thông tin tài khoản
│   ├── addresses.php            # Quản lý địa chỉ nhận/trả xe
│   ├── payment-history.php      # Lịch sử thanh toán
│   ├── notifications.php        # Trung tâm thông báo

## 🚀 Cài đặt trên XAMPP

### Yêu cầu
- XAMPP (PHP 7.4+, MySQL 5.7+)
- Trình duyệt web hiện đại

### Các bước

1. **Copy project vào XAMPP**
   - Copy folder vào: `C:\xampp\htdocs\webthuexe`

2. **Start XAMPP**
   - Mở XAMPP Control Panel
   - Start **Apache** và **MySQL**

3. **Import database**
   - Mở phpMyAdmin: `http://localhost/phpmyadmin`
   - Click tab **SQL** (hoặc **Import**)
   - Copy toàn bộ nội dung file `schema.sql` và dán vào
   - Click **Go** để chạy
   - **Lưu ý**: File schema.sql sẽ xóa database cũ và tạo mới (nếu có dữ liệu cũ, hãy backup trước!)

4. **Cấu hình database** (mặc định đã đúng cho XAMPP)
   - Mở `config/database.php`
   - Kiểm tra: `localhost`, `root`, không password
   - Nếu đúng rồi thì không cần sửa

5. **Truy cập website**
   - Mở trình duyệt: `http://localhost/webthuexe`
   - Sẽ tự động redirect đến diễn đàn

### 📝 File SQL

- **`schema.sql`** ✅ - **File SQL duy nhất cần dùng** (tạo database, bảng, dữ liệu mẫu)

## 👤 Tài khoản demo

| Username | Password | Role | Mô tả |
|----------|----------|------|-------|
| admin | 123456 | admin | Quản trị viên |
| user1 | 123456 | user | User thường (có thể vừa thuê vừa đăng bài) |
| user2 | 123456 | user | User thường |
| user3 | 123456 | user | User thường |
| user4 | 123456 | user | User thường |

**Lưu ý**: Tất cả user (trừ admin) đều có thể vừa thuê xe vừa đăng bài cho thuê xe.

## 🧪 Test thanh toán VNPAY

Để test thanh toán: đăng nhập → tạo/xem bài viết cho thuê xe → đặt xe → thanh toán. Hệ thống sử dụng VNPAY Sandbox.

**Thông tin thẻ test:**
- Ngân hàng: NCB
- Số thẻ: 9704198526191432198
- Tên chủ thẻ: NGUYEN VAN A
- Ngày phát hành: 07/15
- Mã OTP: 123456

## 📊 Database Schema

### Bảng chính

- **users**: Người dùng (role: user/admin)
- **cars**: Thông tin xe do chủ xe đăng
- **bookings**: Đơn đặt xe
- **payments**: Thanh toán
- **user_addresses**: Địa chỉ nhận/trả xe yêu thích
- **user_notifications**: Thông báo gửi cho user
- **reviews**: Đánh giá xe
- **messages**: Tin nhắn chat (Tự động tạo khi chạy messages.php)

Xem chi tiết trong file `schema.sql`

## 🔒 Bảo mật

- ✅ Prepared Statements (SQL Injection prevention)
- ✅ htmlspecialchars() (XSS prevention)
- ✅ Password hashing (password_hash)
- ✅ Session-based authentication
- ✅ File upload validation
- ✅ Role-based access control


## 🎯 Tính năng nổi bật

1. **Diễn đàn tích hợp**: Đăng bài viết cho thuê xe như một bài post trong diễn đàn
2. **Dual role**: Mỗi user có thể vừa là người thuê vừa là người cho thuê
3. **Bình luận**: Hỗ trợ bình luận và reply trên bài viết
4. **Tích hợp booking**: Đặt xe trực tiếp từ bài viết
5. **Thanh toán online**: Tích hợp VNPAY

## 📞 Hỗ trợ

Nếu gặp vấn đề:
1. Kiểm tra log lỗi PHP
2. Kiểm tra cấu hình database
3. Kiểm tra quyền thư mục `uploads/`
4. Đảm bảo đã import đúng schema.sql

## 🚀 Phát triển tiếp

Có thể thêm:
- Thông báo real-time
- Upload nhiều ảnh
- Like/Dislike bài viết
- Tag và hashtag
- Tìm kiếm nâng cao
- Email notifications
- API RESTful

---

**License**: MIT  
**Author**: [Your Name]  
**Version**: 2.0 (Diễn đàn version)
