<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="md:flex md:items-center md:justify-between">
            <div class="flex justify-center md:justify-start mb-6 md:mb-0">
                <span class="text-xl font-bold text-primary">ArtBids</span>
            </div>
            <p class="text-center text-sm text-gray-500">
                &copy; 2026 ArtBids Platform. สงวนลิขสิทธิ์. ระบบประมูลที่ปลอดภัย
            </p>
        </div>
    </div>
</footer>
<script>
    // ⏰ ตัวจำลอง Cron Job (รันเบื้องหลังทุกๆ 1 นาที)
    setInterval(() => {
        fetch('/auction_of_paintings/api/index.php?route=v1/system/cron-close-auctions')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.message.includes('ปิดประมูลไป') && !data.message.includes('0 รายการ')) {
                    console.log("Auto-Cron Executed: ", data.message);
                }
            }).catch(err => console.error("Cron error"));
    }, 60000); // 60000 ms = 1 นาที
</script>
</body>

</html>