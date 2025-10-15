-- Schema cho Website Thuê Xe Tự Lái Online
-- Tạo database
CREATE DATABASE IF NOT EXISTS car_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE car_rental;

-- Bảng users: Quản lý người dùng (Khách hàng, Chủ xe, Admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'host', 'admin') DEFAULT 'customer',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng cars: Quản lý thông tin xe
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    price_per_day DECIMAL(10,2) NOT NULL,
    car_type VARCHAR(50) NOT NULL,
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
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

-- Dữ liệu mẫu

-- Thêm users mẫu (password: 123456)
INSERT INTO users (username, email, password, role, full_name, phone) VALUES
('admin', 'admin@carrental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Quản Trị Viên', '0901234567'),
('host1', 'host1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'host', 'Nguyễn Văn A', '0902345678'),
('host2', 'host2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'host', 'Trần Thị B', '0903456789'),
('customer1', 'customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Lê Văn C', '0904567890'),
('customer2', 'customer2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Phạm Thị D', '0905678901');

-- Thêm cars mẫu
INSERT INTO cars (owner_id, name, description, image, price_per_day, car_type, status) VALUES
(2, 'Toyota Vios 2023', 'Xe sedan 5 chỗ, tiết kiệm nhiên liệu, phù hợp đi phố và đường dài', 'toyota-vios.jpg', 500000, 'sedan', 'available'),
(2, 'Honda City 2023', 'Xe sedan sang trọng, nội thất hiện đại, động cơ mạnh mẽ', 'honda-city.jpg', 550000, 'sedan', 'available'),
(3, 'Ford Ranger 2022', 'Xe bán tải 5 chỗ, mạnh mẽ, phù hợp địa hình phức tạp', 'ford-ranger.jpg', 1200000, 'pickup', 'available'),
(3, 'Mazda CX-5 2023', 'Xe SUV 7 chỗ, rộng rãi, an toàn cho gia đình', 'mazda-cx5.jpg', 900000, 'suv', 'available'),
(2, 'Hyundai Accent 2023', 'Xe sedan 5 chỗ, giá cả phải chăng, phù hợp sinh viên', 'hyundai-accent.jpg', 450000, 'sedan', 'available'),
(3, 'Mitsubishi Xpander 2022', 'Xe MPV 7 chỗ, tiện nghi cho gia đình đông người', 'mitsubishi-xpander.jpg', 700000, 'mpv', 'available');

-- Thêm bookings mẫu
INSERT INTO bookings (car_id, customer_id, start_date, end_date, total_price, status) VALUES
(1, 4, '2025-10-20', '2025-10-23', 1500000, 'confirmed'),
(2, 5, '2025-10-18', '2025-10-20', 1100000, 'pending'),
(3, 4, '2025-10-25', '2025-10-28', 3600000, 'completed');

-- Thêm payments mẫu
INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) VALUES
(1, 1500000, 'VNPAY', 'VNP202510201234567', 'completed'),
(3, 3600000, 'VNPAY', 'VNP202510151234568', 'completed');

-- Thêm reviews mẫu
INSERT INTO reviews (car_id, customer_id, booking_id, rating, comment) VALUES
(3, 4, 3, 5, 'Xe rất tốt, chủ xe nhiệt tình, sẽ thuê lại lần sau!'),
(1, 4, 1, 4, 'Xe đẹp, sạch sẽ. Giá hợp lý.');


