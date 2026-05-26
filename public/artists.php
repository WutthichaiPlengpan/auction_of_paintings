<?php include 'layouts/header.php'; ?>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full min-h-[calc(100vh-140px)]">
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">👨‍🎨 ทำเนียบศิลปินของเรา</h1>
        <p class="text-gray-500">ค้นพบและติดตามผลงานจากศิลปินมากพรสวรรค์</p>
    </div>

    <div id="artists-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        <div class="animate-pulse flex flex-col items-center p-6 bg-white rounded-3xl shadow-sm border border-gray-100">
            <div class="w-24 h-24 bg-gray-200 rounded-full mb-4"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
        </div>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const container = document.getElementById('artists-container');

        fetch('/auction_of_paintings/api/index.php?route=v1/artists/list')
        .then(res => res.json())
        .then(result => {
            container.innerHTML = '';

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(artist => {
                    const name = artist.display_name || 'ศิลปินนิรนาม';
                    const card = `
                    <div onclick="window.location.href='/auction_of_paintings/public/artist_profile?id=${artist.id}'" 
                         class="bg-white rounded-3xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col items-center text-center cursor-pointer group">
                        
                        <div class="relative w-28 h-28 mb-4">
                            <div class="absolute inset-0 bg-primary rounded-full blur opacity-0 group-hover:opacity-30 transition-opacity duration-300"></div>
                            <img src="${artist.avatar_url}" class="relative w-full h-full object-cover rounded-full border-4 border-white shadow-md group-hover:scale-105 transition-transform duration-300">
                        </div>
                        
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-primary transition-colors">${name}</h3>
                        
                        <div class="flex gap-4 mt-4 w-full border-t border-gray-100 pt-4">
                            <div class="flex-1">
                                <p class="text-xs text-gray-400 uppercase tracking-wider">ผลงาน</p>
                                <p class="font-bold text-gray-900">${artist.total_arts}</p>
                            </div>
                            <div class="w-px bg-gray-100"></div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-400 uppercase tracking-wider">ขายแล้ว</p>
                                <p class="font-bold text-green-600">${artist.sold_arts}</p>
                            </div>
                        </div>
                    </div>`;
                    container.innerHTML += card;
                });
            } else {
                container.innerHTML = `<div class="col-span-full text-center py-12 text-gray-500">ยังไม่มีศิลปินในระบบ</div>`;
            }
        });
    });
</script>

<?php include 'layouts/footer.php'; ?>