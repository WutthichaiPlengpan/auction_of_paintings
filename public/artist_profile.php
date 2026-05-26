<?php 
// ดึง Header มาแสดง
include 'layouts/header.php'; 
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full min-h-[calc(100vh-140px)]">
    
    <!-- Skeleton Loading ของ Profile -->
    <div id="profile-skeleton" class="animate-pulse mb-10">
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 flex flex-col md:flex-row items-center md:items-start gap-6">
            <div class="w-32 h-32 bg-gray-200 rounded-full"></div>
            <div class="space-y-4 text-center md:text-left flex-grow">
                <div class="h-8 bg-gray-200 rounded w-1/3 mx-auto md:mx-0"></div>
                <div class="h-4 bg-gray-200 rounded w-1/4 mx-auto md:mx-0"></div>
                <div class="flex gap-4 justify-center md:justify-start mt-4">
                    <div class="h-16 bg-gray-100 rounded-xl w-24"></div>
                    <div class="h-16 bg-gray-100 rounded-xl w-24"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ข้อมูลโปรไฟล์ศิลปินจริง (ซ่อนไว้ก่อนจนกว่าโหลดเสร็จ) -->
    <div id="artist-profile-header" class="hidden mb-10">
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 flex flex-col md:flex-row items-center md:items-start gap-8 relative overflow-hidden">
            <!-- พื้นหลังตกแต่ง -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-50 rounded-full blur-3xl -mr-10 -mt-10 opacity-50 pointer-events-none"></div>

            <img id="artist-avatar" src="" alt="Artist" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg z-10">
            
            <div class="text-center md:text-left z-10">
                <h1 id="artist-name" class="text-3xl font-bold text-gray-900 mb-1">...</h1>
                <p class="text-sm text-gray-500 mb-6 flex items-center justify-center md:justify-start">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    เข้าร่วมเมื่อ: <span id="artist-joined" class="ml-1">...</span>
                </p>
                
                <div class="flex gap-4 justify-center md:justify-start">
                    <div class="bg-indigo-50 px-5 py-3 rounded-xl border border-indigo-100 text-center min-w-[100px]">
                        <p class="text-2xl font-bold text-primary" id="stat-total">0</p>
                        <p class="text-xs font-medium text-indigo-800 uppercase tracking-wider mt-1">ผลงานทั้งหมด</p>
                    </div>
                    <div class="bg-green-50 px-5 py-3 rounded-xl border border-green-100 text-center min-w-[100px]">
                        <p class="text-2xl font-bold text-green-600" id="stat-sold">0</p>
                        <p class="text-xs font-medium text-green-800 uppercase tracking-wider mt-1">ขายแล้ว</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab เปลี่ยนหน้า -->
    <div class="border-b border-gray-200 mb-8">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('active')" id="tab-active" class="border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-bold text-lg transition">
                กำลังประมูล (<span id="count-active">0</span>)
            </button>
            <button onclick="switchTab('past')" id="tab-past" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-lg transition">
                ผลงานที่ผ่านมา (<span id="count-past">0</span>)
            </button>
        </nav>
    </div>

    <!-- กล่องแสดงผลงาน Active -->
    <div id="container-active" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"></div>

    <!-- กล่องแสดงผลงาน Past -->
    <div id="container-past" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"></div>

</main>

