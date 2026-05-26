<?php 
// ถอยกลับไป 2 โฟลเดอร์เพื่อเรียกใช้ header
include '../../layouts/header.php'; 
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 min-h-[calc(100vh-140px)]">
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        
        <div class="bg-indigo-600 px-8 py-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">➕ ลงผลงานประมูล</h1>
                <p class="text-indigo-100 text-sm mt-1">กรอกรายละเอียดผลงานศิลปะของคุณเพื่อเริ่มการประมูล</p>
            </div>
            <a href="dashboard.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-sm font-medium transition">
                กลับหน้า Dashboard
            </a>
        </div>

        <form id="auctionForm" class="p-8 space-y-8">
            
            <!-- ส่วนอัปโหลดรูปภาพหลัก -->
            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">รูปภาพผลงาน (ความละเอียดสูง)</label>
                <div class="mt-1 flex justify-center px-6 pt-10 pb-12 border-2 border-gray-300 border-dashed rounded-2xl hover:bg-gray-50 transition cursor-pointer relative overflow-hidden" id="img-dropzone">
                    <div class="space-y-2 text-center" id="img-placeholder">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600 justify-center">
                            <label for="product_img" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-indigo-500 focus-within:outline-none">
                                <span>คลิกเพื่ออัปโหลดรูปภาพ</span>
                                <input id="product_img" name="product_img" type="file" class="sr-only" accept="image/jpeg, image/png" required onchange="previewProductImage(this)">
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG ไม่เกิน 10MB</p>
                    </div>
                    <img id="product-preview" class="hidden absolute inset-0 w-full h-full object-contain bg-gray-100" />
                </div>
            </div>

            <!-- ข้อมูลผลงาน -->
            <div class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผลงาน</label>
                    <input type="text" id="title" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white" placeholder="เช่น The Silent Ocean">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดและแรงบันดาลใจ</label>
                    <textarea id="description" rows="4" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white" placeholder="อธิบายขนาด วัสดุที่ใช้ และแนวคิดของภาพวาดนี้..."></textarea>
                </div>
            </div>

            <!-- การตั้งราคาและเวลา -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 p-6 rounded-2xl border border-gray-100">
                <div>
                    <label for="start_price" class="block text-sm font-medium text-gray-700 mb-1">ราคาเริ่มต้น (บาท)</label>
                    <input type="number" id="start_price" min="1" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none" placeholder="0.00">
                </div>
                <div>
                    <label for="min_step" class="block text-sm font-medium text-gray-700 mb-1">บิดขั้นต่ำ (บาท/ครั้ง)</label>
                    <input type="number" id="min_step" min="1" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none" placeholder="เช่น 100">
                </div>
                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">วัน-เวลา สิ้นสุดการประมูล</label>
                    <input type="datetime-local" id="end_time" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" id="btnSubmit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-sm text-lg font-bold text-white bg-primary hover:bg-indigo-700 focus:outline-none transition">
                    ยืนยันการลงประมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ตรวจสอบสิทธิ์ก่อนเข้าใช้งาน
    document.addEventListener("DOMContentLoaded", () => {
        const token = localStorage.getItem('jwt_token');
        const role = localStorage.getItem('user_role');
        
        if (!token || role !== 'seller') {
            Swal.fire('ไม่มีสิทธิ์เข้าถึง', 'หน้านี้สงวนไว้สำหรับผู้ลงผลงานเท่านั้น', 'warning')
            .then(() => window.location.href = '/auction_of_paintings/public/index.php');
        }

        // ตั้งค่าเวลาขั้นต่ำสุดคือ "ปัจจุบัน + 1 ชั่วโมง"
        const now = new Date();
        now.setHours(now.getHours() + 1);
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('end_time').min = now.toISOString().slice(0,16);
    });

    // ฟังก์ชันแสดงตัวอย่างรูปภาพ
    function previewProductImage(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('img-placeholder').classList.add('hidden');
                const preview = document.getElementById('product-preview');
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    // 🚀 ฟังก์ชัน Submit ข้อมูลไปยัง API จริง
    document.getElementById('auctionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const token = localStorage.getItem('jwt_token');

        const btnSubmit = document.getElementById('btnSubmit');
        btnSubmit.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังอัปโหลดผลงาน...';
        btnSubmit.disabled = true;
        btnSubmit.classList.add('opacity-70', 'cursor-not-allowed');

        // เตรียมข้อมูลแบบ FormData (เพราะมีรูปภาพ)
        const formData = new FormData();
        formData.append('title', document.getElementById('title').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('start_price', document.getElementById('start_price').value);
        formData.append('min_step', document.getElementById('min_step').value);
        formData.append('end_time', document.getElementById('end_time').value);
        formData.append('product_img', document.getElementById('product_img').files[0]);

        // ยิง API
        fetch('/auction_of_paintings/api/index.php?route=v1/seller/auctions/create', {
            method: 'POST',
            headers: { 
                'Authorization': 'Bearer ' + token 
                // ไม่ต้องใส่ Content-Type เมื่อใช้ FormData
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json(); 
            
            if (response.ok) {
                // อัปโหลดสำเร็จ
                Swal.fire({
                    icon: 'success',
                    title: 'ลงผลงานสำเร็จ!',
                    text: data.message,
                    confirmButtonColor: '#4F46E5'
                }).then(() => {
                    window.location.href = 'dashboard.php';
                });
            } else {
                // 🛑 มี Error (เช่น KYC ไม่ผ่าน หรือ รูปผิดประเภท)
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถลงผลงานได้',
                    text: data.message,
                    confirmButtonColor: '#4F46E5'
                }).then(() => {
                    // ถ้ายังไม่ได้ทำ KYC ให้บังคับไปทำ
                    if (data.message.includes('ยืนยันตัวตน')) {
                        window.location.href = 'kyc.php';
                    }
                });
                resetButton();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            resetButton();
        });

        function resetButton() {
            btnSubmit.innerHTML = 'ยืนยันการลงประมูล';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    });
</script>

<?php include '../../layouts/footer.php'; ?>