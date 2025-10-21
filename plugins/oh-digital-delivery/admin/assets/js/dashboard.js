(function (window, document) {
    'use strict';

    const config = window.OHDigitalAdmin || {};
    if (!config.root) {
        return;
    }

    const charts = {};
    const currencyCache = new Map();
    const numberFormatter = new Intl.NumberFormat(config.locale || 'tr-TR');

    function getCurrencyFormatter(currency) {
        const key = (config.locale || 'tr-TR') + '-' + currency;
        if (! currencyCache.has(key)) {
            currencyCache.set(
                key,
                new Intl.NumberFormat(config.locale || 'tr-TR', {
                    style: 'currency',
                    currency: currency || config.currency || 'TRY',
                    maximumFractionDigits: 2,
                })
            );
        }

        return currencyCache.get(key);
    }

    function formatCurrency(value, currency) {
        return getCurrencyFormatter(currency || config.currency || 'TRY').format(Number(value || 0));
    }

    function formatNumber(value) {
        return numberFormatter.format(Number(value || 0));
    }

    function formatDelta(current, previous, options = {}) {
        const diff = Number(current || 0) - Number(previous || 0);
        if (Math.abs(diff) < 0.001) {
            return options.emptyLabel || '±0';
        }

        const sign = diff > 0 ? '+' : '';
        if (options.currency) {
            return sign + formatCurrency(Math.abs(diff), options.currency);
        }

        return sign + formatNumber(Math.abs(diff));
    }

    function apiFetch(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : config.root.replace(/\/$/, '') + '/' + endpoint.replace(/^\//, '');
        const headers = Object.assign({ 'X-WP-Nonce': config.nonce }, options.headers || {});
        const opts = Object.assign(
            {
                credentials: 'same-origin',
                headers,
            },
            options
        );

        if (opts.body && ! (opts.body instanceof FormData)) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(opts.body);
        }

        return fetch(url, opts).then(async (response) => {
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            const payload = isJson ? await response.json() : {};

            if (! response.ok) {
                const message = payload && payload.message ? payload.message : response.statusText;
                throw new Error(message || 'Request failed');
            }

            return payload;
        });
    }

    function updateCard(root, key, value, delta) {
        const card = root.querySelector('[data-stat="' + key + '"]');
        if (! card) {
            return;
        }

        const valueEl = card.querySelector('[data-value]');
        if (valueEl) {
            valueEl.textContent = value;
        }

        const deltaEl = card.querySelector('[data-delta]');
        if (deltaEl) {
            if (delta === undefined || delta === null || '' === delta) {
                deltaEl.textContent = '';
                deltaEl.style.display = 'none';
            } else {
                deltaEl.textContent = delta;
                deltaEl.style.display = '';
            }
        }
    }

    function renderTrendChart(dataset) {
        const canvas = document.getElementById('oh-dashboard-trend');
        if (! canvas) {
            return;
        }

        if (charts.trend) {
            charts.trend.destroy();
        }

        const labels = dataset.map((item) => item.label);
        const orders = dataset.map((item) => item.orders);
        const revenue = dataset.map((item) => item.revenue);

        charts.trend = new window.Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: config.i18n.orders,
                        data: orders,
                        borderColor: '#77d9ff',
                        backgroundColor: 'rgba(119, 217, 255, 0.2)',
                        tension: 0.3,
                        yAxisID: 'y',
                    },
                    {
                        label: config.i18n.revenue,
                        data: revenue,
                        borderColor: '#9c7bff',
                        backgroundColor: 'rgba(156, 123, 255, 0.2)',
                        tension: 0.3,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: '#f5f7fa',
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#b7c4ff',
                        },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: '#b7c4ff',
                            callback: (value) => formatCurrency(value),
                        },
                    },
                    x: {
                        ticks: {
                            color: '#c3cdf9',
                        },
                    },
                },
            },
        });
    }

    function renderStatusChart(breakdown) {
        const canvas = document.getElementById('oh-dashboard-status');
        if (! canvas) {
            return;
        }

        if (charts.status) {
            charts.status.destroy();
        }

        const labels = Object.keys(breakdown).map((key) => config.i18n.statusLabels[key] || key);
        const values = Object.keys(breakdown).map((key) => breakdown[key]);

        charts.status = new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: ['#ffd369', '#6be3b2', '#ff9f68', '#ff6b6b'],
                    },
                ],
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#f5f7fa',
                        },
                    },
                },
            },
        });
    }

    function renderDailyChart(dataset) {
        const canvas = document.getElementById('oh-dashboard-daily');
        if (! canvas) {
            return;
        }

        if (charts.daily) {
            charts.daily.destroy();
        }

        charts.daily = new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: dataset.map((item) => item.label),
                datasets: [
                    {
                        type: 'line',
                        label: config.i18n.revenue,
                        data: dataset.map((item) => item.revenue),
                        borderColor: '#9c7bff',
                        backgroundColor: 'rgba(156, 123, 255, 0.2)',
                        yAxisID: 'y1',
                        tension: 0.3,
                    },
                    {
                        type: 'bar',
                        label: config.i18n.orders,
                        data: dataset.map((item) => item.orders),
                        backgroundColor: 'rgba(119, 217, 255, 0.5)',
                        borderColor: '#77d9ff',
                        borderWidth: 1,
                        yAxisID: 'y',
                    },
                ],
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: '#f5f7fa',
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#b7c4ff',
                        },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            color: '#b7c4ff',
                            callback: (value) => formatCurrency(value),
                        },
                    },
                    x: {
                        ticks: {
                            color: '#c3cdf9',
                            maxRotation: 0,
                        },
                    },
                },
            },
        });
    }

    function renderPaymentsList(root, payments) {
        const list = root.querySelector('[data-payment-breakdown]');
        if (! list) {
            return;
        }

        list.innerHTML = '';
        if (! payments || ! payments.length) {
            list.innerHTML = '<li>' + (config.i18n.noResults || '—') + '</li>';
            return;
        }

        payments.forEach((payment) => {
            const li = document.createElement('li');
            li.textContent = payment.label + ' — ' + formatNumber(payment.orders) + ' · ' + formatCurrency(payment.revenue);
            list.appendChild(li);
        });
    }

    function renderTopProducts(listRoot, products) {
        if (! listRoot) {
            return;
        }

        listRoot.innerHTML = '';
        if (! products || ! products.length) {
            listRoot.innerHTML = '<li>' + (config.i18n.noResults || '—') + '</li>';
            return;
        }

        products.forEach((product) => {
            const li = document.createElement('li');
            li.textContent = product.product_name + ' — ' + formatNumber(product.quantity) + ' · ' + formatCurrency(product.revenue);
            listRoot.appendChild(li);
        });
    }

    function renderCustomerMetrics(root, metrics) {
        const container = root.querySelector('[data-customer-metrics]');
        if (! container) {
            return;
        }

        const rows = container.querySelectorAll('div');
        if (rows[0]) {
            rows[0].querySelector('dd').textContent = formatNumber(metrics.total_customers || 0);
        }
        if (rows[1]) {
            rows[1].querySelector('dd').textContent = (metrics.repeat_rate || 0) + '%';
        }
    }

    function renderRecentOrders(body, orders) {
        if (! body) {
            return;
        }

        body.innerHTML = '';
        if (! orders || ! orders.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.textContent = config.i18n.noResults || '—';
            row.appendChild(cell);
            body.appendChild(row);
            return;
        }

        orders.forEach((order) => {
            const row = document.createElement('tr');
            const items = order.items.map((item) => item.quantity + '× ' + item.name).join(', ');

            row.innerHTML =
                '<td>#' + order.number + '</td>' +
                '<td>' + items + '</td>' +
                '<td>' + formatCurrency(order.total, order.currency) + '</td>' +
                '<td><span class="oh-status oh-status--' + order.delivery_status + '">' + (config.i18n.statusLabels[order.delivery_status] || order.delivery_status) + '</span></td>' +
                '<td>' + (order.date_created || '') + '</td>';

            body.appendChild(row);
        });
    }

    function updateDashboard(root, payload) {
        const today = payload.today || {};
        const yesterday = payload.yesterday || {};
        const seven = payload.short_term ? payload.short_term.seven_days || {} : {};
        const thirty = payload.short_term ? payload.short_term.thirty_days || {} : {};
        const totals = payload.totals || {};

        updateCard(root, 'today-orders', formatNumber(today.orders || 0), formatDelta(today.orders || 0, yesterday.orders || 0));
        updateCard(root, 'today-revenue', formatCurrency(today.revenue || 0), formatDelta(today.revenue || 0, yesterday.revenue || 0, { currency: config.currency }));
        updateCard(root, 'today-deliveries', formatNumber(today.deliveries || 0), formatDelta(today.deliveries || 0, yesterday.deliveries || 0));
        updateCard(root, 'seven-days', formatNumber(seven.orders || 0) + ' · ' + formatCurrency(seven.revenue || 0), (config.i18n.revenue || 'Ciro') + ': ' + formatCurrency(seven.revenue || 0));
        updateCard(root, 'thirty-days', formatNumber(thirty.orders || 0) + ' · ' + formatCurrency(thirty.revenue || 0), (config.i18n.revenue || 'Ciro') + ': ' + formatCurrency(thirty.revenue || 0));
        updateCard(root, 'pending-orders', formatNumber(totals.pending_orders || 0));
        updateCard(root, 'total-revenue', formatCurrency(totals.total_revenue || 0));
        updateCard(root, 'stock-waiting', formatNumber(totals.stock_waiting || 0));
        updateCard(root, 'completed-deliveries', formatNumber(totals.completed_deliveries || 0));

        renderTrendChart(payload.charts ? payload.charts.monthly_trend || [] : []);
        renderStatusChart(payload.charts ? payload.charts.status_breakdown || {} : {});
        renderDailyChart(payload.charts ? payload.charts.daily_sales || [] : []);
        renderTopProducts(root.querySelector('[data-top-products]'), payload.top_products || []);
        renderPaymentsList(root, payload.payments || []);
        renderCustomerMetrics(root, payload.customer || {});
        renderRecentOrders(root.querySelector('[data-recent-orders]'), payload.recent_orders || []);
    }

    function initDashboard(root) {
        const rangeWrapper = root.querySelector('[data-range-picker]');

        function requestStats(params = {}) {
            const search = new URLSearchParams();
            Object.keys(params).forEach((key) => {
                if (params[key]) {
                    search.append(key, params[key]);
                }
            });

            const endpoint = 'stats' + (search.toString() ? '?' + search.toString() : '');
            root.setAttribute('data-loading', 'true');
            apiFetch(endpoint)
                .then((response) => {
                    root.removeAttribute('data-loading');
                    updateDashboard(root, response.data || response);
                })
                .catch((error) => {
                    root.removeAttribute('data-loading');
                    console.error(error);
                });
        }

        if (rangeWrapper) {
            rangeWrapper.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-range]');
                if (! button) {
                    return;
                }

                rangeWrapper.querySelectorAll('button[data-range]').forEach((btn) => btn.classList.remove('is-active'));
                button.classList.add('is-active');

                const range = button.getAttribute('data-range');
                if (range === 'custom') {
                    return;
                }

                requestStats({ range });
            });

            const apply = rangeWrapper.querySelector('[data-range-apply]');
            if (apply) {
                apply.addEventListener('click', () => {
                    const start = rangeWrapper.querySelector('[data-range-start]').value;
                    const end = rangeWrapper.querySelector('[data-range-end]').value;
                    if (start && end) {
                        rangeWrapper.querySelectorAll('button[data-range]').forEach((btn) => btn.classList.remove('is-active'));
                        requestStats({ start, end });
                    }
                });
            }
        }

        requestStats({ range: 30 });
    }

    function createStatusBadge(status) {
        const span = document.createElement('span');
        span.className = 'oh-status oh-status--' + status;
        span.textContent = config.i18n.statusLabels[status] || status;
        return span;
    }

    function initOrders(root) {
        const form = root.querySelector('[data-orders-filters]');
        const tableBody = root.querySelector('[data-orders-body]');
        const pagination = root.querySelector('[data-orders-pagination]');
        const resetButton = root.querySelector('[data-reset-filters]');
        const exportButton = root.querySelector('[data-export-orders]');

        const state = {
            page: 1,
            perPage: 20,
            lastQuery: {},
            cache: [],
        };

        function renderOrders(orders) {
            tableBody.innerHTML = '';
            if (! orders || ! orders.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 8;
                cell.textContent = config.i18n.noResults || '—';
                row.appendChild(cell);
                tableBody.appendChild(row);
                return;
            }

            orders.forEach((order) => {
                const row = document.createElement('tr');
                const items = order.items
                    .map((item) => '<div>' + item.quantity + '× ' + item.name + '</div>')
                    .join('');

                row.innerHTML =
                    '<td><strong>#' + order.number + '</strong></td>' +
                    '<td>' + order.customer_name + '<br><small>' + (order.customer_email || '') + '</small></td>' +
                    '<td>' + items + '</td>' +
                    '<td></td>' +
                    '<td>' + formatNumber(order.assigned_count || 0) + ' / ' + formatNumber(order.item_count || 0) + '</td>' +
                    '<td>' + formatCurrency(order.total, order.currency) + '</td>' +
                    '<td>' + (order.date_created || '') + '</td>' +
                    '<td class="oh-orders__actions"></td>';

                const statusCell = row.children[3];
                statusCell.appendChild(createStatusBadge(order.delivery_status));

                const actionsCell = row.querySelector('.oh-orders__actions');
                const resend = document.createElement('button');
                resend.type = 'button';
                resend.className = 'button button-secondary';
                resend.textContent = config.i18n.resend || 'Yeniden Gönder';
                resend.dataset.orderId = order.id;
                resend.dataset.action = 'resend';

                const cancel = document.createElement('button');
                cancel.type = 'button';
                cancel.className = 'button';
                cancel.textContent = config.i18n.cancel || 'İptal Et';
                cancel.dataset.orderId = order.id;
                cancel.dataset.action = 'cancel';

                actionsCell.appendChild(resend);
                actionsCell.appendChild(cancel);

                tableBody.appendChild(row);
            });
        }

        function renderPagination(totalPages) {
            pagination.innerHTML = '';
            if (! totalPages || totalPages <= 1) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'oh-pagination';

            const prev = document.createElement('button');
            prev.type = 'button';
            prev.className = 'button';
            prev.textContent = '‹';
            prev.disabled = state.page <= 1;
            prev.addEventListener('click', () => {
                if (state.page > 1) {
                    state.page -= 1;
                    loadOrders();
                }
            });

            const next = document.createElement('button');
            next.type = 'button';
            next.className = 'button';
            next.textContent = '›';
            next.disabled = state.page >= totalPages;
            next.addEventListener('click', () => {
                if (state.page < totalPages) {
                    state.page += 1;
                    loadOrders();
                }
            });

            const label = document.createElement('span');
            label.textContent = state.page + ' / ' + totalPages;

            wrapper.appendChild(prev);
            wrapper.appendChild(label);
            wrapper.appendChild(next);
            pagination.appendChild(wrapper);
        }

        function serializeFilters() {
            const params = new URLSearchParams();
            const formData = new FormData(form);

            for (const [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }

            params.set('page', state.page);
            params.set('per_page', state.perPage);
            state.lastQuery = Object.fromEntries(params.entries());
            return params;
        }

        function loadOrders() {
            const params = serializeFilters();
            tableBody.innerHTML = '<tr><td colspan="8">' + (config.i18n.loading || '...') + '</td></tr>';

            apiFetch('orders?' + params.toString())
                .then((response) => {
                    const data = response.data || response;
                    state.cache = data.orders || [];
                    renderOrders(data.orders || []);
                    renderPagination(data.pages || 1);
                })
                .catch((error) => {
                    console.error(error);
                    tableBody.innerHTML = '<tr><td colspan="8">' + (error.message || 'Hata oluştu') + '</td></tr>';
                });
        }

        function handleAction(event) {
            const button = event.target.closest('button[data-action]');
            if (! button) {
                return;
            }

            const orderId = button.dataset.orderId;
            const action = button.dataset.action;
            if (! orderId || ! action) {
                return;
            }

            button.disabled = true;
            apiFetch('orders/' + orderId + '/' + action, { method: 'POST' })
                .then(() => {
                    button.disabled = false;
                    loadOrders();
                    window.alert(config.i18n.actionSuccess || 'İşlem tamamlandı.');
                })
                .catch((error) => {
                    button.disabled = false;
                    window.alert(error.message || config.i18n.actionError || 'İşlem başarısız.');
                });
        }

        function exportOrders() {
            if (! state.cache.length) {
                window.alert(config.i18n.noResults || 'Veri yok');
                return;
            }

            const headers = config.i18n.exportHeaders || ['Sipariş', 'Müşteri', 'E-posta', 'Ürünler', 'Durum', 'Kod Adedi', 'Toplam', 'Tarih'];
            const rows = state.cache.map((order) => {
                const items = order.items.map((item) => item.quantity + 'x ' + item.name).join(' | ');
                return [
                    '#' + order.number,
                    order.customer_name,
                    order.customer_email,
                    items,
                    config.i18n.statusLabels[order.delivery_status] || order.delivery_status,
                    order.assigned_count + '/' + order.item_count,
                    formatCurrency(order.total, order.currency),
                    order.date_created || '',
                ];
            });

            const csv = [headers]
                .concat(rows)
                .map((line) => line.map((value) => '"' + String(value).replace(/"/g, '""') + '"').join(','))
                .join('\n');

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = config.i18n.exportFile || 'orders.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            state.page = 1;
            loadOrders();
        });

        tableBody.addEventListener('click', handleAction);

        if (resetButton) {
            resetButton.addEventListener('click', () => {
                form.reset();
                state.page = 1;
                loadOrders();
            });
        }

        if (exportButton) {
            exportButton.addEventListener('click', exportOrders);
        }

        loadOrders();
    }

    function renderReportCharts(payload) {
        const salesCanvas = document.getElementById('oh-reports-sales');
        const productsCanvas = document.getElementById('oh-reports-products');
        const paymentsCanvas = document.getElementById('oh-reports-payments');

        if (salesCanvas) {
            if (charts.reportSales) {
                charts.reportSales.destroy();
            }
            charts.reportSales = new window.Chart(salesCanvas, {
                type: 'line',
                data: {
                    labels: (payload.charts ? payload.charts.daily_sales || [] : []).map((item) => item.label),
                    datasets: [
                        {
                            label: config.i18n.revenue,
                            data: (payload.charts ? payload.charts.daily_sales || [] : []).map((item) => item.revenue),
                            borderColor: '#9c7bff',
                            backgroundColor: 'rgba(156, 123, 255, 0.25)',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            labels: { color: '#f5f7fa' },
                        },
                    },
                    scales: {
                        y: {
                            ticks: {
                                color: '#b7c4ff',
                                callback: (value) => formatCurrency(value),
                            },
                        },
                        x: {
                            ticks: {
                                color: '#c3cdf9',
                            },
                        },
                    },
                },
            });
        }

        const topProducts = payload.top_products || [];
        if (productsCanvas) {
            if (charts.reportProducts) {
                charts.reportProducts.destroy();
            }

            charts.reportProducts = new window.Chart(productsCanvas, {
                type: 'bar',
                data: {
                    labels: topProducts.map((item) => item.product_name),
                    datasets: [
                        {
                            label: config.i18n.revenue,
                            data: topProducts.map((item) => item.revenue),
                            backgroundColor: 'rgba(119, 217, 255, 0.6)',
                            borderColor: '#77d9ff',
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            ticks: {
                                color: '#b7c4ff',
                                callback: (value) => formatCurrency(value),
                            },
                        },
                        x: {
                            ticks: {
                                color: '#c3cdf9',
                                autoSkip: false,
                            },
                        },
                    },
                },
            });
        }

        if (paymentsCanvas) {
            if (charts.reportPayments) {
                charts.reportPayments.destroy();
            }

            const payments = payload.payments || [];
            charts.reportPayments = new window.Chart(paymentsCanvas, {
                type: 'doughnut',
                data: {
                    labels: payments.map((item) => item.label),
                    datasets: [
                        {
                            data: payments.map((item) => item.revenue),
                            backgroundColor: ['#ff9f68', '#6be3b2', '#9c7bff', '#ffd369', '#ff6b6b'],
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#f5f7fa',
                            },
                        },
                    },
                },
            });
        }

        const productTable = document.querySelector('[data-report-products]');
        if (productTable) {
            productTable.innerHTML = '';
            if (! topProducts.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 3;
                cell.textContent = config.i18n.noResults || '—';
                row.appendChild(cell);
                productTable.appendChild(row);
            } else {
                topProducts.forEach((product) => {
                    const row = document.createElement('tr');
                    row.innerHTML =
                        '<td>' + product.product_name + '</td>' +
                        '<td>' + formatNumber(product.quantity) + '</td>' +
                        '<td>' + formatCurrency(product.revenue) + '</td>';
                    productTable.appendChild(row);
                });
            }
        }
    }

    function initReports(root) {
        const range = root.querySelector('[data-report-range]');

        function request(rangeParams = {}) {
            const params = new URLSearchParams();
            Object.keys(rangeParams).forEach((key) => {
                if (rangeParams[key]) {
                    params.append(key, rangeParams[key]);
                }
            });

            const endpoint = 'stats' + (params.toString() ? '?' + params.toString() : '');
            apiFetch(endpoint)
                .then((response) => {
                    renderReportCharts(response.data || response);
                })
                .catch((error) => {
                    console.error(error);
                });
        }

        if (range) {
            range.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-range]');
                if (! button) {
                    return;
                }

                range.querySelectorAll('button[data-range]').forEach((btn) => btn.classList.remove('is-active'));
                button.classList.add('is-active');

                const value = button.getAttribute('data-range');
                if (value === 'custom') {
                    return;
                }

                request({ range: value });
            });

            const apply = range.querySelector('[data-range-apply]');
            if (apply) {
                apply.addEventListener('click', () => {
                    const start = range.querySelector('[data-range-start]').value;
                    const end = range.querySelector('[data-range-end]').value;
                    if (start && end) {
                        range.querySelectorAll('button[data-range]').forEach((btn) => btn.classList.remove('is-active'));
                        request({ start, end });
                    }
                });
            }
        }

        request({ range: 30 });
    }

    function bindCodeForms() {
        const forms = document.querySelectorAll('[data-code-update]');
        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(form);
                const action = form.getAttribute('action');
                form.classList.add('is-loading');
                fetch(action, {
                    method: 'POST',
                    headers: { 'X-WP-Nonce': config.nonce },
                    body: formData,
                    credentials: 'same-origin',
                })
                    .then(() => window.location.reload())
                    .catch((error) => {
                        console.error(error);
                        form.classList.remove('is-loading');
                    });
            });
        });
    }

    function init() {
        const dashboard = document.querySelector('[data-oh-dashboard]');
        if (dashboard) {
            initDashboard(dashboard);
        }

        const orders = document.querySelector('[data-oh-orders]');
        if (orders) {
            initOrders(orders);
        }

        const reports = document.querySelector('[data-oh-reports]');
        if (reports) {
            initReports(reports);
        }

        bindCodeForms();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
