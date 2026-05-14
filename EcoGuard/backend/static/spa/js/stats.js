document.addEventListener('DOMContentLoaded', () => {
    const pieChart = echarts.init(document.getElementById('pieChart'));
    const trendChart = echarts.init(document.getElementById('lineChart'));
    const forecastChart = echarts.init(document.getElementById('forecastChart'));
    const forecastMeta = document.getElementById('forecastMeta');
    const forecastRecommendations = document.getElementById('forecastRecommendations');
    const map = L.map('statsMap').setView([30, 110], 5);
    const actualPointLayer = L.layerGroup().addTo(map);
    const hotspotLayer = L.layerGroup().addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const dotIcon = L.divIcon({
        className: 'map-dot-icon',
        html: '<span class="map-dot"></span>',
        iconSize: [10, 10],
        iconAnchor: [5, 5]
    });

    const asArray = (value) => (Array.isArray(value) ? value : []);
    const hasCoordinate = (lat, lng) => lat !== null && lat !== undefined && lng !== null && lng !== undefined;
    const popupText = (value) => String(value ?? '').replace(/[<>]/g, '');

    function renderPieChart(pieData) {
        const finalData = pieData && pieData.length ? pieData : [{
            name: '暂无数据',
            value: 1,
            itemStyle: {color: '#22303a'}
        }];

        pieChart.setOption({
            tooltip: {},
            series: [{
                type: 'pie',
                radius: ['40%', '70%'],
                data: finalData,
                label: {color: '#82b2c6'}
            }]
        });
    }

    function renderEmptyTrend() {
        trendChart.clear();
        trendChart.setOption({
            graphic: [{
                type: 'text',
                left: 'center',
                top: '45%',
                style: {text: '暂无趋势数据', fill: '#6b7280', font: '14px Microsoft YaHei'}
            }]
        });
    }

    function renderTrendChart(lineData) {
        const labels = asArray(lineData && lineData.labels);
        const seriesRaw = asArray(lineData && lineData.series);

        if (!labels.length || !seriesRaw.length) {
            renderEmptyTrend();
            return;
        }

        const series = seriesRaw.map((item) => ({
            name: item.name,
            type: 'line',
            smooth: true,
            symbol: 'circle',
            symbolSize: 6,
            showSymbol: false,
            emphasis: {focus: 'series'},
            data: asArray(item.values)
        }));

        trendChart.setOption({
            tooltip: {trigger: 'axis'},
            legend: {type: 'scroll', top: 0, textStyle: {color: '#82b2c6'}},
            grid: {left: 48, right: 24, top: 54, bottom: 54},
            xAxis: {
                type: 'category',
                data: labels,
                boundaryGap: false,
                axisLabel: {color: '#82b2c6', rotate: labels.length > 12 ? 30 : 0},
                axisLine: {lineStyle: {color: 'rgba(130, 178, 198, 0.35)'}}
            },
            yAxis: {
                type: 'value',
                minInterval: 1,
                axisLabel: {color: '#82b2c6'},
                splitLine: {lineStyle: {color: 'rgba(130, 178, 198, 0.12)'}}
            },
            dataZoom: labels.length > 12 ? [{type: 'inside'}, {type: 'slider', height: 16, bottom: 10}] : [],
            series: series
        });
    }

    function renderMapPoints(locations) {
        actualPointLayer.clearLayers();
        asArray(locations).forEach((location) => {
            if (!hasCoordinate(location.lat, location.lng)) return;
            const marker = L.marker([location.lat, location.lng], {icon: dotIcon}).addTo(actualPointLayer);
            marker.bindPopup(`<b>任务 ${popupText(location.id)}</b><br>${popupText(location.trash_types)}`);
        });
    }

    function riskColor(score) {
        if (score >= 80) return '#ef4444';
        if (score >= 60) return '#f97316';
        if (score >= 40) return '#facc15';
        return '#22c55e';
    }

    function getHotspotRegionText(item) {
        const parts = [item.province, item.city, item.district, item.town, item.road]
            .map((value) => String(value || '').trim())
            .filter((value) => value.length > 0);
        if (parts.length) return parts.join(' / ');
        const displayName = String(item.display_name || '').trim();
        return displayName || '未知位置';
    }

    function renderForecastChart(payload) {
        const chartData = payload && payload.chart_data ? payload.chart_data : {labels: [], values: []};

        if (!chartData.labels.length || !chartData.values.length) {
            forecastChart.clear();
            forecastChart.setOption({
                graphic: [{
                    type: 'text',
                    left: 'center',
                    top: '45%',
                    style: {text: '暂无预测数据', fill: '#6b7280', font: '14px Microsoft YaHei'}
                }]
            });
            return;
        }

        forecastChart.setOption({
            tooltip: {trigger: 'axis'},
            grid: {left: 48, right: 24, top: 24, bottom: 36},
            xAxis: {
                type: 'category',
                data: chartData.labels,
                axisLabel: {color: '#82b2c6'},
                axisLine: {lineStyle: {color: 'rgba(130, 178, 198, 0.35)'}}
            },
            yAxis: {
                type: 'value',
                minInterval: 1,
                axisLabel: {color: '#82b2c6'},
                splitLine: {lineStyle: {color: 'rgba(130, 178, 198, 0.12)'}}
            },
            series: [{
                type: 'bar',
                barWidth: '42%',
                data: chartData.values,
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        {offset: 0, color: '#fb7185'},
                        {offset: 1, color: '#f97316'}
                    ]),
                    borderRadius: [6, 6, 0, 0]
                }
            }]
        });
    }

    function renderForecastMeta(payload) {
        const summary = payload && payload.summary ? payload.summary : {};
        forecastMeta.innerHTML = `
            <div class="forecast-meta-item"><span>分析网格</span><strong>${popupText(summary.cells_analyzed || 0)}</strong></div>
            <div class="forecast-meta-item"><span>历史任务</span><strong>${popupText(summary.tasks_used || 0)}</strong></div>
            <div class="forecast-meta-item"><span>检测目标</span><strong>${popupText(summary.detections_used || 0)}</strong></div>
        `;
    }

    function renderRecommendations(payload) {
        const recommendations = asArray(payload && payload.recommendations);
        const hotspots = asArray(payload && payload.hotspots);
        const blocks = [];

        hotspots.slice(0, 3).forEach((item) => {
            const regionText = getHotspotRegionText(item);
            blocks.push(`
                <div class="forecast-list-item">
                    <div class="forecast-list-head">
                        <span class="forecast-rank">TOP ${popupText(item.rank)}</span>
                        <span class="forecast-risk" style="background:${riskColor(item.risk_score)}">风险 ${popupText(item.risk_score)}</span>
                    </div>
                    <div class="forecast-list-main">预测目标数 ${popupText(item.predicted_count)}，主导类型：${popupText(asArray(item.dominant_labels).join('、') || '未知')}</div>
                    <div class="forecast-list-sub">位置：${popupText(regionText)}</div>
                    <div class="forecast-list-sub">${popupText(item.reason)}</div>
                </div>
            `);
        });

        recommendations.forEach((item) => {
            blocks.push(`<div class="forecast-list-note">${popupText(item)}</div>`);
        });

        forecastRecommendations.innerHTML = blocks.length
            ? blocks.join('')
            : '<div class="forecast-list-empty">暂无巡检建议</div>';
    }

    function renderHotspotsOnMap(payload) {
        hotspotLayer.clearLayers();
        asArray(payload && payload.hotspots).forEach((item) => {
            if (!hasCoordinate(item.center_lat, item.center_lng)) return;
            const color = riskColor(item.risk_score);
            const circle = L.circleMarker([item.center_lat, item.center_lng], {
                radius: Math.max(8, Math.min(20, Math.round(item.risk_score / 6))),
                color: color,
                weight: 2,
                fillColor: color,
                fillOpacity: 0.35
            }).addTo(hotspotLayer);
            circle.bindPopup(
                `<b>预测热点 TOP ${popupText(item.rank)}</b><br>` +
                `风险分：${popupText(item.risk_score)}<br>` +
                `预测目标数：${popupText(item.predicted_count)}<br>` +
                `主导类型：${popupText(asArray(item.dominant_labels).join('、') || '未知')}<br>` +
                `位置：${popupText(getHotspotRegionText(item))}`
            );
        });
    }

    function updateMapViewport(summaryPayload, forecastPayload) {
        const points = [];
        asArray(summaryPayload && summaryPayload.locations).forEach((item) => {
            if (hasCoordinate(item.lat, item.lng)) {
                points.push([item.lat, item.lng]);
            }
        });
        asArray(forecastPayload && forecastPayload.hotspots).forEach((item) => {
            if (hasCoordinate(item.center_lat, item.center_lng)) {
                points.push([item.center_lat, item.center_lng]);
            }
        });

        if (!points.length) {
            return;
        }

        map.fitBounds(points, {padding: [32, 32], maxZoom: 13});
    }

    function renderHotspotFallback() {
        renderForecastChart({chart_data: {labels: [], values: []}});
        renderForecastMeta({summary: {cells_analyzed: 0, tasks_used: 0, detections_used: 0}});
        forecastRecommendations.innerHTML = '<div class="forecast-list-empty">热点数据加载中或暂不可用</div>';
    }

    async function loadDashboard() {
        let summaryPayload = null;
        try {
            const summaryResponse = await fetch('/api/stats/summary');
            summaryPayload = await summaryResponse.json();
            if (!summaryPayload.ok) {
                renderHotspotFallback();
                return;
            }

            renderPieChart(summaryPayload.pie_data);
            renderTrendChart(summaryPayload.line_data);
            renderMapPoints(summaryPayload.locations);
        } catch (error) {
            console.error('load summary failed:', error);
            renderHotspotFallback();
            return;
        }

        try {
            const forecastResponse = await fetch('/api/stats/hotspots');
            const forecastPayload = await forecastResponse.json();
            if (forecastPayload.ok) {
                renderForecastChart(forecastPayload);
                renderForecastMeta(forecastPayload);
                renderRecommendations(forecastPayload);
                renderHotspotsOnMap(forecastPayload);
                updateMapViewport(summaryPayload, forecastPayload);
            } else {
                renderHotspotFallback();
            }
        } catch (error) {
            console.error('load hotspots failed:', error);
            renderHotspotFallback();
        }

        setTimeout(() => {
            try {
                map.invalidateSize();
            } catch (error) {
            }
        }, 300);
    }

    loadDashboard();
});
