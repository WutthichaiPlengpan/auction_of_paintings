<?php include '../../layouts/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 min-h-[calc(100vh-140px)]">
    
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">💼 ระบบการเงิน (Finance)</h1>
            <p class="text-gray-500 mt-1">ตรวจสอบการชำระเงินและโอนเงินให้ผู้ขาย (เมื่อส่งมอบงานสำเร็จ)</p>
        </div>
        
        <div class="flex gap-4 mt-4 md:mt-0">
            <div class="bg-indigo-50 border border-indigo-100 px-4 py-2 rounded-xl text-center">
                <p class="text-xs text-indigo-600 font-bold uppercase tracking-wider">เว็บได้ค่าคอมมิชชั่น</p>
                <p class="text-xl font-bold text-gray-900" id="total-commission">฿0</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 font-semibold">บิลประมูล</th>
                        <th class="px-6 py-4 font-semibold text-right">ยอดรวม (100%)</th>
                        <th class="px-6 py-4 font-semibold text-right text-indigo-600">ค่าคอม (17%)</th>
                        <th class="px-6 py-4 font-semibold text-right text-green-600">ยอดจ่ายผู้ขาย (83%)</th>
                        <th class="px-6 py-4 font-semibold text-center">สถานะ</th>
                        <th class="px-6 py-4 font-semibold text-center">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="finance-table-body" class="divide-y divide-gray-100">
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">กำลังโหลดข้อมูล...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadFinanceData();
    });

    function loadFinanceData() {
        const token = localStorage.getItem('jwt_token');

        fetch('/auction_of_paintings/api/index.php?route=v1/admin/finance/list', {
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(result => {
            const tbody = document.getElementById('finance-table-body');
            tbody.innerHTML = '';

            if (result.status === 'success' && result.data.length > 0) {
                let totalComm = 0;

                result.data.forEach(item => {
                    const finalPrice = parseFloat(item.final_price);
                    const commAmt = parseFloat(item.commission_amount);
                    const sellerNet = parseFloat(item.seller_net_amount);
                    
                    if(item.payment_status === 'transferred_to_seller') totalComm += commAmt;

                    let statusBadge = '';
                    let actionBtn = '';

                    // 💡 Logic ล็อกปุ่ม Escrow สำหรับ Admin
                    if (item.payment_status === 'pending') {
                        statusBadge = `<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">รอผู้ซื้อโอน</span>`;
                        actionBtn = `-`;
                    } else if (item.payment_status === 'paid_to_admin') {
                        statusBadge = `<span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold animate-pulse">สลิปใหม่รอตรวจ</span>`;
                        actionBtn = `<button onclick="verifyBuyerSlip(${item.id}, '${item.buyer_slip_url}', ${finalPrice})" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md transition">ตรวจสลิปผู้ซื้อ</button>`;
                    } else if (item.payment_status === 'admin_verified') {
                        
                        // ถ้ารับเงินแล้ว แต่กระบวนการส่งของยังไม่จบ
                        if (item.shipping_status === 'pending') {
                            statusBadge = `<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">ผู้ขายกำลังเตรียมส่ง</span>`;
                            actionBtn = `<span class="text-[11px] font-bold text-gray-400 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">🔒 ล็อก (รอส่งของ)</span>`;
                        } else if (item.shipping_status === 'shipped') {
                            statusBadge = `<span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold">ส่งแล้ว (รอผู้ซื้อรับ)</span>`;
                            actionBtn = `<span class="text-[11px] font-bold text-gray-400 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">🔒 ล็อก (รอรับของ)</span>`;
                        } else if (item.shipping_status === 'received') {
                            statusBadge = `<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold border border-green-300 animate-pulse">รับของแล้ว พร้อมโอน!</span>`;
                            // 🟢 ปลดล็อกปุ่มให้แอดมินโอนเงินได้!
                            actionBtn = `<button onclick="transferToSeller(${item.id}, '${item.seller_bank_name}', '${item.seller_bank_acc}', '${item.seller_name}', ${sellerNet})" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md transition animate-bounce">โอนเงินให้ผู้ขาย</button>`;
                        }

                    } else if (item.payment_status === 'transferred_to_seller') {
                        statusBadge = `<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">จบงาน/โอนแล้ว</span>`;
                        actionBtn = `<a href="${item.admin_slip_url}" target="_blank" class="text-indigo-600 text-sm font-bold hover:underline">ดูสลิปที่โอน</a>`;
                    }

                    const row = `
                    <tr class="hover:bg-gray-50 transition border-b border-gray-50">
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-900">${item.title}</p>
                            <p class="text-[11px] text-gray-500 mt-1">ผู้ซื้อ: ${item.buyer_name}</p>
                            <p class="text-[11px] text-gray-500">ผู้ขาย: ${item.seller_name}</p>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">฿${new Intl.NumberFormat('th-TH').format(finalPrice)}</td>
                        <td class="px-6 py-4 text-right font-bold text-indigo-600 bg-indigo-50/30">฿${new Intl.NumberFormat('th-TH').format(commAmt)}</td>
                        <td class="px-6 py-4 text-right font-bold text-green-600 bg-green-50/30">฿${new Intl.NumberFormat('th-TH').format(sellerNet)}</td>
                        <td class="px-6 py-4 text-center">${statusBadge}</td>
                        <td class="px-6 py-4 text-center">${actionBtn}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
                document.getElementById('total-commission').innerText = `฿${new Intl.NumberFormat('th-TH').format(totalComm)}`;
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">ยังไม่มีประวัติการเงิน</td></tr>`;
            }
        });
    }

    // ฟังก์ชันตรวจสลิป และโอนเงิน ยังคงใช้งานได้ตามปกติ (ใช้โค้ดเดิมได้เลยครับ)
    function verifyBuyerSlip(txId, slipUrl, amount) {
        Swal.fire({
            title: 'ตรวจสอบสลิปการโอนเงิน',
            html: `
                <p class="text-gray-600 mb-4">ยอดที่ต้องได้รับ: <strong class="text-xl text-green-600">฿${new Intl.NumberFormat('th-TH').format(amount)}</strong></p>
                <img src="${slipUrl}" class="w-full max-h-96 object-contain rounded-xl border border-gray-200">
            `,
            showCancelButton: true, showDenyButton: true,
            confirmButtonText: 'อนุมัติสลิป (เงินเข้าจริง)', denyButtonText: 'ปฏิเสธ (สลิปปลอม)', cancelButtonText: 'ปิด',
            confirmButtonColor: '#10B981', denyButtonColor: '#EF4444'
        }).then((result) => {
            const token = localStorage.getItem('jwt_token');
            if (result.isConfirmed) {
                fetch('/auction_of_paintings/api/index.php?route=v1/admin/finance/verify-buyer', {
                    method: 'POST', headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_id: txId, action: 'approve' })
                }).then(res => res.json()).then(() => loadFinanceData());
            } else if (result.isDenied) {
                fetch('/auction_of_paintings/api/index.php?route=v1/admin/finance/verify-buyer', {
                    method: 'POST', headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_id: txId, action: 'reject' })
                }).then(res => res.json()).then(() => loadFinanceData());
            }
        });
    }

    function transferToSeller(txId, bankName, bankAcc, sellerName, amount) {
        Swal.fire({
            title: 'โอนเงินให้ผู้ขาย',
            html: `
                <div class="text-left bg-gray-50 p-4 rounded-xl border border-gray-200 mb-4">
                    <p class="text-xs text-gray-500 uppercase">ยอดโอนสุทธิ</p>
                    <p class="text-3xl font-bold text-primary mb-3">฿${new Intl.NumberFormat('th-TH').format(amount)}</p>
                    <hr>
                    <p class="text-sm text-gray-500 mt-3">ชื่อบัญชี: <strong>${sellerName}</strong></p>
                    <p class="text-sm text-gray-500">ธนาคาร: <strong>${bankName}</strong></p>
                    <p class="text-sm text-gray-500">เลขบัญชี: <strong class="text-lg text-gray-900">${bankAcc}</strong></p>
                </div>
                <input type="file" id="admin_slip" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*">
            `,
            confirmButtonText: 'ยืนยันการโอนเงิน',
            confirmButtonColor: '#4F46E5',
            preConfirm: () => {
                const fileInput = document.getElementById('admin_slip');
                if (fileInput.files.length === 0) Swal.showValidationMessage('กรุณาแนบรูปสลิปการโอนเงิน');
                return fileInput.files[0];
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const token = localStorage.getItem('jwt_token');
                const formData = new FormData();
                formData.append('transaction_id', txId);
                formData.append('admin_slip', result.value);

                fetch('/auction_of_paintings/api/index.php?route=v1/admin/finance/transfer-seller', {
                    method: 'POST', headers: { 'Authorization': 'Bearer ' + token },
                    body: formData
                }).then(res => res.json()).then(() => {
                    Swal.fire('สำเร็จ', 'บันทึกการโอนเงินเรียบร้อย', 'success');
                    loadFinanceData();
                });
            }
        });
    }
</script>

<?php include '../../layouts/footer.php'; ?>