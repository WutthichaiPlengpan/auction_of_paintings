<?php include '../../layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 min-h-[calc(100vh-140px)]">
    
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">👥 จัดการสมาชิก (Users)</h1>
            <p class="text-gray-500 mt-1">ควบคุมสิทธิ์และระงับการใช้งานบัญชีที่ทำผิดกฎ</p>
        </div>
        
        <div class="w-full md:w-72">
            <div class="relative">
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อ, อีเมล, เบอร์โทร..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 font-semibold">ข้อมูลผู้ใช้</th>
                        <th class="px-6 py-4 font-semibold">ติดต่อ</th>
                        <th class="px-6 py-4 font-semibold text-center">ประเภทบัญชี / บัตร ปชช.</th>
                        <th class="px-6 py-4 font-semibold text-center">สถานะ</th>
                        <th class="px-6 py-4 font-semibold text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="users-table-body" class="divide-y divide-gray-100">
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">กำลังโหลดข้อมูล...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let allUsers = [];

    document.addEventListener("DOMContentLoaded", () => {
        loadUsers();

        // ระบบค้นหา
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            const keyword = e.target.value.toLowerCase();
            const filtered = allUsers.filter(u => 
                u.username.toLowerCase().includes(keyword) || 
                u.email.toLowerCase().includes(keyword) || 
                (u.phone && u.phone.includes(keyword))
            );
            renderTable(filtered);
        });
    });

    function loadUsers() {
        const token = localStorage.getItem('jwt_token');

        fetch('/auction_of_paintings/api/index.php?route=v1/admin/users/list', {
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success') {
                allUsers = result.data;
                renderTable(allUsers);
            }
        });
    }

    function renderTable(dataList) {
        const tbody = document.getElementById('users-table-body');
        tbody.innerHTML = '';

        if (dataList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">ไม่พบข้อมูลผู้ใช้</td></tr>`;
            return;
        }

        dataList.forEach(user => {
            let roleBadge = user.role === 'admin' ? `<span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold">Admin</span>` : 
                           (user.role === 'seller' ? `<span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs font-bold">Seller</span>` : 
                                                     `<span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-bold">Buyer</span>`);
            
            let statusBadge = user.status === 'active' 
                ? `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">✅ ปกติ</span>`
                : `<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold animate-pulse">🚫 ถูกระงับ</span>`;

            let actionBtn = '';
            if (user.role !== 'admin') { // ป้องกันแอดมินแบนตัวเอง
                if (user.status === 'active') {
                    actionBtn = `<button onclick="toggleBan(${user.id}, 'ban', '${user.username}')" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-xl text-xs font-bold transition">ระงับบัญชี (Ban)</button>`;
                } else {
                    actionBtn = `<button onclick="toggleBan(${user.id}, 'unban', '${user.username}')" class="bg-green-50 hover:bg-green-100 text-green-600 border border-green-200 px-4 py-2 rounded-xl text-xs font-bold transition">ปลดแบน</button>`;
                }
            }

            const row = `
            <tr class="hover:bg-gray-50 transition ${user.status === 'banned' ? 'bg-red-50/30' : ''}">
                <td class="px-6 py-4">
                    <p class="font-bold text-gray-900">${user.username}</p>
                    <p class="text-xs text-gray-400 mt-1">สมัครเมื่อ: ${new Date(user.created_at).toLocaleDateString('th-TH')}</p>
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-gray-600">✉️ ${user.email}</p>
                    <p class="text-sm text-gray-600 mt-1">📞 ${user.phone || '-'}</p>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="mb-1">${roleBadge}</div>
                    ${user.id_card_display !== '-' ? `<p class="text-[10px] text-gray-500 font-mono">ID: ${user.id_card_display}</p>` : ''}
                </td>
                <td class="px-6 py-4 text-center">${statusBadge}</td>
                <td class="px-6 py-4 text-right">${actionBtn}</td>
            </tr>`;
            tbody.innerHTML += row;
        });
    }

    function toggleBan(userId, action, username) {
        if (action === 'ban') {
            Swal.fire({
                title: `ระงับบัญชี ${username}?`,
                text: "หากบัญชีนี้เป็นผู้ขาย เลขบัตรประชาชนจะถูกบันทึกลง Blacklist ด้วย",
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'ระบุเหตุผลการแบน...',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                confirmButtonText: 'ยืนยันการแบน'
            }).then((result) => {
                if (result.isConfirmed) executeToggleBan(userId, action, result.value || 'ทำผิดกฎแพลตฟอร์ม');
            });
        } else {
            executeToggleBan(userId, action, '');
        }
    }

    function executeToggleBan(userId, action, reason) {
        const token = localStorage.getItem('jwt_token');
        fetch('/auction_of_শিকpaintings/api/index.php?route=v1/admin/users/toggle-ban', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, action: action, reason: reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('สำเร็จ', data.message, 'success');
                loadUsers(); // โหลดข้อมูลใหม่
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        });
    }
</script>

<?php include '../../layouts/footer.php'; ?>