let map;
let marker;
let isOnline = false;

// โหลด Longdo Map
window.onload = function () {
    map = new longdo.Map({
        placeholder: document.getElementById('map')
    });
    map.Ui.Zoombar.visible(true);
    map.Ui.Geolocation.visible(false);
};

// ปักหมุดตำแหน่งปัจจุบัน
document.getElementById('btn-current-location').addEventListener('click', () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((position) => {
            const { latitude, longitude } = position.coords;

            // วาง Marker บนแผนที่
            if (marker) map.Overlays.remove(marker);
            marker = new longdo.Marker({ lon: longitude, lat: latitude });
            map.Overlays.add(marker);
            map.location({ lon: longitude, lat: latitude });

            // ส่งข้อมูลตำแหน่งไปยังเซิร์ฟเวอร์
            updateLocation(latitude, longitude);
        });
    } else {
        alert('Geolocation ไม่พร้อมใช้งานบนเบราว์เซอร์นี้');
    }
});

// สลับสถานะ Online/Offline
document.getElementById('btn-status').addEventListener('click', () => {
    isOnline = !isOnline;
    const statusText = isOnline ? '🟢 Online' : '🔴 Offline';
    document.getElementById('btn-status').textContent = statusText;

    // อัพเดตสถานะไปยังเซิร์ฟเวอร์
    updateStatus(isOnline ? 'online' : 'offline');
});

// ฟังก์ชันอัพเดตตำแหน่ง
function updateLocation(latitude, longitude) {
    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            latitude,
            longitude
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ตำแหน่งอัพเดตสำเร็จ');
            console.log('ตำแหน่งอัพเดตสำเร็จ');
        } else {
            alert('เกิดข้อผิดพลาดในการอัพเดตตำแหน่ง: ' + data.error);
            console.error('เกิดข้อผิดพลาด:', data.error);
        }
    })
    .catch(err => {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        console.error('Error:', err);
    });
}

// ฟังก์ชันอัพเดตสถานะ
function updateStatus(status) {
    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('สถานะอัพเดตเป็น ' + status + ' สำเร็จ');
            console.log('สถานะอัพเดตสำเร็จ');
        } else {
            alert('เกิดข้อผิดพลาดในการอัพเดตสถานะ: ' + data.error);
            console.error('เกิดข้อผิดพลาด:', data.error);
        }
    })
    .catch(err => {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        console.error('Error:', err);
    });
}
