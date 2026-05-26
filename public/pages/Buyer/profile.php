<?php include '../../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 min-h-[calc(100vh-140px)]">

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gray-800 px-8 py-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">⚙️ ตั้งค่าโปรไฟล์และที่อยู่</h2>
                <p class="text-gray-300 text-sm mt-1">จัดการข้อมูลส่วนตัวที่ใช้ในการร่วมประมูลและจัดส่งผลงาน</p>
            </div>
        </div>

        <form id="profileForm" class="p-8">
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <div class="flex flex-col items-center space-y-3 w-full md:w-32">
                    <div class="relative group cursor-pointer">
                        <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-gray-100 bg-gray-50 flex items-center justify-center relative">
                            <img id="avatar-preview" src="" class="hidden w-full h-full object-cover" alt="Profile">
                            <div id="avatar-placeholder" class="text-4xl font-bold text-gray-300">U</div>
                            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                            </div>
                        </div>
                        <input type="file" id="avatar_img" accept="image/jpeg, image/png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewAvatar(this)">
                    </div>
                    <p class="text-[10px] text-gray-400">คลิกเพื่อเปลี่ยนรูป</p>
                </div>

                <div class="flex-grow w-full space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">ชื่อที่แสดง (Display Name)</label>
                        <input type="text" id="display_name" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50" placeholder="นามแฝงของคุณ">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">🏠 ที่อยู่จัดส่งผลงาน (สำหรับผู้ซื้อ)</label>
                        <textarea id="address" rows="4" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary outline-none transition bg-gray-50" placeholder="ระบุบ้านเลขที่, ถนน, แขวง/ตำบล, เขต/อำเภอ, จังหวัด, รหัสไปรษณีย์ และเบอร์โทรศัพท์ติดต่อ"></textarea>
                        <p class="text-[11px] text-orange-500 mt-2 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path></svg>
                            กรุณาระบุที่อยู่ให้ชัดเจน เพื่อความถูกต้องในการจัดส่งหากคุณประมูลชนะ
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" id="btnUpdateProfile" class="w-full md:w-auto px-10 py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold transition shadow-lg">
                            บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    </div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadProfileData();
    });

    function loadProfileData() {
        const token = localStorage.getItem('jwt_token');
        fetch('/auction_of_paintings/api/index.php?route=v1/user/profile', {
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success') {
                const user = result.data;
                document.getElementById('display_name').value = user.display_name || '';
                document.getElementById('address').value = user.address || '';
                
                if (user.avatar_url) {
                    const preview = document.getElementById('avatar-preview');
                    preview.src = user.avatar_url;
                    preview.classList.remove('hidden');
                    document.getElementById('avatar-placeholder').classList.add('hidden');
                }
            }
        });
    }

    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const preview = document.getElementById('avatar-preview');
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                document.getElementById('avatar-placeholder').classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const token = localStorage.getItem('jwt_token');
        const btn = document.getElementById('btnUpdateProfile');
        
        btn.disabled = true; btn.innerHTML = 'กำลังบันทึก...';

        const formData = new FormData();
        formData.append('display_name', document.getElementById('display_name').value);
        formData.append('address', document.getElementById('address').value);
        
        const avatarFile = document.getElementById('avatar_img').files[0];
        if (avatarFile) formData.append('avatar', avatarFile);

        fetch('/auction_of_paintings/api/index.php?route=v1/user/profile/update', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('สำเร็จ', 'อัปเดตข้อมูลโปรไฟล์และที่อยู่จัดส่งแล้ว', 'success').then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        })
        .finally(() => {
            btn.disabled = false; btn.innerHTML = 'บันทึกข้อมูลส่วนตัว';
        });
    });
</script>

<?php include '../../layouts/footer.php'; ?>