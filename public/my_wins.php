<?php include 'layouts/header.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full min-h-[calc(100vh-140px)]">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">🏆 ผลงานที่ฉันชนะการประมูล</h1>
        <p class="text-gray-500 mt-1">ชำระเงินเข้าแพลตฟอร์ม และกดยืนยันเมื่อได้รับผลงาน เพื่อให้ระบบโอนเงินให้ศิลปิน</p>
    </div>

    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 mb-8 flex flex-col md:flex-row items-center gap-6 shadow-sm">
        <div class="bg-white p-4 rounded-xl shadow-sm flex-shrink-0">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Kasikornbank_Logo.svg/1200px-Kasikornbank_Logo.svg.png" class="h-12 object-contain" alt="KBank">
        </div>
        <div class="flex-grow">
            <p class="text-sm text-indigo-600 font-bold uppercase tracking-wider mb-1">บัญชีรับเงินของเว็บไซต์ (ตัวกลาง)</p>
            <p class="text-2xl font-bold text-gray-900 tracking-widest">123-4-56789-0</p>
            <p class="text-gray-600">ธนาคารกสิกรไทย • ชื่อบัญชี: บริษัท อาร์ตบิดส์ จำกัด</p>
        </div>
        <div class="text-sm text-gray-500 bg-white p-4 rounded-xl border border-indigo-100/50 shadow-sm md:max-w-xs">
            <p class="font-bold text-gray-700 flex items-center mb-1">
                <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                ระบบปลอดภัย 100%
            </p>
            เงินของคุณจะถูกเก็บไว้เป็นส่วนกลาง และจะโอนให้ผู้ขายเมื่อคุณได้รับของและกดยืนยันแล้วเท่านั้น
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 font-semibold">ผลงาน</th>
                        <th class="px-6 py-4 font-semibold text-right">ยอดประมูล</th>
                        <th class="px-6 py-4 font-semibold text-center">สถานะ</th>
                        <th class="px-6 py-4 font-semibold text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="wins-table-body" class="divide-y divide-gray-100">
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">กำลังโหลดข้อมูล...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="slipModal" class="hidden fixed inset-0 bg-gray-900/75 z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl transform transition-all">
        <h3 class="text-2xl font-bold text-gray-900 mb-2">อัปโหลดสลิปโอนเงิน</h3>
        <p class="text-gray-500 mb-4 text-sm">ยอดชำระ: <span id="modal-price" class="font-bold text-primary text-lg">0</span> บาท</p>
        
        <div class="bg-orange-50 border border-orange-100 p-3 rounded-xl mb-6">
            <p class="text-xs text-orange-600 font-medium">⚠️ ระบบจะจัดส่งผลงานตาม <strong>"ที่อยู่จัดส่ง"</strong> ที่คุณระบุไว้ในหน้าโปรไฟล์ กรุณาตรวจสอบให้แน่ใจว่ากรอกที่อยู่ครบถ้วนแล้ว</p>
        </div>

        <form id="slipForm">
            <input type="hidden" id="modal-tx-id" name="transaction_id">
            
            <label class="block w-full border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center cursor-pointer hover:bg-gray-50 transition mb-6">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span class="text-sm font-medium text-gray-900" id="file-name-display">คลิกเพื่อเลือกไฟล์รูปภาพสลิป</span>
                <input type="file" id="slip_image" name="slip_image" accept="image/jpeg, image/png" class="hidden" required onchange="document.getElementById('file-name-display').innerText = this.files[0].name">
            </label>

            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition">ยกเลิก</button>
                <button type="submit" id="btnSubmit" class="flex-1 bg-primary hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-md transition">ยืนยันการแจ้งโอน</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadMyWins();
    });

    function loadMyWins() {
        const token = localStorage.getItem('jwt_token');
        fetch('/auction_of_paintings/api/index.php?route=v1/buyer/wins', {
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(result => {
            const tbody = document.getElementById('wins-table-body');
            tbody.innerHTML = '';

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const priceStr = new Intl.NumberFormat('th-TH').format(item.final_price);
                    
                    let statusBadge = '';
                    let actionBtn = '';

                    // 💡 Logic ของระบบ Escrow สำหรับผู้ซื้อ
                    if (item.payment_status === 'pending') {
                        statusBadge = `<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">รอชำระเงิน</span>`;
                        actionBtn = `<button onclick="openModal(${item.id}, '${priceStr}')" class="bg-primary hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md transition">แจ้งชำระเงิน</button>`;
                    } else if (item.payment_status === 'paid_to_admin') {
                        statusBadge = `<span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold">รอแอดมินตรวจสอบ</span>`;
                        actionBtn = `<span class="text-gray-400 text-sm italic">รอตรวจสลิป...</span>`;
                    } else if (item.payment_status === 'admin_verified' && item.shipping_status === 'pending') {
                        statusBadge = `<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold animate-pulse">กำลังเตรียมจัดส่ง</span>`;
                        actionBtn = `<span class="text-blue-500 text-sm font-medium">รอผู้ขายส่งของ</span>`;
                    } else if (item.shipping_status === 'shipped') {
                        statusBadge = `
                            <div class="flex flex-col items-center">
                                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold mb-1">📦 จัดส่งแล้ว</span>
                                <span class="text-[10px] text-gray-500 font-mono">Tracking: ${item.tracking_number}</span>
                            </div>`;
                        // 🟢 ปุ่มพระเอก: ผู้ซื้อกดยืนยันรับของ
                        actionBtn = `<button onclick="confirmReceipt(${item.id})" class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md transition animate-bounce">ยืนยันได้รับสินค้า</button>`;
                    } else if (item.shipping_status === 'received' || item.payment_status === 'transferred_to_seller') {
                        statusBadge = `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">✅ เสร็จสิ้นสมบูรณ์</span>`;
                        actionBtn = `<span class="text-green-600 text-sm font-bold">รับของเรียบร้อย</span>`;
                    }

                    const row = `
                    <tr class="hover:bg-gray-50 transition border-b border-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img src="${item.image_url}" class="w-16 h-16 rounded-xl object-cover border border-gray-200 shadow-sm">
                                <div class="ml-4">
                                    <p class="font-bold text-gray-900">${item.title}</p>
                                    ${item.shipping_status === 'shipped' ? `<a href="/auction_of_paintings/public/uploads/tracking/${item.shipping_proof_img}" target="_blank" class="text-[11px] text-indigo-500 hover:underline mt-1 block">ดูรูปหลักฐานการส่ง</a>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900 text-lg">฿${priceStr}</td>
                        <td class="px-6 py-4 text-center">${statusBadge}</td>
                        <td class="px-6 py-4 text-right">${actionBtn}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">คุณยังไม่ชนะการประมูลใดๆ</td></tr>`;
            }
        });
    }

    // เปิด/ปิด Modal
    function openModal(id, price) {
        document.getElementById('modal-tx-id').value = id;
        document.getElementById('modal-price').innerText = price;
        document.getElementById('slipModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('slipModal').classList.add('hidden');
        document.getElementById('slipForm').reset();
        document.getElementById('file-name-display').innerText = 'คลิกเพื่อเลือกไฟล์รูปภาพสลิป';
    }

    // ผู้ซื้อส่งสลิปโอนเงิน
    document.getElementById('slipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const token = localStorage.getItem('jwt_token');
        const formData = new FormData(this);
        const btn = document.getElementById('btnSubmit');
        btn.innerHTML = 'กำลังอัปโหลด...'; btn.disabled = true;

        fetch('/auction_of_paintings/api/index.php?route=v1/buyer/upload_slip', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            closeModal();
            btn.innerHTML = 'ยืนยันการแจ้งโอน'; btn.disabled = false;
            if (data.status === 'success') {
                Swal.fire('สำเร็จ', data.message, 'success').then(() => loadMyWins());
            } else {
                // แจ้งเตือนกรณีลืมใส่ที่อยู่
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: data.message,
                    icon: 'warning',
                    confirmButtonText: 'ไปตั้งค่าโปรไฟล์',
                    showCancelButton: true,
                    cancelButtonText: 'ปิด'
                }).then((result) => {
                    if(result.isConfirmed) window.location.href = '/auction_of_paintings/public/pages/User/profile.php';
                });
            }
        });
    });

    // 🟢 ฟังก์ชันใหม่: ผู้ซื้อกดยืนยันรับของ
    function confirmReceipt(txId) {
        Swal.fire({
            title: 'ยืนยันการรับสินค้า?',
            text: "เมื่อคุณกดยืนยัน ระบบจะถือว่าการซื้อขายเสร็จสมบูรณ์ และจะโอนเงินให้ผู้ขายทันที",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#9CA3AF',
            confirmButtonText: 'ใช่, ฉันได้รับของแล้ว',
            cancelButtonText: 'ยังไม่ได้รับของ'
        }).then((result) => {
            if (result.isConfirmed) {
                const token = localStorage.getItem('jwt_token');
                fetch('/auction_of_paintings/api/index.php?route=v1/buyer/confirm-receipt', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_id: txId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('สำเร็จ!', data.message, 'success').then(() => loadMyWins());
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                });
            }
        });
    }
</script>

<?php include 'layouts/footer.php'; ?>