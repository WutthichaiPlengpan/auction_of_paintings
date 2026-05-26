<?php include 'layouts/header.php'; ?>

<div class="flex items-center justify-center min-h-[calc(100vh-140px)] p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden my-8 border border-gray-100">
        <div class="p-8 sm:p-10 text-center">
            
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-4">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">ตั้งค่าความปลอดภัย (2FA)</h2>
                <p class="text-sm text-gray-500 mt-2">เปิดแอป Microsoft Authenticator หรือ Google Authenticator เพื่อสแกน QR Code นี้</p>
            </div>

            <div id="qr-container" class="flex justify-center mb-6 bg-gray-50 p-4 rounded-2xl border border-gray-200 min-h-[250px] items-center">
                <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>

            <div class="mb-6 text-left">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">รหัสตั้งค่า (ถ้าสแกนไม่ได้)</label>
                <div class="bg-gray-100 p-3 rounded-lg flex justify-between items-center">
                    <code id="secret-key" class="text-gray-800 font-mono text-sm tracking-widest">กำลังโหลด...</code>
                    <button onclick="copySecret()" class="text-primary hover:text-indigo-700 text-sm font-medium">คัดลอก</button>
                </div>
            </div>

            <button id="btnFinish" onclick="window.location.href='index.php'"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-primary hover:bg-indigo-700 focus:outline-none transition">
                ดำเนินการเสร็จสิ้น
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // ดึง Token ที่เก็บไว้ตอน Login
        const token = localStorage.getItem('jwt_token');

        if (!token) {
            Swal.fire({ icon: 'warning', title: 'กรุณาเข้าสู่ระบบก่อน', confirmButtonColor: '#4F46E5' })
            .then(() => { window.location.href = 'login.php'; });
            return;
        }

        // ยิง API ไปขอ QR Code
       fetch('/auction_of_paintings/api/index.php?route=v1/auth/generate-2fa', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token, 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // แสดง QR Code (แปลงจาก base64 SVG)
                const qrImg = `<img src="data:image/svg+xml;base64,${data.qr_svg}" alt="QR Code" class="w-48 h-48">`;
                document.getElementById('qr-container').innerHTML = qrImg;
                
                // แสดงรหัส Secret
                document.getElementById('secret-key').innerText = data.secret;
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // ฟังก์ชันกดคัดลอกรหัส
    function copySecret() {
        const text = document.getElementById('secret-key').innerText;
        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({ icon: 'success', title: 'คัดลอกรหัสแล้ว', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
        });
    }
</script>

<?php include 'layouts/footer.php'; ?>