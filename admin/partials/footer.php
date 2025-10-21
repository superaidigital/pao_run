<?php
// admin/partials/footer.php
// ส่วนท้ายที่ใช้ร่วมกันในระบบ Admin
?>
    </main> <!-- ปิด Main Content -->

    <!-- Modal Box for Admin Panel -->
    <div id="message-box" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all duration-300" id="message-dialog">
            <div class="flex justify-between items-center p-4 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800" id="message-title"></h3>
                <button class="text-gray-400 hover:text-red-500 transition" onclick="hideMessage()">
                    <i class="fa-solid fa-times text-2xl"></i>
                </button>
            </div>
            <div class="p-6">
                <div id="message-content-container"></div>
            </div>
        </div>
    </div>
    
    <script>
        function hideMessage() {
            const box = document.getElementById('message-box');
            if (box) box.classList.add('hidden');
        }
    </script>
</body>
</html>

