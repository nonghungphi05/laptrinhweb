<?php
/**
 * Trang chủ - Giao diện mới (Tailwind CSS)
 */
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/helpers.php';

// Lấy base path
$base_path = getBasePath();

// Lấy 10 xe nổi bật (từ database)
$cars_query = "SELECT c.*, u.full_name
               FROM cars c
               JOIN users u ON c.owner_id = u.id
               WHERE c.status = 'available'
               ORDER BY c.created_at DESC
               LIMIT 10";
$cars_result = $conn->query($cars_query);
$featured_cars = [];
if ($cars_result && $cars_result->num_rows > 0) {
    $featured_cars = $cars_result->fetch_all(MYSQLI_ASSOC);
}

$popular_locations = [
    [
        'code' => 'hcm', 
        'name' => 'TP. Hồ Chí Minh',
        'image' => 'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=800&q=80'
    ],
    [
        'code' => 'hanoi', 
        'name' => 'Hà Nội',
        'image' => 'https://images.unsplash.com/photo-1672578918034-5e164fe5b7dc?w=800&q=80'
    ],
    [
        'code' => 'danang', 
        'name' => 'Đà Nẵng',
        'image' => 'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?w=800&q=80'
    ],
    [
        'code' => 'dalat', 
        'name' => 'Đà Lạt',
        'image' => 'https://images.unsplash.com/photo-1558338475-7ac335028946?w=800&q=80'
    ]
];
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>CarRental - Hành trình của bạn, lựa chọn của bạn</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f98006",
                        "background-light": "#f8f7f5",
                        "background-dark": "#23190f",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "Noto Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-[#181411] dark:text-white">
    <div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <div class="flex flex-1 justify-center py-5">
                <div class="layout-content-container flex flex-col w-full max-w-6xl flex-1">
                    <!-- Header -->
                    <?php include 'includes/header.php'; ?>
                    
                    <!-- Main Content -->
                    <main class="flex-1">
                        <!-- Hero Section -->
                        <div class="px-4 md:px-0">
                            <div class="p-0 md:p-4">
                                <div class="flex min-h-[480px] flex-col gap-6 bg-cover bg-center bg-no-repeat md:gap-8 md:rounded-xl items-center justify-center p-4 text-center" 
                                     style='background-image: linear-gradient(rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.4) 100%), url("https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80");'>
                                    <div class="flex flex-col gap-2">
                                        <h1 class="text-white text-4xl font-black leading-tight tracking-[-0.033em] md:text-5xl">
                                            Hành trình của bạn, lựa chọn của bạn
                                        </h1>
                                        <h2 class="text-white text-sm font-normal leading-normal md:text-base">
                                            Khám phá hàng ngàn chiếc xe tự lái cho mọi chuyến đi, dễ dàng, an toàn và tiết kiệm.
                                        </h2>
                                    </div>
                                    <label class="flex flex-col min-w-40 h-14 w-full max-w-[480px] md:h-16">
                                        <div class="flex w-full flex-1 items-stretch rounded-lg h-full shadow-lg">
                                            <div class="text-primary flex border border-[#e6e0db] bg-white items-center justify-center pl-[15px] rounded-l-lg border-r-0">
                                                <span class="material-symbols-outlined">search</span>
                                            </div>
                                            <input type="text" 
                                                   id="hero-search-input" 
                                                   class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden text-[#181411] focus:outline-0 focus:ring-0 border border-[#e6e0db] bg-white focus:border-primary h-full placeholder:text-[#8c755f] px-[15px] border-r-0 border-l-0 text-sm font-normal leading-normal md:text-base cursor-pointer" 
                                                   placeholder="Nhập địa điểm bạn muốn thuê xe" 
                                                   value=""
                                                   readonly
                                                   onclick="openSearchModal()"/>
                                            <div class="flex items-center justify-center rounded-r-lg border-l-0 border border-[#e6e0db] bg-white pr-[7px]">
                                                <button type="button" 
                                                        class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 md:h-12 md:px-5 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] md:text-base"
                                                        onclick="openSearchModal()">
                                                    <span class="truncate">Tìm xe</span>
                                                </button>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Featured Cars Section -->
                        <section class="py-10" id="featured-cars">
                            <h2 class="text-[#181411] dark:text-gray-200 text-[22px] font-bold leading-tight tracking-[-0.015em] px-4 pb-3 pt-5">Dòng xe nổi bật</h2>
                            <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4 px-4">
                                <?php if (!empty($featured_cars)): ?>
                                    <?php foreach ($featured_cars as $car): ?>
                                        <a href="<?php echo $base_path ? $base_path . '/client/car-detail.php?id=' . $car['id'] : 'client/car-detail.php?id=' . $car['id']; ?>" 
                                           class="flex flex-col gap-3 pb-3 group">
                                            <div class="w-full bg-center bg-no-repeat aspect-video bg-cover rounded-lg overflow-hidden transform group-hover:scale-105 transition-transform duration-300" 
                                                 style='background-image: url("<?php echo $base_path ? $base_path . '/uploads/' : 'uploads/'; ?><?php echo htmlspecialchars($car['image'] ?: 'default-car.jpg'); ?>");'
                                                 onerror="this.style.backgroundImage='url(<?php echo $base_path ? $base_path . '/uploads/default-car.jpg' : 'uploads/default-car.jpg'; ?>)'">
                                            </div>
                                            <div>
                                                <p class="text-[#181411] dark:text-white text-base font-medium leading-normal"><?php echo htmlspecialchars($car['name']); ?></p>
                                                <p class="text-[#8c755f] dark:text-gray-400 text-sm font-normal leading-normal">
                                                    Từ <?php echo number_format($car['price_per_day']); ?>đ/ngày
                                                </p>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-span-full text-center py-8">
                                        <p class="text-gray-500">Chưa có xe nào được đăng.</p>
                                        <?php if (isLoggedIn()): ?>
                                            <a href="<?php echo $base_path ? $base_path . '/host/add-car.php' : 'host/add-car.php'; ?>" 
                                               class="text-primary hover:underline mt-2 inline-block">Đăng xe đầu tiên</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                        
                        <!-- Popular Locations Section -->
                        <section class="py-10 bg-white dark:bg-background-dark/30">
                            <h2 class="text-[#181411] dark:text-gray-200 text-[22px] font-bold leading-tight tracking-[-0.015em] px-4 pb-3 pt-5">Khám phá các điểm đến hàng đầu</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4">
                                <?php foreach ($popular_locations as $loc): ?>
                                    <a href="<?php echo $base_path ? $base_path . '/cars/index.php?location=' . $loc['code'] : 'cars/index.php?location=' . $loc['code']; ?>" 
                                       class="relative overflow-hidden rounded-lg group">
                                        <div class="w-full bg-center bg-no-repeat aspect-square bg-cover transition-transform duration-300 group-hover:scale-110" 
                                             style='background-image: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 50%, transparent 100%), url("<?php echo $loc['image']; ?>");'>
                                        </div>
                                        <p class="absolute bottom-4 left-4 text-white text-lg font-bold drop-shadow-lg"><?php echo htmlspecialchars($loc['name']); ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        
                        <!-- Why Choose Us Section -->
                        <section class="py-16 px-4 text-center">
                            <h2 class="text-[#181411] dark:text-gray-200 text-2xl font-bold leading-tight tracking-[-0.015em] mb-2">Tại sao chọn chúng tôi?</h2>
                            <p class="text-[#8c755f] dark:text-gray-400 max-w-2xl mx-auto mb-12">Trải nghiệm thuê xe an toàn, tiện lợi và minh bạch với những lợi ích vượt trội.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="flex items-center justify-center size-16 bg-primary/20 text-primary rounded-full">
                                        <span class="material-symbols-outlined text-4xl">verified_user</span>
                                    </div>
                                    <h3 class="text-[#181411] dark:text-white text-lg font-bold">Bảo hiểm toàn diện</h3>
                                    <p class="text-[#8c755f] dark:text-gray-400 text-sm">An tâm trên mọi hành trình với các gói bảo hiểm uy tín từ đối tác hàng đầu.</p>
                                </div>
                                <div class="flex flex-col items-center gap-4">
                                    <div class="flex items-center justify-center size-16 bg-primary/20 text-primary rounded-full">
                                        <span class="material-symbols-outlined text-4xl">description</span>
                                    </div>
                                    <h3 class="text-[#181411] dark:text-white text-lg font-bold">Thủ tục đơn giản</h3>
                                    <p class="text-[#8c755f] dark:text-gray-400 text-sm">Hoàn tất thuê xe chỉ với vài bước đơn giản, không cần giấy tờ phức tạp.</p>
                                </div>
                                <div class="flex flex-col items-center gap-4">
                                    <div class="flex items-center justify-center size-16 bg-primary/20 text-primary rounded-full">
                                        <span class="material-symbols-outlined text-4xl">local_shipping</span>
                                    </div>
                                    <h3 class="text-[#181411] dark:text-white text-lg font-bold">Giao xe tận nơi</h3>
                                    <p class="text-[#8c755f] dark:text-gray-400 text-sm">Nhận và trả xe tại địa điểm bạn yêu cầu, tiết kiệm thời gian di chuyển.</p>
                                </div>
                                <div class="flex flex-col items-center gap-4">
                                    <div class="flex items-center justify-center size-16 bg-primary/20 text-primary rounded-full">
                                        <span class="material-symbols-outlined text-4xl">support_agent</span>
                                    </div>
                                    <h3 class="text-[#181411] dark:text-white text-lg font-bold">Hỗ trợ 24/7</h3>
                                    <p class="text-[#8c755f] dark:text-gray-400 text-sm">Đội ngũ hỗ trợ luôn sẵn sàng giải đáp mọi thắc mắc của bạn trên suốt hành trình.</p>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Modal -->
    <div id="search-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" onclick="closeSearchModal(event)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-[#181411] dark:text-white">Tìm kiếm xe</h3>
                <button type="button" onclick="closeSearchModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="advanced-search-form" method="GET" action="<?php echo $base_path ? $base_path . '/cars/index.php' : 'cars/index.php'; ?>" class="p-6">
                <div class="space-y-6">
                    <!-- Từ khóa -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Từ khóa</label>
                        <input type="text" 
                               id="search-keyword" 
                               name="search"
                               placeholder="Nhập tên xe, mô tả..." 
                               autocomplete="off"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Địa điểm -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Địa điểm</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="">
                                Tất cả
                            </button>
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="hcm">
                                TP. HCM
                            </button>
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="hanoi">
                                Hà Nội
                            </button>
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="danang">
                                Đà Nẵng
                            </button>
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="dalat">
                                Đà Lạt
                            </button>
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="nhatrang">
                                Nha Trang
                            </button>
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="cantho">
                                Cần Thơ
                            </button>
                            <button type="button" class="location-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="phuquoc">
                                Phú Quốc
                            </button>
                        </div>
                        <input type="hidden" name="location" id="selected-location" value="">
                    </div>

                    <!-- Loại xe -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Loại xe</label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                            <button type="button" class="car-type-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="">
                                Tất cả
                            </button>
                            <button type="button" class="car-type-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="sedan">
                                Sedan
                            </button>
                            <button type="button" class="car-type-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="suv">
                                SUV
                            </button>
                            <button type="button" class="car-type-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="mpv">
                                MPV
                            </button>
                            <button type="button" class="car-type-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="pickup">
                                Bán tải
                            </button>
                            <button type="button" class="car-type-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="hatchback">
                                Hatchback
                            </button>
                        </div>
                        <input type="hidden" name="car_type" id="selected-car-type" value="">
                    </div>

                    <!-- Ngân sách -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Ngân sách</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button type="button" class="budget-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="">
                                Tất cả
                            </button>
                            <button type="button" class="budget-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="cheap">
                                Dưới 500K
                            </button>
                            <button type="button" class="budget-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="mid">
                                500K - 1M
                            </button>
                            <button type="button" class="budget-option px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors text-sm" data-value="premium">
                                Trên 1M
                            </button>
                        </div>
                        <input type="hidden" name="budget" id="selected-budget" value="">
                    </div>
                </div>
                <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" 
                            id="btn-clear-filters" 
                            class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Xóa bộ lọc
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-bold">
                        Tìm xe
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Search Modal Functions
        function openSearchModal() {
            document.getElementById('search-modal').classList.remove('hidden');
            document.getElementById('search-modal').classList.add('flex');
            // Focus vào ô tìm kiếm
            setTimeout(() => {
                document.getElementById('search-keyword').focus();
            }, 100);
        }
        
        function closeSearchModal(event) {
            if (!event || event.target === event.currentTarget || event.target.closest('button[onclick="closeSearchModal()"]')) {
                document.getElementById('search-modal').classList.add('hidden');
                document.getElementById('search-modal').classList.remove('flex');
            }
        }
        
        // Đóng modal khi nhấn Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSearchModal();
            }
        });
        
        // Helper function để xử lý single-select buttons
        function setupSingleSelectButtons(selector, hiddenInputId) {
            const buttons = document.querySelectorAll(selector);
            const hiddenInput = document.getElementById(hiddenInputId);
            
            buttons.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active state from all buttons in group
                    buttons.forEach(b => {
                        b.classList.remove('border-primary', 'bg-primary/10', 'text-primary', 'font-semibold');
                        b.classList.add('border-gray-300');
                    });
                    
                    // Add active state to clicked button
                    this.classList.add('border-primary', 'bg-primary/10', 'text-primary', 'font-semibold');
                    this.classList.remove('border-gray-300');
                    
                    // Update hidden input
                    hiddenInput.value = this.dataset.value;
                });
            });
            
            // Set first button (Tất cả) as default active
            if (buttons.length > 0) {
                buttons[0].classList.add('border-primary', 'bg-primary/10', 'text-primary', 'font-semibold');
                buttons[0].classList.remove('border-gray-300');
            }
        }
        
        // Setup all filter button groups
        setupSingleSelectButtons('.location-option', 'selected-location');
        setupSingleSelectButtons('.car-type-option', 'selected-car-type');
        setupSingleSelectButtons('.budget-option', 'selected-budget');
        
        // Clear filters
        const btnClearFilters = document.getElementById('btn-clear-filters');
        if (btnClearFilters) {
            btnClearFilters.addEventListener('click', function() {
                // Clear search keyword
                document.getElementById('search-keyword').value = '';
                
                // Reset all filter groups to default (first option)
                ['location-option', 'car-type-option', 'budget-option'].forEach(className => {
                    const buttons = document.querySelectorAll('.' + className);
                    buttons.forEach((btn, index) => {
                        btn.classList.remove('border-primary', 'bg-primary/10', 'text-primary', 'font-semibold');
                        btn.classList.add('border-gray-300');
                        if (index === 0) {
                            btn.classList.add('border-primary', 'bg-primary/10', 'text-primary', 'font-semibold');
                            btn.classList.remove('border-gray-300');
                        }
                    });
                });
                
                // Clear hidden inputs
                document.getElementById('selected-location').value = '';
                document.getElementById('selected-car-type').value = '';
                document.getElementById('selected-budget').value = '';
            });
        }
        
        // Submit form on Enter key in search input
        document.getElementById('search-keyword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('advanced-search-form').submit();
            }
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
