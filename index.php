<?php
/**
 * Trang chủ - Giao diện BeeCar
 */
require_once 'config/database.php';
require_once 'config/session.php';

// Lấy base path
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_path = rtrim($script_dir, '/');
if ($base_path === '\\' || $base_path === '') {
    $base_path = '';
} else {
    $base_path = '/' . ltrim($base_path, '/');
}

if (empty($base_path) || $base_path === '/') {
    $parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    if (!empty($parts[0])) {
        $base_path = '/' . $parts[0];
    } else {
    $base_path = '';
}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thuê Xe Tự Lái - Cùng Bạn Trên Mọi Hành Trình</title>
    <link rel="stylesheet" href="<?php echo $base_path ? $base_path . '/assets/css/style.css' : 'assets/css/style.css'; ?>">
    <style>
        /* Hero section background image - fix đường dẫn từ root */
        main.hero-section {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.5) 100%),
                        url('<?php echo $base_path ? $base_path . '/images/hinh1.jpg' : 'images/hinh1.jpg'; ?>') no-repeat center center/cover !important;
            background-blend-mode: multiply !important;
        }
        
        /* Highlight số 10.000 */
        .hero-subtitle strong {
            color: #00ff88 !important;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">
            <a href="<?php echo $base_path ? $base_path . '/index.php' : 'index.php'; ?>" style="text-decoration: none; color: inherit;">🚗 THUÊ XE</a>
        </div>
        <nav class="nav-links">
            <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : 'forum/create-post.php'; ?>">Trở thành Chủ Xe</a>
            <a href="<?php echo $base_path ? $base_path . '/forum/index.php' : 'forum/index.php'; ?>">Diễn đàn</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'client/my-bookings.php'; ?>">Chuyến của tôi</a>
            <?php endif; ?>
        </nav>
        <div class="user-actions">
            <?php if (isLoggedIn()): ?>
                <div class="user-menu" style="display: inline-block; position: relative;">
                    <button class="btn btn-login" style="margin-right: 8px;">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?> ▼
                    </button>
                    <div class="user-dropdown" style="display: none; position: absolute; top: 100%; right: 0; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; padding: 0.5rem 0; min-width: 200px; margin-top: 0.5rem; z-index: 1000;">
                        <a href="<?php echo $base_path ? $base_path . '/forum/my-posts.php' : 'forum/my-posts.php'; ?>" style="display: block; padding: 0.75rem 1.5rem; color: #333; text-decoration: none;">Bài viết của tôi</a>
                        <a href="<?php echo $base_path ? $base_path . '/client/my-bookings.php' : 'client/my-bookings.php'; ?>" style="display: block; padding: 0.75rem 1.5rem; color: #333; text-decoration: none;">Đơn đặt của tôi</a>
                        <?php if (hasRole('admin')): ?>
                            <a href="<?php echo $base_path ? $base_path . '/admin/dashboard.php' : 'admin/dashboard.php'; ?>" style="display: block; padding: 0.75rem 1.5rem; color: #333; text-decoration: none;">Quản trị</a>
                        <?php endif; ?>
                        <a href="<?php echo $base_path ? $base_path . '/auth/logout.php' : 'auth/logout.php'; ?>" style="display: block; padding: 0.75rem 1.5rem; color: #333; text-decoration: none;">Đăng xuất</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo $base_path ? $base_path . '/auth/login.php' : 'auth/login.php'; ?>" class="btn btn-login">Đăng nhập</a>
                <a href="<?php echo $base_path ? $base_path . '/auth/register.php' : 'auth/register.php'; ?>" class="btn btn-signup">Đăng ký</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="hero-section">
        <div class="hero-content-wrapper">
            <h1 class="hero-title">Thuê Xe Tự Lái - Cùng Bạn Trên Mọi Hành Trình</h1>
            <p class="hero-subtitle">Trải nghiệm sự khác biệt từ hơn <strong>10.000</strong> xe gia đình đời mới khắp Việt Nam</p>
            
            <div class="service-selector">
                <button type="button" class="btn btn-service active" data-service="self-drive">Xe tự lái</button>
                <button type="button" class="btn btn-service" data-service="with-driver">Xe có tài xế</button>
                <button type="button" class="btn btn-service" data-service="long-term">Thuê xe dài hạn</button>
            </div>
        </div>
    </main>

    <section class="content-section promo-search-wrapper">
        <form method="GET" action="<?php echo $base_path ? $base_path . '/forum/index.php' : 'forum/index.php'; ?>" style="margin: 0;" id="search-form">
            <input type="hidden" name="type" value="rental" id="search-type">
            <input type="hidden" name="location" id="hidden-location" value="hcm">
            <div class="detailed-search-box">
                <div class="search-input location" id="location-selector" style="cursor: pointer;">
                    <label>Địa điểm</label>
                    <p id="location-display" style="margin: 0; font-size: 1em; font-weight: bold; color: var(--text-color); cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
                        <span>TP. Hồ Chí Minh</span>
                        <span style="font-size: 0.8em;">▼</span>
                    </p>
                </div>
                <div class="search-input datetime">
                    <label>Thời gian thuê</label>
                    <p style="margin: 0; font-size: 1em; font-weight: bold; color: var(--text-color); cursor: pointer;">21:00, 12/11/2025 - 20:00, 13/11/2025</p>
                    <input type="hidden" name="rental-time" value="21:00, 12/11/2025 - 20:00, 13/11/2025">
                </div>
                <button type="button" class="btn btn-search-detail" id="btn-open-search-modal">Tìm Xe</button>
            </div>
        </form>
    </section>

    <!-- Modal tìm kiếm nâng cao -->
    <div id="search-modal" class="search-modal" style="display: none;">
        <div class="search-modal-content">
            <div class="search-modal-header">
                <h3>Tìm kiếm</h3>
                <span class="search-modal-close">&times;</span>
            </div>
            <form id="advanced-search-form" method="GET" action="<?php echo $base_path ? $base_path . '/forum/index.php' : 'forum/index.php'; ?>">
                <input type="hidden" name="type" value="rental">
                <div class="search-modal-body">
                    <!-- Địa điểm -->
                    <div class="search-section">
                        <div class="section-header">
                            <label class="section-label">Địa điểm</label>
                        </div>
                        <div class="location-search-wrapper">
                            <div class="location-search-input">
                                <input type="text" id="location-search" placeholder="Nhập địa điểm, sân bay, ga, bến xe..." autocomplete="off">
                                <button type="button" class="btn-current-location" id="btn-current-location" title="Sử dụng vị trí hiện tại">
                                    Vị trí hiện tại
                                </button>
                            </div>
                            <div class="location-suggestions" id="location-suggestions" style="display: none;">
                                <div class="suggestion-header">Đề xuất</div>
                                <div class="suggestion-list" id="suggestion-list">
                                    <!-- Sẽ được populate bằng JavaScript -->
                                </div>
                            </div>
                            <div class="location-selected" id="location-selected">
                                <input type="hidden" name="location" id="selected-location" value="hcm">
                                <span id="selected-location-name">TP. Hồ Chí Minh</span>
                            </div>
                        </div>
                    </div>

                    <!-- Theo nhu cầu -->
                    <div class="search-section">
                        <div class="section-header">
                            <label class="section-label">Theo nhu cầu</label>
                        </div>
                        <div class="filter-options">
                            <button type="button" class="filter-option" data-filter="need" data-value="new-driver">
                                Lái mới
                            </button>
                            <button type="button" class="filter-option" data-filter="need" data-value="work-commute">
                                Công việc, đi lại
                            </button>
                            <button type="button" class="filter-option" data-filter="need" data-value="family">
                                Gia đình
                            </button>
                            <button type="button" class="filter-option" data-filter="need" data-value="camping">
                                Cắm trại, chở đồ
                            </button>
                            <button type="button" class="filter-option" data-filter="need" data-value="friends">
                                Nhóm bạn
                            </button>
                            <button type="button" class="filter-option" data-filter="need" data-value="party">
                                Tiếp khách, dự tiệc
                            </button>
                        </div>
                        <input type="hidden" name="needs" id="selected-needs" value="">
                    </div>

                    <!-- Theo xu hướng -->
                    <div class="search-section">
                        <div class="section-header">
                            <label class="section-label">Theo xu hướng</label>
                        </div>
                        <div class="filter-options">
                            <button type="button" class="filter-option" data-filter="trend" data-value="electric">
                                Xe điện
                            </button>
                            <button type="button" class="filter-option" data-filter="trend" data-value="hybrid">
                                Xe hybrid
                            </button>
                            <button type="button" class="filter-option" data-filter="trend" data-value="sports">
                                Xe thể thao
                            </button>
                        </div>
                        <input type="hidden" name="trends" id="selected-trends" value="">
                    </div>

                    <!-- Ngân sách -->
                    <div class="search-section">
                        <div class="section-header">
                            <label class="section-label">Ngân sách</label>
                        </div>
                        <div class="filter-options">
                            <button type="button" class="filter-option" data-filter="budget" data-value="cheap">
                                Giá rẻ
                            </button>
                            <button type="button" class="filter-option" data-filter="budget" data-value="economical">
                                Tiết kiệm
                            </button>
                        </div>
                        <input type="hidden" name="budgets" id="selected-budgets" value="">
                    </div>
                </div>
                <div class="search-modal-footer">
                    <button type="button" class="btn-clear-filters" id="btn-clear-filters">Xóa bộ lọc</button>
                    <button type="submit" class="btn-search-submit">Tìm xe</button>
                </div>
            </form>
        </div>
    </div>

    <section class="content-section promotion-section">
        <h2 class="section-title">Chương Trình Khuyến Mãi</h2>
        <p class="section-subtitle">Nhận nhiều ưu đãi hấp dẫn từ chúng tôi</p>
        
            <div class="grid-container promo-cards">
                <div class="promo-card">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎁</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: #333;">Giảm 20% cho khách hàng mới</h3>
                    <p style="color: #666; font-size: 0.95rem;">Áp dụng cho lần thuê đầu tiên</p>
                </div>
                <div class="promo-card">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⭐</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: #333;">Thuê 3 ngày tặng 1 ngày</h3>
                    <p style="color: #666; font-size: 0.95rem;">Áp dụng cho tất cả loại xe</p>
                </div>
                <div class="promo-card">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💳</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem; color: #333;">Thanh toán online giảm 5%</h3>
                    <p style="color: #666; font-size: 0.95rem;">Khi thanh toán qua VNPAY</p>
                </div>
            </div>
    </section>

    <!-- Featured Cars Section -->
    <section class="content-section" style="background-color: #f7f7f7;">
        <h2 class="section-title">Xe Nổi Bật</h2>
        <p class="section-subtitle">Những chiếc xe được yêu thích nhất</p>
        
        <div class="grid-container">
            <?php
            // Lấy 6 xe nổi bật
            $cars_query = "SELECT c.*, u.full_name, p.id as post_id, p.title as post_title
                           FROM cars c
                           JOIN users u ON c.owner_id = u.id
                           LEFT JOIN posts p ON c.post_id = p.id
                           WHERE c.status = 'available'
                           ORDER BY c.created_at DESC
                           LIMIT 6";
            $cars_result = $conn->query($cars_query);
            
            if ($cars_result && $cars_result->num_rows > 0):
                while ($car = $cars_result->fetch_assoc()):
            ?>
            <div class="car-card">
                <div style="width: 100%; height: 200px; overflow: hidden; background: #f0f0f0;">
                    <img src="<?php echo $base_path ? $base_path . '/uploads/' : 'uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($car['name']); ?>"
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.src='<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : 'uploads/default-car.jpg'; ?>'">
                </div>
                <div style="padding: 1.5rem;">
                    <h3 style="margin-bottom: 0.5rem; color: #333; font-size: 1.3rem;"><?php echo htmlspecialchars($car['name']); ?></h3>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">Chủ xe: <?php echo htmlspecialchars($car['full_name']); ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <span style="background: #f0f0f0; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($car['car_type']); ?></span>
                        <span style="font-size: 1.2rem; font-weight: bold; color: var(--beecar-purple);"><?php echo number_format($car['price_per_day']); ?> đ/ngày</span>
                    </div>
                    <?php if ($car['post_id']): ?>
                        <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' : 'forum/post-detail.php?id='; ?><?php echo $car['post_id']; ?>" 
                           class="btn btn-signup" style="display: block; text-align: center; text-decoration: none; width: 100%;">Xem chi tiết</a>
                    <?php else: ?>
                        <a href="<?php echo $base_path ? $base_path . '/forum/index.php' : 'forum/index.php'; ?>" 
                           class="btn btn-signup" style="display: block; text-align: center; text-decoration: none; width: 100%;">Xem chi tiết</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
                endwhile;
            else:
            ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                <p>Chưa có xe nào được đăng. <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : 'forum/create-post.php'; ?>">Đăng xe đầu tiên</a></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="<?php echo $base_path ? $base_path . '/forum/index.php?type=rental' : 'forum/index.php?type=rental'; ?>" 
               class="btn btn-login" style="padding: 1rem 2.5rem; text-decoration: none;">Xem tất cả xe</a>
        </div>
    </section>
    
    <footer class="main-footer">
        <p>&copy; 2025 Thuê Xe Tự Lái Online. Tất cả quyền được bảo lưu.</p>
        <p style="margin-top: 0.5rem;">Liên hệ: contact@carrental.com | Hotline: 1900-xxxx</p>
    </footer>
    
    <script>
        // Service selector
        document.querySelectorAll('.btn-service').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.btn-service').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const service = this.getAttribute('data-service');
                const searchTypeInput = document.getElementById('search-type');
                if (searchTypeInput) {
                    searchTypeInput.value = service === 'self-drive' ? 'rental' : service;
                }
            });
        });
        
        // User menu dropdown
        const userMenu = document.querySelector('.user-menu');
        if (userMenu) {
            const userButton = userMenu.querySelector('.btn-login');
            const userDropdown = userMenu.querySelector('.user-dropdown');
            
            if (userButton && userDropdown) {
                userButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.style.display = userDropdown.style.display === 'none' || userDropdown.style.display === '' ? 'block' : 'none';
                });
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                document.querySelectorAll('.user-dropdown').forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
            }
        });
        
        // Search Modal - Modal tìm kiếm nâng cao
        const locationSelector = document.getElementById('location-selector');
        const searchModal = document.getElementById('search-modal');
        const locationDisplay = document.getElementById('location-display');
        const searchModalClose = document.querySelector('.search-modal-close');
        const locationSearch = document.getElementById('location-search');
        const btnCurrentLocation = document.getElementById('btn-current-location');
        const locationSuggestions = document.getElementById('location-suggestions');
        const suggestionList = document.getElementById('suggestion-list');
        const selectedLocation = document.getElementById('selected-location');
        const selectedLocationName = document.getElementById('selected-location-name');
        const filterOptions = document.querySelectorAll('.filter-option');
        const btnClearFilters = document.getElementById('btn-clear-filters');
        
        // Dữ liệu địa điểm và đề xuất
        const locations = {
            'hcm': 'TP. Hồ Chí Minh',
            'hanoi': 'Hà Nội',
            'danang': 'Đà Nẵng',
            'nhatrang': 'Nha Trang',
            'dalat': 'Đà Lạt',
            'haiphong': 'Hải Phòng',
            'cantho': 'Cần Thơ',
            'vungtau': 'Vũng Tàu',
            'phuquoc': 'Phú Quốc',
            'hue': 'Huế',
            'quynhon': 'Quy Nhon',
            'hoian': 'Hội An'
        };
        
        // Đề xuất sân bay, ga, bến xe
        const suggestions = {
            'hcm': [
                { name: 'Sân bay Tân Sơn Nhất', type: 'airport' },
                { name: 'Ga Sài Gòn', type: 'station' },
                { name: 'Bến xe Miền Đông', type: 'bus' },
                { name: 'Bến xe Miền Tây', type: 'bus' },
                { name: 'Bến Bạch Đằng', type: 'port' }
            ],
            'hanoi': [
                { name: 'Sân bay Nội Bài', type: 'airport' },
                { name: 'Ga Hà Nội', type: 'station' },
                { name: 'Bến xe Giáp Bát', type: 'bus' },
                { name: 'Bến xe Mỹ Đình', type: 'bus' },
                { name: 'Bến xe Nước Ngầm', type: 'bus' }
            ],
            'danang': [
                { name: 'Sân bay Đà Nẵng', type: 'airport' },
                { name: 'Ga Đà Nẵng', type: 'station' },
                { name: 'Bến xe Đà Nẵng', type: 'bus' }
            ],
            'nhatrang': [
                { name: 'Sân bay Cam Ranh', type: 'airport' },
                { name: 'Ga Nha Trang', type: 'station' },
                { name: 'Bến xe Nha Trang', type: 'bus' }
            ],
            'dalat': [
                { name: 'Sân bay Liên Khương', type: 'airport' },
                { name: 'Bến xe Đà Lạt', type: 'bus' }
            ],
            'haiphong': [
                { name: 'Sân bay Cát Bi', type: 'airport' },
                { name: 'Ga Hải Phòng', type: 'station' },
                { name: 'Bến xe Hải Phòng', type: 'bus' }
            ],
            'cantho': [
                { name: 'Sân bay Cần Thơ', type: 'airport' },
                { name: 'Bến xe Cần Thơ', type: 'bus' }
            ],
            'vungtau': [
                { name: 'Bến xe Vũng Tàu', type: 'bus' },
                { name: 'Cảng Vũng Tàu', type: 'port' }
            ],
            'phuquoc': [
                { name: 'Sân bay Phú Quốc', type: 'airport' },
                { name: 'Bến tàu Phú Quốc', type: 'port' }
            ],
            'hue': [
                { name: 'Sân bay Phú Bài', type: 'airport' },
                { name: 'Ga Huế', type: 'station' },
                { name: 'Bến xe Huế', type: 'bus' }
            ],
            'quynhon': [
                { name: 'Sân bay Phù Cát', type: 'airport' },
                { name: 'Ga Quy Nhon', type: 'station' },
                { name: 'Bến xe Quy Nhon', type: 'bus' }
            ],
            'hoian': [
                { name: 'Bến xe Hội An', type: 'bus' }
            ]
        };
        
        // Mở modal khi click vào địa điểm hoặc nút Tìm Xe
        const btnOpenSearchModal = document.getElementById('btn-open-search-modal');
        if (btnOpenSearchModal && searchModal) {
            btnOpenSearchModal.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                searchModal.style.display = 'flex';
                if (selectedLocation) {
                    updateSuggestions(selectedLocation.value);
                }
            });
        }
        
        if (locationSelector && searchModal) {
            locationSelector.addEventListener('click', function(e) {
                e.stopPropagation();
                searchModal.style.display = 'flex';
                if (selectedLocation) {
                    updateSuggestions(selectedLocation.value);
                }
            });
        }
        
        // Đóng modal
        if (searchModalClose) {
            searchModalClose.addEventListener('click', function() {
                searchModal.style.display = 'none';
            });
        }
        
        if (searchModal) {
            searchModal.addEventListener('click', function(e) {
                if (e.target === searchModal) {
                    searchModal.style.display = 'none';
                }
            });
        }
        
        // Tìm kiếm địa điểm
        if (locationSearch) {
            locationSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                if (query.length > 0) {
                    showLocationSearchResults(query);
                } else {
                    locationSuggestions.style.display = 'none';
                }
            });
        }
        
        // Vị trí hiện tại
        if (btnCurrentLocation) {
            btnCurrentLocation.addEventListener('click', function() {
                if (navigator.geolocation) {
                    this.textContent = 'Đang lấy vị trí...';
                    this.disabled = true;
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            // Giả sử tìm địa điểm gần nhất (có thể tích hợp API geocoding)
                            setLocation('hcm', 'Vị trí hiện tại');
                            btnCurrentLocation.textContent = 'Vị trí hiện tại';
                            btnCurrentLocation.disabled = false;
                        },
                        function(error) {
                            alert('Không thể lấy vị trí. Vui lòng chọn địa điểm thủ công.');
                            btnCurrentLocation.textContent = 'Vị trí hiện tại';
                            btnCurrentLocation.disabled = false;
                        }
                    );
                } else {
                    alert('Trình duyệt không hỗ trợ định vị.');
                }
            });
        }
        
        // Cập nhật đề xuất
        function updateSuggestions(locationCode) {
            if (suggestions[locationCode]) {
                suggestionList.innerHTML = '';
                suggestions[locationCode].forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = `<span class="suggestion-text">${item.name}</span>`;
                    div.addEventListener('click', function() {
                        setLocation(locationCode, item.name);
                        locationSearch.value = item.name;
                        locationSuggestions.style.display = 'none';
                    });
                    suggestionList.appendChild(div);
                });
                locationSuggestions.style.display = 'block';
            } else {
                locationSuggestions.style.display = 'none';
            }
        }
        
        // Hiển thị kết quả tìm kiếm địa điểm
        function showLocationSearchResults(query) {
            const results = [];
            // Tìm trong danh sách thành phố
            Object.keys(locations).forEach(code => {
                if (locations[code].toLowerCase().includes(query)) {
                    results.push({ code: code, name: locations[code], type: 'city' });
                }
            });
            // Tìm trong đề xuất
            Object.keys(suggestions).forEach(code => {
                suggestions[code].forEach(item => {
                    if (item.name.toLowerCase().includes(query)) {
                        results.push({ code: code, name: item.name, type: item.type });
                    }
                });
            });
            
            suggestionList.innerHTML = '';
            if (results.length > 0) {
                results.forEach(result => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = `<span class="suggestion-text">${result.name}</span>`;
                    div.addEventListener('click', function() {
                        setLocation(result.code, result.name);
                        locationSearch.value = result.name;
                        locationSuggestions.style.display = 'none';
                    });
                    suggestionList.appendChild(div);
                });
                locationSuggestions.style.display = 'block';
            } else {
                locationSuggestions.style.display = 'none';
            }
        }
        
        // Set location
        function setLocation(code, name) {
            selectedLocation.value = code;
            selectedLocationName.textContent = name;
            if (locationDisplay) {
                locationDisplay.querySelector('span').textContent = name;
            }
            updateSuggestions(code);
        }
        
        // Filter options - chọn nhiều
        const selectedFilters = {
            need: [],
            trend: [],
            budget: []
        };
        
        filterOptions.forEach(option => {
            option.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                const value = this.getAttribute('data-value');
                
                if (this.classList.contains('active')) {
                    this.classList.remove('active');
                    selectedFilters[filter] = selectedFilters[filter].filter(v => v !== value);
                } else {
                    this.classList.add('active');
                    selectedFilters[filter].push(value);
                }
                
                // Cập nhật hidden inputs
                document.getElementById('selected-needs').value = selectedFilters.need.join(',');
                document.getElementById('selected-trends').value = selectedFilters.trend.join(',');
                document.getElementById('selected-budgets').value = selectedFilters.budget.join(',');
            });
        });
        
        // Xóa bộ lọc
        if (btnClearFilters) {
            btnClearFilters.addEventListener('click', function() {
                filterOptions.forEach(option => {
                    option.classList.remove('active');
                });
                selectedFilters.need = [];
                selectedFilters.trend = [];
                selectedFilters.budget = [];
                document.getElementById('selected-needs').value = '';
                document.getElementById('selected-trends').value = '';
                document.getElementById('selected-budgets').value = '';
                setLocation('hcm', 'TP. Hồ Chí Minh');
                locationSearch.value = '';
                locationSuggestions.style.display = 'none';
            });
        }
        
        // Cập nhật đề xuất khi mở modal
        if (searchModal) {
            searchModal.addEventListener('click', function(e) {
                if (e.target === searchModal || e.target.closest('.search-modal-content')) {
                    updateSuggestions(selectedLocation.value);
                }
            });
        }
    </script>
</body>
</html>
