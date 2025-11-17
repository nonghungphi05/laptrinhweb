<?php
/**
 * Trang Về Chúng Tôi - CarRental
 */
require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Về Chúng Tôi - CarRental';
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f48c25",
                        "secondary": "#003366",
                        "background-light": "#f8f7f5",
                        "background-dark": "#181411",
                        "text-light": "#333333",
                        "text-dark": "#f8f7f5",
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
<body class="font-display bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark">
    <div class="relative flex min-h-screen w-full flex-col">
        <?php include 'includes/header.php'; ?>
        
        <main class="flex-1">
            <!-- Hero Section -->
            <section class="relative">
                <div class="flex min-h-[480px] flex-col gap-6 bg-cover bg-center bg-no-repeat items-center justify-center text-center p-4" 
                     style='background-image: linear-gradient(rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.5) 100%), url("https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=1600&h=800&fit=crop");'>
                    <div class="flex flex-col gap-2">
                        <h1 class="text-white text-4xl font-black leading-tight tracking-[-0.033em] md:text-6xl">
                            Về Chúng Tôi
                        </h1>
                        <h2 class="text-white text-base font-normal leading-normal md:text-lg">
                            Câu chuyện đằng sau mỗi chuyến đi của bạn.
                        </h2>
                    </div>
                </div>
            </section>

            <!-- Main Content -->
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 space-y-24">
                <!-- Câu chuyện và Tầm nhìn Section -->
                <section class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-secondary dark:text-primary text-2xl md:text-3xl font-bold leading-tight tracking-[-0.015em] pb-4">
                            Câu chuyện của chúng tôi
                        </h2>
                        <p class="text-base font-normal leading-relaxed">
                            Được thành lập với niềm đam mê mang lại sự tự do và linh hoạt trong di chuyển, CarRental ra đời để đơn giản hóa quá trình thuê xe. Chúng tôi tin rằng mọi người xứng đáng có được những chuyến đi suôn sẻ, an toàn và đáng nhớ. Mục tiêu của chúng tôi là trở thành nền tảng thuê xe trực tuyến đáng tin cậy nhất, kết nối bạn với chiếc xe hoàn hảo cho mọi hành trình.
                        </p>
                    </div>
                    <div>
                        <h2 class="text-secondary dark:text-primary text-2xl md:text-3xl font-bold leading-tight tracking-[-0.015em] pb-4">
                            Tầm nhìn và Sứ mệnh
                        </h2>
                        <p class="text-base font-normal leading-relaxed">
                            <strong>Tầm nhìn:</strong> Trở thành lựa chọn hàng đầu cho dịch vụ thuê xe tại Việt Nam, kiến tạo những trải nghiệm di chuyển liền mạch và tiện lợi.
                            <br/><br/>
                            <strong>Sứ mệnh:</strong> Cung cấp một nền tảng minh bạch, dễ sử dụng với đa dạng các dòng xe và dịch vụ khách hàng xuất sắc, đảm bảo sự hài lòng tuyệt đối cho mỗi khách hàng.
                        </p>
                    </div>
                </section>

                <!-- Giá trị cốt lõi Section -->
                <section>
                    <div class="text-center mb-12">
                        <h2 class="text-secondary dark:text-primary text-3xl md:text-4xl font-bold leading-tight tracking-[-0.015em]">
                            Những Gì Chúng Tôi Theo Đuổi
                        </h2>
                        <p class="mt-4 max-w-2xl mx-auto text-lg leading-8">
                            Các giá trị cốt lõi định hình văn hóa và kim chỉ nam cho mọi hoạt động của chúng tôi.
                        </p>
                    </div>
                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="bg-white dark:bg-background-dark dark:border dark:border-gray-700 p-8 rounded-xl shadow-sm text-center">
                            <div class="flex items-center justify-center bg-primary/20 rounded-full size-16 mx-auto mb-6">
                                <span class="material-symbols-outlined text-primary text-4xl">group</span>
                            </div>
                            <h3 class="text-xl font-bold text-secondary dark:text-white mb-2">Khách Hàng Là Trung Tâm</h3>
                            <p class="text-sm leading-relaxed">
                                Mọi quyết định của chúng tôi đều bắt đầu và kết thúc bằng việc mang lại giá trị tốt nhất cho khách hàng.
                            </p>
                        </div>
                        <div class="bg-white dark:bg-background-dark dark:border dark:border-gray-700 p-8 rounded-xl shadow-sm text-center">
                            <div class="flex items-center justify-center bg-primary/20 rounded-full size-16 mx-auto mb-6">
                                <span class="material-symbols-outlined text-primary text-4xl">verified</span>
                            </div>
                            <h3 class="text-xl font-bold text-secondary dark:text-white mb-2">Minh Bạch & Tin Cậy</h3>
                            <p class="text-sm leading-relaxed">
                                Chúng tôi cam kết cung cấp thông tin rõ ràng, chính xác và xây dựng một nền tảng đáng tin cậy.
                            </p>
                        </div>
                        <div class="bg-white dark:bg-background-dark dark:border dark:border-gray-700 p-8 rounded-xl shadow-sm text-center">
                            <div class="flex items-center justify-center bg-primary/20 rounded-full size-16 mx-auto mb-6">
                                <span class="material-symbols-outlined text-primary text-4xl">auto_awesome</span>
                            </div>
                            <h3 class="text-xl font-bold text-secondary dark:text-white mb-2">Không Ngừng Đổi Mới</h3>
                            <p class="text-sm leading-relaxed">
                                Luôn tìm kiếm và áp dụng công nghệ mới để cải tiến sản phẩm và nâng cao trải nghiệm người dùng.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Đội ngũ Section -->
                <section>
                    <div class="text-center mb-12">
                        <h2 class="text-secondary dark:text-primary text-3xl md:text-4xl font-bold leading-tight tracking-[-0.015em]">
                            Gặp Gỡ Đội Ngũ
                        </h2>
                        <p class="mt-4 max-w-2xl mx-auto text-lg leading-8">
                            Chúng tôi là một tập thể gồm những con người đầy nhiệt huyết, cùng chung tay xây dựng một dịch vụ tuyệt vời.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        <div class="text-center">
                            <img class="rounded-full w-32 h-32 object-cover mx-auto mb-4 shadow-md" 
                                 alt="Portrait of John Doe" 
                                 src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=faces"/>
                            <h3 class="font-bold text-lg text-secondary dark:text-white">John Doe</h3>
                            <p class="text-primary text-sm">CEO & Founder</p>
                        </div>
                        <div class="text-center">
                            <img class="rounded-full w-32 h-32 object-cover mx-auto mb-4 shadow-md" 
                                 alt="Portrait of Jane Smith" 
                                 src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop&crop=faces"/>
                            <h3 class="font-bold text-lg text-secondary dark:text-white">Jane Smith</h3>
                            <p class="text-primary text-sm">Head of Operations</p>
                        </div>
                        <div class="text-center">
                            <img class="rounded-full w-32 h-32 object-cover mx-auto mb-4 shadow-md" 
                                 alt="Portrait of Peter Jones" 
                                 src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop&crop=faces"/>
                            <h3 class="font-bold text-lg text-secondary dark:text-white">Peter Jones</h3>
                            <p class="text-primary text-sm">CTO</p>
                        </div>
                        <div class="text-center">
                            <img class="rounded-full w-32 h-32 object-cover mx-auto mb-4 shadow-md" 
                                 alt="Portrait of Sarah Miller" 
                                 src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop&crop=faces"/>
                            <h3 class="font-bold text-lg text-secondary dark:text-white">Sarah Miller</h3>
                            <p class="text-primary text-sm">Marketing Director</p>
                        </div>
                    </div>
                </section>

                <!-- CTA Section -->
                <section class="bg-secondary dark:bg-gray-800 rounded-xl p-12 text-center">
                    <h2 class="text-white text-3xl font-bold">Sẵn sàng cho chuyến đi tiếp theo?</h2>
                    <p class="text-white/80 mt-3 mb-8 max-w-xl mx-auto">
                        Khám phá hàng trăm dòng xe chất lượng cao và đặt ngay chiếc xe phù hợp cho hành trình của bạn.
                    </p>
                    <a href="<?php echo $base_path ? $base_path . '/cars/index.php' : 'cars/index.php'; ?>" 
                       class="inline-flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-8 bg-primary text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-opacity-90 transition-colors">
                        <span class="truncate">Khám Phá Các Dòng Xe</span>
                    </a>
                </section>
            </div>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>
</body>
</html>

