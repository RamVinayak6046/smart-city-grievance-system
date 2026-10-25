/* ============================================================
   CivicFix Pro — App JavaScript (app.js)
   Vanilla JS only — no frameworks.
   ============================================================ */

(function () {
    'use strict';

    // ── Sidebar Toggle (mobile) ─────────────────────────────────
    const sidebarToggle  = document.getElementById('sidebarToggle');
    const sidebar        = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar && sidebar.classList.add('open');
        sidebarOverlay && sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar && sidebar.classList.remove('open');
        sidebarOverlay && sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    sidebarToggle  && sidebarToggle.addEventListener('click', openSidebar);
    sidebarOverlay && sidebarOverlay.addEventListener('click', closeSidebar);

    // ── Auto-dismiss alerts after 5 s ──────────────────────────
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap && bootstrap.Alert.getOrCreateInstance(el);
            bsAlert && bsAlert.close();
        }, 5000);
    });

    // ── Confirm dialogs for dangerous actions ────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const msg = btn.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ── Character counter for textareas ─────────────────────────
    document.querySelectorAll('textarea[maxlength]').forEach(function (ta) {
        const max     = parseInt(ta.getAttribute('maxlength'), 10);
        const counter = document.createElement('small');
        counter.className = 'form-text text-muted';
        counter.textContent = '0 / ' + max + ' characters';
        ta.parentNode.appendChild(counter);

        ta.addEventListener('input', function () {
            counter.textContent = ta.value.length + ' / ' + max + ' characters';
            counter.className   = ta.value.length > max * 0.9 ? 'form-text text-danger' : 'form-text text-muted';
        });
    });

    // ── Image file preview ───────────────────────────────────────
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        const previewId = input.getAttribute('data-preview');
        const container = document.getElementById(previewId);
        if (!container) return;

        input.addEventListener('change', function () {
            container.innerHTML = '';
            Array.from(input.files).forEach(function (file) {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail me-2 mb-2';
                    img.style.maxHeight = '120px';
                    img.alt = file.name;
                    container.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    });

    // ── Notification polling (every 60 s) ────────────────────────
    const notifBadge = document.querySelector('.notif-badge-count');
    if (notifBadge) {
        function pollNotifications() {
            fetch(window.APP_URL + '/actions/get_notif_count.php', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && typeof data.count === 'number') {
                        if (data.count > 0) {
                            notifBadge.textContent = data.count > 99 ? '99+' : data.count;
                            notifBadge.style.display = 'inline-flex';
                        } else {
                            notifBadge.style.display = 'none';
                        }
                    }
                })
                .catch(function () {});
        }

        setInterval(pollNotifications, 60000);
    }

    // ── GPS location on complaint form ───────────────────────────
    const gpsBtn   = document.getElementById('btnGetLocation');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const gpsInfo  = document.getElementById('gpsStatus');

    if (gpsBtn && latInput && lngInput) {
        gpsBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                gpsInfo && (gpsInfo.textContent = 'Geolocation not supported by your browser.');
                return;
            }
            gpsBtn.disabled    = true;
            gpsBtn.textContent = 'Detecting…';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    latInput.value = pos.coords.latitude.toFixed(7);
                    lngInput.value = pos.coords.longitude.toFixed(7);
                    if (gpsInfo) gpsInfo.textContent = 'Location captured: ' + latInput.value + ', ' + lngInput.value;
                    gpsBtn.textContent = 'Location Captured ✓';
                },
                function (err) {
                    if (gpsInfo) gpsInfo.textContent = 'Could not get location. You can submit without it.';
                    gpsBtn.disabled    = false;
                    gpsBtn.textContent = 'Use My Location';
                }
            );
        });
    }

})();
