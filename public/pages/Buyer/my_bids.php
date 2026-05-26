<?php 
// ถอยหลัง 3 สเตป เพื่อดึง Header
include '../../layouts/header.php'; 
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 min-h-[calc(100vh-140px)]">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">ประวัติการประมูลของฉัน 🔨</h1>
        <p class="text-gray-500 mt-1">ติดตามสถานะการประมูลและสินค้าที่คุณชนะ</p>
    </div>

    <!-- Tab เปลี่ยนหน้า -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('active')" id="tab-active" class="border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-bold text-lg transition">
                กำลังประมูล (<span id="count-active">0</span>)
            </button>
            <button onclick="switchTab('past')" id="tab-past" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-lg transition">
                ประวัติที่ผ่านมา (<span id="count-past">0</span>)
            </button>
        </nav>
    </div>

    <!-- กล่องแสดงผลงาน Active -->
    <div id="container-active" class="space-y-4">
        <!-- Skeleton Loading -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex animate-pulse">
            <div class="w-24 h-24 bg-gray-200 rounded-xl mr-4"></div>
            <div class="flex-grow py-2">
                <div class="h-4 bg-gray-200 rounded w-1/3 mb-3"></div>
                <div class="h-3 bg-gray-200 rounded w-1/4 mb-4"></div>
                <div class="h-6 bg-gray-200 rounded-full w-24"></div>
            </div>
        </div>
    </div>

    <!-- กล่องแสดงผลงาน Past -->
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
            container.innerHTML = `<div class="bg-white rounded-2xl p-10 text-center text-gray-500 border border-gray-100 shadow-sm">คุณยังไม่มีประวัติในหมวดหมู่นี้<br><a href="/auction_of_paintings/public/index" class="text-primary font-bold hover:underline mt-2 inline-block">ไปดูผลงานที่น่าสนใจกันเถอะ!</a></div>`;
            return;
        }

        dataList.forEach(item => {
            const currentPrice = new Intl.NumberFormat('th-TH').format(item.current_price);
            const myMaxBid = new Intl.NumberFormat('th-TH').format(item.my_max_bid);
            
            // 💡 จัดการ UI ตามสถานะการประมูล
            let statusBadge = '';
            let actionBtn = `<button onclick="viewDetail(${item.id})" class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-xl text-sm font-bold transition">ดูรายละเอียด</button>`;

            if (type === 'active') {
                if (item.bid_status === 'winning') {
                    statusBadge = `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">👑 คุณให้ราคาสูงสุด</span>`;
                } else {
                    statusBadge = `<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">⚠️ มีคนให้ราคาสูงกว่า!</span>`;
                    actionBtn = `<button onclick="viewDetail(${item.id})" class="bg-red-600 text-white hover:bg-red-700 px-4 py-2 rounded-xl text-sm font-bold shadow-md transition">สู้ราคาต่อ</button>`;
                }
            } else {
                if (item.bid_status === 'won') {
                    statusBadge = `<span class="bg-indigo-100 text-primary px-3 py-1 rounded-full text-xs font-bold border border-indigo-200">🎉 ชนะการประมูล</span>`;
                    actionBtn = `<button onclick="checkout(${item.id})" class="bg-primary text-white hover:bg-indigo-700 px-4 py-2 rounded-xl text-sm font-bold shadow-md transition">ชำระเงิน</button>`;
                } else {
                    statusBadge = `<span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">แพ้การประมูล</span>`;
                }
            }

            const cardHtml = `
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md transition border border-gray-100 flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                <img src="${item.image_url}" alt="Artwork" class="w-full sm:w-28 h-32 sm:h-28 object-cover rounded-xl border border-gray-200">
                
                <div class="flex-grow w-full">
                    <div class="flex justify-between items-start mb-1">
                        <h3 class="text-lg font-bold text-gray-900 truncate pr-4">${item.title}</h3>
                        ${statusBadge}
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide">ราคาปัจจุบัน</p>
                            <p class="text-lg font-bold text-gray-900">฿${currentPrice}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide">ราคาที่คุณบิด</p>
                            <p class="text-lg font-bold ${item.bid_status === 'outbid' || item.bid_status === 'lost' ? 'text-red-500 line-through' : 'text-primary'}">฿${myMaxBid}</p>
                        </div>
                    </div>
                </div>
                
                <div class="w-full sm:w-auto mt-2 sm:mt-0 flex justify-end">
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

    function checkout(id) {
        // ฟังก์ชันสำหรับจ่ายเงินเมื่อชนะประมูล
        Swal.fire({
            title: 'เตรียมพร้อมชำระเงิน',
            text: 'ระบบชำระเงิน (QR Code/โอนเงิน) จะเปิดให้ใช้งานในเร็วๆ นี้',
            icon: 'info',
            confirmButtonColor: '#4F46E5'
        });
    }
</script>

<?php include '../../layouts/footer.php'; ?>