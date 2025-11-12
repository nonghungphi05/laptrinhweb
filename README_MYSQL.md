# Hướng dẫn Import MySQL vào XAMPP

## Tạo Database Mới (Khuyến nghị)

Nếu bạn chưa có database hoặc muốn tạo mới hoàn toàn:

### Cách 1: Import trực tiếp (Đơn giản nhất)

1. Mở **phpMyAdmin** trong XAMPP: `http://localhost/phpmyadmin`
2. Chọn tab **SQL**
3. Copy toàn bộ nội dung file **`schema.sql`** và paste vào
4. Click **Go** để chạy
   - File sẽ tự động tạo database `carrental`
   - Tạo tất cả bảng (users, cars, posts, categories, comments, bookings, payments, reviews)
   - Insert dữ liệu mẫu (5 users, 6 cars, 9 posts)
5. Xong! Database đã sẵn sàng sử dụng

### Cách 2: Tạo database trước, sau đó import

1. Mở **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Tạo database mới: `carrental`
3. Chọn database vừa tạo
4. Vào tab **Import**
5. Chọn file **`schema.sql`**
6. Click **Go** để import
7. Xong! Database đã có đầy đủ dữ liệu mẫu

## Thông tin Database

- **Database name**: `carrental` (không có underscore)
- **Charset**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`

## Thông tin tài khoản mẫu

- **Admin**: 
  - Username: `admin`
  - Password: `123456`
  
- **Users**: 
  - Username: `user1`, `user2`, `user3`, `user4`
  - Password: `123456` (tất cả)

## Kiểm tra

Sau khi import, kiểm tra bằng lệnh:

```sql
-- Kiểm tra users
SELECT * FROM users;
-- Phải có 5 users (1 admin, 4 users)

-- Kiểm tra cars
SELECT * FROM cars;
-- Phải có 6 cars

-- Kiểm tra posts
SELECT * FROM posts;
-- Phải có 9 posts (6 rental, 3 discussion)

-- Kiểm tra cột location
DESCRIBE cars;
-- Phải có cột location

-- Kiểm tra dữ liệu location
SELECT id, name, location FROM cars;
```

## Lưu ý

- **Chỉ cần 1 file**: `schema.sql` (đã đầy đủ, không cần file migration)
- **Database name**: Phải là `carrental` (không có underscore)
- **Config**: File `config/database.php` đã được cấu hình đúng
- **Sao lưu**: Trước khi import, nên backup database cũ (nếu có)

## Xử lý lỗi

### Lỗi: "Table 'carrental.users' doesn't exist"
- **Nguyên nhân**: Database chưa được import hoặc import sai
- **Giải pháp**: Import lại file `schema.sql`

### Lỗi: "Access denied"
- **Nguyên nhân**: Thông tin database trong `config/database.php` sai
- **Giải pháp**: Kiểm tra lại DB_USER, DB_PASS, DB_NAME trong `config/database.php`

### Lỗi: "Unknown database 'carrental'"
- **Nguyên nhân**: Database chưa được tạo
- **Giải pháp**: Import file `schema.sql` (sẽ tự động tạo database)

## Cấu trúc Database

1. **users** - Người dùng (Admin, User)
2. **categories** - Danh mục bài viết
3. **cars** - Thông tin xe (có cột location)
4. **posts** - Bài viết (rental, discussion)
5. **comments** - Bình luận
6. **bookings** - Đơn đặt xe
7. **payments** - Thanh toán
8. **reviews** - Đánh giá

## Xóa và tạo lại Database

Nếu muốn xóa và tạo lại database:

1. Mở **phpMyAdmin**
2. Chọn database `carrental`
3. Click **Xóa** (Drop)
4. Import lại file `schema.sql`

Hoặc chạy trực tiếp file `schema.sql` (sẽ tự động DROP và CREATE lại database)
