<?php
// templates/message_box.php
// โค้ดสำหรับสร้างกล่องข้อความ (Modal) ที่จะถูกควบคุมโดย JavaScript
?>
<!-- Message Box Container -->
<div id="message-box" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center p-4 z-50">
    <!-- Modal Dialog -->
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0" id="message-dialog">
        <!-- Header -->
        <div class="flex justify-between items-center p-4 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-800" id="message-title">หัวข้อข้อความ</h3>
            <button class="text-gray-400 hover:text-red-500 transition" onclick="hideMessage()">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6">
            <p id="message-text" class="text-gray-700 leading-relaxed">
                <!-- เนื้อหาจะถูกใส่โดย JavaScript -->
            </p>
            <!-- Container สำหรับเนื้อหา HTML ที่ซับซ้อนขึ้น (ถ้ามี) -->
            <div id="message-content-container" class="hidden mt-4">
                <!-- เนื้อหา HTML จะถูกใส่โดย JavaScript -->
            </div>
        </div>

        <!-- Footer / Actions -->
        <div class="flex justify-end items-center p-4 bg-gray-50 rounded-b-xl space-x-3">
            <button class="py-2 px-5 rounded-lg text-sm font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300 transition" onclick="hideMessage()">
                ปิด
            </button>
            <button id="message-action-btn" class="hidden py-2 px-5 rounded-lg text-sm font-semibold bg-primary text-white hover:opacity-90 transition" onclick="confirmAction()">
                ยืนยัน
            </button>
        </div>
    </div>
    
    <script>
        // เพิ่ม animation เล็กน้อยตอนเปิด modal
        // ตรวจสอบว่า element message-box ถูกสร้างขึ้นแล้วหรือยัง
        const messageBoxObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "class") {
                    const dialog = document.getElementById('message-dialog');
                    if (!mutation.target.classList.contains('hidden')) {
                        // Use timeout to allow the element to be visible before transitioning
                        setTimeout(() => {
                            dialog.classList.remove('scale-95', 'opacity-0');
                            dialog.classList.add('scale-100', 'opacity-100');
                        }, 10);
                    } else {
                         dialog.classList.add('scale-95', 'opacity-0');
                         dialog.classList.remove('scale-100', 'opacity-100');
                    }
                }
            });
        });
        
        const messageBoxElement = document.getElementById('message-box');
        if (messageBoxElement) {
            messageBoxObserver.observe(messageBoxElement, {
                attributes: true
            });
        }
    </script>
</div>