<script>
    // เก็บตัวแปร Global ไว้ใช้งาน
    let globalActiveAuctions = [];
    let globalPastAuctions = [];

    document.addEventListener("DOMContentLoaded", () => {
        // ดึง ID จาก URL (?id=...)
        const urlParams = new URLSearchParams(window.location.search);
        const artistId = urlParams.get('id');

        if (!artistId) {
            Swal.fire('ข้อผิดพลาด', 'ไม่พบรหัสศิลปิน', 'error').then(() => window.location.href = '/auction_of_paintings/public/index');
            return;
        }

        // ยิง API ดึงข้อมูลศิลปิน
        fetch(`/auction_of_paintings/api/index.php?route=v1/artist/profile&id=${artistId}`)
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                // ซ่อน Skeleton, โชว์ของจริง
                document.getElementById('profile-skeleton').classList.add('hidden');
                document.getElementById('artist-profile-header').classList.remove('hidden');

                const artist = result.artist || {};
                const stats = result.stats || {};

                // อัปเดตข้อมูล Profile (ใส่ Fallback รูปภาพเผื่อไม่มี)
                document.getElementById('artist-avatar').src = artist.avatar_url || 'https://via.placeholder.com/150';
                document.getElementById('artist-name').innerText = artist.display_name || 'ศิลปินนิรนาม';
                
                const joinedDate = artist.created_at ? new Date(artist.created_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long' }) : '-';
                document.getElementById('artist-joined').innerText = joinedDate;

                // อัปเดต Stats
                document.getElementById('stat-total').innerText = stats.total_artworks || 0;
                document.getElementById('stat-sold').innerText = stats.sold_artworks || 0;

                // 🚀 Frontend Lazy Check: ตรวจสอบและคัดแยกผลงานใหม่ตาม "เวลาจริง"
                const allAuctions = [...(result.active_auctions || []), ...(result.past_auctions || [])];
                const now = new Date().getTime();

                globalActiveAuctions = [];
                globalPastAuctions = [];

                allAuctions.forEach(item => {
                    const endTime = new Date(item.end_time).getTime();
                    
                    // ถ้าสถานะเป็น active และ 'เวลายังไม่หมด'
                    if (item.status === 'active' && endTime > now) {
                        globalActiveAuctions.push(item);
                    } else {
                        // ถ้าหมดเวลาแล้ว แต่สถานะยังเป็น active อยู่ ให้บังคับเปลี่ยนเป็น ended
                        if (item.status === 'active' && endTime <= now) {
                            item.status = 'ended'; 
                        }
                        globalPastAuctions.push(item);
                    }
                });

                document.getElementById('count-active').innerText = globalActiveAuctions.length;
                document.getElementById('count-past').innerText = globalPastAuctions.length;

                // เรนเดอร์การ์ดผลงาน
                renderAuctions('active');
                renderAuctions('past');

            } else {
                Swal.fire('ข้อผิดพลาด', result.message, 'error').then(() => window.location.href = '/auction_of_paintings/public/index');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        });
    });

    // ฟังก์ชันวาดการ์ดผลงาน
    function renderAuctions(type) {
        const container = document.getElementById(`container-${type}`);
        const dataList = type === 'active' ? globalActiveAuctions : globalPastAuctions;

        let allCardsHtml = '';

        if (dataList.length === 0) {
            container.innerHTML = `<div class="col-span-full py-12 text-center text-gray-500 bg-gray-50 rounded-2xl border border-gray-100">ไม่มีผลงานในหมวดหมู่นี้</div>`;
            return;
        }

        dataList.forEach(item => {
            const priceFormatted = new Intl.NumberFormat('th-TH').format(item.current_price || 0);
            const imageUrl = item.image_url || (item.image_filename ? `/auction_of_paintings/public/uploads/products/${item.image_filename}` : 'https://via.placeholder.com/500?text=No+Image');
            
            // ป้ายสถานะ
            let badgeHtml = '';
            if (item.status === 'active') {
                badgeHtml = `<div class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm">กำลังประมูล</div>`;
            } else if (item.status === 'sold') {
                badgeHtml = `<div class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm">ขายแล้ว</div>`;
            } else {
                badgeHtml = `<div class="absolute top-2 right-2 bg-gray-800 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm">จบการประมูล</div>`;
            }

            allCardsHtml += `
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 group cursor-pointer flex flex-col" onclick="viewDetail(${item.id})">
                <div class="relative h-48 overflow-hidden bg-gray-100">
                    <img src="${imageUrl}" alt="${item.title}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    ${badgeHtml}
                </div>
                <div class="p-5 flex flex-col flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 truncate">${item.title}</h3>
                    <div class="flex justify-between items-end mt-4">
                        <div>
                            <p class="text-xs text-gray-400">ราคาปิดประมูล / ล่าสุด</p>
                            <p class="text-xl font-bold text-primary">฿${priceFormatted}</p>
                        </div>
                    </div>
                </div>
            </div>`;
        });
        
        container.innerHTML = allCardsHtml;
    }

    // ฟังก์ชันสลับ Tab
    function switchTab(type) {
        const tabActive = document.getElementById('tab-active');
        const tabPast = document.getElementById('tab-past');
        const containerActive = document.getElementById('container-active');
        const containerPast = document.getElementById('container-past');

        // รีเซ็ตคลาส
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

<?php include 'layouts/footer.php'; ?>