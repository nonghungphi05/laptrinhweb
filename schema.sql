-- Schema cho Website Thuê Xe Tự Lái
-- Đảm bảo encoding UTF-8 khi import
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

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


INSERT INTO users (id, username, email, password, role, full_name, phone, avatar, created_at) VALUES
-- Admin
(1, 'admin', 'admin@carrental.vn', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'admin', 'Quản Trị Viên', '0901234567', NULL, '2023-01-01 00:00:00'),

-- Host 1: Chuyên xe sang/cao cấp
(2, 'hoangminh', 'hoangminh@gmail.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'host', 'Nguyễn Hoàng Minh', '0912345678', NULL, '2023-03-15 10:30:00'),

-- Host 2: Chuyên xe gia đình/SUV
(3, 'thanhnga', 'thanhnga@gmail.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'host', 'Trần Thanh Nga', '0923456789', NULL, '2023-06-20 14:00:00'),

-- Host 3: Chuyên xe điện/hybrid
(4, 'duclong', 'duclong@gmail.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'host', 'Lê Đức Long', '0934567890', NULL, '2024-01-10 09:00:00'),

-- User thuê xe
(5, 'khachhang', 'khachhang@gmail.com', '$2y$10$H47mtZqeAzFQQ9W2yooUZeVMuappZxf4zYzZ49gWkg.mJ/9T2KlpC', 'user', 'Phạm Quốc Bảo', '0945678901', NULL, '2024-06-01 11:00:00');

-- ==============================================
-- CARS: 34 xe từ folder uploads
-- owner_id: 2 (hoangminh), 3 (thanhnga), 4 (duclong)
-- ==============================================

INSERT INTO cars (id, owner_id, name, description, image, price_per_day, car_type, seats, transmission, fuel, location, pickup_address, status, created_at) VALUES

-- ============ HOST 1: Nguyễn Hoàng Minh (xe sang, sedan cao cấp) ============

(1, 2, 'Mercedes S400L 2015', 
'Mercedes-Benz S400L - dòng sedan hạng sang flagship của Mercedes. Nội thất da cao cấp, ghế massage, cửa sổ trời toàn cảnh. Động cơ V6 hybrid êm ái và tiết kiệm nhiên liệu. Xe được bảo dưỡng định kỳ tại hãng, sạch sẽ, thơm tho.', 
'MERCEDESS400l2015.jpg', 2800000, 'sedan', 5, 'auto', 'gasoline', 'hcm', '456 Nguyễn Văn Trỗi, Phú Nhuận, TP.HCM', 'available', '2023-03-20 10:00:00'),

(2, 2, 'Mercedes E200 Exclusive 2017', 
'Mercedes E200 Exclusive - dòng sedan hạng sang phân khúc E. Thiết kế sang trọng, nội thất tinh tế với màn hình đôi 12.3 inch. An toàn với hệ thống phanh ABS, ESP, 9 túi khí. Phù hợp công tác, họp hành, đón khách VIP.', 
'MERCEDESE200EXCLUSIVE2017.jpg', 1800000, 'sedan', 5, 'auto', 'gasoline', 'hcm', '789 Cách Mạng Tháng 8, Quận 3, TP.HCM', 'available', '2023-04-15 11:00:00'),

(3, 2, 'Audi A6 2020', 
'Audi A6 2020 - sedan thể thao sang trọng với động cơ 2.0 TFSI mạnh mẽ 245 mã lực. Virtual cockpit hiện đại, nội thất bọc da Nappa. Quattro AWD giúp xe vận hành ổn định. Xe còn mới, nội thất nguyên bản.', 
'AUDIA62020.jpg', 2200000, 'sedan', 5, 'auto', 'gasoline', 'hcm', '123 Điện Biên Phủ, Bình Thạnh, TP.HCM', 'available', '2023-05-10 09:00:00'),

(4, 2, 'Audi A4 2019', 
'Audi A4 - sedan sang trọng cỡ trung với thiết kế tinh tế, nội thất chất lượng cao. Động cơ TFSI 2.0L tiết kiệm nhiên liệu. Hệ thống MMI Navigation Plus với màn hình cảm ứng. Xe đẹp, bảo dưỡng đầy đủ.', 
'AUDIA42019.jpg', 1600000, 'sedan', 5, 'auto', 'gasoline', 'hcm', '234 Lê Văn Sỹ, Quận 3, TP.HCM', 'available', '2023-06-01 14:00:00'),

(5, 2, 'BMW 520i 2012', 
'BMW 520i - sedan thể thao hạng sang dòng 5 Series. Động cơ 2.0L TwinPower Turbo 184 mã lực. iDrive system với màn hình 10.2 inch. Nội thất da sang trọng, ghế chỉnh điện nhớ vị trí. Xe được chăm sóc kỹ lưỡng.', 
'BMW520i2012.jpg', 1200000, 'sedan', 5, 'auto', 'gasoline', 'hanoi', '567 Kim Mã, Ba Đình, Hà Nội', 'available', '2023-07-15 10:00:00'),

(6, 2, 'BMW X6 2009', 
'BMW X6 - SUV coupe thể thao đầy cá tính. Thiết kế độc đáo kết hợp SUV và coupe. Động cơ V6 mạnh mẽ, vận hành êm ái. Nội thất rộng rãi, tiện nghi cao cấp. Xe đã qua bảo dưỡng toàn bộ, sẵn sàng phục vụ.', 
'BMWX62009.jpg', 1400000, 'suv', 5, 'auto', 'gasoline', 'hanoi', '890 Láng Hạ, Đống Đa, Hà Nội', 'available', '2023-08-01 09:00:00'),

(7, 2, 'Mazda 3 Deluxe 2022', 
'Mazda 3 - sedan thiết kế Kodo đẹp mắt, nội thất cao cấp vượt tầm giá. Động cơ Skyactiv-G 1.5L tiết kiệm nhiên liệu. An toàn với GVC+, camera lùi, cảm biến sau. Xe mới, còn bảo hành hãng.', 
'MAZDA3Deluxe2022.jpg', 850000, 'sedan', 5, 'auto', 'gasoline', 'hcm', '321 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', 'available', '2023-09-10 11:00:00'),

(8, 2, 'MG5 Standard 2023', 
'MG5 - sedan cỡ C với giá cạnh tranh, trang bị đầy đủ. Thiết kế trẻ trung, năng động. Động cơ 1.5L 112 mã lực tiết kiệm xăng. Màn hình giải trí 10.1 inch, kết nối Apple CarPlay. Xe mới 100%, chưa qua sử dụng.', 
'MG5STANDARD2023.jpg', 700000, 'sedan', 5, 'auto', 'gasoline', 'danang', '456 Nguyễn Văn Linh, Hải Châu, Đà Nẵng', 'available', '2023-10-01 08:00:00'),

(9, 2, 'Kia Morning 2022', 
'Kia Morning - xe đô thị nhỏ gọn, cực kỳ linh hoạt trong phố đông. Tiết kiệm xăng chỉ 5L/100km. Phù hợp di chuyển nội thành, đỗ xe dễ dàng. Xe sạch sẽ, bảo dưỡng định kỳ, sẵn sàng giao xe.', 
'KIAMORNING2022.jpg', 450000, 'sedan', 5, 'manual', 'gasoline', 'cantho', '159 Nguyễn Văn Linh, Ninh Kiều, Cần Thơ', 'available', '2023-11-15 10:00:00'),

(10, 2, 'Mitsubishi Attrage 2023', 
'Mitsubishi Attrage - sedan cỡ B tiết kiệm nhiên liệu nhất phân khúc chỉ 4.9L/100km. Thiết kế Dynamic Shield mới mẻ. Cốp xe rộng 450L, ghế gập linh hoạt. Phù hợp chạy Grab/công việc/đi lại hàng ngày.', 
'MITSUBISHIATTRAGE2023.jpg', 550000, 'sedan', 5, 'auto', 'gasoline', 'danang', '789 Điện Biên Phủ, Thanh Khê, Đà Nẵng', 'available', '2023-12-01 09:00:00'),

(11, 2, 'VinFast Limo Green 2025', 
'VinFast Limo - sedan điện cao cấp nhất của VinFast. Pin 88kWh cho tầm xa 450km. Hỗ trợ sạc nhanh 35 phút (10-70%). Nội thất sang trọng, hệ thống ADAS tiên tiến. Xe mới 100%, trải nghiệm xe điện cao cấp.', 
'VINFASTLIMOGREEN2025.jpg', 1500000, 'sedan', 5, 'auto', 'electric', 'hcm', '222 Nguyễn Hữu Thọ, Quận 7, TP.HCM', 'available', '2024-01-20 10:00:00'),

-- ============ HOST 2: Trần Thanh Nga (xe gia đình, SUV, MPV) ============

(12, 3, 'Mazda CX-8 Premium 2024', 
'Mazda CX-8 Premium - SUV 7 chỗ cao cấp với thiết kế Kodo tinh tế. Nội thất da Nappa, ghế thông hơi, điều hòa 3 vùng. Động cơ Skyactiv-G 2.5L 188 mã lực. Hàng ghế 3 rộng rãi cho người lớn. Xe mới tinh, còn bảo hành.', 
'MAZDACX8PREMIUM2024.jpg', 1400000, 'suv', 7, 'auto', 'gasoline', 'phuquoc', '111 Trần Hưng Đạo, Dương Đông, Phú Quốc', 'available', '2023-06-25 14:00:00'),

(13, 3, 'Suzuki XL7 2022', 
'Suzuki XL7 - SUV 7 chỗ giá tốt, gầm cao 200mm vượt đường xấu dễ dàng. Động cơ 1.5L tiết kiệm chỉ 6L/100km. Nội thất rộng rãi, hàng ghế 3 gập phẳng. Phù hợp gia đình đông người, du lịch.', 
'SUZUKIXL72022.jpg', 750000, 'suv', 7, 'auto', 'gasoline', 'nhatrang', '333 Trần Phú, Lộc Thọ, Nha Trang', 'available', '2023-07-10 09:00:00'),

(14, 3, 'Honda CR-V G 2018', 
'Honda CR-V G - SUV 7 chỗ bán chạy nhất Việt Nam. Động cơ VTEC Turbo 1.5L mạnh mẽ 188 mã lực. An toàn với Honda Sensing. Cốp điện, ghế da, cruise control. Xe nguyên zin, bảo dưỡng định kỳ tại hãng.', 
'HONDACRVG2018.jpg', 950000, 'suv', 7, 'auto', 'gasoline', 'dalat', '555 Phan Đình Phùng, Phường 2, Đà Lạt', 'available', '2023-08-05 10:00:00'),

(15, 3, 'Kia Sorento Deluxe 2018', 
'Kia Sorento Deluxe - SUV 7 chỗ máy dầu tiết kiệm. Động cơ CRDi 2.2L Diesel mạnh mẽ, moment xoắn cao. Nội thất rộng rãi, ghế da cao cấp. Phù hợp đi tỉnh, đường dài. Xe còn mới, tiết kiệm chi phí.', 
'KIASORENTODELUXE2018.jpg', 900000, 'suv', 7, 'auto', 'diesel', 'hanoi', '777 Nguyễn Trãi, Thanh Xuân, Hà Nội', 'available', '2023-09-15 11:00:00'),

(16, 3, 'Ford EcoSport 2021', 
'Ford EcoSport - SUV cỡ nhỏ linh hoạt trong phố. Gầm cao 200mm, phù hợp đường ngập. Động cơ EcoBoost 1.0L Turbo tiết kiệm. Cốp mở ngang tiện lợi, lốp dự phòng treo sau. Xe đẹp, nội thất nguyên bản.', 
'FORDECOSPORT2021.jpg', 650000, 'suv', 5, 'auto', 'gasoline', 'cantho', '999 Mậu Thân, Ninh Kiều, Cần Thơ', 'available', '2023-10-20 08:00:00'),

(17, 3, 'Peugeot 3008 2020', 
'Peugeot 3008 - SUV châu Âu với thiết kế i-Cockpit độc đáo. Nội thất sang trọng, màn hình 8 inch, vô lăng nhỏ gọn. Động cơ 1.6L Turbo 165 mã lực. Xe nhập Pháp, chất lượng châu Âu, bảo dưỡng đầy đủ.', 
'PEUGEOT30082020.jpg', 1000000, 'suv', 5, 'auto', 'gasoline', 'nhatrang', '246 Yersin, Lộc Thọ, Nha Trang', 'available', '2023-11-05 14:00:00'),

(18, 3, 'Peugeot 2008 2022', 
'Peugeot 2008 - SUV cỡ B cao cấp với thiết kế châu Âu nổi bật. i-Cockpit 3D hiện đại nhất phân khúc. Động cơ PureTech 1.2L Turbo tiết kiệm. Xe nhập khẩu nguyên chiếc, còn bảo hành hãng.', 
'PEUGEOT20082022.jpg', 850000, 'suv', 5, 'auto', 'gasoline', 'dalat', '135 Nguyễn Chí Thanh, Phường 1, Đà Lạt', 'available', '2023-12-10 09:00:00'),

(19, 3, 'Ford Territory Titanium X 2023', 
'Ford Territory Titanium X - SUV cỡ C hiện đại với màn hình 12.3 inch. Động cơ EcoBoost 1.5L Turbo 160 mã lực. Co-Pilot360 an toàn chủ động. Xe mới, đầy đủ option, phù hợp gia đình trẻ.', 
'FORDTERRITORYTITANIUMX2023.jpg', 1050000, 'suv', 5, 'auto', 'gasoline', 'phuquoc', '468 Nguyễn Trung Trực, Dương Đông, Phú Quốc', 'available', '2024-01-15 10:00:00'),

(20, 3, 'Volkswagen T-Cross 2024', 
'Volkswagen T-Cross - SUV nhỏ gọn từ Đức với chất lượng châu Âu. Thiết kế trẻ trung, năng động. Động cơ TSI 1.5L Turbo tiết kiệm. An toàn với 6 túi khí, ESP. Xe mới 100%, nhập khẩu nguyên chiếc.', 
'VOLKSWAGENT-CROSS2024.jpg', 900000, 'suv', 5, 'auto', 'gasoline', 'phuquoc', '579 Trần Hưng Đạo, An Thới, Phú Quốc', 'available', '2024-02-01 11:00:00'),

(21, 3, 'Chevrolet Captiva 2009', 
'Chevrolet Captiva - SUV 7 chỗ bền bỉ của Mỹ. Động cơ 2.4L mạnh mẽ, vận hành êm ái. Nội thất rộng rãi, cốp lớn. Xe đã qua đại tu, thay mới nhiều chi tiết. Giá tốt, phù hợp đi du lịch nhóm.', 
'CHEVROLETCAPTIVA2009.jpg', 600000, 'suv', 7, 'auto', 'gasoline', 'cantho', '321 Lê Lợi, Ninh Kiều, Cần Thơ', 'available', '2023-07-20 10:00:00'),

(22, 3, 'Hyundai Custin Premier 2024', 
'Hyundai Custin Premier - MPV 7 chỗ cao cấp nhất với ghế thương gia hàng 2. Cửa trượt điện 2 bên, ghế massage. Động cơ Smartstream 2.0L. Màn hình đôi 10.4 inch. Xe mới 100%, sang trọng như xe hạng sang.', 
'HYUNDAICUSTINPREMIER2024.jpg', 1300000, 'mpv', 7, 'auto', 'gasoline', 'hcm', '753 Lý Tự Trọng, Quận 1, TP.HCM', 'available', '2024-03-01 09:00:00'),

(23, 3, 'Hyundai Custin Luxury 2024', 
'Hyundai Custin Luxury - MPV 7 chỗ rộng rãi nhất phân khúc. Cửa trượt điện, ghế da cao cấp. Động cơ Smartstream 2.0L 156 mã lực. An toàn với SmartSense. Xe gia đình lý tưởng, đi du lịch thoải mái.', 
'HYUNDAICUSTINLUXURY2024.jpg', 1100000, 'mpv', 7, 'auto', 'gasoline', 'hcm', '864 Nguyễn Đình Chiểu, Quận 3, TP.HCM', 'available', '2024-03-15 14:00:00'),

(24, 3, 'Mitsubishi Xpander 2022', 
'Mitsubishi Xpander - MPV 7 chỗ bán chạy nhất Việt Nam. Thiết kế Dynamic Shield, gầm cao 205mm. Động cơ MIVEC 1.5L tiết kiệm chỉ 6.5L/100km. Hàng ghế 3 rộng, cốp 227L. Xe đẹp, bảo dưỡng đầy đủ.', 
'MITSUBISHIXPANDER2022.jpg', 750000, 'mpv', 7, 'auto', 'gasoline', 'cantho', '951 30 Tháng 4, Ninh Kiều, Cần Thơ', 'available', '2023-08-25 10:00:00'),

(25, 3, 'Kia Carens Luxury 2024', 
'Kia Carens Luxury - MPV 7 chỗ thiết kế thể thao độc đáo. Nội thất hiện đại, màn hình 10.25 inch. Ghế thông hơi, điều hòa 2 vùng. Động cơ Smartstream 1.5L Turbo 138 mã lực. Xe mới, còn bảo hành.', 
'KIACARENSLUXURY2024.jpg', 900000, 'mpv', 7, 'auto', 'gasoline', 'danang', '147 Trần Phú, Hải Châu, Đà Nẵng', 'available', '2024-02-20 08:00:00'),

(26, 3, 'Toyota Veloz Cross 2022', 
'Toyota Veloz Cross - MPV 7 chỗ thể thao từ Toyota. Thiết kế góc cạnh nam tính. Toyota Safety Sense 3.0 an toàn hàng đầu. Động cơ 1.5L 106 mã lực. Ghế da, cruise control, phanh tay điện tử. Xe zin, bảo dưỡng hãng.', 
'TOYOTAVELOZCROSS2022.jpg', 850000, 'mpv', 7, 'auto', 'gasoline', 'nhatrang', '258 Thống Nhất, Phường Phương Sài, Nha Trang', 'available', '2023-10-05 11:00:00'),

(27, 3, 'Toyota Corolla Cross HV 2022', 
'Toyota Corolla Cross Hybrid - SUV hybrid tiết kiệm xăng nhất phân khúc chỉ 4.5L/100km. Động cơ Hybrid 1.8L êm ái. Toyota Safety Sense 2.0. Cốp điện, ghế da, màn hình 9 inch. Xe còn mới, bảo hành hybrid 8 năm.', 
'TOYOTACOROLLACROSSHV2022.jpg', 1000000, 'suv', 5, 'auto', 'hybrid', 'dalat', '369 Phan Bội Châu, Phường 1, Đà Lạt', 'available', '2023-11-20 09:00:00'),

-- ============ HOST 3: Lê Đức Long (xe điện, bán tải, đặc biệt) ============

(28, 4, 'VinFast VF8 Eco 2023', 
'VinFast VF8 Eco - SUV điện 5 chỗ đầu tiên của VinFast. Pin 82kWh cho tầm xa 420km. Hỗ trợ sạc nhanh 24 phút (10-70%). ADAS hỗ trợ lái tiên tiến. Nội thất rộng rãi, cốp 604L. Xe điện thế hệ mới, trải nghiệm đỉnh cao.', 
'VINFASTVF8ECO2023.jpg', 1200000, 'suv', 5, 'auto', 'electric', 'hcm', '147 Nguyễn Hữu Cảnh, Bình Thạnh, TP.HCM', 'available', '2024-01-15 10:00:00'),

(29, 4, 'VinFast VF7 Plus 2024', 
'VinFast VF7 Plus - SUV điện cỡ C hiện đại. Pin 75.3kWh cho tầm xa 431km. Thiết kế coupe SUV thể thao. ADAS 11 tính năng an toàn. Màn hình 12.9 inch, AR-HUD. Xe mới 100%, trạm sạc miễn phí VinFast.', 
'VINFASTVF7PLUS2024.jpg', 1100000, 'suv', 5, 'auto', 'electric', 'phuquoc', '258 Cầu Cạn, Dương Đông, Phú Quốc', 'available', '2024-02-10 09:00:00'),

(30, 4, 'VinFast VF6 Plus 2024', 
'VinFast VF6 Plus - SUV điện cỡ B nhỏ gọn cho đô thị. Pin 59.6kWh cho tầm xa 399km. Thiết kế hiện đại, năng động. ADAS đầy đủ. Phù hợp di chuyển nội thành, sạc đêm tại nhà. Xe mới, còn bảo hành pin 10 năm.', 
'VINFASTVF6PLUS2024.jpg', 900000, 'suv', 5, 'auto', 'electric', 'dalat', '369 Hùng Vương, Phường 10, Đà Lạt', 'available', '2024-03-05 14:00:00'),

(31, 4, 'VinFast VF e34 2022', 
'VinFast VF e34 - xe điện đô thị phổ thông đầu tiên tại Việt Nam. Pin 42kWh cho tầm xa 285km. Chi phí sạc chỉ 25.000đ/100km. Kết nối app VinFast thông minh. Xe đẹp, lựa chọn xanh cho môi trường.', 
'VINFASTVFE342022.jpg', 600000, 'sedan', 5, 'auto', 'electric', 'hanoi', '456 Giải Phóng, Hoàng Mai, Hà Nội', 'available', '2023-09-01 10:00:00'),

(32, 4, 'Ford Ranger XLS 4x2 2021', 
'Ford Ranger XLS 4x2 - bán tải số 1 Việt Nam. Động cơ Bi-Turbo 2.0L 170 mã lực. Thùng xe rộng, tải trọng 740kg. Gầm cao, vượt mọi địa hình. An toàn với 6 túi khí, ESC, HSA. Xe đẹp, bảo dưỡng định kỳ.', 
'FORDRANGERXLS4x22021.jpg', 1000000, 'pickup', 5, 'auto', 'diesel', 'nhatrang', '741 Nguyễn Thiện Thuật, Lộc Thọ, Nha Trang', 'available', '2023-08-15 11:00:00'),

(33, 4, 'Mitsubishi Triton 4x2 2022', 
'Mitsubishi Triton 4x2 - bán tải mạnh mẽ với động cơ MIVEC 2.4L diesel 178 mã lực. Hệ thống Super Select 4WD-II. Thùng xe lót nhựa, chống trầy. Phù hợp chở hàng, đi công trình. Xe bền bỉ, ít hỏng vặt.', 
'MITSUBISHITRITON4x22022.jpg', 900000, 'pickup', 5, 'manual', 'diesel', 'hanoi', '852 Phạm Văn Đồng, Bắc Từ Liêm, Hà Nội', 'available', '2023-09-20 09:00:00'),

(34, 4, 'Chevrolet Colorado 4x4 2017', 
'Chevrolet Colorado 4x4 - bán tải Mỹ mạnh mẽ với động cơ Duramax 2.8L diesel. Hệ thống 4x4 toàn thời gian. Nội thất da, ghế chỉnh điện. Thùng xe rộng, lót bọc nhựa. Xe đã qua kiểm tra 150 điểm, sẵn sàng chinh phục.', 
'CHEVROLETCOLORADO4x42017.jpg', 850000, 'pickup', 5, 'manual', 'diesel', 'danang', '963 Ngô Quyền, Sơn Trà, Đà Nẵng', 'available', '2023-10-10 08:00:00');

-- ==============================================
-- CAR_IMAGES: 3 hình cho mỗi xe
-- ==============================================

INSERT INTO car_images (car_id, file_path, is_primary) VALUES
-- Xe 1: Mercedes S400L 2015
(1, 'MERCEDESS400l2015.jpg', 1),
(1, 'MERCEDESS400l2015(1).jpg', 0),
(1, 'MERCEDESS400l2015(2).jpg', 0),

-- Xe 2: Mercedes E200 Exclusive 2017
(2, 'MERCEDESE200EXCLUSIVE2017.jpg', 1),
(2, 'MERCEDESE200EXCLUSIVE2017(1).jpg', 0),
(2, 'MERCEDESE200EXCLUSIVE2017(2).jpg', 0),

-- Xe 3: Audi A6 2020
(3, 'AUDIA62020.jpg', 1),
(3, 'AUDIA62020(1).jpg', 0),
(3, 'AUDIA62020(2).jpg', 0),

-- Xe 4: Audi A4 2019
(4, 'AUDIA42019.jpg', 1),
(4, 'AUDIA42019(1).jpg', 0),
(4, 'AUDIA42019(2).jpg', 0),

-- Xe 5: BMW 520i 2012
(5, 'BMW520i2012.jpg', 1),
(5, 'BMW520i2012(1).jpg', 0),
(5, 'BMW520i2012(2).jpg', 0),

-- Xe 6: BMW X6 2009
(6, 'BMWX62009.jpg', 1),
(6, 'BMWX62009(1).jpg', 0),
(6, 'BMWX62009(2).jpg', 0),

-- Xe 7: Mazda 3 Deluxe 2022
(7, 'MAZDA3Deluxe2022.jpg', 1),
(7, 'MAZDA3Deluxe2022(1).jpg', 0),
(7, 'MAZDA3Deluxe2022(2).jpg', 0),

-- Xe 8: MG5 Standard 2023
(8, 'MG5STANDARD2023.jpg', 1),
(8, 'MG5STANDARD2023(1).jpg', 0),
(8, 'MG5STANDARD2023(2).jpg', 0),

-- Xe 9: Kia Morning 2022
(9, 'KIAMORNING2022.jpg', 1),
(9, 'KIAMORNING2022(1).jpg', 0),
(9, 'KIAMORNING2022(2).jpg', 0),

-- Xe 10: Mitsubishi Attrage 2023
(10, 'MITSUBISHIATTRAGE2023.jpg', 1),
(10, 'MITSUBISHIATTRAGE2023(1).jpg', 0),
(10, 'MITSUBISHIATTRAGE2023(2).jpg', 0),

-- Xe 11: VinFast Limo Green 2025
(11, 'VINFASTLIMOGREEN2025.jpg', 1),
(11, 'VINFASTLIMOGREEN2025(1).jpg', 0),
(11, 'VINFASTLIMOGREEN2025(2).jpg', 0),

-- Xe 12: Mazda CX-8 Premium 2024
(12, 'MAZDACX8PREMIUM2024.jpg', 1),
(12, 'MAZDACX8PREMIUM2024(1).jpg', 0),
(12, 'MAZDACX8PREMIUM2024(2).jpg', 0),

-- Xe 13: Suzuki XL7 2022
(13, 'SUZUKIXL72022.jpg', 1),
(13, 'SUZUKIXL72022(1).jpg', 0),
(13, 'SUZUKIXL72022(2).jpg', 0),

-- Xe 14: Honda CR-V G 2018
(14, 'HONDACRVG2018.jpg', 1),
(14, 'HONDACRVG2018(1).jpg', 0),
(14, 'HONDACRVG2018(2).jpg', 0),

-- Xe 15: Kia Sorento Deluxe 2018
(15, 'KIASORENTODELUXE2018.jpg', 1),
(15, 'KIASORENTODELUXE2018(1).jpg', 0),
(15, 'KIASORENTODELUXE2018(2).jpg', 0),

-- Xe 16: Ford EcoSport 2021
(16, 'FORDECOSPORT2021.jpg', 1),
(16, 'FORDECOSPORT2021(1).jpg', 0),
(16, 'FORDECOSPORT2021(2).jpg', 0),

-- Xe 17: Peugeot 3008 2020
(17, 'PEUGEOT30082020.jpg', 1),
(17, 'PEUGEOT30082020(1).jpg', 0),
(17, 'PEUGEOT30082020(2).jpg', 0),

-- Xe 18: Peugeot 2008 2022
(18, 'PEUGEOT20082022.jpg', 1),
(18, 'PEUGEOT20082022(1).jpg', 0),
(18, 'PEUGEOT20082022(2).jpg', 0),

-- Xe 19: Ford Territory Titanium X 2023
(19, 'FORDTERRITORYTITANIUMX2023.jpg', 1),
(19, 'FORDTERRITORYTITANIUMX2023(1).jpg', 0),
(19, 'FORDTERRITORYTITANIUMX2023(2).jpg', 0),

-- Xe 20: Volkswagen T-Cross 2024
(20, 'VOLKSWAGENT-CROSS2024.jpg', 1),
(20, 'VOLKSWAGENT-CROSS2024(1).jpg', 0),
(20, 'VOLKSWAGENT-CROSS2024(2).jpg', 0),

-- Xe 21: Chevrolet Captiva 2009
(21, 'CHEVROLETCAPTIVA2009.jpg', 1),
(21, 'CHEVROLETCAPTIVA2009(1).jpg', 0),
(21, 'CHEVROLETCAPTIVA2009(2).jpg', 0),

-- Xe 22: Hyundai Custin Premier 2024
(22, 'HYUNDAICUSTINPREMIER2024.jpg', 1),
(22, 'HYUNDAICUSTINPREMIER2024(1).jpg', 0),
(22, 'HYUNDAICUSTINPREMIER2024(2).jpg', 0),

-- Xe 23: Hyundai Custin Luxury 2024
(23, 'HYUNDAICUSTINLUXURY2024.jpg', 1),
(23, 'HYUNDAICUSTINLUXURY2024(1).jpg', 0),
(23, 'HYUNDAICUSTINLUXURY2024(2).jpg', 0),

-- Xe 24: Mitsubishi Xpander 2022
(24, 'MITSUBISHIXPANDER2022.jpg', 1),
(24, 'MITSUBISHIXPANDER2022(1).jpg', 0),
(24, 'MITSUBISHIXPANDER2022(2).jpg', 0),

-- Xe 25: Kia Carens Luxury 2024
(25, 'KIACARENSLUXURY2024.jpg', 1),
(25, 'KIACARENSLUXURY2024(1).jpg', 0),
(25, 'KIACARENSLUXURY2024(2).jpg', 0),

-- Xe 26: Toyota Veloz Cross 2022
(26, 'TOYOTAVELOZCROSS2022.jpg', 1),
(26, 'TOYOTAVELOZCROSS2022(1).jpg', 0),
(26, 'TOYOTAVELOZCROSS2022(2).jpg', 0),

-- Xe 27: Toyota Corolla Cross HV 2022
(27, 'TOYOTACOROLLACROSSHV2022.jpg', 1),
(27, 'TOYOTACOROLLACROSSHV2022(1).jpg', 0),
(27, 'TOYOTACOROLLACROSSHV2022(2).jpg', 0),

-- Xe 28: VinFast VF8 Eco 2023
(28, 'VINFASTVF8ECO2023.jpg', 1),
(28, 'VINFASTVF8ECO2023(1).jpg', 0),
(28, 'VINFASTVF8ECO2023(2).jpg', 0),

-- Xe 29: VinFast VF7 Plus 2024
(29, 'VINFASTVF7PLUS2024.jpg', 1),
(29, 'VINFASTVF7PLUS2024(1).jpg', 0),
(29, 'VINFASTVF7PLUS2024(2).jpg', 0),

-- Xe 30: VinFast VF6 Plus 2024
(30, 'VINFASTVF6PLUS2024.jpg', 1),
(30, 'VINFASTVF6PLUS2024(1).jpg', 0),
(30, 'VINFASTVF6PLUS2024(2).jpg', 0),

-- Xe 31: VinFast VF e34 2022
(31, 'VINFASTVFE342022.jpg', 1),
(31, 'VINFASTVFE342022(1).jpg', 0),
(31, 'VINFASTVFE342022(2).jpg', 0),

-- Xe 32: Ford Ranger XLS 4x2 2021
(32, 'FORDRANGERXLS4x22021.jpg', 1),
(32, 'FORDRANGERXLS4x22021(1).jpg', 0),
(32, 'FORDRANGERXLS4x22021(2).jpg', 0),

-- Xe 33: Mitsubishi Triton 4x2 2022
(33, 'MITSUBISHITRITON4x22022.jpg', 1),
(33, 'MITSUBISHITRITON4x22022(1).jpg', 0),
(33, 'MITSUBISHITRITON4x22022(2).jpg', 0),

-- Xe 34: Chevrolet Colorado 4x4 2017
(34, 'CHEVROLETCOLORADO4x42017.jpg', 1),
(34, 'CHEVROLETCOLORADO4x42017(1).jpg', 0),
(34, 'CHEVROLETCOLORADO4x42017(2).jpg', 0);

-- ==============================================
-- USER_ADDRESSES: Địa chỉ của users
-- ==============================================

INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_line, district, city, province, is_default) VALUES
-- Địa chỉ Host 1: Nguyễn Hoàng Minh
(2, 'Nhà riêng', 'Nguyễn Hoàng Minh', '0912345678', '456 Nguyễn Văn Trỗi', 'Phường 8', 'Quận Phú Nhuận', 'TP. Hồ Chí Minh', 1),
(2, 'Garage xe', 'Nguyễn Hoàng Minh', '0912345678', '789 Cách Mạng Tháng 8', 'Phường 6', 'Quận 3', 'TP. Hồ Chí Minh', 0),

-- Địa chỉ Host 2: Trần Thanh Nga
(3, 'Nhà riêng', 'Trần Thanh Nga', '0923456789', '111 Trường Chinh', 'Phường 12', 'Quận Tân Bình', 'TP. Hồ Chí Minh', 1),
(3, 'Văn phòng', 'Trần Thanh Nga', '0923456789', '333 Quang Trung', 'Phường 10', 'Quận Gò Vấp', 'TP. Hồ Chí Minh', 0),

-- Địa chỉ Host 3: Lê Đức Long
(4, 'Nhà riêng', 'Lê Đức Long', '0934567890', '147 Nguyễn Hữu Cảnh', 'Phường 22', 'Quận Bình Thạnh', 'TP. Hồ Chí Minh', 1),
(4, 'Bãi xe điện', 'Lê Đức Long', '0934567890', '258 Đinh Tiên Hoàng', 'Phường Đa Kao', 'Quận 1', 'TP. Hồ Chí Minh', 0),

-- Địa chỉ User: Phạm Quốc Bảo
(5, 'Nhà riêng', 'Phạm Quốc Bảo', '0945678901', '123 Lý Thường Kiệt', 'Phường 7', 'Quận Tân Bình', 'TP. Hồ Chí Minh', 1),
(5, 'Công ty', 'Phạm Quốc Bảo', '0945678901', '456 Nguyễn Thị Minh Khai', 'Phường Đa Kao', 'Quận 1', 'TP. Hồ Chí Minh', 0),
(5, 'Nhà bố mẹ', 'Phạm Quốc Bảo', '0945678901', '789 Trần Hưng Đạo', 'Phường 2', 'Quận 5', 'TP. Hồ Chí Minh', 0);

-- ==============================================
-- BOOKINGS: Các đơn đặt xe mẫu
-- Sử dụng CURDATE() để có dữ liệu phù hợp với thời gian hiện tại
-- ==============================================

INSERT INTO bookings (id, car_id, customer_id, start_date, pickup_time, end_date, return_time, pickup_location, return_location, pickup_type, total_price, status, created_at) VALUES
-- Booking đã hoàn thành THÁNG NÀY (xe Mercedes S400L) - Host 1
(1, 1, 5, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:00:00', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '18:00:00', '456 Nguyễn Văn Trỗi, Phú Nhuận, TP.HCM', '456 Nguyễn Văn Trỗi, Phú Nhuận, TP.HCM', 'self', 5600000, 'completed', DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Booking đã hoàn thành THÁNG NÀY (xe Mazda CX-8) - Host 2
(2, 12, 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY), '07:00:00', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '20:00:00', '111 Trường Chinh, Tân Bình, TP.HCM', '111 Trường Chinh, Tân Bình, TP.HCM', 'self', 7000000, 'completed', DATE_SUB(NOW(), INTERVAL 12 DAY)),

-- Booking đã hoàn thành THÁNG NÀY (xe VinFast VF8) - Host 3
(3, 28, 5, DATE_SUB(CURDATE(), INTERVAL 8 DAY), '09:00:00', DATE_SUB(CURDATE(), INTERVAL 4 DAY), '17:00:00', '147 Nguyễn Hữu Cảnh, Bình Thạnh, TP.HCM', '147 Nguyễn Hữu Cảnh, Bình Thạnh, TP.HCM', 'delivery', 4800000, 'completed', DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Booking đã xác nhận (xe Mitsubishi Xpander - sắp diễn ra) - Host 2
(4, 24, 5, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '08:00:00', DATE_ADD(CURDATE(), INTERVAL 10 DAY), '18:00:00', '951 3 Tháng 2, Quận 10, TP.HCM', '951 3 Tháng 2, Quận 10, TP.HCM', 'self', 3750000, 'confirmed', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Booking đang chờ xác nhận (xe Honda CR-V) - Host 2
(5, 14, 5, DATE_ADD(CURDATE(), INTERVAL 10 DAY), '10:00:00', DATE_ADD(CURDATE(), INTERVAL 15 DAY), '16:00:00', '555 Xô Viết Nghệ Tĩnh, Bình Thạnh, TP.HCM', '555 Xô Viết Nghệ Tĩnh, Bình Thạnh, TP.HCM', 'self', 4750000, 'pending', NOW()),

-- Booking đã hủy (xe Kia Morning) - Host 1
(6, 9, 5, DATE_SUB(CURDATE(), INTERVAL 20 DAY), '08:00:00', DATE_SUB(CURDATE(), INTERVAL 18 DAY), '18:00:00', '159 Lý Thường Kiệt, Quận 10, TP.HCM', '159 Lý Thường Kiệt, Quận 10, TP.HCM', 'self', 900000, 'cancelled', DATE_SUB(NOW(), INTERVAL 22 DAY)),

-- Booking đã từ chối (xe BMW X6) - Host 1
(7, 6, 5, DATE_SUB(CURDATE(), INTERVAL 15 DAY), '09:00:00', DATE_SUB(CURDATE(), INTERVAL 13 DAY), '17:00:00', '890 Láng Hạ, Đống Đa, Hà Nội', '890 Láng Hạ, Đống Đa, Hà Nội', 'self', 2800000, 'rejected', DATE_SUB(NOW(), INTERVAL 17 DAY)),

-- Booking đã hoàn thành THÁNG NÀY (xe Ford Ranger) - Host 3
(8, 32, 5, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '07:00:00', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '19:00:00', '741 Hồng Bàng, Quận 6, TP.HCM', '741 Hồng Bàng, Quận 6, TP.HCM', 'self', 5000000, 'completed', DATE_SUB(NOW(), INTERVAL 8 DAY)),

-- THÊM: Booking hoàn thành hôm nay (xe Audi A6) - Host 1
(9, 3, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', CURDATE(), '18:00:00', '123 Điện Biên Phủ, Bình Thạnh, TP.HCM', '123 Điện Biên Phủ, Bình Thạnh, TP.HCM', 'self', 6600000, 'completed', DATE_SUB(NOW(), INTERVAL 4 DAY)),

-- THÊM: Booking hoàn thành 2 ngày trước (xe Peugeot 3008) - Host 2
(10, 17, 5, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '09:00:00', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '17:00:00', '246 Phan Xích Long, Phú Nhuận, TP.HCM', '246 Phan Xích Long, Phú Nhuận, TP.HCM', 'self', 3000000, 'completed', DATE_SUB(NOW(), INTERVAL 6 DAY)),

-- THÊM: Booking hoàn thành 1 ngày trước (xe VinFast VF7) - Host 3
(11, 29, 5, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '10:00:00', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '16:00:00', '258 Đinh Tiên Hoàng, Quận 1, TP.HCM', '258 Đinh Tiên Hoàng, Quận 1, TP.HCM', 'delivery', 3300000, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY)),

-- THÊM: Booking đang diễn ra (xe Toyota Corolla Cross) - Host 2
(12, 27, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '18:00:00', '369 Cộng Hòa, Tân Bình, TP.HCM', '369 Cộng Hòa, Tân Bình, TP.HCM', 'self', 3000000, 'confirmed', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ==============================================
-- PAYMENTS: Thanh toán cho các booking
-- Sử dụng NOW() để có dữ liệu phù hợp với thời gian hiện tại
-- ==============================================

INSERT INTO payments (id, booking_id, amount, payment_method, transaction_id, status, created_at) VALUES
-- Thanh toán hoàn thành cho booking 1 (Mercedes S400L) - 7 ngày trước
(1, 1, 5600000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '123456'), 'completed', DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Thanh toán hoàn thành cho booking 2 (Mazda CX-8) - 12 ngày trước
(2, 2, 7000000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '234567'), 'completed', DATE_SUB(NOW(), INTERVAL 12 DAY)),

-- Thanh toán hoàn thành cho booking 3 (VinFast VF8) - 10 ngày trước
(3, 3, 4800000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '345678'), 'completed', DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Thanh toán hoàn thành cho booking 4 (Xpander - đã xác nhận) - 2 ngày trước
(4, 4, 3750000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '456789'), 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Thanh toán chờ cho booking 5 (Honda CR-V - pending)
(5, 5, 4750000, 'VNPAY', NULL, 'pending', NOW()),

-- Thanh toán thất bại cho booking 6 (đã hủy)
(6, 6, 900000, 'VNPAY', NULL, 'failed', DATE_SUB(NOW(), INTERVAL 22 DAY)),

-- Thanh toán thất bại cho booking 7 (đã từ chối)
(7, 7, 2800000, 'VNPAY', NULL, 'failed', DATE_SUB(NOW(), INTERVAL 17 DAY)),

-- Thanh toán hoàn thành cho booking 8 (Ford Ranger) - 8 ngày trước
(8, 8, 5000000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '567890'), 'completed', DATE_SUB(NOW(), INTERVAL 8 DAY)),

-- THÊM: Thanh toán cho booking 9 (Audi A6) - 4 ngày trước
(9, 9, 6600000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '678901'), 'completed', DATE_SUB(NOW(), INTERVAL 4 DAY)),

-- THÊM: Thanh toán cho booking 10 (Peugeot 3008) - 6 ngày trước
(10, 10, 3000000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '789012'), 'completed', DATE_SUB(NOW(), INTERVAL 6 DAY)),

-- THÊM: Thanh toán cho booking 11 (VinFast VF7) - 5 ngày trước
(11, 11, 3300000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '890123'), 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY)),

