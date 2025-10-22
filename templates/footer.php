<?php
// templates/footer.php
// ส่วนท้ายของเว็บไซต์
// ดึงข้อมูล Global Settings อีกครั้งเผื่อมีการเปลี่ยนแปลง
$global_settings = get_global_settings($mysqli);
?>
        </main>
        
        <!-- ส่วนท้ายเว็บไซต์ (Footer) -->
        <footer class="mt-10 bg-gray-800 text-white rounded-xl shadow-lg p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-sm">
                 <div>
                    <h4 class="text-xl font-bold mb-3 text-primary">
                        <i class="fa-solid fa-running mr-2"></i> SISAKET PAO RUN
                    </h4>
                    <p class="text-gray-400 mb-4">
                        แพลตฟอร์มรับสมัครงานวิ่งแบบ Multi-Event ที่ได้มาตรฐาน รองรับกิจกรรมต่อเนื่องทุกปี
                    </p>
                    <p class="text-gray-300 mb-2">
                        <i class="fa-solid fa-envelope w-4 mr-2 text-primary"></i> 
                        <span id="global-email"><?= e($global_settings['system_email'] ?? 'support@email.com') ?></span>
                    </p>
                    <p class="text-gray-300">
                        <i class="fa-solid fa-phone w-4 mr-2 text-primary"></i> 
                        <span id="global-phone"><?= e($global_settings['system_phone'] ?? '02-XXX-XXXX') ?></span> (ฝ่ายสนับสนุน)
                    </p>
                </div>

                <div>
                    <h4 class="text-lg font-semibold mb-3 border-b border-gray-700 pb-1">ลิงก์ด่วน</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php?page=home" class="text-gray-300 hover:text-primary transition">หน้าหลักกิจกรรม</a></li>
                        <li><a href="index.php?page=dashboard" class="text-gray-300 hover:text-primary transition">แดชบอร์ดผู้สมัคร</a></li>
                        <li><a href="index.php?page=search_runner" class="text-gray-300 hover:text-primary transition">ค้นหานักวิ่ง</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-semibold mb-3 border-b border-gray-700 pb-1">นโยบายและความน่าเชื่อถือ</h4>
                     <ul class="space-y-2 mb-4">
                        <li><a href="#" class="text-gray-300 hover:text-primary transition">นโยบายความเป็นส่วนตัว</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-primary transition">ข้อกำหนดและเงื่อนไข</a></li>
                    </ul>
                    
                    <h4 class="text-lg font-semibold mb-2">สำหรับนักวิ่ง</h4>
                    <div class="flex space-x-2">
                         <a href="index.php?page=dashboard" class="text-gray-300 hover:text-primary transition text-sm font-semibold">
                            <i class="fa-solid fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                        </a>
                        <span class="text-gray-500">|</span>
                        <a href="index.php?page=register_member" class="text-gray-300 hover:text-primary transition text-sm font-semibold">
                            <i class="fa-solid fa-user-plus mr-1"></i> สมัครสมาชิก
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-gray-700 flex justify-between items-center text-xs text-gray-500">
                <span>&copy; <?= date('Y') ?> SISAKET PAO RUN System. All rights reserved.</span>
                <a href="admin/login.php" class="text-gray-400 hover:text-red-400 transition text-xs font-semibold">
                    <i class="fa-solid fa-lock mr-1"></i> เข้าสู่ระบบเจ้าหน้าที่
                </a>
            </div>
        </footer>

        <?php include 'message_box.php'; ?>

        <!-- Image Gallery Lightbox Modal -->
        <div id="gallery-modal" class="hidden fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center p-4 z-[100]">
            <div class="relative w-full h-full flex items-center justify-center">
                <!-- Close Button -->
                <button onclick="closeGallery()" class="absolute top-2 right-4 text-white text-5xl font-bold z-10">&times;</button>

                <!-- Main Image Display -->
                <img id="gallery-image" src="" class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg">

                <!-- Prev/Next Buttons -->
                <button onclick="prevGalleryImage()" class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-black/30 p-3 rounded-full text-2xl hover:bg-black/50 transition"><i class="fa-solid fa-chevron-left"></i></button>
                <button onclick="nextGalleryImage()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-black/30 p-3 rounded-full text-2xl hover:bg-black/50 transition"><i class="fa-solid fa-chevron-right"></i></button>

                <!-- Counter and Title -->
                <div id="gallery-caption" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white bg-black/50 px-4 py-2 rounded-lg text-center">
                    <p id="gallery-title" class="font-bold"></p>
                    <p id="gallery-counter" class="text-sm"></p>
                </div>
            </div>
        </div>


    </div> <!-- ปิด #app-container -->

    <script>
        // --- Centralized Modal/Lightbox Logic ---
        
        // --- Message Box ---
        function showMessage(title, text) {
            const box = document.getElementById('message-box');
            if (box) {
                document.getElementById('message-title').textContent = title;
                document.getElementById('message-text').textContent = text;
                document.getElementById('message-text').classList.remove('hidden');
                document.getElementById('message-content-container').classList.add('hidden');
                document.getElementById('message-action-btn').classList.add('hidden');
                box.classList.remove('hidden');
            }
        }

        function hideMessage() {
            const box = document.getElementById('message-box');
            if (box) {
                box.classList.add('hidden');
            }
        }
        
        // --- Image Gallery Lightbox ---
        let galleryImages = [];
        let currentImageIndex = 0;
        let galleryTitleText = '';

        function openGallery(title, images, startIndex = 0) {
            if (!images || images.length === 0) return;
            
            galleryTitleText = title;
            galleryImages = images;
            currentImageIndex = startIndex;
            
            document.getElementById('gallery-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; 
            
            displayCurrentImage();
        }

        function closeGallery() {
            document.getElementById('gallery-modal').classList.add('hidden');
            document.body.style.overflow = 'auto'; 
        }

        function displayCurrentImage() {
            const imgElement = document.getElementById('gallery-image');
            const titleElement = document.getElementById('gallery-title');
            const counterElement = document.getElementById('gallery-counter');
            
            imgElement.src = `./${galleryImages[currentImageIndex]}`; 
            titleElement.textContent = galleryTitleText;
            counterElement.textContent = `${currentImageIndex + 1} / ${galleryImages.length}`;
        }

        function nextGalleryImage() {
            currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
            displayCurrentImage();
        }

        function prevGalleryImage() {
            currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
            displayCurrentImage();
        }
    </script>
</body>
</html>

