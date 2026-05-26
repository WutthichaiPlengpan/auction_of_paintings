<?php 
// ดึงส่วนหัวเว็บ (Header) และ CSS/JS Libraries มาแสดง
include 'layouts/header.php'; 
?>

<div class="flex items-center justify-center min-h-[calc(100vh-140px)] p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden my-8 border border-gray-100">
        <div class="p-8 sm:p-10">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900">สร้างบัญชีใหม่</h2>
                <p class="text-sm text-gray-500 mt-1">เข้าร่วมประมูลงานศิลปะสุดพิเศษกับเรา</p>
            </div>

            <form id="registerForm" class="space-y-5">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คุณต้องการใช้งานในฐานะใด?</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer rounded-xl border border-gray-300 bg-white p-4 shadow-sm hover:bg-indigo-50 focus-within:ring-2 focus-within:ring-primary transition-all">
                            <input type="radio" name="role" value="bidder" class="sr-only peer" checked>
                            <div class="flex flex-col text-center w-full">
                                <span class="block text-sm font-bold text-gray-900 peer-checked:text-primary">ผู้ประมูล</span>
                                <span class="mt-1 block text-xs text-gray-500">ซื้อผลงาน</span>
                            </div>
                            <div class="absolute inset-0 rounded-xl border-2 border-transparent peer-checked:border-primary pointer-events-none" aria-hidden="true"></div>
                        </label>

                        <label class="relative flex cursor-pointer rounded-xl border border-gray-300 bg-white p-4 shadow-sm hover:bg-indigo-50 focus-within:ring-2 focus-within:ring-primary transition-all">
                            <input type="radio" name="role" value="seller" class="sr-only peer">
                            <div class="flex flex-col text-center w-full">
                                <span class="block text-sm font-bold text-gray-900 peer-checked:text-primary">ผู้ลงผลงาน</span>
                                <span class="mt-1 block text-xs text-gray-500">ลงประมูล</span>
                            </div>
                            <div class="absolute inset-0 rounded-xl border-2 border-transparent peer-checked:border-primary pointer-events-none" aria-hidden="true"></div>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="ตั้งชื่อผู้ใช้งานของคุณ">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="you@example.com">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                    <input type="tel" id="phone" name="phone" required pattern="[0-9]{10}" maxlength="10"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="08XXXXXXXX (เฉพาะตัวเลข 10 หลัก)">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required minlength="8"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="รหัสผ่านที่คาดเดายาก">
                    
                    <div class="mt-3 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-medium text-gray-500">ระดับความปลอดภัย:</span>
                            <span id="strength-text" class="text-xs font-bold text-red-500">อ่อนมาก</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mb-3">
                            <div id="strength-bar" class="bg-red-500 h-1.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <ul class="text-xs space-y-1">
                            <li id="req-length" class="text-red-500 flex items-center"><span class="mr-2 text-lg leading-none">&times;</span> อย่างน้อย 8 ตัวอักษร</li>
                            <li id="req-upper" class="text-red-500 flex items-center"><span class="mr-2 text-lg leading-none">&times;</span> ตัวพิมพ์ใหญ่ (A-Z)</li>
                            <li id="req-lower" class="text-red-500 flex items-center"><span class="mr-2 text-lg leading-none">&times;</span> ตัวพิมพ์เล็ก (a-z)</li>
                            <li id="req-number" class="text-red-500 flex items-center"><span class="mr-2 text-lg leading-none">&times;</span> ตัวเลข (0-9)</li>
                            <li id="req-special" class="text-red-500 flex items-center"><span class="mr-2 text-lg leading-none">&times;</span> อักขระพิเศษ (เช่น !@#$%)</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่านอีกครั้ง</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="กรอกรหัสผ่านอีกครั้ง">
                </div>

                <div id="turnstile-placeholder" class="mt-4 flex justify-center">
                    </div>

                <div class="flex items-start mt-4">
                    <div class="flex items-center h-5">
                        <input id="terms" name="terms" type="checkbox" required
                            class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="font-medium text-gray-600">ฉันยอมรับ <a href="#" class="text-primary hover:underline">เงื่อนไขการให้บริการ</a></label>
                    </div>
                </div>

                <button type="submit" id="btnSubmit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-primary hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition mt-6">
                    สมัครสมาชิก
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    มีบัญชีอยู่แล้วใช่ไหม? 
                    <a href="login.php" class="font-medium text-primary hover:underline">เข้าสู่ระบบ</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // --- ระบบตรวจสอบความปลอดภัยรหัสผ่าน ---
    let isPasswordStrong = false;
    const passwordInput = document.getElementById('password');
    
    passwordInput.addEventListener('input', function() {
        const val = passwordInput.value;
        let strength = 0;

        const rules = [
            { id: 'req-length', regex: /.{8,}/ },
            { id: 'req-upper', regex: /[A-Z]/ },
            { id: 'req-lower', regex: /[a-z]/ },
            { id: 'req-number', regex: /[0-9]/ },
            { id: 'req-special', regex: /[^A-Za-z0-9]/ }
        ];

        rules.forEach(rule => {
            const el = document.getElementById(rule.id);
            if (rule.regex.test(val)) {
                el.classList.replace('text-red-500', 'text-green-500');
                el.innerHTML = '<span class="mr-2 text-lg leading-none">&check;</span> ' + el.innerText.substring(2);
                strength++;
            } else {
                el.classList.replace('text-green-500', 'text-red-500');
                el.innerHTML = '<span class="mr-2 text-lg leading-none">&times;</span> ' + el.innerText.substring(2);
            }
        });

        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        strengthBar.style.width = (strength / 5) * 100 + '%';

        if (strength === 0) {
            strengthBar.className = 'bg-red-500 h-1.5 rounded-full transition-all duration-300';
            strengthText.textContent = 'อ่อนมาก';
            strengthText.className = 'text-xs font-bold text-red-500';
            isPasswordStrong = false;
        } else if (strength <= 2) {
            strengthBar.className = 'bg-red-400 h-1.5 rounded-full transition-all duration-300';
            strengthText.textContent = 'อ่อน';
            strengthText.className = 'text-xs font-bold text-red-400';
            isPasswordStrong = false;
        } else if (strength <= 4) {
            strengthBar.className = 'bg-yellow-500 h-1.5 rounded-full transition-all duration-300';
            strengthText.textContent = 'ปานกลาง';
            strengthText.className = 'text-xs font-bold text-yellow-500';
            isPasswordStrong = false;
        } else {
            strengthBar.className = 'bg-green-500 h-1.5 rounded-full transition-all duration-300';
            strengthText.textContent = 'ปลอดภัยสูง';
            strengthText.className = 'text-xs font-bold text-green-500';
            isPasswordStrong = true;
        }
    });

    // --- 🚀 ระบบ Submit Form และยิง API ---
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!isPasswordStrong) {
            Swal.fire({
                icon: 'warning',
                title: 'รหัสผ่านยังไม่ปลอดภัย',
                text: 'กรุณาตั้งรหัสผ่านให้ตรงตามเงื่อนไขทุกข้อ',
                confirmButtonColor: '#4F46E5'
            });
            return;
        }

        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const btnSubmit = document.getElementById('btnSubmit');

        if (password !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'รหัสผ่านไม่ตรงกัน',
                text: 'กรุณาตรวจสอบการยืนยันรหัสผ่านอีกครั้ง',
                confirmButtonColor: '#4F46E5'
            });
            return;
        }

        // ดึงข้อมูลทั้งหมด รวมถึงเบอร์โทร
        const role = document.querySelector('input[name="role"]:checked').value;
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value; // 🆕 ดึงค่าเบอร์โทร

        btnSubmit.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังประมวลผล...';
        btnSubmit.disabled = true;
        btnSubmit.classList.add('opacity-70', 'cursor-not-allowed');

        // ข้อมูลที่เตรียมส่งไปยัง API
        const requestData = {
            role: role,
            username: username,
            email: email,
            phone: phone, // 🆕 ส่งเบอร์โทรไปให้ PHP
            password: password
        };

        fetch('../api/index.php?route=v1/auth/register', {
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
                let successMessage = role === 'seller' ? 
                    'สมัครสมาชิกผู้ลงผลงานสำเร็จ! ระบบกำลังพาท่านไปเข้าสู่ระบบเพื่อทำ e-KYC' : 
                    'สมัครสมาชิกสำเร็จ! ระบบกำลังพาท่านไปยังหน้าเข้าสู่ระบบ';

                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: successMessage,
                    showConfirmButton: true,
                    confirmButtonColor: '#4F46E5',
                    timer: 3000
                }).then(() => {
                    window.location.href = 'login.php';
                });
            } else { 
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message || 'ไม่สามารถสมัครสมาชิกได้',
                    confirmButtonColor: '#4F46E5'
                });
                
                btnSubmit.innerHTML = 'สมัครสมาชิก';
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
                text: 'กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ตหรือลองใหม่ภายหลัง',
                confirmButtonColor: '#4F46E5'
            });
            
            btnSubmit.innerHTML = 'สมัครสมาชิก';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed');
        });
    });
</script>

<?php 
// ดึงส่วนท้ายเว็บ (Footer) มาแสดง
include 'layouts/footer.php'; 
?>