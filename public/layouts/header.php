<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtBids - แพลตฟอร์มประมูลงานศิลปะออนไลน์</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { primary: '#4F46E5', secondary: '#111827' }
                }
            }
        }
    </script>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- โลโก้ -->
                <div class="flex-shrink-0 flex items-center cursor-pointer">
                    <a href="/auction_of_paintings/public/index" class="text-2xl font-bold text-primary">ArtBids</a>
                </div>

                <!-- เมนูหลัก (Desktop) -->
                <nav class="hidden md:flex space-x-8">
                    <a href="/auction_of_paintings/public/index"
                        class="text-gray-900 font-medium hover:text-primary transition">หน้าแรก</a>
                    <a href="/auction_of_paintings/public/my_bids" class="text-gray-500 hover:text-primary transition">กำลังประมูล</a>
                    <a href="/auction_of_paintings/public/artists"
                        class="text-gray-500 hover:text-primary transition">ศิลปิน</a>
                </nav>

                <!-- พื้นที่สำหรับแสดงปุ่ม Login หรือ Profile -->
                <div class="hidden md:flex items-center space-x-4">

                    <!-- 1. แสดงเมื่อ "ยังไม่ได้ล็อกอิน" -->
                    <div id="nav-guest" class="flex items-center space-x-4">
                        <a href="/auction_of_paintings/public/login"
                            class="text-gray-600 font-medium hover:text-primary transition">เข้าสู่ระบบ</a>
                        <a href="/auction_of_paintings/public/register"
                            class="bg-primary text-white px-5 py-2 rounded-full font-medium hover:bg-indigo-700 transition shadow-md">สมัครสมาชิก</a>
                    </div>

                    <!-- 2. แสดงเมื่อ "ล็อกอินแล้ว" -->
                    <div id="nav-user" class="hidden relative flex items-center space-x-3">
                        <button id="profile-btn"
                            class="flex items-center space-x-2 focus:outline-none hover:bg-gray-50 p-2 rounded-xl transition">
                            <div class="w-9 h-9 bg-indigo-100 rounded-full flex items-center justify-center text-primary font-bold text-sm"
                                id="user-avatar-text">U</div>
                            <div class="text-left hidden lg:block">
                                <p id="user-name-display" class="text-sm font-bold text-gray-800 leading-tight">Username
                                </p>
                                <p id="user-role-display" class="text-xs text-gray-500 leading-tight">Role</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="profile-dropdown"
                            class="hidden absolute right-0 top-12 mt-2 w-56 bg-white rounded-xl shadow-lg py-2 border border-gray-100 z-50 opacity-0 transition-opacity duration-200">
                            <div id="dropdown-menu-items"></div>
                            <div class="h-px bg-gray-100 my-2"></div>
                            <button onclick="logout()"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition">
                                ออกจากระบบ
                            </button>
                        </div>
                    </div>

                </div>

                <!-- ปุ่มเมนูมือถือ -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gray-500 hover:text-gray-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <script>
        function parseJwt(token) {
            try {
                const base64Url = token.split('.')[1];
                const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                const jsonPayload = decodeURIComponent(atob(base64).split('').map(function (c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
                return JSON.parse(jsonPayload);
            } catch (e) {
                return null;
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const token = localStorage.getItem('jwt_token');
            const navGuest = document.getElementById('nav-guest');
            const navUser = document.getElementById('nav-user');
            const dropdownItems = document.getElementById('dropdown-menu-items');

            // กำหนด Base URL เพื่อให้เรียกใช้โฟลเดอร์ย่อยได้ง่าย
            const baseUrl = '/auction_of_paintings/public';

            if (token) {
                const decodedToken = parseJwt(token);
                const currentTime = Math.floor(Date.now() / 1000);

                if (decodedToken && decodedToken.exp > currentTime) {
                    navGuest.classList.add('hidden');
                    navUser.classList.remove('hidden');

                    const userData = decodedToken.data;
                    const roleThai = userData.role === 'seller' ? 'ผู้ลงผลงานประมูล' : (userData.role === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้เข้าร่วมประมูล');

                    // 1. ตั้งค่าข้อมูลเบื้องต้นจาก Token ไปก่อน
                    document.getElementById('user-name-display').innerText = userData.username;
                    document.getElementById('user-role-display').innerText = roleThai;
                    document.getElementById('user-avatar-text').innerText = userData.username.charAt(0).toUpperCase();

                    // 🚀 2. ดึงข้อมูล Display Name และ Avatar จาก API (สังเกตว่าใช้ .php ตรงนี้)
                    fetch('/auction_of_paintings/api/index.php?route=v1/user/profile', {
                        method: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Content-Type': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(result => {
                            if (result.status === 'success') {
                                // อัปเดตชื่อ
                                document.getElementById('user-name-display').innerText = result.data.display_name;

                                // อัปเดตรูปภาพ (ถ้ามี)
                                if (result.data.avatar_url) {
                                    const avatarTextDiv = document.getElementById('user-avatar-text');
                                    avatarTextDiv.innerHTML = `<img src="${result.data.avatar_url}" alt="Profile" class="w-full h-full object-cover rounded-full border border-indigo-200">`;
                                    avatarTextDiv.classList.remove('bg-indigo-100', 'text-primary');
                                }
                            }
                        })
                        .catch(error => console.error('Error fetching profile:', error));


                    // 🔗 ชี้ลิงก์ไปที่โฟลเดอร์ Pages/Seller และ Pages/Buyer
                    let menuHtml = '';
                    if (userData.role === 'seller') {
                        menuHtml = `
                            <a href="${baseUrl}/pages/Seller/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">📊 Dashboard การขาย</a>
                            <a href="${baseUrl}/pages/Seller/create_auction" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">➕ ลงสินค้าประมูล</a>
                            <a href="${baseUrl}/pages/Seller/kyc" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">⚙️ ตั้งค่าโปรไฟล์ & KYC</a>
                        `;
                    } else if (userData.role === 'admin') {
                        menuHtml = `
                            <a href="${baseUrl}/pages/Admin/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">🛡️ อนุมัติ KYC (Dashboard)</a>
                            <a href="${baseUrl}/pages/Admin/finance" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">💼 ระบบการเงิน (Finance)</a>
                            <a href="${baseUrl}/pages/Admin/users" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">👥 จัดการสมาชิก (Users)</a>
                        `;
                    } else { // bidder
                        menuHtml = `
                            <a href="${baseUrl}/pages/Buyer/my_bids" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">🔨 ประวัติการประมูลของฉัน</a>
                            <a href="/auction_of_paintings/public/my_wins" class="block px-4 py-2 text-sm text-indigo-600 font-bold hover:bg-indigo-50">🏆 ผลงานที่ฉันชนะ</a>
                            <a href="${baseUrl}/pages/Buyer/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-primary transition">⚙️ ตั้งค่าโปรไฟล์</a>
                        `;
                    }
                    dropdownItems.innerHTML = menuHtml;

                } else {
                    localStorage.removeItem('jwt_token');
                    localStorage.removeItem('user_role');
                }
            }

            const profileBtn = document.getElementById('profile-btn');
            const profileDropdown = document.getElementById('profile-dropdown');

            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('hidden');
                    setTimeout(() => profileDropdown.classList.toggle('opacity-0'), 10);
                });

                document.addEventListener('click', (e) => {
                    if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.add('opacity-0');
                        setTimeout(() => profileDropdown.classList.add('hidden'), 200);
                    }
                });
            }
        });

        function logout() {
            Swal.fire({
                title: 'ออกจากระบบ?',
                text: "คุณต้องการออกจากระบบใช่หรือไม่",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4F46E5',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, ออกจากระบบ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem('jwt_token');
                    localStorage.removeItem('user_role');
                    window.location.href = '/auction_of_paintings/public/index'; // 🔗 กลับหน้าแรก (Absolute Path)
                }
            });
        }
    </script>