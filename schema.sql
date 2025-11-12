-- Schema cho Website Thuê Xe Tự Lái Online
-- Tạo database (xóa database cũ nếu có để tránh lỗi)
-- Lưu ý: Database name là 'carrental' (không có underscore)
DROP DATABASE IF EXISTS carrental;
CREATE DATABASE carrental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carrental;

-- ============================================
-- TẠO BẢNG (Thứ tự quan trọng!)
-- ============================================

-- Bảng users: Quản lý người dùng (User, Admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng categories: Danh mục bài viết trong diễn đàn
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng cars: Quản lý thông tin xe
-- Tạo TRƯỚC posts (không có FK post_id trong CREATE TABLE)
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    post_id INT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    price_per_day DECIMAL(10,2) NOT NULL,
    car_type VARCHAR(50) NOT NULL,
    rental_type ENUM('self-drive', 'with-driver', 'long-term') DEFAULT 'self-drive',
    location VARCHAR(100) DEFAULT 'hcm',
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng posts: Bài viết/thread trong diễn đàn
-- Tạo SAU cars (có thể tham chiếu cars qua car_id)
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('rental', 'discussion') DEFAULT 'discussion',
    car_id INT NULL,
    status ENUM('active', 'closed', 'deleted') DEFAULT 'active',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_post_type (post_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng comments: Bình luận trên bài viết
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    parent_id INT NULL,
    status ENUM('active', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng bookings: Quản lý đơn đặt xe
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    customer_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng payments: Quản lý thanh toán
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng reviews: Quản lý đánh giá xe
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    customer_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DỮ LIỆU MẪU
-- ============================================

-- Thêm categories mẫu
INSERT INTO categories (name, slug, description) VALUES
('Cho thuê xe', 'cho-thue-xe', 'Danh mục đăng bài cho thuê xe'),
('Thảo luận', 'thao-luan', 'Thảo luận chung về thuê xe'),
('Kinh nghiệm', 'kinh-nghiem', 'Chia sẻ kinh nghiệm thuê xe'),
('Hỏi đáp', 'hoi-dap', 'Hỏi đáp về thuê xe'),
('Tin tức', 'tin-tuc', 'Tin tức về ngành thuê xe');

-- Thêm users mẫu (password: 123456)
-- Hash password: $2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC
-- Tất cả users: admin, user1, user2, user3, user4 đều có password: 123456
INSERT INTO users (username, email, password, role, full_name, phone) VALUES
('admin', 'admin@carrental.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'admin', 'Quản Trị Viên', '0901234567'),
('user1', 'user1@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Nguyễn Văn A', '0902345678'),
('user2', 'user2@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Trần Thị B', '0903456789'),
('user3', 'user3@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Lê Văn C', '0904567890'),
('user4', 'user4@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Phạm Thị D', '0905678901');

-- Thêm cars mẫu
-- owner_id: user1=2, user2=3, user3=4, user4=5
-- rental_type: self-drive (xe tự lái), with-driver (xe có tài xế), long-term (thuê xe dài hạn)
INSERT INTO cars (owner_id, name, description, image, price_per_day, car_type, rental_type, location, status) VALUES
-- Xe tự lái (self-drive)
(2, 'Toyota Vios 2023', 'Xe sedan 5 chỗ, tiết kiệm nhiên liệu, phù hợp đi phố và đường dài. Xe mới, sạch sẽ, đầy đủ giấy tờ. Phù hợp cho người mới lái xe.', 'toyota-vios.jpg', 500000, 'sedan', 'self-drive', 'hcm', 'available'),
(2, 'Honda City 2023', 'Xe sedan sang trọng, nội thất hiện đại, động cơ mạnh mẽ. Xe mới, đầy đủ tiện nghi, phù hợp cho các chuyến đi công tác hoặc du lịch.', 'honda-city.jpg', 550000, 'sedan', 'self-drive', 'hcm', 'available'),
(3, 'Ford Ranger 2022', 'Xe bán tải 5 chỗ, mạnh mẽ, phù hợp địa hình phức tạp. Xe mạnh mẽ, phù hợp cho các chuyến đi địa hình hoặc vận chuyển hàng hóa.', 'ford-ranger.jpg', 1200000, 'pickup', 'self-drive', 'hanoi', 'available'),
(3, 'Mazda CX-5 2023', 'Xe SUV 7 chỗ, rộng rãi, an toàn cho gia đình. Xe rộng rãi, an toàn, phù hợp cho gia đình đông người đi du lịch.', 'mazda-cx5.jpg', 900000, 'suv', 'self-drive', 'hcm', 'available'),
(2, 'Hyundai Accent 2023', 'Xe sedan 5 chỗ, giá cả phải chăng, phù hợp sinh viên. Giá rẻ, tiết kiệm nhiên liệu, phù hợp cho sinh viên hoặc người mới lái xe.', 'hyundai-accent.jpg', 450000, 'sedan', 'self-drive', 'danang', 'available'),
(3, 'Mitsubishi Xpander 2022', 'Xe MPV 7 chỗ, tiện nghi cho gia đình đông người. Xe 7 chỗ, rộng rãi, tiện nghi, phù hợp cho gia đình đi du lịch.', 'mitsubishi-xpander.jpg', 700000, 'mpv', 'self-drive', 'hcm', 'available'),

-- Xe có tài xế (with-driver)
(4, 'Toyota Camry 2023', 'Xe sedan hạng sang, có tài xế chuyên nghiệp. Phù hợp cho sự kiện, đám cưới, công tác. Tài xế có kinh nghiệm, lịch sự, đúng giờ.', 'toyota-camry-driver.jpg', 1500000, 'sedan', 'with-driver', 'hcm', 'available'),
(4, 'Mercedes E-Class 2023', 'Xe sang trọng, có tài xế chuyên nghiệp. Phù hợp cho các sự kiện quan trọng, đám cưới, đón tiếp khách VIP.', 'mercedes-e-class-driver.jpg', 2500000, 'sedan', 'with-driver', 'hcm', 'available'),
(5, 'Ford Transit 16 chỗ', 'Xe 16 chỗ, có tài xế chuyên nghiệp. Phù hợp cho đoàn khách, tour du lịch, đưa đón sân bay.', 'ford-transit-driver.jpg', 2000000, 'van', 'with-driver', 'hanoi', 'available'),
(4, 'Honda CR-V 2023', 'Xe SUV 7 chỗ, có tài xế chuyên nghiệp. Phù hợp cho gia đình đi du lịch, tour, công tác.', 'honda-crv-driver.jpg', 1800000, 'suv', 'with-driver', 'hcm', 'available'),

-- Thuê xe dài hạn (long-term)
(5, 'Toyota Vios 2022', 'Xe sedan 5 chỗ, phù hợp thuê dài hạn (từ 3 tháng trở lên). Giá ưu đãi cho thuê dài hạn, hỗ trợ bảo dưỡng định kỳ.', 'toyota-vios-longterm.jpg', 8000000, 'sedan', 'long-term', 'hcm', 'available'),
(2, 'Hyundai Accent 2022', 'Xe sedan 5 chỗ, phù hợp thuê dài hạn cho công ty, cá nhân. Giá ưu đãi, hỗ trợ bảo dưỡng, đổi xe khi cần.', 'hyundai-accent-longterm.jpg', 7000000, 'sedan', 'long-term', 'hanoi', 'available'),
(3, 'Mazda CX-5 2022', 'Xe SUV 7 chỗ, phù hợp thuê dài hạn cho gia đình, công ty. Giá ưu đãi, hỗ trợ bảo dưỡng định kỳ.', 'mazda-cx5-longterm.jpg', 12000000, 'suv', 'long-term', 'hcm', 'available');

-- Tạo posts mẫu từ các xe (bài viết cho thuê xe)
-- user_id: user1=2, user2=3, user3=4, user4=5
-- car_id: 1-13 (tương ứng với cars id 1-13)
INSERT INTO posts (user_id, category_id, title, content, post_type, car_id, status) VALUES
-- Xe tự lái
(2, 1, 'Cho thuê: Toyota Vios 2023 - Xe tự lái', '🚗 XE TỰ LÁI\n\nMô tả: Xe sedan 5 chỗ, tiết kiệm nhiên liệu, phù hợp đi phố và đường dài. Xe mới, sạch sẽ, đầy đủ giấy tờ.\n\nGiá: 500,000 VNĐ/ngày\nLoại xe: Sedan\nĐịa điểm: TP. Hồ Chí Minh\n\nXe được bảo dưỡng định kỳ, sạch sẽ, đầy đủ giấy tờ. Liên hệ để biết thêm chi tiết!', 'rental', 1, 'active'),
(2, 1, 'Cho thuê: Honda City 2023 - Xe tự lái', '🚗 XE TỰ LÁI\n\nMô tả: Xe sedan sang trọng, nội thất hiện đại, động cơ mạnh mẽ. Xe mới, đầy đủ tiện nghi.\n\nGiá: 550,000 VNĐ/ngày\nLoại xe: Sedan\nĐịa điểm: TP. Hồ Chí Minh\n\nPhù hợp cho các chuyến đi công tác hoặc du lịch.', 'rental', 2, 'active'),
(3, 1, 'Cho thuê: Ford Ranger 2022 - Xe tự lái', '🚗 XE TỰ LÁI\n\nMô tả: Xe bán tải 5 chỗ, mạnh mẽ, phù hợp địa hình phức tạp.\n\nGiá: 1,200,000 VNĐ/ngày\nLoại xe: Bán tải\nĐịa điểm: Hà Nội\n\nXe mạnh mẽ, phù hợp cho các chuyến đi địa hình hoặc vận chuyển hàng hóa.', 'rental', 3, 'active'),
(3, 1, 'Cho thuê: Mazda CX-5 2023 - Xe tự lái', '🚗 XE TỰ LÁI\n\nMô tả: Xe SUV 7 chỗ, rộng rãi, an toàn cho gia đình.\n\nGiá: 900,000 VNĐ/ngày\nLoại xe: SUV\nĐịa điểm: TP. Hồ Chí Minh\n\nXe rộng rãi, an toàn, phù hợp cho gia đình đông người đi du lịch.', 'rental', 4, 'active'),
(2, 1, 'Cho thuê: Hyundai Accent 2023 - Xe tự lái', '🚗 XE TỰ LÁI\n\nMô tả: Xe sedan 5 chỗ, giá cả phải chăng, phù hợp sinh viên.\n\nGiá: 450,000 VNĐ/ngày\nLoại xe: Sedan\nĐịa điểm: Đà Nẵng\n\nGiá rẻ, tiết kiệm nhiên liệu, phù hợp cho sinh viên hoặc người mới lái xe.', 'rental', 5, 'active'),
(3, 1, 'Cho thuê: Mitsubishi Xpander 2022 - Xe tự lái', '🚗 XE TỰ LÁI\n\nMô tả: Xe MPV 7 chỗ, tiện nghi cho gia đình đông người.\n\nGiá: 700,000 VNĐ/ngày\nLoại xe: MPV\nĐịa điểm: TP. Hồ Chí Minh\n\nXe 7 chỗ, rộng rãi, tiện nghi, phù hợp cho gia đình đi du lịch.', 'rental', 6, 'active'),

-- Xe có tài xế
(4, 1, 'Cho thuê: Toyota Camry 2023 - Có tài xế', '🚕 XE CÓ TÀI XẾ\n\nMô tả: Xe sedan hạng sang, có tài xế chuyên nghiệp. Phù hợp cho sự kiện, đám cưới, công tác.\n\nGiá: 1,500,000 VNĐ/ngày\nLoại xe: Sedan\nĐịa điểm: TP. Hồ Chí Minh\n\nTài xế có kinh nghiệm, lịch sự, đúng giờ. Phù hợp cho các sự kiện quan trọng.', 'rental', 7, 'active'),
(4, 1, 'Cho thuê: Mercedes E-Class 2023 - Có tài xế', '🚕 XE CÓ TÀI XẾ\n\nMô tả: Xe sang trọng, có tài xế chuyên nghiệp. Phù hợp cho các sự kiện quan trọng, đám cưới, đón tiếp khách VIP.\n\nGiá: 2,500,000 VNĐ/ngày\nLoại xe: Sedan cao cấp\nĐịa điểm: TP. Hồ Chí Minh\n\nXe sang trọng, tài xế chuyên nghiệp, phục vụ tận tình.', 'rental', 8, 'active'),
(5, 1, 'Cho thuê: Ford Transit 16 chỗ - Có tài xế', '🚕 XE CÓ TÀI XẾ\n\nMô tả: Xe 16 chỗ, có tài xế chuyên nghiệp. Phù hợp cho đoàn khách, tour du lịch, đưa đón sân bay.\n\nGiá: 2,000,000 VNĐ/ngày\nLoại xe: Xe 16 chỗ\nĐịa điểm: Hà Nội\n\nTài xế có kinh nghiệm lái xe lớn, phục vụ đoàn khách chuyên nghiệp.', 'rental', 9, 'active'),
(4, 1, 'Cho thuê: Honda CR-V 2023 - Có tài xế', '🚕 XE CÓ TÀI XẾ\n\nMô tả: Xe SUV 7 chỗ, có tài xế chuyên nghiệp. Phù hợp cho gia đình đi du lịch, tour, công tác.\n\nGiá: 1,800,000 VNĐ/ngày\nLoại xe: SUV\nĐịa điểm: TP. Hồ Chí Minh\n\nTài xế có kinh nghiệm, hiểu biết về địa phương, phục vụ tận tình.', 'rental', 10, 'active'),

-- Thuê xe dài hạn
(5, 1, 'Cho thuê: Toyota Vios 2022 - Thuê dài hạn', '📅 THUÊ XE DÀI HẠN\n\nMô tả: Xe sedan 5 chỗ, phù hợp thuê dài hạn (từ 3 tháng trở lên). Giá ưu đãi cho thuê dài hạn.\n\nGiá: 8,000,000 VNĐ/tháng\nLoại xe: Sedan\nĐịa điểm: TP. Hồ Chí Minh\n\nHỗ trợ bảo dưỡng định kỳ, đổi xe khi cần. Phù hợp cho công ty, cá nhân thuê dài hạn.', 'rental', 11, 'active'),
(2, 1, 'Cho thuê: Hyundai Accent 2022 - Thuê dài hạn', '📅 THUÊ XE DÀI HẠN\n\nMô tả: Xe sedan 5 chỗ, phù hợp thuê dài hạn cho công ty, cá nhân. Giá ưu đãi.\n\nGiá: 7,000,000 VNĐ/tháng\nLoại xe: Sedan\nĐịa điểm: Hà Nội\n\nHỗ trợ bảo dưỡng, đổi xe khi cần. Phù hợp cho thuê dài hạn từ 3 tháng trở lên.', 'rental', 12, 'active'),
(3, 1, 'Cho thuê: Mazda CX-5 2022 - Thuê dài hạn', '📅 THUÊ XE DÀI HẠN\n\nMô tả: Xe SUV 7 chỗ, phù hợp thuê dài hạn cho gia đình, công ty. Giá ưu đãi.\n\nGiá: 12,000,000 VNĐ/tháng\nLoại xe: SUV\nĐịa điểm: TP. Hồ Chí Minh\n\nHỗ trợ bảo dưỡng định kỳ, đổi xe khi cần. Phù hợp cho thuê dài hạn từ 3 tháng trở lên.', 'rental', 13, 'active');

-- Cập nhật cars.post_id từ posts vừa tạo (posts id 1-13)
UPDATE cars SET post_id = 1 WHERE id = 1;
UPDATE cars SET post_id = 2 WHERE id = 2;
UPDATE cars SET post_id = 3 WHERE id = 3;
UPDATE cars SET post_id = 4 WHERE id = 4;
UPDATE cars SET post_id = 5 WHERE id = 5;
UPDATE cars SET post_id = 6 WHERE id = 6;
UPDATE cars SET post_id = 7 WHERE id = 7;
UPDATE cars SET post_id = 8 WHERE id = 8;
UPDATE cars SET post_id = 9 WHERE id = 9;
UPDATE cars SET post_id = 10 WHERE id = 10;
UPDATE cars SET post_id = 11 WHERE id = 11;
UPDATE cars SET post_id = 12 WHERE id = 12;
UPDATE cars SET post_id = 13 WHERE id = 13;

-- Thêm foreign key post_id vào bảng cars SAU KHI đã có dữ liệu trong posts
-- QUAN TRỌNG: Phải thêm foreign key SAU KHI đã insert posts và update cars.post_id
ALTER TABLE cars 
ADD CONSTRAINT fk_cars_post_id 
FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE SET NULL;

-- Thêm một số bài viết thảo luận mẫu
-- user_id: user3=4, user4=5
INSERT INTO posts (user_id, category_id, title, content, post_type, status) VALUES
(4, 2, 'Kinh nghiệm thuê xe tự lái lần đầu', 'Xin chào mọi người, tôi là người mới và muốn thuê xe tự lái lần đầu. Có ai có kinh nghiệm chia sẻ không? Tôi nên chú ý điều gì?', 'discussion', 'active'),
(5, 3, 'Làm thế nào để chọn xe phù hợp?', 'Mình đang phân vân giữa sedan và SUV. Có ai tư vấn giúp không? Mình muốn đi du lịch cùng gia đình 4 người.', 'discussion', 'active'),
(4, 4, 'Có cần bằng lái quốc tế không?', 'Mình có bằng lái Việt Nam, khi thuê xe có cần bằng quốc tế không? Có ai biết không?', 'discussion', 'active');

-- Thêm bookings mẫu
-- customer_id: user3=4, user4=5
INSERT INTO bookings (car_id, customer_id, start_date, end_date, total_price, status) VALUES
(1, 4, '2025-10-20', '2025-10-23', 1500000, 'confirmed'),
(2, 5, '2025-10-18', '2025-10-20', 1100000, 'pending'),
(3, 4, '2025-10-25', '2025-10-28', 3600000, 'completed');

-- Thêm payments mẫu
INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) VALUES
(1, 1500000, 'VNPAY', 'VNP202510201234567', 'completed'),
(3, 3600000, 'VNPAY', 'VNP202510151234568', 'completed');

-- Thêm reviews mẫu
-- customer_id: user3=4
INSERT INTO reviews (car_id, customer_id, booking_id, rating, comment) VALUES
(3, 4, 3, 5, 'Xe rất tốt, chủ xe nhiệt tình, sẽ thuê lại lần sau!'),
(1, 4, 1, 4, 'Xe đẹp, sạch sẽ. Giá hợp lý.');
