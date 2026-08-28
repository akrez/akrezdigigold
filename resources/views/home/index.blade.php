@extends('layouts.app')

@section('title', 'Akrez Gold')

@section('content')

    <div class="container-fluid">
        <div x-data="summary()">
            <div class="row">
                <div class="col-12 col-xl-8 mx-auto pt-2">
                    <canvas id="chart-prices-CARAT_18" height="96"></canvas>
                </div>
            </div>
            <template x-if="carats.length">
                <div class="row">
                    <div class="col-12 col-xl-8 mx-auto">

                        <ul class="nav nav-pills nav-fill gap-0 pt-2">
                            <template x-for="carat in carats" :key="carat.name">
                                <li class="nav-item">
                                    <button type="button" class="nav-link w-100" :class="activeCarat === carat.name ? 'active' : ''" @click="activeCarat = carat.name" x-text="carat.trans"></button>
                                </li>
                            </template>
                        </ul>

                        <template x-if="sources.length">
                            <ul class="nav nav-pills nav-fill gap-0 pt-2">
                                <template x-for="source in sources" :key="source.name">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link w-100" :class="activeSource === source.name ? 'active' : ''" @click="activeSource = source.name" x-text="source.trans"></button>
                                    </li>
                                </template>
                            </ul>
                        </template>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mt-2">
                                <thead class="bg-200 text-900">
                                    <tr class="table-dark">
                                        <th></th>
                                        <th>قیمت هر گرم</th>
                                        <th>وزن</th>
                                        <th>قیمت</th>
                                    </tr>
                                </thead>
                                <template x-for="scrap in scraps" :key="scrap.source.name">
                                    <template x-for="(variants, variantCarat) in scrap.variants" :key="variantCarat">
                                        <template x-for="(variant, index) in variants.slice(0, 10)" :key="variant.id">
                                            <tbody :class="{ 'table-secondary': index % 2 == 1 }"
                                                x-show="activeSource === scrap.source.name && activeCarat === variantCarat">
                                                <tr>
                                                    <td rowspan="2" class="text-center p-0">
                                                        <img :src="variant.img" class="max-50px" alt="">
                                                    </td>
                                                    <td colspan="2">
                                                        <a class="text-decoration-none" target="_blank" :href="variant.url" x-text="variant.ttl"></a>
                                                    </td>
                                                    <td x-text="variant.sel"></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-monospace" x-text="variant.ppgf"></td>
                                                    <td class="font-monospace" x-text="variant.siz"></td>
                                                    <td class="font-monospace" x-text="variant.prcf"></td>
                                                </tr>
                                            </tbody>
                                        </template>
                                    </template>
                                </template>
                            </table>
                        </div>

                    </div>
                </div>
            </template>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('summary', () => ({
                carats: [],
                scraps: [],
                sources: [],
                activeCarat: '',
                activeSource: '',
                chartData: [],
                init() {
                    const summary = @json($summary);
                    const chart = @json($chart);

                    this.carats = summary.carats || [];
                    this.scraps = summary.scraps || [];
                    this.scraps.forEach(scrap => {
                        this.sources.push(scrap.source);
                    });
                    this.activeCarat = this.carats[0]?.name || '';
                    this.activeSource = this.sources[0]?.name || '';
                    this.initChart('CARAT_18', chart.prices.CARAT_18);
                    this.$nextTick(() => {
                        this.drawChart('CARAT_18', 'chart-prices-CARAT_18');
                    });
                },
                initChart(chartKey, chartData) {
                    if (!chartData) return;
                    const brands = Object.keys(chartData).filter(brand => chartData[brand].length > 0);
                    const allTimes = new Set();
                    brands.forEach(brand => {
                        chartData[brand].forEach(item => {
                            const date = new Date(item.created_at);
                            allTimes.add(
                                date.getHours().toString().padStart(2, '0') + ':' +
                                date.getMinutes().toString().padStart(2, '0')
                            );
                        });
                    });
                    const labels = Array.from(allTimes).sort();
                    const datasets = brands.map((brand, index) => {
                        const priceMap = {};
                        chartData[brand].forEach(item => {
                            const date = new Date(item.created_at);
                            priceMap[
                                date.getHours().toString().padStart(2, '0') + ':' +
                                date.getMinutes().toString().padStart(2, '0')
                            ] = item.price;
                        });
                        const prices = labels.map(time => priceMap[time] || null);
                        const color = `hsl(${index * 50}, 70%, 50%)`;
                        return {
                            label: brand,
                            data: prices,
                            borderColor: color,
                            backgroundColor: color + '33',
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 3,
                            tension: 0.1,
                            fill: false,
                            spanGaps: true
                        };
                    });
                    this.chartData[chartKey] = {
                        labels: labels,
                        datasets: datasets
                    };
                },
                drawChart(chartKey, chartDomId) {
                    data = this.chartData[chartKey];

                    const canvas = document.getElementById(chartDomId);
                    if (!canvas) {
                        console.error('Canvas element not found');
                        return;
                    }

                    const ctx = canvas.getContext('2d');
                    if (!data || !data.datasets.length) {
                        console.error('No chart data available');
                        return;
                    }

                    new Chart(ctx, {
                        type: 'line',
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            family: '"Vazirmatn Variable", sans-serif'
                                        } 
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        maxRotation: 90,
                                        minRotation: 90,
                                        font: {
                                            family: '"Vazirmatn Variable", sans-serif'
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: false,
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)'
                                    },
                                    ticks: {
                                        font: {
                                            family: '"Vazirmatn Variable", sans-serif'
                                        }
                                    }
                                }
                            },
                            interaction: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    });
                }
            }));
        });
    </script>

@endsection
