<?php
// ถอยหลัง 3 สเตป เพื่อดึง Header
include '../../layouts/header.php';
?>

<div class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full min-h-[calc(100vh-140px)]">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">ประวัติการประมูลของฉัน 🔨</h1>
        <p class="text-gray-500 mt-1">ติดตามสถานะการประมูลและสินค้าที่คุณเสนอราคาไว้</p>
    </div>

    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
            <button onclick="switchTab('active')" id="tab-active"
                class="border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-bold text-lg transition">
                กำลังประมูล (<span id="count-active">0</span>)
            </button>
            <button onclick="switchTab('past')" id="tab-past"
                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-lg transition">
                ประวัติที่ผ่านมา (<span id="count-past">0</span>)
            </button>
        </nav>
    </div>

    <div id="container-active" class="space-y-4">
        <div
            class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-5 animate-pulse">
            <div class="w-full sm:w-32 h-40 sm:h-32 bg-gray-200 rounded-xl flex-shrink-0"></div>
            <div class="flex-grow w-full py-2">
                <div class="flex justify-between mb-4">
                    <div class="h-5 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-5 bg-gray-200 rounded-full w-24"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="h-16 bg-gray-100 rounded-xl"></div>
                    <div class="h-16 bg-gray-100 rounded-xl"></div>
                </div>
            </div>
            <div class="w-full sm:w-auto sm:self-center mt-2 sm:mt-0 flex-shrink-0">
                <div class="h-10 bg-gray-200 rounded-xl w-full sm:w-28"></div>
            </div>
        </div>
    </div>

    <div id="container-past" class="hidden space-y-4"></div>

</div>

