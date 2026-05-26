<?php
// ดึงส่วนหัวเว็บ (Header)
include 'layouts/header.php';
?>

<div class="flex items-center justify-center min-h-[calc(100vh-140px)] p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden my-8 border border-gray-100">
        <div class="p-8 sm:p-10">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900">เข้าสู่ระบบ</h2>
                <p class="text-sm text-gray-500 mt-1">ยินดีต้อนรับกลับสู่แพลตฟอร์ม ArtBids</p>
            </div>

            <form id="loginForm" class="space-y-5">

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้งาน หรือ
                        อีเมล</label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="กรอกชื่อผู้ใช้งาน หรือ อีเมล">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
                        <a href="#" class="text-xs font-medium text-primary hover:underline">ลืมรหัสผ่าน?</a>
                    </div>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="กรอกรหัสผ่านของคุณ">
                </div>

                <div id="otp-container" class="hidden">
                    <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">รหัส OTP (2FA)</label>
                    <input type="text" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white text-center tracking-[0.5em] font-bold text-xl"
                        placeholder="000000">
                    <p class="text-xs text-gray-500 mt-2 text-center">กรุณากรอกรหัส 6 หลักจากแอป Authenticator</p>
                </div>

                <button type="submit" id="btnSubmit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-primary hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition mt-6">
                    เข้าสู่ระบบ
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    ยังไม่มีบัญชีใช่ไหม?
                    <a href="register.php" class="font-medium text-primary hover:underline">สมัครสมาชิก</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    let isOtpPhase = false; // ตัวแปรเช็คว่ากำลังอยู่หน้ากรอก OTP หรือไม่

    document.getElementById('loginForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const otp = document.getElementById('otp').value;
        const btnSubmit = document.getElementById('btnSubmit');

        // เปลี่ยนสถานะปุ่มเป็น Loading
        btnSubmit.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...';
        btnSubmit.disabled = true;
        btnSubmit.classList.add('opacity-70', 'cursor-not-allowed');

        // เตรียมข้อมูลส่งไป API
        const requestData = {
            username: username,
            password: password
        };

        // ถ้าอยู่ในโหมด OTP ให้แนบรหัส 6 หลักไปด้วย
        if (isOtpPhase) {
            requestData.otp = otp;
        }

        fetch('../api/index.php?route=v1/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(async response => {
            const data = await response.json();

            if (response.ok) {
                // 🛑 กรณีที่ 1: รหัสผ่านถูก แต่เปิด 2FA ไว้ (ระบบต้องการ OTP)
                if (data.status === 'require_otp') {
                    isOtpPhase = true; // เปลี่ยนเป็นโหมด OTP
                    
                    // ซ่อนช่อง Username/Password และโชว์กล่อง OTP
                    document.getElementById('username').parentElement.classList.add('hidden');
                    document.getElementById('password').parentElement.classList.add('hidden');
                    document.getElementById('otp-container').classList.remove('hidden');
                    
                    Swal.fire({
                        icon: 'info',
                        title: 'กรุณากรอกรหัส OTP',
                        text: 'เปิดแอป Authenticator ของคุณเพื่อดูรหัส 6 หลัก',
                        timer: 2500,
                        showConfirmButton: false
                    });

                    btnSubmit.innerHTML = 'ยืนยันรหัสเข้าสู่ระบบ';
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
                } 
                // ✅ กรณีที่ 2: ล็อกอินสำเร็จ (ได้ Token)
                else if (data.token) {
                    localStorage.setItem('jwt_token', data.token);
                    localStorage.setItem('user_role', data.role);

                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: 'กำลังตรวจสอบสถานะความปลอดภัย...',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        
                        // 🛑 ถ้ายังไม่เคยตั้งค่า 2FA ให้บังคับเด้งไปหน้า Setup ทันที!
                        if (data.is_2fa_setup === false) {
                            window.location.href = 'setup_2fa.php';
                        } 
                        // ถ้าตั้งค่า 2FA แล้ว ก็ให้ไปหน้าหลักตามปกติ
                        else {
                            if (data.role === 'admin') {
                                // ไปหน้า Dashboard ของ Admin
                                window.location.href = '/auction_of_paintings/public/pages/Admin/dashboard';
                            } else if (data.role === 'seller') {
                                // ไปหน้า Dashboard ของ Seller (ผู้ขาย)
                                window.location.href = '/auction_of_paintings/public/pages/Seller/dashboard';
                            } else {
                                // ไปหน้าแรกสำหรับคนทั่วไป/ผู้ซื้อ
                                window.location.href = '/auction_of_paintings/public/index';
                            }
                        }
                    });
                }
            } else {
                // ❌ กรณีที่ 3: รหัสผ่าน หรือ OTP ผิด
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message || 'ข้อมูลไม่ถูกต้อง',
                    confirmButtonColor: '#4F46E5'
                });

                btnSubmit.innerHTML = isOtpPhase ? 'ยืนยันรหัสเข้าสู่ระบบ' : 'เข้าสู่ระบบ';
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
                
                // ถ้า OTP ผิด ให้เคลียร์ช่อง OTP ให้กรอกใหม่
                if (isOtpPhase) document.getElementById('otp').value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
                confirmButtonColor: '#4F46E5'
            });
            btnSubmit.innerHTML = isOtpPhase ? 'ยืนยันรหัสเข้าสู่ระบบ' : 'เข้าสู่ระบบ';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
        });
    });
</script>

<?php
// ดึงส่วนท้ายเว็บ (Footer)
include 'layouts/footer.php';
?>