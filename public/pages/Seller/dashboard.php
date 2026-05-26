<?php include '../../layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 min-h-[calc(100vh-140px)]">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🎨 จัดการผลงานประมูล (Seller)</h1>
            <p class="text-gray-500 mt-1">ติดตามสถานะผลงาน และจัดส่งสินค้าให้ผู้ชนะ</p>
        </div>
        <a href="/auction_of_paintings/public/pages/Seller/create_auction" class="bg-primary hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold shadow-md transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            ลงขายผลงานใหม่
        </a>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 font-semibold">ผลงาน</th>
                        <th class="px-6 py-4 font-semibold text-right">ยอดสุทธิ (Net)</th>
                        <th class="px-6 py-4 font-semibold text-center">บิด</th>
                        <th class="px-6 py-4 font-semibold text-center">สถานะการเงิน/จัดส่ง</th>
                        <th class="px-6 py-4 font-semibold text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="auction-table-body" class="divide-y divide-gray-100">
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">กำลังโหลดข้อมูล...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="shippingModal" class="hidden fixed inset-0 bg-gray-900/75 z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full mx-4 shadow-2xl transform transition-all">
        <h3 class="text-2xl font-bold text-gray-900 mb-2">📦 แจ้งการจัดส่งผลงาน</h3>
        
        <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl mb-6 mt-4">
            <p class="text-xs text-blue-500 font-bold uppercase mb-1">จัดส่งไปยังที่อยู่:</p>
            <p id="modal-buyer-address" class="text-sm text-gray-800 font-medium"></p>
        </div>

        <form id="shippingForm">
            <input type="hidden" id="modal-tx-id" name="transaction_id">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">เลขพัสดุ (Tracking Number)</label>
                <input type="text" id="tracking_number" name="tracking_number" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-primary focus:border-primary outline-none" placeholder="เช่น TH0123456789">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">รูปถ่ายหลักฐานการส่ง (ใบเสร็จ/กล่อง)</label>
                <input type="file" id="shipping_proof" name="shipping_proof" accept="image/*" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeShippingModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition">ยกเลิก</button>
                <button type="submit" id="btnSubmitShipping" class="flex-1 bg-primary hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-md transition">ยืนยันการจัดส่ง</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadSellerAuctions();
    });

    function loadSellerAuctions() {
        const token = localStorage.getItem('jwt_token');
        fetch('/auction_of_paintings/api/index.php?route=v1/seller/auctions', {
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(result => {
            const tbody = document.getElementById('auction-table-body');
            tbody.innerHTML = '';

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const displayPrice = item.seller_net_amount ? parseFloat(item.seller_net_amount) : item.current_price;
                    const priceStr = new Intl.NumberFormat('th-TH').format(displayPrice);

                    // 💡 ทำความสะอาดตัวแปรที่อยู่ก่อนนำไปใช้งานใน onclick
                    const safeAddress = item.shipping_address 
                        ? item.shipping_address.replace(/\r?\n/g, ' ').replace(/'/g, "\\'") 
                        : '';

                    let statusBadge = '';
                    let actionBtn = '';

                    if (item.real_status === 'active' && item.status !== 'sold') {
                        statusBadge = `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">🟢 กำลังประมูล</span>`;
                        actionBtn = `<button onclick="viewDetail(${item.id})" class="text-indigo-600 font-bold text-sm hover:underline">ดูหน้าประมูล</button>`;
                    } else if (item.bid_count > 0 || item.status === 'sold') {
                        // 💡 Flow การจัดส่ง
                        if (item.payment_status === 'pending') {
                            statusBadge = `<span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">⏳ รอผู้ซื้อโอนเงิน</span>`;
                            actionBtn = `<span class="text-xs text-gray-400">รอชำระเงิน...</span>`;
                        } else if (item.payment_status === 'paid_to_admin') {
                            statusBadge = `<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">🏦 รอแอดมินตรวจยอด</span>`;
                            actionBtn = `<span class="text-xs text-gray-400">รออนุมัติ...</span>`;
                        } else if (item.payment_status === 'admin_verified' && item.shipping_status === 'pending') {
                            // 🚀 ใช้ตัวแปร safeAddress ตรงนี้
                            statusBadge = `<span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-bold animate-pulse">📦 ต้องจัดส่ง!</span>`;
                            actionBtn = `<button onclick="openShippingModal(${item.transaction_id}, '${safeAddress}')" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md transition">ดูที่อยู่ & แจ้งส่งของ</button>`;
                        } else if (item.shipping_status === 'shipped') {
                            statusBadge = `<span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold">🚚 ส่งแล้ว (รอผู้ซื้อรับ)</span>`;
                            actionBtn = `<span class="text-xs text-gray-500 font-mono">Track: ${item.tracking_number}</span>`;
                        } else if (item.shipping_status === 'received' && item.payment_status !== 'transferred_to_seller') {
                            statusBadge = `<span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold">🌟 ผู้ซื้อรับของแล้ว</span>`;
                            actionBtn = `<span class="text-xs text-gray-500">รอแอดมินโอนเงิน...</span>`;
                        } else if (item.payment_status === 'transferred_to_seller') {
                            statusBadge = `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">💸 ได้รับเงินแล้ว (จบงาน)</span>`;
                            actionBtn = `<span class="text-green-600 font-bold text-sm">✓ เสร็จสิ้น</span>`;
                        }
                    } else {
                        statusBadge = `<span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs font-bold">⚪ ปิดประมูล (ไม่มีผู้ซื้อ)</span>`;
                        actionBtn = `-`;
                    }

                    const row = `
                    <tr class="hover:bg-gray-50 transition border-b border-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img src="${item.image_url}" class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                                <div class="ml-4">
                                    <p class="font-bold text-gray-900 truncate max-w-[200px]">${item.title}</p>
                                    ${item.winner_name ? `<p class="text-[10px] text-gray-500 mt-1">ผู้ชนะ: ${item.winner_name}</p>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <p class="font-bold text-green-600">฿${priceStr}</p>
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-bold text-gray-600">${item.bid_count}</td>
                        <td class="px-6 py-4 text-center">${statusBadge}</td>
                        <td class="px-6 py-4 text-right">${actionBtn}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">คุณยังไม่ได้ลงขายผลงานใดๆ</td></tr>`;
            }
        });
    }

    function viewDetail(id) { window.location.href = `/auction_of_paintings/public/auction_detail?id=${id}`; }

    // --- ระบบจัดส่งสินค้า ---
    function openShippingModal(txId, address) {
        document.getElementById('modal-tx-id').value = txId;
        document.getElementById('modal-buyer-address').innerText = address || 'ผู้ซื้อยังไม่ได้ระบุที่อยู่ (กรุณาติดต่อผู้ซื้อ)';
        document.getElementById('shippingModal').classList.remove('hidden');
    }

    function closeShippingModal() {
        document.getElementById('shippingModal').classList.add('hidden');
        document.getElementById('shippingForm').reset();
    }

    document.getElementById('shippingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const token = localStorage.getItem('jwt_token');
        const btn = document.getElementById('btnSubmitShipping');
        btn.innerHTML = 'กำลังบันทึก...'; btn.disabled = true;

        const formData = new FormData(this);

        fetch('/auction_of_paintings/api/index.php?route=v1/seller/update-tracking', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            closeShippingModal();
            btn.innerHTML = 'ยืนยันการจัดส่ง'; btn.disabled = false;
            if (data.status === 'success') {
                Swal.fire('สำเร็จ', data.message, 'success').then(() => loadSellerAuctions());
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        });
    });
</script>

<?php include '../../layouts/footer.php'; ?>