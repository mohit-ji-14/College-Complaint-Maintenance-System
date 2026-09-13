// assets/js/charts.js - Admin Analytics Visualization with Chart.js

function initAdminCharts(statusData, categoryLabels, categoryData) {
    // Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Assigned', 'In Progress', 'Resolved', 'Rejected'],
                datasets: [{
                    data: [
                        statusData.Pending || 0,
                        statusData.Assigned || 0,
                        statusData['In Progress'] || 0,
                        statusData.Resolved || 0,
                        statusData.Rejected || 0
                    ],
                    backgroundColor: ['#f59e0b', '#06b6d4', '#4f46e5', '#10b981', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });
    }

    // Category Bar Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        new Chart(categoryCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Total Complaints',
                    data: categoryData,
                    backgroundColor: 'rgba(79, 70, 229, 0.85)',
                    borderColor: '#4f46e5',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
}
