-- Schema cho Website Thuê Xe Tự Lái
DROP DATABASE IF EXISTS carrental;
CREATE DATABASE carrental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carrental;

-- ============================================
-- BẢNG DỮ LIỆU CHÍNH
-- ============================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'host') DEFAULT 'user',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    price_per_day DECIMAL(10,2) NOT NULL,
    car_type VARCHAR(50) NOT NULL,
    seats INT DEFAULT 4,
    transmission ENUM('auto', 'manual') DEFAULT 'auto',
    fuel ENUM('gasoline', 'diesel', 'electric', 'hybrid') DEFAULT 'gasoline',
    location VARCHAR(100) DEFAULT 'hcm',
    pickup_address VARCHAR(255),
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    customer_id INT NOT NULL,
    start_date DATE NOT NULL,
    pickup_time TIME DEFAULT '08:00:00',
    end_date DATE NOT NULL,
    return_time TIME DEFAULT '18:00:00',
    pickup_location VARCHAR(255),
    return_location VARCHAR(255),
    pickup_type ENUM('self', 'delivery') DEFAULT 'self',
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    district VARCHAR(100),
    city VARCHAR(100),
    province VARCHAR(100),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_addresses_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS car_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    customer_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CÁC BẢNG BỔ TRỢ CHO CHỦ XE
-- ============================================

-- Yêu cầu rút tiền của chủ xe
CREATE TABLE IF NOT EXISTS payout_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    bank_name VARCHAR(120),
    bank_account VARCHAR(120),
    note VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phản hồi của chủ xe với đánh giá
CREATE TABLE IF NOT EXISTS review_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    owner_id INT NOT NULL,
    reply TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Báo cáo đánh giá (chủ xe flag review xấu / sai sự thật)
CREATE TABLE IF NOT EXISTS review_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    owner_id INT NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DỮ LIỆU MẪU
-- ============================================

INSERT INTO users (username, email, password, role, full_name, phone) VALUES
('admin', 'admin@carrental.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'admin', 'Quản Trị Viên', '0901234567'),
('user1', 'user1@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Nguyễn Văn A', '0902345678'),
('user2', 'user2@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Trần Thị B', '0903456789'),
('user3', 'user3@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Lê Văn C', '0904567890'),
('user4', 'user4@example.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Phạm Thị D', '0905678901');

INSERT INTO cars (owner_id, name, description, image, price_per_day, car_type, location, status) VALUES
(2, 'Toyota Vios 2023', 'Xe sedan 5 chỗ, tiết kiệm nhiên liệu, phù hợp đi phố và đường dài.', 'toyota-vios.jpg', 500000, 'sedan', 'hcm', 'available'),
(2, 'Honda City 2023', 'Xe sedan sang trọng, nội thất hiện đại, động cơ mạnh mẽ.', 'honda-city.jpg', 550000, 'sedan', 'hcm', 'available'),
(3, 'Ford Ranger 2022', 'Xe bán tải mạnh mẽ, phù hợp địa hình phức tạp và chở hàng.', 'ford-ranger.jpg', 1200000, 'pickup', 'hanoi', 'available'),
(3, 'Mazda CX-5 2023', 'SUV 7 chỗ rộng rãi, an toàn cho gia đình.', 'mazda-cx5.jpg', 900000, 'suv', 'hcm', 'available'),
(2, 'Hyundai Accent 2023', 'Xe sedan giá tốt, tiết kiệm nhiên liệu, hợp người mới lái.', 'hyundai-accent.jpg', 450000, 'sedan', 'danang', 'available'),
(3, 'Mitsubishi Xpander 2022', 'MPV 7 chỗ tiện nghi cho gia đình đông người.', 'mitsubishi-xpander.jpg', 700000, 'mpv', 'hcm', 'available'),
(4, 'Toyota Camry 2023', 'Sedan hạng sang, nội thất cao cấp.', 'toyota-camry-driver.jpg', 1500000, 'sedan', 'hcm', 'available'),
(4, 'Mercedes E-Class 2023', 'Xe sang trọng dành cho sự kiện và đón khách VIP.', 'mercedes-e-class-driver.jpg', 2500000, 'sedan', 'hcm', 'available'),
(5, 'Ford Transit 16 chỗ', 'Xe 16 chỗ rộng rãi, phù hợp tour hoặc đưa đón sân bay.', 'ford-transit-driver.jpg', 2000000, 'van', 'hanoi', 'available'),
(4, 'Honda CR-V 2023', 'SUV 7 chỗ tiện nghi cho gia đình.', 'honda-crv-driver.jpg', 1800000, 'suv', 'hcm', 'available'),
(5, 'Toyota Vios 2022', 'Xe sedan ổn định, bảo dưỡng định kỳ.', 'toyota-vios-longterm.jpg', 600000, 'sedan', 'hcm', 'available'),
(2, 'Hyundai Accent 2022', 'Xe tự lái giá tốt cho doanh nghiệp/cá nhân.', 'hyundai-accent-longterm.jpg', 600000, 'sedan', 'hanoi', 'available'),
(3, 'Mazda CX-5 2022', 'SUV 7 chỗ linh hoạt cho gia đình hoặc công ty.', 'mazda-cx5-longterm.jpg', 2000000, 'suv', 'hcm', 'available');

INSERT INTO bookings (car_id, customer_id, start_date, end_date, total_price, status) VALUES
(1, 4, '2025-10-20', '2025-10-23', 1500000, 'confirmed'),
(2, 5, '2025-10-18', '2025-10-20', 1100000, 'pending'),
(3, 4, '2025-10-25', '2025-10-28', 3600000, 'completed');

INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) VALUES
(1, 1500000, 'VNPAY', 'VNP202510201234567', 'completed'),
(3, 3600000, 'VNPAY', 'VNP202510151234568', 'completed');

INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_line, district, city, province, is_default) VALUES
(4, 'Nhà riêng', 'Lê Văn C', '0904567890', '123 Đường Lê Lợi', 'Phường Bến Thành', 'Quận 1', 'TP. Hồ Chí Minh', 1),
(4, 'Văn phòng', 'Lê Văn C', '0904567890', '45 Nguyễn Huệ', 'Phường Bến Nghé', 'Quận 1', 'TP. Hồ Chí Minh', 0),
(5, 'Nhà riêng', 'Phạm Thị D', '0905678901', '88 Võ Thị Sáu', 'Phường Đa Kao', 'Quận 1', 'TP. Hồ Chí Minh', 1);

INSERT INTO reviews (car_id, customer_id, booking_id, rating, comment) VALUES
(3, 4, 3, 5, 'Xe rất tốt, chủ xe nhiệt tình, sẽ thuê lại lần sau!'),
(1, 4, 1, 4, 'Xe đẹp, sạch sẽ. Giá hợp lý.');

