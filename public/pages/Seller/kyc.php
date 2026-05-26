<?php
include '../../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <!-- 🟢 ส่วนที่ 1: ตั้งค่าโปรไฟล์ทั่วไป -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-800 px-8 py-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">ตั้งค่าโปรไฟล์ทั่วไป</h2>
                <p class="text-gray-300 text-sm mt-1">ข้อมูลนี้จะแสดงต่อสาธารณะให้ผู้ใช้งานท่านอื่นเห็น</p>
            </div>
        </div>

        <form id="profileForm" class="p-8">
            <div class="flex flex-col md:flex-row gap-8 items-center md:items-start">

                <!-- เปลี่ยนรูปโปรไฟล์ -->
                <div class="flex flex-col items-center space-y-3">
                    <div class="relative group cursor-pointer">
                        <div
                            class="w-32 h-32 rounded-full overflow-hidden border-4 border-gray-100 bg-gray-50 flex items-center justify-center relative">
                            <!-- รูปโปรไฟล์ปัจจุบัน -->
                            <img id="avatar-preview" src="https://ui-avatars.com/api/?name=User&background=random"
                                class="w-full h-full object-cover" alt="Profile">

                            <!-- Overlay ตอน Hover -->
                            <div
                                class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <input type="file" id="avatar_img" accept="image/jpeg, image/png"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            onchange="previewAvatar(this)">
                    </div>
                    <p class="text-xs text-gray-500">คลิกเพื่อเปลี่ยนรูป</p>
                </div>

                <!-- เปลี่ยนชื่อ Display Name -->
                <div class="flex-grow w-full space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อที่แสดงบนเว็บ (Display
                            Name)</label>
                        <input type="text" id="display_name" required
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-800 outline-none transition bg-gray-50 focus:bg-white"
                            placeholder="นามแฝงของคุณ">
                    </div>
                    <button type="submit" id="btnUpdateProfile"
                        class="w-full md:w-auto px-6 py-3 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-gray-900 hover:bg-black focus:outline-none transition">
                        บันทึกโปรไฟล์
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 🔵 ส่วนที่ 2: ยืนยันตัวตน KYC (โค้ดเดิมของคุณ) -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">

        <div class="bg-indigo-600 px-8 py-6 text-white">
            <h1 class="text-2xl font-bold">ยืนยันตัวตน (e-KYC)</h1>
            <p class="text-indigo-100 text-sm mt-1">เพื่อความปลอดภัย
                โปรดให้ข้อมูลตามความเป็นจริงและตรงกับบัญชีรับเงินของคุณ</p>
        </div>

        <form id="kycForm" class="p-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล (ตามบัตรประชาชน)</label>
                    <input type="text" id="real_name" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="นาย สมชาย ใจดี">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เลขประจำตัวประชาชน (13 หลัก)</label>
                    <input type="text" id="id_card_no" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="X-XXXX-XXXXX-XX-X">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ธนาคารรับเงิน</label>
                    <select id="bank_name" required
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50 focus:bg-white appearance-none">
                        <option value="">-- เลือกธนาคาร --</option>
                        <option value="KBank">ธนาคารกสิกรไทย</option>
                        <option value="SCB">ธนาคารไทยพาณิชย์</option>
                        <option value="BBL">ธนาคารกรุงเทพ</option>
                        <option value="KTB">ธนาคารกรุงไทย</option>
                        <option value="Krungsri">ธนาคารกรุงศรีอยุธยา</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เลขที่บัญชี</label>
                    <input type="text" id="bank_acc_no" required pattern="[0-9]+"
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50 focus:bg-white"
                        placeholder="กรอกเฉพาะตัวเลขบัญชี">
                </div>
            </div>

            <hr class="border-gray-100">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- ถ่ายรูปหน้าบัตรประชาชน -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-2">1. ถ่ายรูปหน้าบัตรประชาชน</label>
                    <p class="text-xs text-gray-500 mb-3">เห็นตัวอักษรชัดเจน ไม่มีแสงสะท้อนทับข้อมูลสำคัญ</p>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-2xl hover:bg-gray-50 transition cursor-pointer relative overflow-hidden"
                        id="id-dropzone">
                        <div class="space-y-1 text-center" id="id-placeholder">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                viewBox="0 0 48 48" aria-hidden="true">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="id_card_img"
                                    class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-indigo-500 focus-within:outline-none">
                                    <span>อัปโหลดไฟล์</span>
                                    <input id="id_card_img" type="file" class="sr-only" accept="image/jpeg, image/png"
                                        required onchange="previewKycImage(this, 'id-preview', 'id-placeholder')">
                                </label>
                            </div>
                        </div>
                        <img id="id-preview" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl" />
                    </div>
                </div>

                <!-- เซลฟี่คู่บัตร -->
                <div>
                    <label class="block text-sm font-bold text-gray-900 mb-2">2. ถ่ายรูปเซลฟี่คู่กับบัตร</label>
                    <p class="text-xs text-gray-500 mb-3">ถือบัตรประชาชนไว้ระดับคาง เห็นใบหน้าและบัตรชัดเจน</p>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-2xl hover:bg-gray-50 transition cursor-pointer relative overflow-hidden"
                        id="selfie-dropzone">
                        <div class="space-y-1 text-center" id="selfie-placeholder">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                viewBox="0 0 48 48" aria-hidden="true">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="selfie_img"
                                    class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-indigo-500 focus-within:outline-none">
                                    <span>อัปโหลดไฟล์</span>
                                    <input id="selfie_img" type="file" class="sr-only" accept="image/jpeg, image/png"
                                        required
                                        onchange="previewKycImage(this, 'selfie-preview', 'selfie-placeholder')">
                                </label>
                            </div>
                        </div>
                        <img id="selfie-preview"
                            class="hidden absolute inset-0 w-full h-full object-cover rounded-xl" />
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                <div class="flex items-start">
                    <div class="flex items-center h-5 mt-0.5">
                        <input id="accept_terms" name="accept_terms" type="checkbox" required
                            class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary transition-colors cursor-pointer">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="accept_terms" class="font-bold text-gray-900 cursor-pointer text-base">
                            ฉันยอมรับข้อตกลงและเงื่อนไขการให้บริการ
                        </label>
                        <p class="text-gray-600 mt-1.5 leading-relaxed">
                            ข้าพเจ้ายินยอมให้แพลตฟอร์มหักค่าธรรมเนียม (Commission Fee) ในอัตรา <span
                                class="font-bold text-red-500 text-base bg-red-50 px-2 py-0.5 rounded border border-red-100">17%</span>
                            ของยอดปิดประมูลสุทธิเมื่อผลงานถูกขายสำเร็จ
                        </p>

                        <div class="mt-3 bg-white/60 p-3 rounded-xl border border-gray-200">
                            <p class="text-[11px] text-gray-500 font-medium leading-relaxed flex items-start">
                                <svg class="w-4 h-4 text-gray-400 mr-1.5 flex-shrink-0" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><strong class="text-gray-600">หมายเหตุสำคัญ:</strong>
                                    ผู้ขายมีหน้าที่รับผิดชอบในการยื่นแบบแสดงรายการภาษีเงินได้บุคคลธรรมดาสำหรับรายได้จากการขายผลงานศิลปะด้วยตนเอง
                                    แพลตฟอร์มเป็นเพียงตัวกลางในการประมูลเท่านั้น</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pt-4">
                <button type="submit" id="btnSubmitKyc"
                    class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-sm text-lg font-bold text-white bg-primary hover:bg-indigo-700 focus:outline-none transition">
                    ส่งข้อมูลยืนยันตัวตน
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- โหลดข้อมูลโปรไฟล์ตั้งต้น ---
    document.addEventListener("DOMContentLoaded", () => {
        const token = localStorage.getItem('jwt_token');
        if (token) {
            const decoded = parseJwt(token); // ใช้ฟังก์ชันจาก header.php ได้เลย
            if (decoded) {
                document.getElementById('display_name').value = decoded.data.username;
                // ถ้ามี URL รูปภาพจากการดึง API ค่อยมาเซ็ตที่ document.getElementById('avatar-preview').src
            }
        }
    });

    // --- ระบบ Profile ---
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) { document.getElementById('avatar-preview').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('profileForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const token = localStorage.getItem('jwt_token');
        const btn = document.getElementById('btnUpdateProfile');

        btn.innerHTML = 'กำลังบันทึก...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('display_name', document.getElementById('display_name').value);
        if (document.getElementById('avatar_img').files[0]) {
            formData.append('avatar', document.getElementById('avatar_img').files[0]);
        }

        // 💡 เราจะต้องสร้าง API รับค่านี้ (เช่น v1/user/profile/update)
        fetch('/auction_of_paintings/api/index.php?route=v1/user/profile/update', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        }).then(async res => {
            // จำลองสำเร็จ
            Swal.fire('สำเร็จ', 'อัปเดตโปรไฟล์เรียบร้อยแล้ว', 'success');
            btn.innerHTML = 'บันทึกโปรไฟล์';
            btn.disabled = false;
        }).catch(() => {
            Swal.fire('ข้อผิดพลาด', 'อัปเดตไม่สำเร็จ', 'error');
            btn.innerHTML = 'บันทึกโปรไฟล์';
            btn.disabled = false;
        });
    });

    // --- ระบบ KYC (เหมือนเดิม) ---
    const idCardInput = document.getElementById('id_card_no');
    idCardInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        let formattedValue = '';
        if (value.length > 0) {
            formattedValue += value.substring(0, 1);
            if (value.length > 1) formattedValue += '-' + value.substring(1, 5);
            if (value.length > 5) formattedValue += '-' + value.substring(5, 10);
            if (value.length > 10) formattedValue += '-' + value.substring(10, 12);
            if (value.length > 12) formattedValue += '-' + value.substring(12, 13);
        }
        e.target.value = formattedValue;
    });

    function previewKycImage(input, previewId, placeholderId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById(placeholderId).classList.add('hidden');
                const preview = document.getElementById(previewId);
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('kycForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const token = localStorage.getItem('jwt_token');
        if (!token) { return Swal.fire('ข้อผิดพลาด', 'กรุณาเข้าสู่ระบบก่อน', 'error').then(() => window.location.href = '../../login.php'); }

        const cleanIdCardNo = document.getElementById('id_card_no').value.replace(/-/g, '');
        if (cleanIdCardNo.length !== 13) { return Swal.fire('ข้อมูลไม่ถูกต้อง', 'กรุณากรอกเลขบัตรประชาชนให้ครบ 13 หลัก', 'warning'); }

        const acceptTerms = document.getElementById('accept_terms');
        if (!acceptTerms.checked) {
            Swal.fire('กรุณายอมรับเงื่อนไข', 'คุณต้องกดยอมรับข้อตกลงการหักค่าธรรมเนียม 17% ก่อนดำเนินการต่อ', 'warning');
            return;
        }

        const btnSubmit = document.getElementById('btnSubmitKyc');
        btnSubmit.innerHTML = 'กำลังอัปโหลดข้อมูล...';
        btnSubmit.disabled = true;

        const formData = new FormData();
        formData.append('real_name', document.getElementById('real_name').value);
        formData.append('id_card_no', cleanIdCardNo);
        formData.append('bank_name', document.getElementById('bank_name').value);
        formData.append('bank_acc_no', document.getElementById('bank_acc_no').value);
        formData.append('id_card_img', document.getElementById('id_card_img').files[0]);
        formData.append('selfie_img', document.getElementById('selfie_img').files[0]);

        fetch('/auction_of_paintings/api/index.php?route=v1/seller/kyc', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        }).then(async response => {
            const data = await response.json();
            if (response.ok) {
                Swal.fire({ icon: 'success', title: 'ส่งข้อมูลเรียบร้อย!', text: 'กรุณารอแอดมินตรวจสอบ 1-2 วัน', confirmButtonColor: '#4F46E5' })
                    .then(() => window.location.href = '../../index.php');
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
                resetKycBtn();
            }
        }).catch(() => { Swal.fire('ข้อผิดพลาด', 'ติดต่อเซิร์ฟเวอร์ไม่ได้', 'error'); resetKycBtn(); });

        function resetKycBtn() {
            btnSubmit.innerHTML = 'ส่งข้อมูลยืนยันตัวตน';
            btnSubmit.disabled = false;
        }
    });
</script>

<?php include '../../layouts/footer.php'; ?>