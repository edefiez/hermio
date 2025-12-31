/**
 * Analytics Dashboard - Chart.js Integration
 * Renders scan analytics charts in the dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    const chartCanvas = document.getElementById('scansPerDayChart');
    
    if (!chartCanvas || !window.analyticsData) {
        return;
    }

    const scansPerDay = window.analyticsData.scansPerDay;
    
    // Prepare data for Chart.js
    const dates = Object.keys(scansPerDay);
    const counts = Object.values(scansPerDay);

    // Format dates for display (MM/DD)
    const labels = dates.map(date => {
        const d = new Date(date);
        return `${d.getMonth() + 1}/${d.getDate()}`;
    });

    // Create the chart
    const ctx = chartCanvas.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Scans',
                data: counts,
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.3,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: 'rgb(102, 126, 234)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 3,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function(tooltipItems) {
                            const index = tooltipItems[0].dataIndex;
                            return dates[index];
                        },
                        label: function(context) {
                            return 'Scans: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
