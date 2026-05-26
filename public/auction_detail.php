<?php include 'layouts/header.php'; ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 min-h-[calc(100vh-140px)]">
    
    <!-- ปุ่มย้อนกลับ -->
    <a href="/auction_of_paintings/public/index" class="inline-flex items-center text-gray-500 hover:text-primary font-medium mb-6 transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        กลับไปหน้าแรก
    </a>

    <!-- ☠️ Skeleton Loading (ตอนกำลังโหลด) -->
    <div id="detail-skeleton" class="animate-pulse flex flex-col lg:flex-row gap-8">
        <div class="lg:w-1/2 bg-gray-200 rounded-3xl h-[500px]"></div>
        <div class="lg:w-1/2 space-y-4">
            <div class="h-10 bg-gray-200 rounded w-3/4"></div>
            <div class="h-6 bg-gray-200 rounded w-1/2"></div>
            <div class="h-32 bg-gray-100 rounded-2xl w-full mt-6"></div>
        </div>
    </div>

    <!-- 📦 ส่วนเนื้อหาจริง (ซ่อนไว้ก่อนจนกว่าจะโหลด API เสร็จ) -->
    <div id="detail-content" class="hidden flex flex-col lg:flex-row gap-10">
        
        <!-- ฝั่งซ้าย: รูปภาพผลงาน -->
        <div class="lg:w-1/2">
            <div class="bg-white p-4 rounded-3xl shadow-sm border border-gray-100 sticky top-24">
                <img id="product-image" src="" alt="Artwork" class="w-full h-auto object-cover rounded-2xl max-h-[600px]">
            </div>
        </div>

        <!-- ฝั่งขวา: ข้อมูล และ การประมูล -->
        <div class="lg:w-1/2 flex flex-col">
            
            <div class="mb-6">
                <!-- ป้ายสถานะ -->
                <div id="status-badge" class="mb-3 inline-block"></div>
                
                <h1 id="product-title" class="text-3xl md:text-4xl font-bold text-gray-900 leading-tight">...</h1>
                
                <!-- ข้อมูลศิลปิน -->
                <a id="artist-link" href="#" class="mt-4 flex items-center group cursor-pointer inline-block w-max">
                    <img id="artist-avatar" src="" class="w-10 h-10 rounded-full object-cover border-2 border-gray-100 group-hover:border-primary transition">
                    <div class="ml-3">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">สร้างสรรค์โดย</p>
                        <p id="artist-name" class="text-sm font-bold text-gray-900 group-hover:text-primary transition">...</p>
                    </div>
                </a>
            </div>

            <!-- กล่องราคา & เวลา -->
            <div class="bg-gray-900 rounded-3xl p-6 md:p-8 text-white mb-8 shadow-lg relative overflow-hidden">
                <div class="absolute top-0 right-0 w-48 h-48 bg-white opacity-5 rounded-full blur-3xl -mr-10 -mt-10"></div>
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 relative z-10 gap-4">
                    <div>
                        <p class="text-gray-400 text-sm mb-1">ราคาประมูลสูงสุดปัจจุบัน</p>
                        <p class="text-4xl md:text-5xl font-bold text-green-400">฿<span id="current-price">0</span></p>
                        <p class="text-xs text-gray-500 mt-2">บิดขั้นต่ำ <span id="min-step" class="text-gray-300">0</span> บาท/ครั้ง</p>
                    </div>
                    <div class="text-left md:text-right">
                        <p class="text-gray-400 text-sm mb-1">เวลาที่เหลือ</p>
                        <p id="countdown" class="text-2xl font-bold text-white bg-white/10 px-4 py-2 rounded-xl border border-white/20">--:--:--</p>
                    </div>
                </div>

                <!-- ฟอร์มสู้ราคา -->
                <div id="bid-action-area" class="relative z-10 flex gap-3">
                    <input type="number" id="bid-amount" class="flex-grow px-4 py-3 rounded-xl bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-lg" placeholder="ใส่ราคาที่ต้องการสู้...">
                    <button onclick="placeBid()" id="btn-bid" class="bg-primary hover:bg-indigo-500 text-white font-bold px-8 py-3 rounded-xl transition shadow-md whitespace-nowrap">
                        บิดราคา!
                    </button>
                </div>
                <p id="bid-warning" class="text-xs text-red-400 mt-2 hidden">ราคาต้องมากกว่าราคาปัจจุบัน + บิดขั้นต่ำ</p>
            </div>

            <!-- รายละเอียดผลงาน -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-3 border-b border-gray-100 pb-2">รายละเอียดผลงาน</h3>
                <p id="product-desc" class="text-gray-600 leading-relaxed text-sm whitespace-pre-line"></p>
            </div>

            <!-- ประวัติการประมูล -->
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    ประวัติการประมูล (10 ล่าสุด)
                </h3>
                <div id="bid-history" class="space-y-3">
                    <!-- ประวัติจะถูกแทรกที่นี่ -->
                </div>
            </div>

        </div>
    </div>
