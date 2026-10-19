<?php
// includes/footer.php - Footer Template
?>
    </main>
    <footer class="bg-white border-top py-3 px-4 text-center text-muted small mt-auto">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
            <span>&copy; <?= date('Y') ?> <strong>Sistem Informasi Manajemen Parkir</strong>. All rights reserved.</span>
            <span class="mt-1 mt-sm-0 text-secondary">Versi 1.0.0 &bull; drawSQL Database Architecture</span>
        </div>
    </footer>
</div>
</div> <!-- End app-wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- JsBarcode -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
// Jam Digital Realtime
function updateClock() {
    const el = document.getElementById('liveClock');
    if (!el) return;
    const now = new Date();
    const options = { 
        weekday: 'short', 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: false 
    };
    el.innerText = now.toLocaleDateString('id-ID', options) + ' WIB';
}
setInterval(updateClock, 1000);
updateClock();

// Toggle Sidebar Mobile
const toggleBtn = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('appSidebar');
if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('d-none');
    });
}
</script>
</body>
</html>

