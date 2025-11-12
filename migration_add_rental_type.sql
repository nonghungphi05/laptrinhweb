-- Migration: Thêm cột rental_type vào bảng cars
-- Chạy script này nếu bạn đã có database và muốn thêm tính năng phân loại dịch vụ

USE carrental;

-- Kiểm tra xem cột rental_type đã tồn tại chưa
-- Nếu chưa có, thêm cột mới
ALTER TABLE cars 
ADD COLUMN IF NOT EXISTS rental_type ENUM('self-drive', 'with-driver', 'long-term') DEFAULT 'self-drive' AFTER car_type;

-- Cập nhật dữ liệu hiện có: đặt mặc định là 'self-drive' cho các xe đã có
UPDATE cars 
SET rental_type = 'self-drive' 
WHERE rental_type IS NULL;

-- Kiểm tra kết quả
SELECT id, name, rental_type, location, price_per_day 
FROM cars 
ORDER BY id;

