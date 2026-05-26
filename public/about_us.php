<?php 
// ดึง Header จากโฟลเดอร์ layouts
include 'layouts/header.php'; 
?>

<div class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full min-h-[calc(100vh-140px)]">
    
    <div class="text-center mb-12">
        <span class="text-indigo-600 font-bold text-sm uppercase tracking-widest bg-indigo-50 px-4 py-1.5 rounded-full">About Us</span>
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-3 mb-4">🎨 เกี่ยวกับเรา & ความตั้งใจของเรา</h1>
        <p class="text-gray-500 max-w-2xl mx-auto">เรื่องราวเบื้องหลังการพัฒนาแพลตฟอร์มประมูลงานศิลปะที่ปลอดภัยและโปร่งใสที่สุด</p>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden p-6 sm:p-10 space-y-8">
        
        <div class="space-y-4">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <span class="mr-2 text-xl">💡</span> จุดเริ่มต้นจากเหตุการณ์จริง
            </h2>
            <p class="text-gray-650 leading-relaxed text-justify">
                แพลตฟอร์มประมูลผลงานศิลปะออนไลน์แห่งนี้ เริ่มต้นพัฒนาขึ้นจากเหตุการณ์จริงบนโซเชียลมีเดีย เกี่ยวกับกลโกงในกลุ่มประมูลภาพวาด ที่มิจฉาชีพใช้วิธีปลอมแปลงบัญชีผู้ใช้งาน ทั้งชื่อ รูปโปรไฟล์ และรายละเอียดจนคล้ายกับศิลปินหรือเจ้าของโพสต์ตัวจริงอย่างแนบเนียน แล้วทักไปหลอกลวงให้ผู้ชนะการประมูลโอนเงินเข้าบัญชีส่วนตัว ทำให้เกิดความเสียหายทั้งต่อทรัพย์สินและความเชื่อมั่นในชุมชนคนรักศิลปะ
            </p>
            <p class="text-gray-650 leading-relaxed text-justify">
                จากเหตุการณ์ดังกล่าว ทำให้เราตระหนักถึงข้อจำกัดของแพลตฟอร์มทั่วไป โดยเฉพาะเรื่อง <strong class="text-gray-900">การยืนยันตัวตนของผู้ใช้งาน ระบบตรวจสอบความโปร่งใสของการประมูล และการจัดการกับบัญชีที่มีพฤติกรรมทุจริต</strong> ซึ่งส่งผลกระทบโดยตรงต่อทั้งศิลปิน (ผู้ขาย) และนักสะสม (ผู้ซื้อ)
            </p>
        </div>

        <hr class="border-gray-100">

        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <span class="mr-2 text-xl">🛡️</span> พื้นที่ประมูลที่ปลอดภัยและโปร่งใส
            </h2>
            <p class="text-gray-650 leading-relaxed">
                ด้วยเหตุนี้ เราจึงตั้งใจพัฒนาแพลตฟอร์มประมูลภาพแห่งนี้ขึ้นมา เพื่อสร้างระบบนิเวศน์และพื้นที่ที่ปลอดภัยที่สุดสำหรับทุกคนที่รักในงานศิลปะ โดยเราได้ออกแบบและวางรากฐานระบบด้วยฟีเจอร์สำคัญ:
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                    <p class="font-bold text-gray-900 flex items-center mb-2">
                        <span class="text-indigo-600 mr-2">🔒</span> ระบบยืนยันตัวตน (e-KYC)
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed">ผู้ขายทุกคนต้องผ่านการตรวจสอบเอกสารและบัญชีธนาคารที่ถูกต้อง และมีการเข้ารหัสข้อมูลส่วนบุคคลที่ปลอดภัยสูงสุด</p>
                </div>
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                    <p class="font-bold text-gray-900 flex items-center mb-2">
                        <span class="text-green-600 mr-2">🤝</span> ระบบคนกลางถือเงิน (Escrow)
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed">ระบบจะเก็บรักษาเงินค่าประมูลไว้จนกว่าผู้ซื้อจะได้รับภาพวาดและกดยืนยันความถูกต้อง เงินจึงจะถูกโอนไปยังศิลปิน</p>
                </div>
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                    <p class="font-bold text-gray-900 flex items-center mb-2">
                        <span class="text-blue-600 mr-2">📈</span> การประมูลที่โปร่งใส
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed">บันทึกประวัติเสนอราคาชัดเจน ตรวจสอบได้แบบ Real-time และมีระบบป้องกันการปั่นราคาเพื่อความยุติธรรมที่สุด</p>
                </div>
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                    <p class="font-bold text-gray-900 flex items-center mb-2">
                        <span class="text-purple-600 mr-2">💎</span> ค่าธรรมเนียมที่เป็นธรรม
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed">จัดเก็บค่าคอมมิชชั่นในอัตราที่เหมาะสม เพื่อนำมาใช้ในการพัฒนาระบบ รักษาความปลอดภัย และสนับสนุนชุมชนศิลปะต่อไป</p>
                </div>
            </div>
        </div>

        <hr class="border-gray-100">

        <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-6 text-center">
            <p class="text-gray-700 leading-relaxed font-medium">
                " เราหวังเป็นอย่างยิ่งว่า แพลตฟอร์มนี้จะช่วยขจัดปัญหาการหลอกลวง และเปลี่ยนการประมูลภาพออนไลน์ให้เป็นเรื่องที่ปลอดภัย สบายใจ และเปี่ยมไปด้วยความสุขสำหรับทุกคน "
            </p>
            <p class="text-sm text-gray-500 mt-4">
                ขอบพระคุณศิลปิน นักสะสม และผู้ใช้งานทุกท่านที่สนับสนุนและเชื่อมั่นในเรา<br>
                <span class="font-bold text-indigo-600 mt-1 block">ทุกการสนับสนุนของคุณ คือกำลังสำคัญในการพัฒนาระบบให้ดียิ่งขึ้น</span>
            </p>
        </div>

    </div>
</div>

<?php 
// ดึง Footer จากโฟลเดอร์ layouts
include 'layouts/footer.php'; 
?>