-- THÊM: Thanh toán cho booking 12 (Toyota Corolla Cross) - 3 ngày trước
(12, 12, 3000000, 'VNPAY', CONCAT('VNP', DATE_FORMAT(NOW(), '%Y%m%d'), '901234'), 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ==============================================
-- REVIEWS: Đánh giá của khách hàng
-- ==============================================

INSERT INTO reviews (id, car_id, customer_id, booking_id, rating, comment, created_at) VALUES
-- Review cho xe Mercedes S400L (5 sao) - booking 1
(1, 1, 5, 1, 5, 'Xe cực kỳ sang trọng, nội thất sạch sẽ như mới. Chủ xe anh Minh rất nhiệt tình, hướng dẫn sử dụng xe kỹ càng. Ghế massage rất thoải mái cho chuyến đi dài. Chắc chắn sẽ thuê lại!', DATE_SUB(NOW(), INTERVAL 3 DAY)),

-- Review cho xe Mazda CX-8 (4 sao) - booking 2
(2, 12, 5, 2, 4, 'SUV 7 chỗ rộng rãi, cả gia đình 6 người ngồi thoải mái. Xe chạy êm, tiết kiệm xăng hơn mong đợi. Chị Nga giao xe đúng giờ, xe sạch sẽ. Trừ 1 sao vì camera lùi hơi mờ ban đêm.', DATE_SUB(NOW(), INTERVAL 5 DAY)),

-- Review cho xe VinFast VF8 (5 sao) - booking 3
(3, 28, 5, 3, 5, 'Lần đầu trải nghiệm xe điện, quá ấn tượng! Xe êm như đi trên mây, tăng tốc mạnh mẽ. Pin sử dụng 4 ngày vẫn dư dả. Anh Long hướng dẫn sạc xe rất chi tiết. Sẽ thuê xe điện VinFast nữa!', DATE_SUB(NOW(), INTERVAL 4 DAY)),

-- Review cho xe Ford Ranger (5 sao) - booking 8
(4, 32, 5, 8, 5, 'Bán tải đúng nghĩa! Chở đồ chuyển nhà dễ dàng, thùng xe rộng. Xe mạnh mẽ, vượt qua đoạn đường xấu ngon lành. Anh Long bảo dưỡng xe tốt, máy móc ổn định. Recommend mạnh!', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Review cho xe Audi A6 (5 sao) - booking 9
(5, 3, 5, 9, 5, 'Sedan hạng sang đúng nghĩa! Nội thất Virtual Cockpit đẹp mê ly. Động cơ mạnh mẽ, vượt êm ru. Anh Minh chăm xe rất kỹ. Sẽ thuê lại!', DATE_SUB(NOW(), INTERVAL 0 DAY)),

-- Review cho xe Peugeot 3008 (5 sao) - booking 10
(6, 17, 5, 10, 5, 'Nội thất i-Cockpit quá đẹp, không giống xe nào! Vô lăng nhỏ gọn lái rất thích. Xe Pháp chất lượng châu Âu, êm và chắc chắn. Sẽ thuê lại cho chuyến đi Đà Lạt.', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Review cho xe VinFast VF7 (4 sao) - booking 11
(7, 29, 5, 11, 4, 'Xe điện thế hệ mới, công nghệ cao. ADAS hoạt động tốt trên cao tốc. Thiết kế đẹp, nhiều người hỏi thăm. Trừ 1 sao vì trạm sạc hơi xa khu vực tôi đi.', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Review cho xe Mitsubishi Xpander (4 sao) - đánh giá cũ
(8, 24, 5, NULL, 4, 'MPV phổ thông tốt nhất tầm giá! 7 chỗ rộng rãi, gầm cao không sợ ngập. Tiết kiệm xăng chỉ 7L/100km đường trường. Chị Nga giao xe tận nơi rất tiện.', DATE_SUB(NOW(), INTERVAL 30 DAY)),

-- Review cho xe Honda CR-V (4 sao) - đánh giá cũ
(9, 14, 5, NULL, 4, 'Xe SUV gia đình rất tốt, Honda bền bỉ như lời đồn. Nội thất hơi cũ nhưng sạch sẽ. Chị Nga hỗ trợ nhiệt tình khi cần đổi lịch trả xe.', DATE_SUB(NOW(), INTERVAL 45 DAY));

-- ==============================================
-- REVIEW_REPLIES: Phản hồi của chủ xe
-- ==============================================

INSERT INTO review_replies (id, review_id, owner_id, reply, created_at) VALUES
-- Phản hồi review Mercedes S400L
(1, 1, 2, 'Cảm ơn anh Bảo đã tin tưởng và đánh giá cao! Rất vui khi xe đáp ứng được nhu cầu của anh. Hẹn gặp lại anh trong những chuyến đi tiếp theo nhé! 🚗', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Phản hồi review Mazda CX-8
(2, 2, 3, 'Cảm ơn anh Bảo đã góp ý! Em sẽ cho kiểm tra và thay camera lùi sớm. Chúc gia đình anh những chuyến đi vui vẻ!', DATE_SUB(NOW(), INTERVAL 4 DAY)),

-- Phản hồi review VinFast VF8
(3, 3, 4, 'Cảm ơn anh đã trải nghiệm xe điện VinFast! Rất vui vì anh hài lòng. Xe điện là xu hướng tương lai, em còn VF7 và VF6 mới hơn nếu anh muốn thử nhé! ⚡', DATE_SUB(NOW(), INTERVAL 3 DAY)),

-- Phản hồi review Ford Ranger
(4, 4, 4, 'Cảm ơn anh Bảo! Ranger đúng là chiến binh đường trường. Anh cần thuê bán tải cứ liên hệ em, em còn Triton và Colorado nữa ạ! 💪', DATE_SUB(NOW(), INTERVAL 0 DAY)),

-- Phản hồi review Peugeot 3008
(5, 6, 3, 'Cảm ơn anh đã yêu thích xe Pháp! Đà Lạt với 3008 là combo hoàn hảo, đèo dốc xe chạy rất êm. Hẹn gặp lại anh! 🇫🇷', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ==============================================
-- REVIEW_FLAGS: Báo cáo đánh giá (mẫu)
-- ==============================================

-- Không có báo cáo nào để admin test tạo mới

-- ==============================================
-- PAYOUT_REQUESTS: Yêu cầu rút tiền
-- ==============================================

INSERT INTO payout_requests (id, owner_id, amount, bank_name, bank_account, note, status, created_at) VALUES
-- Host 1: Đã rút tiền thành công - 20 ngày trước
(1, 2, 3000000, 'Vietcombank', '0071000123456', 'Rút tiền tuần trước', 'approved', DATE_SUB(NOW(), INTERVAL 20 DAY)),

-- Host 2: Đang chờ duyệt - hôm qua
(2, 3, 5000000, 'Techcombank', '19021234567890', 'Rút tiền cuối tháng', 'pending', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Host 3: Yêu cầu bị từ chối (số tài khoản sai) - 10 ngày trước
(3, 4, 2000000, 'MB Bank', '0123456789', 'Rút tiền', 'rejected', DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Host 1: Yêu cầu mới - hôm nay
(4, 2, 4500000, 'Vietcombank', '0071000123456', 'Rút doanh thu tuần này', 'pending', NOW());

-- ==============================================
-- HOÀN TẤT - Kiểm tra dữ liệu
-- ==============================================

SELECT '=== THỐNG KÊ DỮ LIỆU MẪU ===' as '';
SELECT CONCAT('Tổng users: ', COUNT(*)) as '' FROM users;
SELECT CONCAT('- Admin: ', COUNT(*)) as '' FROM users WHERE role = 'admin';
SELECT CONCAT('- Host: ', COUNT(*)) as '' FROM users WHERE role = 'host';
SELECT CONCAT('- User: ', COUNT(*)) as '' FROM users WHERE role = 'user';
SELECT CONCAT('Tổng xe: ', COUNT(*)) as '' FROM cars;
SELECT CONCAT('Tổng hình ảnh: ', COUNT(*)) as '' FROM car_images;
SELECT CONCAT('Tổng booking: ', COUNT(*)) as '' FROM bookings;
SELECT CONCAT('Tổng payment: ', COUNT(*)) as '' FROM payments;
SELECT CONCAT('Tổng review: ', COUNT(*)) as '' FROM reviews;
SELECT CONCAT('Tổng địa chỉ: ', COUNT(*)) as '' FROM user_addresses;
SELECT CONCAT('Tổng yêu cầu rút tiền: ', COUNT(*)) as '' FROM payout_requests;
SELECT '=== HOÀN TẤT ===' as '';