</main>
<script>
    let currentMaxPrice = 0;
    let minStepPrice = 0;
    let productId = null;
    let pollingInterval = null; // ตัวแปรเก็บเวลา Polling

    document.addEventListener("DOMContentLoaded", () => {
        const urlParams = new URLSearchParams(window.location.search);
        productId = urlParams.get('id');

        if (!productId) {
            Swal.fire('ข้อผิดพลาด', 'ไม่พบรหัสสินค้า', 'error').then(() => window.location.href = '/auction_of_paintings/public/index');
            return;
        }

        // โหลดข้อมูลครั้งแรก
        fetch(`/auction_of_paintings/api/index.php?route=v1/auctions/detail&id=${productId}`)
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                document.getElementById('detail-skeleton')?.classList.add('hidden');
                document.getElementById('detail-content')?.classList.remove('hidden');

                const product = result.product;
                currentMaxPrice = parseFloat(product.current_price) || 0;
                minStepPrice = parseFloat(product.min_step) || 0;

                // อัปเดตข้อมูลสินค้าบนหน้าเว็บ
                document.getElementById('product-image')?.setAttribute('src', product.image_url || 'https://via.placeholder.com/600');
                if(document.getElementById('product-title')) document.getElementById('product-title').innerText = product.title || 'ไม่มีชื่อสินค้า';
                if(document.getElementById('product-desc')) document.getElementById('product-desc').innerText = product.description || '-';
                if(document.getElementById('artist-name')) document.getElementById('artist-name').innerText = product.seller_name || 'Unknown';
                
                updatePriceDisplay(); // แสดงราคาปัจจุบัน

                if (document.getElementById('artist-avatar') && product.seller_avatar_url) {
                    document.getElementById('artist-avatar').src = product.seller_avatar_url;
                }
                if (document.getElementById('artist-link')) {
                    document.getElementById('artist-link').href = `/auction_of_paintings/public/artist_profile?id=${product.seller_id}`;
                }

                // สถานะ และ เวลา
                const statusBadge = document.getElementById('status-badge');
                const actionArea = document.getElementById('bid-action-area');
                
                if (product.status === 'active') {
                    if(statusBadge) statusBadge.innerHTML = `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200 animate-pulse">กำลังเปิดประมูล</span>`;
                    if(product.end_time) startCountdown(product.end_time);
                    
                    // 🚀 เริ่มต้นการทำ AJAX Polling (เช็คราคาทุกๆ 2.5 วินาที)
                    startPolling();
                } else {
                    if(statusBadge) statusBadge.innerHTML = `<span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-bold border border-gray-200">จบการประมูลแล้ว</span>`;
                    if(actionArea) actionArea.innerHTML = `<div class="w-full bg-gray-800 text-center py-3 rounded-xl text-gray-400 font-bold border border-gray-700">หมดเวลาการเสนอราคา</div>`;
                    if(document.getElementById('countdown')) document.getElementById('countdown').innerText = '00:00:00';
                }

                renderBidHistory(result.bid_history || []);
            } else {
                Swal.fire('ข้อผิดพลาด', result.message || 'เกิดข้อผิดพลาด', 'error').then(() => window.location.href = '/auction_of_paintings/public/index');
            }
        });
    });

    // ==========================================
    // 🚀 ระบบ AJAX Short Polling (แทน WebSocket)
    // ==========================================
    function startPolling() {
        // แอบดึงข้อมูลใหม่ทุกๆ 2.5 วินาที
        pollingInterval = setInterval(() => {
            fetch(`/auction_of_paintings/api/index.php?route=v1/auctions/poll&id=${productId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const newPrice = parseFloat(data.current_price);
                    
                    // ถ้าตรวจพบว่าราคามีการเปลี่ยนแปลง!
                    if (newPrice > currentMaxPrice) {
                        currentMaxPrice = newPrice;
                        
                        // อัปเดตราคาบนหน้าจอ พร้อมทำเอฟเฟกต์กระพริบ
                        updatePriceDisplay();
                        const priceEl = document.getElementById('current-price');
                        if (priceEl) {
                            priceEl.classList.add('text-yellow-300', 'scale-110');
                            setTimeout(() => priceEl.classList.remove('text-yellow-300', 'scale-110'), 500);
                        }

                        // อัปเดตประวัติการบิดใหม่
                        renderBidHistory(data.bid_history || []);
                    }

                    // ถ้าแอบดึงข้อมูลแล้วพบว่าสถานะโดนเปลี่ยนเป็น sold แล้ว ให้หยุดดึงข้อมูล
                    if (data.product_status !== 'active') {
                        clearInterval(pollingInterval);
                        document.getElementById('bid-action-area').innerHTML = `<div class="w-full bg-gray-800 text-center py-3 rounded-xl text-gray-400 font-bold border border-gray-700">หมดเวลาการเสนอราคา</div>`;
                    }
                }
            })
            .catch(err => console.error("Polling Error:", err)); // แอบซ่อน Error ไว้เงียบๆ ไม่ต้องโวยวายให้ผู้ใช้เห็น
        }, 2500);
    }

    // ฟังก์ชันจัดการแสดงผลราคา และคำนวณขั้นต่ำ
    function updatePriceDisplay() {
        if(document.getElementById('current-price')) document.getElementById('current-price').innerText = new Intl.NumberFormat('th-TH').format(currentMaxPrice);
        if(document.getElementById('min-step')) document.getElementById('min-step').innerText = new Intl.NumberFormat('th-TH').format(minStepPrice);
        
        const bidAmountInput = document.getElementById('bid-amount');
        if (bidAmountInput) {
            const nextMinBid = currentMaxPrice + minStepPrice;
            bidAmountInput.value = nextMinBid;
            bidAmountInput.min = nextMinBid;
        }
    }

    // ==========================================
    // 💰 การเสนอราคาผ่าน API ปกติ (แทน WebSocket)
    // ==========================================
    function placeBid() {
        const token = localStorage.getItem('jwt_token');
        if (!token) {
            Swal.fire('กรุณาเข้าสู่ระบบ', 'คุณต้องเข้าสู่ระบบก่อนเสนอราคา', 'warning').then(() => window.location.href = '/auction_of_paintings/public/login');
            return;
        }

        const bidInput = document.getElementById('bid-amount');
        if (!bidInput) return;
        const bidAmount = parseFloat(bidInput.value);
        const minRequired = currentMaxPrice + minStepPrice;

        const warning = document.getElementById('bid-warning');
        if (isNaN(bidAmount) || bidAmount < minRequired) {
            if (warning) {
                warning.innerText = `คุณต้องเสนอราคาอย่างน้อย ฿${new Intl.NumberFormat('th-TH').format(minRequired)}`;
                warning.classList.remove('hidden');
            }
            return;
        }

        if(warning) warning.classList.add('hidden');
        
        Swal.fire({
            title: 'ยืนยันการเสนอราคา',
            text: `คุณต้องการเสนอราคา ฿${new Intl.NumberFormat('th-TH').format(bidAmount)} ใช่หรือไม่?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'ยืนยันสู้ราคา!'
        }).then((result) => {
            if (result.isConfirmed) {
                
                // ปิดปุ่มระหว่างส่งข้อมูล
                const btn = document.getElementById('btn-bid');
                if (btn) {
                    btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-3 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังส่ง...';
                    btn.disabled = true;
                }

                // 🚀 ยิง API POST แบบปกติ
                fetch('/auction_of_paintings/api/index.php?route=v1/auctions/bid', {
                    method: 'POST',
                    headers: { 
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json' 
                    },
                    body: JSON.stringify({ product_id: productId, amount: bidAmount })
                })
                .then(res => res.json())
                .then(data => {
                    resetBidButton();
                    if (data.status === 'success') {
                        Swal.fire({
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
                            icon: 'success', title: 'เสนอราคาสำเร็จ!'
                        });
                        // ไม่ต้องทำอะไรเพิ่ม เพราะเดี๋ยวระบบ Polling จะดึงราคาใหม่มาโชว์ที่หน้าจอเองใน 2 วิ
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                })
                .catch(err => {
                    resetBidButton();
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                });
            }
        });
    }

    function resetBidButton() {
        const btn = document.getElementById('btn-bid');
        if (btn) {
            btn.innerHTML = 'บิดราคา!';
            btn.disabled = false;
        }
    }

    function renderBidHistory(historyArray) {
        const historyContainer = document.getElementById('bid-history');
        if (!historyContainer) return;

        if (historyArray.length === 0) {
            historyContainer.innerHTML = '<p id="empty-history" class="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-xl text-center">ยังไม่มีการเสนอราคา เป็นคนแรกสิ!</p>';
            return;
        }

        historyContainer.innerHTML = '';
        historyArray.forEach((bid, index) => {
            const isTop = index === 0;
            const timeStr = new Date(bid.created_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            historyContainer.innerHTML += `
            <div class="bid-item flex items-center justify-between p-3 rounded-xl border ${isTop ? 'bg-green-50 border-green-100 top-bid' : 'bg-white border-gray-100'} transition-all duration-300">
                <div class="flex items-center">
                    <img src="${bid.bidder_avatar_url}" class="w-8 h-8 rounded-full border border-gray-200">
                    <div class="ml-3">
                        <p class="text-sm font-bold text-gray-900">${bid.bidder_name} ${isTop ? '<span class="top-badge text-[10px] bg-green-500 text-white px-2 py-0.5 rounded ml-1">สูงสุด</span>' : ''}</p>
                        <p class="text-xs text-gray-400">${timeStr}</p>
                    </div>
                </div>
                <p class="font-bold price-text ${isTop ? 'text-green-600' : 'text-gray-600'}">฿${new Intl.NumberFormat('th-TH').format(bid.amount)}</p>
            </div>`;
        });
    }

    function startCountdown(endTimeStr) {
        const countDownDate = new Date(endTimeStr).getTime();
        const element = document.getElementById('countdown');
        if (!element) return;

        const x = setInterval(function() {
            const now = new Date().getTime();
            const distance = countDownDate - now;

            if (distance < 0) {
                clearInterval(x);
                element.innerHTML = "หมดเวลาประมูล";
                element.classList.remove('text-white');
                element.classList.add('text-red-400');
                
                const actionArea = document.getElementById('bid-action-area');
                if (actionArea) actionArea.innerHTML = `<div class="w-full bg-gray-800 text-center py-3 rounded-xl text-gray-400 font-bold border border-gray-700">การประมูลจบลงแล้ว</div>`;
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let timeText = '';
            if (days > 0) timeText += `${days} วัน `;
            timeText += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            element.innerHTML = timeText;
        }, 1000);
    }
</script>
<?php include 'layouts/footer.php'; ?>