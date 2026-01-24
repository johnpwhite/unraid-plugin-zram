// zram-card.js

(function() {
    const ctx = document.getElementById('zramChart').getContext('2d');
    let chartInstance = null;
    const historyLimit = 30; // Keep last 30 data points
    const historyData = {
        labels: [],
        saved: []
    };

    // Helper: Format Bytes
    function formatBytes(bytes, decimals = 2) {
        if (!+bytes) return '0 B';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }

    // Initialize Chart
    function initChart() {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js not loaded yet.');
            return;
        }

        // Unraid theme colors (approximate)
        const accentColor = getComputedStyle(document.documentElement).getPropertyValue('--orange-500') || '#ff8c00';
        const gridColor = 'rgba(255, 255, 255, 0.1)';
        const textColor = '#ccc';

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: historyData.labels,
                datasets: [{
                    label: 'RAM Saved',
                    data: historyData.saved,
                    borderColor: accentColor,
                    backgroundColor: accentColor + '33', // 20% opacity
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4 // Smooth curves
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return 'Saved: ' + formatBytes(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: false // Hide time labels for cleaner look
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return formatBytes(value, 0);
                            }
                        }
                    }
                },
                animation: { duration: 0 } // Disable animation for performance on updates
            }
        });
    }

    // Fetch and Update
    async function updateStats() {
        try {
            const response = await fetch(ZRAM_CONFIG.url);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();

            // 1. Update Aggregates
            const aggs = data.aggregates;
            document.getElementById('zram-saved').textContent = formatBytes(aggs.memory_saved);
            document.getElementById('zram-ratio').textContent = aggs.compression_ratio + 'x';
            document.getElementById('zram-used').textContent = formatBytes(aggs.total_used);
            
            // Subtitle status
            const statusText = `Total: ${formatBytes(aggs.disk_size_total)} | Used: ${formatBytes(aggs.total_used)}`;
            const sub = document.getElementById('zram-subtitle');
            if (sub) sub.textContent = statusText;
            const subLeg = document.getElementById('zram-subtitle-legacy');
            if (subLeg) subLeg.textContent = statusText;

            // 2. Update Table
            const tbody = document.querySelector('#zram-device-table tbody');
            tbody.innerHTML = '';
            data.devices.forEach(dev => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${dev.name}</td>
                    <td>${formatBytes(dev.disksize)}</td>
                    <td>${formatBytes(dev.data)}</td>
                    <td>${formatBytes(dev.compr)}</td>
                    <td>${dev.algorithm}</td>
                `;
                tbody.appendChild(row);
            });

            // 3. Update Chart
            if (chartInstance) {
                const now = new Date().toLocaleTimeString();
                
                // Add new data
                historyData.labels.push(now);
                historyData.saved.push(aggs.memory_saved);

                // Remove old data
                if (historyData.labels.length > historyLimit) {
                    historyData.labels.shift();
                    historyData.saved.shift();
                }

                chartInstance.update();
            } else {
                initChart();
            }

        } catch (error) {
            console.error('Error fetching ZRAM stats:', error);
        }
    }

    // Start
    initChart();
    updateStats(); // Initial call
    setInterval(updateStats, ZRAM_CONFIG.pollInterval);

})();
