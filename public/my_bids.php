<?php include 'layouts/header.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full min-h-[calc(100vh-140px)]">

    <div class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">⏱️ รายการที่ฉันกำลังประมูล</h1>
            <p class="text-gray-500 mt-1">ติดตามผลงานที่คุณเสนอราคาไว้ (อัปเดตแบบ Real-time)</p>
        </div>
    </div>

    <div id="auction-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <script>
            for (let i = 0; i < 4; i++) {
                document.write(`
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 animate-pulse flex flex-col">
                    <div class="h-48 bg-gray-200 w-full"></div>
                    <div class="p-5 flex-grow">
                        <div class="h-4 bg-gray-200 rounded w-3/4 mb-3"></div>
                        <div class="h-3 bg-gray-200 rounded w-1/2 mb-6"></div>
                    </div>
                </div>`);
            }
        </script>
    </div>

</main>

<script>
    let isFirstLoad = true; 
    let currentAuctionCount = 0; 
    let pollingInterval = null;

    document.addEventListener("DOMContentLoaded", () => {
        const token = localStorage.getItem('jwt_token');
        if (!token) {
            Swal.fire({
                title: 'ต้องเข้าสู่ระบบก่อน',
                text: 'กรุณาเข้าสู่ระบบเพื่อดูรายการประมูลของคุณ',
                icon: 'warning',
                confirmButtonColor: '#4F46E5',
                confirmButtonText: 'ไปหน้าเข้าสู่ระบบ'
            }).then(() => {
                window.location.href = '/auction_of_paintings/public/login';
            });
            return;
        }

        loadMyBids();
        pollingInterval = setInterval(loadMyBids, 3000); // ดึงข้อมูลทุก 3 วินาที
    });

    function loadMyBids() {
        const token = localStorage.getItem('jwt_token');
        const container = document.getElementById('auction-container');

        fetch('/auction_of_paintings/api/index.php?route=v1/buyer/my-bids', { 
            method: 'GET',
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(result => {
            if (result.status !== 'success') return;

            const auctions = result.data || [];

            // ถ้าเป็นการโหลดครั้งแรก หรือมีสินค้าถูกตัดออก (เพราะหมดเวลา)
            if (isFirstLoad || auctions.length !== currentAuctionCount) {
                let allCardsHtml = '';
                currentAuctionCount = auctions.length;

                if (auctions.length === 0) {
                    container.innerHTML = `
                    <div class="col-span-full text-center py-16 bg-white rounded-3xl border border-dashed border-gray-300">
                        <div class="text-5xl mb-4">👀</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">คุณยังไม่ได้เข้าร่วมการประมูลใดๆ</h3>
                        <p class="text-gray-500 mb-6">ไปค้นหาผลงานศิลปะที่ถูกใจแล้วเริ่มเสนอราคากันเลย!</p>
                        <a href="/auction_of_paintings/public/index" class="inline-block bg-primary hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl transition shadow-md">ดูผลงานทั้งหมด</a>
                    </div>`;
                    isFirstLoad = false;
                    return;
                }

                auctions.forEach(item => {
                    const imageUrl = item.image_filename 
                        ? `/auction_of_paintings/public/uploads/products/${item.image_filename}` 
                        : 'https://via.placeholder.com/500?text=No+Image';
                    
                    const priceFormatted = new Intl.NumberFormat('th-TH').format(item.current_price);
                    const artistName = item.artist || 'ศิลปินนิรนาม';

                    allCardsHtml += `
                    <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 group cursor-pointer flex flex-col" onclick="viewDetail(${item.id})">
                        <div class="relative h-48 overflow-hidden bg-gray-100">
                            <img src="${imageUrl}" alt="${item.title}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm" id="countdown-${item.id}">
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
                                    <p class="text-xs text-gray-400">ราคาสูงสุดตอนนี้</p>
                                    <p id="price-${item.id}" class="text-xl font-bold text-primary transition-all duration-300">฿${priceFormatted}</p>
                                </div>
                                <button class="bg-gray-900 text-white text-sm px-4 py-2 rounded-xl hover:bg-primary transition shadow-md">สู้ราคาต่อ</button>
                            </div>
                        </div>
                    </div>`;
                });

                container.innerHTML = allCardsHtml;

                auctions.forEach(item => {
                    startCountdown(item.id, item.end_time);
                });

                isFirstLoad = false;
            } 
            // อัปเดตเฉพาะตัวเลขราคาเมื่อมีการบิดใหม่
            else {
                auctions.forEach(item => {
                    const priceEl = document.getElementById(`price-${item.id}`);
                    if (priceEl) {
                        const newPriceText = `฿${new Intl.NumberFormat('th-TH').format(item.current_price)}`;
                        if (priceEl.innerText !== newPriceText) {
                            priceEl.innerText = newPriceText;
                            priceEl.classList.add('text-yellow-500', 'scale-110');
                            setTimeout(() => priceEl.classList.remove('text-yellow-500', 'scale-110'), 500);
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            if (isFirstLoad) {
                document.getElementById('auction-container').innerHTML = `<div class="col-span-full text-center py-12 text-red-500">ไม่สามารถเชื่อมต่อข้อมูลได้</div>`;
            }
        });
    }

    function startCountdown(elementId, endTimeStr) {
        const countDownDate = new Date(endTimeStr).getTime();
        
        const updateTimer = () => {
            const element = document.getElementById(`countdown-${elementId}`);
            if (!element) return; 

            const now = new Date().getTime();
            const distance = countDownDate - now;

            if (distance < 0) {
                // ถ้าหมดเวลา ให้ลบการ์ดนี้ทิ้ง หรือรีโหลดข้อมูลใหม่
                element.innerHTML = "หมดเวลา";
                element.classList.replace('bg-indigo-600', 'bg-gray-500');
                
                // กระตุ้นให้โหลดข้อมูลใหม่เพื่อเตะการ์ดนี้ออกจากหน้าจอ
                isFirstLoad = true; 
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

<?php include 'layouts/footer.php'; ?>