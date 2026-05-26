<?php 
// ดึง Header จากโฟลเดอร์ layouts
include 'layouts/header.php'; 
?>

<div class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full min-h-[calc(100vh-140px)]">
    
    <div class="text-center mb-12">
        <span class="text-indigo-600 font-bold text-sm uppercase tracking-widest bg-indigo-50 px-4 py-1.5 rounded-full">Contact Us</span>
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-3 mb-4">📞 ติดต่อเรา</h1>
        <p class="text-gray-500 max-w-2xl mx-auto">พบปัญหาการใช้งาน มีข้อสงสัย หรือต้องการแนะนำติชมระบบ สามารถติดต่อเราได้ตลอดเวลา</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1 space-y-4">
            
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex items-start gap-4">
                <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center text-red-500 text-2xl flex-shrink-0">
                    ✉️
                </div>
                <div class="min-w-0 flex-grow">
                    <h3 class="font-bold text-gray-900 mb-1">อีเมลติดต่อกลาง</h3>
                    <p class="text-sm text-gray-500 truncate mb-3">welcomplengpan@gmail.com</p>
                    <a href="mailto:welcomplengpan@gmail.com" class="inline-flex items-center text-xs font-bold text-red-600 hover:underline">
                        ส่งอีเมลหาเรา ➔
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex items-start gap-4">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-green-500 text-2xl flex-shrink-0">
                    💬
                </div>
                <div class="min-w-0 flex-grow">
                    <h3 class="font-bold text-gray-900 mb-1">LINE Official Account</h3>
                    <p class="text-sm text-gray-500 truncate mb-3">@artbids (มี @ ข้างหน้า)</p>
                    <a href="https://line.me/ti/p/~YOUR_LINE_ID" target="_blank" class="inline-flex items-center text-xs font-bold text-green-600 hover:underline">
                        เพิ่มเพื่อนใน LINE ➔
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 text-2xl flex-shrink-0">
                    👥
                </div>
                <div class="min-w-0 flex-grow">
                    <h3 class="font-bold text-gray-900 mb-1">Facebook Page</h3>
                    <p class="text-sm text-gray-500 truncate mb-3">ArtBids - แพลตฟอร์มประมูลภาพวาด</p>
                    <a href="https://facebook.com/YOUR_PAGE" target="_blank" class="inline-flex items-center text-xs font-bold text-blue-600 hover:underline">
                        ไปที่หน้าเพจ ➔
                    </a>
                </div>
            </div>

        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-200 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-2">📥 ส่งข้อความถึงผู้ดูแลระบบ</h2>
                <p class="text-gray-500 text-sm mb-6">กรอกข้อมูลด้านล่าง ทีมงานจะตรวจสอบและติดต่อกลับผ่านอีเมลของคุณโดยเร็วที่สุด</p>

                <form id="contactForm" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">ชื่อผู้ติดต่อ</label>
                            <input type="text" id="contact_name" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50" placeholder="สมชาย ใจดี">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">อีเมลติดต่อกลับ</label>
                            <input type="email" id="contact_email" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50" placeholder="yourname@email.com">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">หัวข้อข้อความ</label>
                        <input type="text" id="contact_subject" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50" placeholder="เช่น สอบถามเรื่องการยืนยันตัวตน, แจ้งปัญหาการใช้งาน">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">รายละเอียดข้อความ</label>
                        <textarea id="contact_message" rows="5" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50" placeholder="พิมพ์ข้อความของคุณที่นี่..."></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" id="btnSubmitContact" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3.5 px-6 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                            <span>🚀 ส่งข้อความถึงเรา</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-5 mt-8 flex items-center gap-4 text-sm text-gray-600">
        <span class="text-xl">⏰</span>
        <p><strong>เวลาทำการของทีมงานผู้ดูแลระบบ:</strong> วันจันทร์ - วันอาทิตย์ เวลา 09:00 น. - 22:00 น. (ระบบประมูลออนไลน์ทำงานอัตโนมัติ 24 ชั่วโมง)</p>
    </div>

</div>

<script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitContact');
        
        btn.disabled = true;
        btn.innerHTML = 'กำลังส่งข้อความ...';

        // 💡 ในสเตปนี้เราจำลองฟรอนต์เอนด์ให้แจ้งเตือนด้วย SweetAlert2 อย่างสวยงาม
        // หากต้องการผูกกับระบบหลังบ้านส่งเมลในอนาคต สามารถเปลี่ยนไปยิง fetch API ได้เลยครับ
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'ส่งข้อความสำเร็จ!',
                text: 'ขอบคุณที่ติดต่อเรา ทีมงานได้รับข้อความเรียบร้อยแล้ว และจะติดต่อกลับโดยเร็วที่สุด',
                confirmButtonColor: '#4F46E5'
            }).then(() => {
                document.getElementById('contactForm').reset();
                btn.disabled = false;
                btn.innerHTML = '<span>🚀 ส่งข้อความถึงเรา</span>';
            });
        }, 1000);
    });
</script>

<?php 
// ดึง Footer จากโฟลเดอร์ layouts
include 'layouts/footer.php'; 
?>