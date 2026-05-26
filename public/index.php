<?php
// ดึง Header มาแสดง
include 'layouts/header.php';
?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full min-h-[calc(100vh-140px)]">

    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-secondary">ผลงานที่กำลังประมูลเดือด 🔥</h1>
            <p class="text-gray-500 mt-1">อัปเดตราคาแบบ Real-time</p>
        </div>
    </div>

    <div id="auction-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <script>
            // แสดง Skeleton Loading ระหว่างรอข้อมูล
            for (let i = 0; i < 4; i++) {
                document.write(`
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 animate-pulse flex flex-col">
                    <div class="h-48 bg-gray-200 w-full"></div>
                    <div class="p-5 flex-grow">
                        <div class="h-4 bg-gray-200 rounded w-3/4 mb-3"></div>
                        <div class="h-3 bg-gray-200 rounded w-1/2 mb-6"></div>
                        <div class="flex justify-between items-center mt-auto">
                            <div class="h-6 bg-gray-200 rounded w-1/3"></div>
                            <div class="h-8 bg-gray-300 rounded-full w-20"></div>
                        </div>
                    </div>
                </div>`);
            }
        </script>
    </div>

</main>

<script>
    let isFirstLoad = true; 
    let currentAuctionCount = 0; 

    document.addEventListener("DOMContentLoaded", () => {
        loadAuctions();
        // ตั้งเวลาตรวจสอบราคาใหม่ทุกๆ 3 วินาที
        setInterval(loadAuctions, 3000);
    });

    function loadAuctions() {
        const container = document.getElementById('auction-container');

        fetch('/auction_of_paintings/api/index.php?route=v1/auctions/active', { method: 'GET' })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(result => {
                if (result.status !== 'success') return;

                const auctions = result.data || [];

                // 1. ถ้าเป็นการโหลดครั้งแรก หรือจำนวนสินค้าเปลี่ยนไป (มีสินค้าเพิ่ม/ลด)
                if (isFirstLoad || auctions.length !== currentAuctionCount) {
                    let allCardsHtml = '';
                    currentAuctionCount = auctions.length;

                    if (auctions.length === 0) {
                        container.innerHTML = `<div class="col-span-full text-center py-12 text-gray-500 bg-gray-50 rounded-2xl border border-gray-100">ขณะนี้ยังไม่มีผลงานเปิดประมูล</div>`;
                        isFirstLoad = false;
                        return;
                    }

                    // สร้าง HTML String ทั้งหมดก่อนแล้วค่อยใส่ทีเดียว เพื่อประสิทธิภาพ
                    auctions.forEach(item => {
                        const imageUrl = item.image_filename 
                            ? `/auction_of_paintings/public/uploads/products/${item.image_filename}` 
                            : 'https://via.placeholder.com/500?text=No+Image';
                        
                        const priceFormatted = new Intl.NumberFormat('th-TH').format(item.current_price);
                        
                        // ตรวจสอบชื่อศิลปิน (ถ้าไม่มีให้ใส่เป็น 'ศิลปินนิรนาม')
                        const artistName = item.artist || item.seller_name || 'ศิลปินนิรนาม';

                        allCardsHtml += `
                        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 group cursor-pointer flex flex-col" onclick="viewDetail(${item.id})">
                            <div class="relative h-48 overflow-hidden bg-gray-100">
                                <img src="${imageUrl}" alt="${item.title}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm" id="countdown-${item.id}">
                                    รอสักครู่...
                                </div>
                            </div>
                            <div class="p-5 flex flex-col flex-grow">
                                <h3 class="text-lg font-bold text-gray-900 truncate">${item.title}</h3>
                                <p class="text-sm text-gray-500 mb-4 border-b border-gray-100 pb-3">
                                    โดย <span class="font-medium text-indigo-600">${artistName}</span>
                                </p>
                                <div class="flex justify-between items-end mt-auto">
                                    <div>
                                        <p class="text-xs text-gray-400">ราคาปัจจุบัน</p>
                                        <p id="price-${item.id}" class="text-xl font-bold text-primary transition-all duration-300">฿${priceFormatted}</p>
                                    </div>
                                    <button class="bg-gray-900 text-white text-sm px-5 py-2.5 rounded-xl hover:bg-primary transition shadow-md">บิดเลย</button>
                                </div>
                            </div>
                        </div>`;
                    });

                    container.innerHTML = allCardsHtml;

                    // หลังจากใส่ HTML แล้วค่อยเริ่มนับเวลาถอยหลัง
                    auctions.forEach(item => {
                        startCountdown(item.id, item.end_time);
                    });

                    isFirstLoad = false;
                } 
                // 2. ถ้าเป็นการ Polling ปกติ ให้อัปเดตเฉพาะตัวเลขราคา
                else {
                    auctions.forEach(item => {
                        const priceEl = document.getElementById(`price-${item.id}`);
                        if (priceEl) {
                            const newPriceText = `฿${new Intl.NumberFormat('th-TH').format(item.current_price)}`;
                            if (priceEl.innerText !== newPriceText) {
                                priceEl.innerText = newPriceText;
                                // เอฟเฟกต์กระพริบเมื่อราคาเปลี่ยน
                                priceEl.classList.add('text-yellow-500', 'scale-110');
                                setTimeout(() => priceEl.classList.remove('text-yellow-500', 'scale-110'), 500);
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Render Error:', error);
                // แสดง Error เฉพาะตอนโหลดครั้งแรกแล้วพังจริงๆ
                if (isFirstLoad) {
                    container.innerHTML = `<div class="col-span-full text-center py-12 text-red-500">ไม่สามารถเชื่อมต่อข้อมูลได้</div>`;
                }
            });
    }

    function startCountdown(elementId, endTimeStr) {
        const countDownDate = new Date(endTimeStr).getTime();
        
        const updateTimer = () => {
            const element = document.getElementById(`countdown-${elementId}`);
            if (!element) return; // ป้องกัน Error ถ้าหา Element ไม่เจอ

            const now = new Date().getTime();
            const distance = countDownDate - now;

            if (distance < 0) {
                element.innerHTML = "หมดเวลาประมูล";
                element.classList.replace('bg-red-500', 'bg-gray-500');
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let timeText = 'เหลือ ';
            if (days > 0) timeText += `${days}ว `;
            timeText += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            element.innerHTML = timeText;
        };

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    function viewDetail(id) {
        window.location.href = `/auction_of_paintings/public/auction_detail?id=${id}`;
    }
</script>

<?php
// ดึง Footer มาแสดง
include 'layouts/footer.php';
?>