<?php 
// ถอยหลังกลับไป 3 สเตปเพื่อดึง Header
include '../../layouts/header.php'; 
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 min-h-[calc(100vh-140px)]">
    
    <div class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🛡️ ระบบจัดการ (Admin Dashboard)</h1>
            <p class="text-gray-500 mt-1">ตรวจสอบและอนุมัติการยืนยันตัวตน (e-KYC)</p>
        </div>
    </div>

    <!-- ตารางแสดงรายการ KYC ที่รออนุมัติ -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-900 text-white flex justify-between items-center">
            <h3 class="text-lg font-bold">รายการรอตรวจสอบ</h3>
            <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full" id="pending-count">0 รายการ</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 font-semibold">ชื่อผู้ใช้ (Email)</th>
                        <th class="px-6 py-4 font-semibold">ชื่อ-นามสกุลจริง</th>
                        <th class="px-6 py-4 font-semibold">ข้อมูลบัญชี/บัตร</th>
                        <th class="px-6 py-4 font-semibold">เอกสารภาพถ่าย</th>
                        <th class="px-6 py-4 font-semibold text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="kyc-table-body" class="divide-y divide-gray-100">
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            กำลังโหลดข้อมูล...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    // 🛑 1. เช็คสิทธิ์ขั้นเด็ดขาด: ถ้าไม่ใช่ admin ให้เตะออกทันที
    document.addEventListener("DOMContentLoaded", () => {
        const token = localStorage.getItem('jwt_token');
        let isAdmin = false;
        
        if (token) {
            const decoded = parseJwt(token); // ฟังก์ชันนี้อยู่ใน header.php
            if (decoded && decoded.data.role === 'admin') {
                isAdmin = true;
            }
        }

        if (!isAdmin) {
            Swal.fire({
                icon: 'error', title: 'ไม่อนุญาต!', text: 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้', confirmButtonColor: '#4F46E5'
            }).then(() => window.location.href = '/auction_of_paintings/public/index');
            return;
        }

        // ดึงข้อมูลเมื่อเป็นแอดมินตัวจริง
        loadPendingKyc();
    });

    // 🔄 2. ฟังก์ชันโหลดข้อมูล
    function loadPendingKyc() {
        const token = localStorage.getItem('jwt_token');

        fetch('/auction_of_paintings/api/index.php?route=v1/admin/kyc/pending', {
            method: 'GET',
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(res => res.json())
        .then(result => {
            const tbody = document.getElementById('kyc-table-body');
            tbody.innerHTML = '';

            if (result.status === 'success' && result.data.length > 0) {
                document.getElementById('pending-count').innerText = `${result.data.length} รายการ`;

                result.data.forEach(item => {
                    
                    // 🛡️ เพิ่มระบบป้องกัน Error กรณีข้อมูลถอดรหัสไม่ได้ (เป็นข้อมูลเก่า)
                    let idCardDisplay = '<span class="text-red-500">ข้อมูลเก่า (ถอดรหัสไม่ได้)</span>';
                    if (item.id_card_no && typeof item.id_card_no === 'string') {
                        idCardDisplay = item.id_card_no.replace(/(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})/, "$1-$2-$3-$4-$5");
                    }

                    let bankAccDisplay = '<span class="text-red-500">ข้อมูลเก่า</span>';
                    if (item.bank_acc_no && typeof item.bank_acc_no === 'string') {
                        bankAccDisplay = item.bank_acc_no;
                    }

                    // จัดการแสดงผลเบอร์โทรศัพท์
                    let phoneDisplay = item.phone ? item.phone : '<span class="text-gray-400 italic">ไม่ระบุเบอร์โทร</span>';

                    const row = `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-900">${item.username}</p>
                            <p class="text-xs text-gray-500 mt-1">✉️ ${item.email}</p>
                            <p class="text-xs text-indigo-600 mt-1 font-medium">📞 ${phoneDisplay}</p>
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-800">${item.real_name}</td>
                        <td class="px-6 py-4">
                            <p class="text-sm"><span class="text-gray-400 text-xs">💳 บัตร:</span> ${idCardDisplay}</p>
                            <p class="text-sm mt-1"><span class="text-gray-400 text-xs">🏦 บัญชี:</span> <span class="font-bold text-indigo-700">${item.bank_name}</span> <span class="tracking-wider">${bankAccDisplay}</span></p>
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="viewDocuments('${item.id_card_url}', '${item.selfie_url}')" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg text-sm font-bold transition flex items-center shadow-sm border border-indigo-100">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                ดูรูปถ่าย
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                <!-- 💡 แก้จาก item.id เป็น item.user_id -->
                                <button onclick="handleAction(${item.user_id}, 'approve')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-bold transition shadow-sm">อนุมัติ</button>
                                <button onclick="handleAction(${item.user_id}, 'reject')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-bold transition shadow-sm">ปฏิเสธ</button>
                            </div>
                        </td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                document.getElementById('pending-count').innerText = `0 รายการ`;
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">ไม่มีรายการยืนยันตัวตนที่รอตรวจสอบ</td></tr>`;
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('kyc-table-body').innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
        });
    }

    // 📸 3. เปิดดูรูปเอกสาร
    function viewDocuments(idCardUrl, selfieUrl) {
        Swal.fire({
            title: 'ตรวจสอบเอกสาร KYC',
            html: `
                <div class="space-y-6 text-left p-2">
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <p class="font-bold text-sm mb-2 text-indigo-700 flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                            1. รูปถ่ายบัตรประชาชน
                        </p>
                        <img src="${idCardUrl}" class="w-full h-auto rounded-xl border border-gray-200 shadow-sm" alt="ID Card">
                    </div>
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <p class="font-bold text-sm mb-2 text-indigo-700 flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            2. รูปเซลฟี่คู่กับบัตร
                        </p>
                        <img src="${selfieUrl}" class="w-full h-auto rounded-xl border border-gray-200 shadow-sm" alt="Selfie">
                    </div>
                </div>
            `,
            width: '600px',
            showCloseButton: true,
            confirmButtonText: 'รับทราบ / ปิดหน้าต่าง',
            confirmButtonColor: '#4F46E5'
        });
    }

    // 🔨 4. จัดการปุ่ม อนุมัติ/ปฏิเสธ
    function handleAction(kycId, action) {
        if (action === 'approve') {
            Swal.fire({
                title: 'ยืนยันการอนุมัติ?',
                text: "ผู้ใช้นี้จะสามารถลงขายผลงานศิลปะได้ทันที",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'ใช่, อนุมัติเลย'
            }).then((result) => {
                if (result.isConfirmed) submitAction(kycId, 'approve', '');
            });
        } else {
            Swal.fire({
                title: 'ปฏิเสธเอกสาร',
                input: 'textarea',
                inputLabel: 'ระบุเหตุผล (เช่น รูปไม่ชัด, ชื่อไม่ตรงกับบัญชี)',
                inputPlaceholder: 'พิมพ์เหตุผลที่นี่...',
                inputAttributes: { 'aria-label': 'Type your message here' },
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'ปฏิเสธ',
                preConfirm: (note) => {
                    if (!note) { Swal.showValidationMessage('กรุณาระบุเหตุผลในการปฏิเสธ'); }
                    return note;
                }
            }).then((result) => {
                if (result.isConfirmed) submitAction(kycId, 'reject', result.value);
            });
        }
    }

    function submitAction(kycId, action, note) {
        const token = localStorage.getItem('jwt_token');

        fetch('/auction_of_paintings/api/index.php?route=v1/admin/kyc/action', {
            method: 'POST',
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ kyc_id: kycId, action: action, note: note })
        })
        .then(res => res.json())
        .then(result => {
            if(result.status === 'success') {
                Swal.fire('สำเร็จ', result.message, 'success');
                loadPendingKyc(); 
            } else {
                Swal.fire('ข้อผิดพลาด', result.message, 'error');
            }
        });
    }
</script>

<?php include '../../layouts/footer.php'; ?>