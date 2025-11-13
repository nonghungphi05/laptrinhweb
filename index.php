<?php
/**
 * Trang chủ - Giao diện mới (Tailwind CSS)
 */
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/helpers.php';

// Lấy base path
$base_path = getBasePath();

// Lấy 8 xe nổi bật (từ database) - chỉ lấy xe có post_id
$cars_query = "SELECT c.*, u.full_name, p.id as post_id, p.title as post_title
               FROM cars c
               JOIN users u ON c.owner_id = u.id
               INNER JOIN posts p ON c.post_id = p.id
               WHERE c.status = 'available' AND p.status = 'active'
               ORDER BY c.created_at DESC
               LIMIT 8";
$cars_result = $conn->query($cars_query);
$featured_cars = [];
if ($cars_result && $cars_result->num_rows > 0) {
    $featured_cars = $cars_result->fetch_all(MYSQLI_ASSOC);
}

// Địa điểm nổi bật
$popular_locations = [
    ['code' => 'hcm', 'name' => 'TP. Hồ Chí Minh'],
    ['code' => 'hanoi', 'name' => 'Hà Nội'],
    ['code' => 'danang', 'name' => 'Đà Nẵng'],
    ['code' => 'dalat', 'name' => 'Đà Lạt']
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
                                            Khám phá hàng ngàn chiếc xe cho mọi chuyến đi. Thuê xe tự lái &amp; có tài xế một cách dễ dàng, an toàn và tiết kiệm.
                                        </h2>
                                    </div>
                                    <label class="flex flex-col min-w-40 h-14 w-full max-w-[480px] md:h-16">
                                        <div class="flex w-full flex-1 items-stretch rounded-lg h-full shadow-lg">
                                            <div class="text-primary flex border border-[#e6e0db] bg-white items-center justify-center pl-[15px] rounded-l-lg border-r-0">
                                                <span class="material-symbols-outlined">search</span>
                                            </div>
                                            <input type="text" 
                                                   id="location-search-input" 
                                                   class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden text-[#181411] focus:outline-0 focus:ring-0 border border-[#e6e0db] bg-white focus:border-primary h-full placeholder:text-[#8c755f] px-[15px] border-r-0 border-l-0 text-sm font-normal leading-normal md:text-base" 
                                                   placeholder="Nhập địa điểm bạn muốn thuê xe" 
                                                   value=""
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
                        <section class="py-10">
                            <h2 class="text-[#181411] dark:text-gray-200 text-[22px] font-bold leading-tight tracking-[-0.015em] px-4 pb-3 pt-5">Dòng xe nổi bật</h2>
                            <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4 px-4">
                                <?php if (!empty($featured_cars)): ?>
                                    <?php foreach ($featured_cars as $car): ?>
                                        <?php if (!empty($car['post_id'])): ?>
                                            <a href="<?php echo $base_path ? $base_path . '/forum/post-detail.php?id=' . $car['post_id'] : 'forum/post-detail.php?id=' . $car['post_id']; ?>" 
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
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-span-full text-center py-8">
                                        <p class="text-gray-500">Chưa có xe nào được đăng.</p>
                                        <?php if (isLoggedIn()): ?>
                                            <a href="<?php echo $base_path ? $base_path . '/forum/create-post.php' : 'forum/create-post.php'; ?>" 
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
                                    <a href="<?php echo $base_path ? $base_path . '/forum/index.php?type=rental&location=' . $loc['code'] : 'forum/index.php?type=rental&location=' . $loc['code']; ?>" 
                                       class="relative overflow-hidden rounded-lg group">
                                        <div class="w-full bg-center bg-no-repeat aspect-square bg-cover transition-transform duration-300 group-hover:scale-110" 
                                             style='background-image: linear-gradient(to top, rgba(0,0,0,0.6), transparent), url("https://images.unsplash.com/photo-1539650116574-75c0c6d73aa0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80");'>
                                        </div>
                                        <p class="absolute bottom-4 left-4 text-white text-lg font-bold"><?php echo htmlspecialchars($loc['name']); ?></p>
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
                <h3 class="text-xl font-bold text-[#181411] dark:text-white">Tìm kiếm</h3>
                <button type="button" onclick="closeSearchModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="advanced-search-form" method="GET" action="<?php echo $base_path ? $base_path . '/forum/index.php' : 'forum/index.php'; ?>" class="p-6">
                <input type="hidden" name="type" value="rental">
                <div class="space-y-6">
                    <!-- Địa điểm -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Địa điểm</label>
                        <div class="space-y-3">
                            <div class="flex gap-2">
                                <input type="text" 
                                       id="location-search" 
                                       placeholder="Nhập địa điểm, sân bay, ga, bến xe..." 
                                       autocomplete="off"
                                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white">
                                <button type="button" 
                                        id="btn-current-location" 
                                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    Vị trí hiện tại
                                </button>
                            </div>
                            <div id="location-suggestions" class="hidden border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 max-h-48 overflow-y-auto">
                                <div class="p-2 text-xs font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">Đề xuất</div>
                                <div id="suggestion-list" class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                            <div id="location-selected" class="flex items-center gap-2 p-3 bg-primary/10 rounded-lg">
                                <input type="hidden" name="location" id="selected-location" value="hcm">
                                <span id="selected-location-name" class="text-primary font-medium">TP. Hồ Chí Minh</span>
                            </div>
                        </div>
                    </div>

                    <!-- Theo nhu cầu -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Theo nhu cầu</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="need" data-value="new-driver">Lái mới</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="need" data-value="work-commute">Công việc, đi lại</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="need" data-value="family">Gia đình</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="need" data-value="camping">Cắm trại, chở đồ</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="need" data-value="friends">Nhóm bạn</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="need" data-value="party">Tiếp khách, dự tiệc</button>
                        </div>
                        <input type="hidden" name="needs" id="selected-needs" value="">
                    </div>

                    <!-- Theo xu hướng -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Theo xu hướng</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="trend" data-value="electric">Xe điện</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="trend" data-value="hybrid">Xe hybrid</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="trend" data-value="sports">Xe thể thao</button>
                        </div>
                        <input type="hidden" name="trends" id="selected-trends" value="">
                    </div>

                    <!-- Ngân sách -->
                    <div>
                        <label class="block text-sm font-semibold mb-3 text-[#181411] dark:text-white">Ngân sách</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="budget" data-value="cheap">Giá rẻ</button>
                            <button type="button" class="filter-option px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary hover:bg-primary/10 transition-colors" data-filter="budget" data-value="economical">Tiết kiệm</button>
                        </div>
                        <input type="hidden" name="budgets" id="selected-budgets" value="">
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
        }
        
        function closeSearchModal(event) {
            if (!event || event.target === event.currentTarget || event.target.closest('.material-symbols-outlined')) {
                document.getElementById('search-modal').classList.add('hidden');
                document.getElementById('search-modal').classList.remove('flex');
            }
        }
        
        // Location data
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
        
        const suggestions = {
            'hcm': [
                { name: 'Sân bay Tân Sơn Nhất', type: 'airport' },
                { name: 'Ga Sài Gòn', type: 'station' },
                { name: 'Bến xe Miền Đông', type: 'bus' },
                { name: 'Bến xe Miền Tây', type: 'bus' }
            ],
            'hanoi': [
                { name: 'Sân bay Nội Bài', type: 'airport' },
                { name: 'Ga Hà Nội', type: 'station' },
                { name: 'Bến xe Giáp Bát', type: 'bus' },
                { name: 'Bến xe Mỹ Đình', type: 'bus' }
            ],
            'danang': [
                { name: 'Sân bay Đà Nẵng', type: 'airport' },
                { name: 'Ga Đà Nẵng', type: 'station' },
                { name: 'Bến xe Đà Nẵng', type: 'bus' }
            ]
        };
        
        // Location search
        const locationSearch = document.getElementById('location-search');
        const locationSuggestions = document.getElementById('location-suggestions');
        const suggestionList = document.getElementById('suggestion-list');
        const selectedLocation = document.getElementById('selected-location');
        const selectedLocationName = document.getElementById('selected-location-name');
        const btnCurrentLocation = document.getElementById('btn-current-location');
        
        function updateSuggestions(locationCode) {
            if (suggestions[locationCode]) {
                suggestionList.innerHTML = '';
                suggestions[locationCode].forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                    div.innerHTML = `<span>${item.name}</span>`;
                    div.addEventListener('click', function() {
                        setLocation(locationCode, item.name);
                        locationSearch.value = item.name;
                        locationSuggestions.classList.add('hidden');
                    });
                    suggestionList.appendChild(div);
                });
                locationSuggestions.classList.remove('hidden');
            } else {
                locationSuggestions.classList.add('hidden');
            }
        }
        
        function setLocation(code, name) {
            selectedLocation.value = code;
            selectedLocationName.textContent = name;
            updateSuggestions(code);
        }
        
        if (locationSearch) {
            locationSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                if (query.length > 0) {
                    showLocationSearchResults(query);
                } else {
                    locationSuggestions.classList.add('hidden');
                }
            });
        }
        
        function showLocationSearchResults(query) {
            const results = [];
            Object.keys(locations).forEach(code => {
                if (locations[code].toLowerCase().includes(query)) {
                    results.push({ code: code, name: locations[code], type: 'city' });
                }
            });
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
                    div.className = 'p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                    div.innerHTML = `<span>${result.name}</span>`;
                    div.addEventListener('click', function() {
                        setLocation(result.code, result.name);
                        locationSearch.value = result.name;
                        locationSuggestions.classList.add('hidden');
                    });
                    suggestionList.appendChild(div);
                });
                locationSuggestions.classList.remove('hidden');
            } else {
                locationSuggestions.classList.add('hidden');
            }
        }
        
        // Current location
        if (btnCurrentLocation) {
            btnCurrentLocation.addEventListener('click', function() {
                if (navigator.geolocation) {
                    this.textContent = 'Đang lấy vị trí...';
                    this.disabled = true;
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
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
        
        // Filter options
        const selectedFilters = {
            need: [],
            trend: [],
            budget: []
        };
        
        document.querySelectorAll('.filter-option').forEach(option => {
            option.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                const value = this.getAttribute('data-value');
                
                if (this.classList.contains('active')) {
                    this.classList.remove('active');
                    this.classList.remove('border-primary', 'bg-primary/20');
                    selectedFilters[filter] = selectedFilters[filter].filter(v => v !== value);
                } else {
                    this.classList.add('active');
                    this.classList.add('border-primary', 'bg-primary/20');
                    selectedFilters[filter].push(value);
                }
                
                document.getElementById('selected-needs').value = selectedFilters.need.join(',');
                document.getElementById('selected-trends').value = selectedFilters.trend.join(',');
                document.getElementById('selected-budgets').value = selectedFilters.budget.join(',');
            });
        });
        
        // Clear filters
        const btnClearFilters = document.getElementById('btn-clear-filters');
        if (btnClearFilters) {
            btnClearFilters.addEventListener('click', function() {
                document.querySelectorAll('.filter-option').forEach(option => {
                    option.classList.remove('active');
                    option.classList.remove('border-primary', 'bg-primary/20');
                });
                selectedFilters.need = [];
                selectedFilters.trend = [];
                selectedFilters.budget = [];
                document.getElementById('selected-needs').value = '';
                document.getElementById('selected-trends').value = '';
                document.getElementById('selected-budgets').value = '';
                setLocation('hcm', 'TP. Hồ Chí Minh');
                locationSearch.value = '';
                locationSuggestions.classList.add('hidden');
            });
        }
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