<script>
    let globalActiveBids = [];
    let globalPastBids = [];

    document.addEventListener("DOMContentLoaded", () => {
        const token = localStorage.getItem('jwt_token');
        if (!token) {
            window.location.href = '/auction_of_paintings/public/login';
            return;
        }

        fetch('/auction_of_paintings/api/index.php?route=v1/buyer/bids', {
            method: 'GET',
            headers: { 'Authorization': 'Bearer ' + token }
        })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    globalActiveBids = result.data.active;
                    globalPastBids = result.data.past;

                    document.getElementById('count-active').innerText = globalActiveBids.length;
                    document.getElementById('count-past').innerText = globalPastBids.length;

                    renderBids('active');
                    renderBids('past');
                } else {
                    Swal.fire('ข้อผิดพลาด', result.message, 'error');
                }
            })
            .catch(err => console.error(err));
    });

    function renderBids(type) {
        const container = document.getElementById(`container-${type}`);
        const dataList = type === 'active' ? globalActiveBids : globalPastBids;

        container.innerHTML = '';

        if (dataList.length === 0) {
            container.innerHTML = `
                <div class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">คุณยังไม่มีประวัติในหมวดหมู่นี้</h3>
                    <p class="text-gray-500 text-sm mb-4">ไปค้นหาผลงานศิลปะที่ถูกใจแล้วเริ่มเสนอราคากันเลย!</p>
                    <a href="/auction_of_paintings/public/index" class="bg-gray-900 hover:bg-black text-white font-bold py-2.5 px-6 rounded-xl transition shadow-md">ดูผลงานทั้งหมด</a>
                </div>`;
            return;
        }

        dataList.forEach(item => {
            const currentPrice = new Intl.NumberFormat('th-TH').format(item.current_price);
            const myMaxBid = new Intl.NumberFormat('th-TH').format(item.my_max_bid);

            // 💡 สลับคำให้สมเหตุสมผล: ถ้ากำลังประมูลใช้ "ราคาปัจจุบัน" ถ้าประวัติใช้ "ราคาปิดประมูล"
            const priceLabel = type === 'active' ? 'ราคาปัจจุบัน' : 'ราคาปิดประมูล';

            let statusBadge = '';
            let actionBtn = `<button onclick="viewDetail(${item.id})" class="w-full sm:w-auto bg-gray-100 text-gray-700 hover:bg-gray-200 px-5 py-2.5 rounded-xl text-sm font-bold transition">ดูรายละเอียด</button>`;

            if (type === 'active') {
                if (item.bid_status === 'winning') {
                    statusBadge = `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold border border-green-200 whitespace-nowrap">👑 ให้ราคาสูงสุด</span>`;
                } else {
                    statusBadge = `<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold border border-red-200 whitespace-nowrap animate-pulse">⚠️ ถูกสู้ราคา!</span>`;
                    actionBtn = `<button onclick="viewDetail(${item.id})" class="w-full sm:w-auto bg-red-600 text-white hover:bg-red-700 px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition">สู้ราคาต่อ</button>`;
                }
            } else {
                if (item.bid_status === 'won') {
                    statusBadge = `<span class="bg-indigo-100 text-primary px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold border border-indigo-200 whitespace-nowrap">🎉 ชนะการประมูล</span>`;
                    actionBtn = `<button onclick="window.location.href='/auction_of_paintings/public/my_wins'" class="w-full sm:w-auto bg-primary text-white hover:bg-indigo-700 px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    จัดการสถานะ
                                 </button>`;
                } else {
                    statusBadge = `<span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[11px] sm:text-xs font-bold border border-gray-200 whitespace-nowrap">⚪ แพ้การประมูล</span>`;
                }
            }

            const cardHtml = `
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md transition border border-gray-100 flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                
                <img src="${item.image_url}" alt="Artwork" class="w-full sm:w-32 h-48 sm:h-32 object-cover rounded-xl border border-gray-200 flex-shrink-0">
                
                <div class="flex-grow w-full min-w-0">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-3 gap-2 sm:gap-0">
                        <h3 class="text-lg font-bold text-gray-900 truncate pr-0 sm:pr-4">${item.title}</h3>
                        <div class="self-start">${statusBadge}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mt-auto">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wide mb-1">${priceLabel}</p>
                            <p class="text-base sm:text-lg font-bold text-gray-900">฿${currentPrice}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[10px] sm:text-xs text-gray-500 uppercase tracking-wide mb-1">ราคาที่คุณบิดสูงสุด</p>
                            <p class="text-base sm:text-lg font-bold ${item.bid_status === 'outbid' || item.bid_status === 'lost' ? 'text-red-500 line-through' : 'text-primary'}">฿${myMaxBid}</p>
                        </div>
                    </div>
                </div>
                
                <div class="w-full sm:w-auto mt-2 sm:mt-0 flex-shrink-0 flex sm:block">
                    ${actionBtn}
                </div>
            </div>`;

            container.innerHTML += cardHtml;
        });
    }

    function switchTab(type) {
        const tabActive = document.getElementById('tab-active');
        const tabPast = document.getElementById('tab-past');
        const containerActive = document.getElementById('container-active');
        const containerPast = document.getElementById('container-past');

        const activeClasses = ['border-primary', 'text-primary'];
        const inactiveClasses = ['border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'];

        if (type === 'active') {
            tabActive.classList.remove(...inactiveClasses);
            tabActive.classList.add(...activeClasses);
            tabPast.classList.remove(...activeClasses);
            tabPast.classList.add(...inactiveClasses);

            containerActive.classList.remove('hidden');
            containerPast.classList.add('hidden');
        } else {
            tabPast.classList.remove(...inactiveClasses);
            tabPast.classList.add(...activeClasses);
            tabActive.classList.remove(...activeClasses);
            tabActive.classList.add(...inactiveClasses);

            containerPast.classList.remove('hidden');
            containerActive.classList.add('hidden');
        }
    }

    function viewDetail(id) {
        window.location.href = `/auction_of_paintings/public/auction_detail?id=${id}`;
    }
</script>

<?php include '../../layouts/footer.php'; ?>