/* ============================================================
   CivicFix Pro — Dashboard JS (dashboard.js)
   Chart.js initialization helpers
   ============================================================ */

(function () {
    'use strict';

    // Default chart colors
    const COLORS = {
        primary:   '#1a3c6e',
        secondary: '#0d9488',
        amber:     '#f59e0b',
        purple:    '#7c3aed',
        green:     '#16a34a',
        red:       '#dc2626',
        cyan:      '#0891b2',
        orange:    '#ea580c',
        gray:      '#6b7280',
    };

    // ── Donut / Doughnut chart helper ────────────────────────────
    window.CivicChart = {
        doughnut: function (canvasId, labels, data, colors) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{ data: data, backgroundColor: colors || Object.values(COLORS), borderWidth: 2, borderColor: '#fff' }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct   = total ? Math.round(ctx.parsed / total * 100) : 0;
                                    return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                }
                            }
                        }
                    },
                    cutout: '65%',
                }
            });
        },

        bar: function (canvasId, labels, datasets, options) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            new Chart(ctx, {
                type: 'bar',
                data: { labels: labels, datasets: datasets },
                options: Object.assign({
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }, options || {})
            });
        },

        line: function (canvasId, labels, datasets, options) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            new Chart(ctx, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: Object.assign({
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { grid: { color: '#f1f5f9' } },
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } }
                    },
                    elements: { line: { tension: .35 }, point: { radius: 3 } }
                }, options || {})
            });
        },

        COLORS: COLORS,
    };

})();
