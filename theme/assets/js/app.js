(function () {
    const restRoot = window.ohTheme ? window.ohTheme.restUrl : '';
    const defaultNonce = window.ohTheme ? window.ohTheme.nonce : null;

    const updateBalanceDisplays = (balance) => {
        const formatted = Number.parseFloat(balance || 0).toFixed(2);
        document.querySelectorAll('[data-role="wallet-balance"]').forEach((el) => {
            el.textContent = formatted;
        });
    };

    const request = (url, options = {}, nonce) => {
        const headers = new Headers(options.headers || {});
        const token = nonce || defaultNonce;
        if (token) {
            headers.set('X-WP-Nonce', token);
        }

        return fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });
    };

    const initHeroSlider = () => {
        const slider = document.querySelector('[data-role="hero-slider"]');
        if (!slider) {
            return;
        }

        const slides = Array.from(slider.querySelectorAll('.oh-hero__slide'));
        const progress = Array.from(document.querySelectorAll('[data-role="hero-progress"] button'));
        let activeIndex = slides.findIndex((slide) => slide.getAttribute('data-active') === 'true');
        let timer;

        const activate = (index) => {
            slides.forEach((slide, idx) => {
                slide.setAttribute('data-active', idx === index ? 'true' : 'false');
            });
            progress.forEach((dot, idx) => {
                dot.classList.toggle('is-active', idx === index);
            });
            activeIndex = index;
        };

        const cycle = () => {
            timer = window.setTimeout(() => {
                const next = (activeIndex + 1) % slides.length;
                activate(next);
                cycle();
            }, 6000);
        };

        progress.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                if (timer) {
                    window.clearTimeout(timer);
                }
                activate(idx);
                cycle();
            });
        });

        if (activeIndex < 0) {
            activate(0);
        }

        cycle();
    };

    const renderProductsInto = (container, html) => {
        if (!container) {
            return;
        }

        container.innerHTML = html;
    };

    const loadProducts = (endpoint, params, target) => {
        if (!endpoint || !target) {
            return;
        }

        const url = new URL(endpoint);
        Object.entries(params || {}).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });

        target.classList.add('is-loading');
        request(url.toString())
            .then((response) => response.json())
            .then((payload) => {
                renderProductsInto(target, payload.html || '');
            })
            .catch(() => {
                renderProductsInto(target, '<p class="oh-empty">Ürünler yüklenemedi.</p>');
            })
            .finally(() => {
                target.classList.remove('is-loading');
            });
    };

    const initCategoryChips = () => {
        const grid = document.querySelector('[data-role="category-grid"]');
        if (!grid) {
            return;
        }

        const endpoint = grid.getAttribute('data-endpoint');
        const chips = Array.from(document.querySelectorAll('[data-role="category-chips"] .oh-category-chip'));

        chips.forEach((chip) => {
            chip.addEventListener('click', () => {
                chips.forEach((node) => node.classList.remove('is-active'));
                chip.classList.add('is-active');
                loadProducts(endpoint, { category: chip.dataset.category }, grid);
            });
        });
    };

    const initArchiveFilters = () => {
        const archiveGrid = document.querySelector('[data-role="archive-grid"]');
        const filterPanel = document.querySelector('[data-role="filter-panel"]');
        if (!archiveGrid || !filterPanel) {
            return;
        }

        const endpoint = filterPanel.getAttribute('data-endpoint');
        const form = filterPanel.querySelector('[data-role="filter-form"]');
        if (form) {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(form);
                const params = Object.fromEntries(formData.entries());
                loadProducts(endpoint, params, archiveGrid);
            });
        }

        const subCategories = Array.from(document.querySelectorAll('[data-role="subcategory-chips"] .oh-category-chip'));
        subCategories.forEach((chip) => {
            chip.addEventListener('click', () => {
                subCategories.forEach((node) => node.classList.remove('is-active'));
                chip.classList.add('is-active');
                loadProducts(endpoint, { category: chip.dataset.category }, archiveGrid);
            });
        });
    };

    const initMiniCart = () => {
        const miniCart = document.querySelector('[data-role="mini-cart"]');
        if (!miniCart) {
            return;
        }

        const close = miniCart.querySelector('[data-action="close"]');
        const body = miniCart.querySelector('[data-role="mini-cart-body"]');

        const hide = () => {
            miniCart.hidden = true;
        };

        if (close) {
            close.addEventListener('click', hide);
        }

        if (window.jQuery) {
            window.jQuery(document.body).on('added_to_cart', (event, fragments, cartHash, $button) => {
                miniCart.hidden = false;
                if (body) {
                    body.innerHTML = fragments && fragments['div.widget_shopping_cart_content'] ? fragments['div.widget_shopping_cart_content'] : '<p class="oh-empty">Sepet güncellendi.</p>';
                }
            });
        }

        miniCart.addEventListener('click', (event) => {
            if (event.target === miniCart) {
                hide();
            }
        });
    };

    const initVariantSelector = () => {
        const variantOptions = document.querySelectorAll('[data-role="variant-select"] input[type="radio"]');
        if (!variantOptions.length) {
            return;
        }

        const priceHolder = document.querySelector('[data-role="product-price"] .price');
        variantOptions.forEach((input) => {
            input.addEventListener('change', () => {
                variantOptions.forEach((node) => node.closest('.oh-pill-select__option').classList.remove('is-active'));
                input.closest('.oh-pill-select__option').classList.add('is-active');
                const price = Number.parseFloat(input.dataset.price || '0');
                if (priceHolder && !Number.isNaN(price)) {
                    priceHolder.innerHTML = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(price);
                }

                const select = document.querySelector('form.variations_form');
                if (select) {
                    const variationInput = select.querySelector('input[name="variation_id"]');
                    if (variationInput) {
                        variationInput.value = input.value;
                    }
                }
            });
        });
    };

    const initCheckoutWalletNotice = () => {
        const notice = document.querySelector('.oh-checkout-wallet-notice');
        if (!notice) {
            return;
        }

        const balance = Number.parseFloat((notice.getAttribute('data-wallet-balance') || '').replace(',', '.')) || 0;
        const total = Number.parseFloat((notice.getAttribute('data-order-total') || '').replace(',', '.')) || 0;
        if (balance >= total) {
            notice.style.display = 'none';
        }

        if (window.jQuery) {
            window.jQuery(document.body).on('updated_checkout', () => {
                const payment = document.querySelector('input[value="terra_wallet"]');
                if (payment && balance < total) {
                    payment.setAttribute('disabled', 'disabled');
                    const label = payment.closest('li');
                    if (label) {
                        label.classList.add('is-disabled');
                    }
                }
            });
        }
    };

    const initPaymentTabs = () => {
        const paymentWrapper = document.querySelector('#payment');
        if (!paymentWrapper) {
            return;
        }

        const methodList = paymentWrapper.querySelector('.wc_payment_methods');
        if (!methodList || paymentWrapper.dataset.tabsApplied === 'true') {
            return;
        }

        paymentWrapper.dataset.tabsApplied = 'true';
        const existingNav = paymentWrapper.querySelector('.oh-payment-tabs__nav');
        if (existingNav) {
            existingNav.remove();
        }
        const methods = Array.from(methodList.querySelectorAll('li'));
        const nav = document.createElement('div');
        nav.className = 'oh-payment-tabs__nav';

        const activate = (gateway) => {
            methods.forEach((li) => {
                const input = li.querySelector('input[type="radio"]');
                const box = li.querySelector('.payment_box');
                const active = input && input.value === gateway;
                if (input) {
                    input.checked = active;
                }
                li.classList.toggle('is-active', active);
                if (box) {
                    box.style.display = active ? 'block' : 'none';
                }
            });
        };

        methods.forEach((li, index) => {
            const input = li.querySelector('input[type="radio"]');
            const label = li.querySelector('label');
            const box = li.querySelector('.payment_box');
            if (box) {
                box.classList.add('oh-payment-tabs__panel');
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'oh-payment-tabs__trigger';
            button.dataset.gateway = input ? input.value : `gateway-${index}`;
            button.textContent = label ? label.textContent.trim() : button.dataset.gateway;

            button.addEventListener('click', () => {
                if (input && input.disabled) {
                    return;
                }
                activate(button.dataset.gateway);
                if (input) {
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
                nav.querySelectorAll('.oh-payment-tabs__trigger').forEach((trigger) => trigger.classList.remove('is-active'));
                button.classList.add('is-active');
            });

            nav.appendChild(button);
        });

        methodList.parentNode.insertBefore(nav, methodList);

        const firstEnabled = nav.querySelector('.oh-payment-tabs__trigger');
        if (firstEnabled) {
            firstEnabled.classList.add('is-active');
            activate(firstEnabled.dataset.gateway);
        }

        if (window.jQuery && paymentWrapper.dataset.tabsListener !== 'true') {
            window.jQuery(document.body).on('updated_checkout', () => {
                paymentWrapper.dataset.tabsApplied = 'false';
                initPaymentTabs();
            });
            paymentWrapper.dataset.tabsListener = 'true';
        }
    };

    initHeroSlider();
    initCategoryChips();
    initArchiveFilters();
    initMiniCart();
    initVariantSelector();
    initCheckoutWalletNotice();
    initPaymentTabs();

    const walletSection = document.querySelector('.oh-account-section--wallet');
    if (walletSection) {
        const { balanceEndpoint, transactionsEndpoint, nonce } = walletSection.dataset;

        const renderTransactions = (items) => {
            const container = walletSection.querySelector('[data-role="wallet-history"]');
            if (!container) {
                return;
            }

            if (!items || !items.length) {
                container.innerHTML = '<p class="oh-empty">' + (walletSection.dataset.emptyText || 'Kayıt bulunamadı.') + '</p>';
                return;
            }

            const rows = items
                .map((item) => {
                    const amount = Number.parseFloat(item.amount || 0);
                    const amountLabel = amount >= 0 ? '+' + amount.toFixed(2) : amount.toFixed(2);
                    const gateway = item.gateway || item.type || 'wallet';
                    const note = item.note || item.reference || '';
                    const created = item.created ? new Date(item.created).toLocaleString() : '';

                    return `
                        <div class="oh-table__row">
                            <div class="oh-table__cell">
                                <span class="oh-table__title">${created}</span>
                                <span class="oh-table__subtitle">${gateway.toUpperCase()}</span>
                            </div>
                            <div class="oh-table__cell">
                                <span class="oh-table__amount ${amount >= 0 ? 'is-credit' : 'is-debit'}">${amountLabel}</span>
                                ${item.balance !== undefined && item.balance !== null ? `<span class="oh-table__hint">${Number.parseFloat(item.balance).toFixed(2)}</span>` : ''}
                                ${note ? `<span class="oh-table__note">${note}</span>` : ''}
                            </div>
                        </div>
                    `;
                })
                .join('');

            container.innerHTML = `<div class="oh-table__body">${rows}</div>`;
        };

        if (balanceEndpoint) {
            request(balanceEndpoint, {}, nonce)
                .then((response) => response.json())
                .then((payload) => {
                    updateBalanceDisplays(payload.balance || 0);
                })
                .catch(() => updateBalanceDisplays(0));
        }

        if (transactionsEndpoint) {
            request(transactionsEndpoint, {}, nonce)
                .then((response) => response.json())
                .then((payload) => renderTransactions(payload.items || []))
                .catch(() => renderTransactions([]));
        }

        const topupForm = walletSection.querySelector('[data-role="wallet-topup"]');
        if (topupForm) {
            topupForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const amount = topupForm.querySelector('input[name="amount"]').value;
                const gateway = topupForm.querySelector('input[name="gateway"]:checked');
                const gatewayLabel = gateway ? gateway.value : '';

                const notice = walletSection.querySelector('.oh-form__hint');
                if (notice) {
                    notice.classList.add('is-active');
                    notice.textContent = `${amount} ₺ için ${gatewayLabel.toUpperCase()} yönlendirmesine aktarılıyorsunuz. Ödeme sağlayıcı entegrasyonu devreye alındığında otomatik olarak tamamlanacaktır.`;
                }
            });
        }
    }

    if (!walletSection && restRoot && document.querySelector('[data-role="wallet-balance"]')) {
        request(`${restRoot}oh/v1/wallet/balance`)
            .then((response) => response.json())
            .then((payload) => updateBalanceDisplays(payload.balance || 0))
            .catch(() => updateBalanceDisplays(0));
    }

    const statusMap = {
        used: { label: 'Tamamlandı', tone: 'success' },
        reserved: { label: 'Bekliyor', tone: 'warning' },
        free: { label: 'Bekliyor', tone: 'warning' },
        failed: { label: 'Hata', tone: 'danger' },
        revoked: { label: 'İptal', tone: 'danger' },
    };

    const buildStatusBadge = (status) => {
        const info = statusMap[status] || { label: status, tone: 'muted' };
        return `<span class="oh-status oh-status--${info.tone}">${info.label}</span>`;
    };

    const licensesSection = document.querySelector('.oh-account-section--licenses');
    if (licensesSection) {
        const { listEndpoint, detailEndpoint, pdfEndpoint, nonce } = licensesSection.dataset;
        const listEl = licensesSection.querySelector('[data-role="list"]');
        const emptyEl = licensesSection.querySelector('[data-role="empty"]');
        let licenses = [];
        let statusFilter = 'all';
        let searchTerm = '';

        const normalize = (value) => (value || '').toString().toLowerCase();

        const renderLicenses = () => {
            if (!listEl) {
                return;
            }

            const filtered = licenses.filter((item) => {
                const matchesStatus = statusFilter === 'all' ? true : item.status === statusFilter;
                if (!matchesStatus) {
                    return false;
                }

                if (!searchTerm) {
                    return true;
                }

                const query = searchTerm.toLowerCase();
                return [
                    item.product_name,
                    item.masked_code,
                    item.order_id,
                    item.order_status,
                ]
                    .filter(Boolean)
                    .map((value) => value.toString().toLowerCase())
                    .some((value) => value.includes(query));
            });

            if (!filtered.length) {
                listEl.innerHTML = '';
                if (emptyEl) {
                    emptyEl.hidden = false;
                }

                return;
            }

            if (emptyEl) {
                emptyEl.hidden = true;
            }

            listEl.innerHTML = filtered
                .map((item) => {
                    const thumb = item.product_image || '';
                    const statusBadge = buildStatusBadge(item.status);
                    const purchaseInfo = item.purchased_at ? `<span>${item.purchased_at}</span>` : '';

                    return `
                        <article class="oh-license-card" data-id="${item.id}" data-order="${item.order_id}" data-status="${item.status}" data-masked="${item.masked_code}">
                            <div class="oh-license-card__header">
                                <div class="oh-license-card__thumb">${thumb}</div>
                                <div class="oh-license-card__meta">
                                    <h3>${item.product_name}</h3>
                                    <p>#${item.order_id} ${purchaseInfo}</p>
                                </div>
                                ${statusBadge}
                            </div>
                            <div class="oh-license-card__code" data-role="code">${item.masked_code}</div>
                            <div class="oh-license-card__actions">
                                <button type="button" class="oh-button oh-button--ghost" data-action="toggle" data-id="${item.id}" data-order="${item.order_id}">
                                    Göster
                                </button>
                                <button type="button" class="oh-button oh-button--ghost" data-action="copy" data-id="${item.id}">
                                    Kopyala
                                </button>
                                <button type="button" class="oh-button oh-button--ghost" data-action="pdf" data-order="${item.order_id}">
                                    PDF
                                </button>
                            </div>
                        </article>
                    `;
                })
                .join('');
        };

        const revealCodes = (orderId) =>
            request(`${detailEndpoint}/${orderId}`, {}, nonce)
                .then((response) => response.json())
                .then((payload) => payload.data || []);

        const downloadPdf = (orderId) => {
            if (!pdfEndpoint) {
                return;
            }

            request(
                pdfEndpoint,
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: Number.parseInt(orderId, 10) }),
                },
                nonce
            )
                .then((response) => response.json())
                .then((payload) => {
                    if (payload && payload.data_url) {
                        window.open(payload.data_url, '_blank');
                    }
                })
                .catch(() => {});
        };

        if (listEndpoint) {
            request(listEndpoint, {}, nonce)
                .then((response) => response.json())
                .then((payload) => {
                    licenses = payload.data || [];
                    renderLicenses();
                })
                .catch(() => {
                    licenses = [];
                    renderLicenses();
                });
        }

        const searchInput = licensesSection.querySelector('[data-filter="query"]');
        if (searchInput) {
            searchInput.addEventListener('input', (event) => {
                searchTerm = event.target.value;
                renderLicenses();
            });
        }

        const chipGroup = licensesSection.querySelector('[data-filter="status"]');
        if (chipGroup) {
            chipGroup.addEventListener('click', (event) => {
                const target = event.target.closest('.oh-chip');
                if (!target) {
                    return;
                }

                chipGroup.querySelectorAll('.oh-chip').forEach((chip) => chip.classList.remove('is-active'));
                target.classList.add('is-active');
                statusFilter = target.getAttribute('data-value') || 'all';
                renderLicenses();
            });
        }

        if (licensesSection) {
            licensesSection.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const action = target.getAttribute('data-action');
                if ('toggle' === action) {
                    const card = target.closest('.oh-license-card');
                    if (!card) {
                        return;
                    }

                    const orderId = card.getAttribute('data-order');
                    const codeId = card.getAttribute('data-id');
                    const codeEl = card.querySelector('[data-role="code"]');
                    if (!orderId || !codeId || !codeEl) {
                        return;
                    }

                    const isRevealed = card.getAttribute('data-visible') === 'true';
                    if (isRevealed) {
                        codeEl.textContent = card.getAttribute('data-masked');
                        card.setAttribute('data-visible', 'false');
                        target.textContent = 'Göster';

                        return;
                    }

                    revealCodes(orderId).then((codes) => {
                        const match = codes.find((code) => String(code.id) === String(codeId));
                        if (match) {
                            codeEl.textContent = match.code;
                            card.setAttribute('data-visible', 'true');
                            target.textContent = 'Gizle';
                        }
                    });
                }

                if ('copy' === action) {
                    const card = target.closest('.oh-license-card');
                    if (!card) {
                        return;
                    }

                    const codeEl = card.querySelector('[data-role="code"]');
                    if (codeEl && navigator.clipboard) {
                        navigator.clipboard.writeText(codeEl.textContent || '').then(() => {
                            target.textContent = 'Kopyalandı';
                            setTimeout(() => {
                                target.textContent = 'Kopyala';
                            }, 2000);
                        });
                    }
                }

                if ('pdf' === action) {
                    const orderId = target.getAttribute('data-order');
                    if (orderId) {
                        downloadPdf(orderId);
                    }
                }

                if ('download-all' === action) {
                    const uniqueOrders = [...new Set(licenses.map((item) => item.order_id))];
                    uniqueOrders.slice(0, 5).forEach((orderId, index) => {
                        setTimeout(() => downloadPdf(orderId), index * 400);
                    });
                }
            });
        }
    }

    const ticketsSection = document.querySelector('.oh-account-section--tickets');
    if (ticketsSection) {
        const { ticketsEndpoint, nonce, currentUser } = ticketsSection.dataset;
        const listEl = ticketsSection.querySelector('[data-role="ticket-list"]');
        const createForm = ticketsSection.querySelector('[data-role="ticket-create"]');
        let tickets = [];

        const renderTickets = () => {
            if (!listEl) {
                return;
            }

            if (!tickets.length) {
                listEl.innerHTML = '<p class="oh-empty">Aktif talebiniz bulunmuyor.</p>';
                return;
            }

            const current = Number.parseInt(currentUser || '0', 10);

            listEl.innerHTML = tickets
                .map((ticket) => {
                    const status = buildStatusBadge(ticket.status);
                    const order = ticket.order_id ? `<span class="oh-ticket-card__order">#${ticket.order_id}</span>` : '';
                    const messages = (ticket.messages || [])
                        .map((message) => {
                            const isOwner = Number.parseInt(message.author, 10) === current;
                            const role = isOwner ? 'Kullanıcı' : 'Destek';
                            const time = message.time ? new Date(message.time).toLocaleString() : '';
                            return `
                                <div class="oh-ticket-message ${isOwner ? 'is-owner' : 'is-support'}">
                                    <div class="oh-ticket-message__meta">
                                        <span>${role}</span>
                                        <span>${time}</span>
                                    </div>
                                    <div class="oh-ticket-message__body">${message.body}</div>
                                </div>
                            `;
                        })
                        .join('');

                    return `
                        <article class="oh-ticket-card" data-ticket="${ticket.id}">
                            <header class="oh-ticket-card__header">
                                <div>
                                    <h3>${ticket.subject}</h3>
                                    ${order}
                                </div>
                                ${status}
                            </header>
                            <div class="oh-ticket-card__messages">${messages}</div>
                            <form class="oh-form oh-ticket-card__reply" data-role="ticket-reply">
                                <textarea name="message" rows="3" class="oh-form__textarea" placeholder="${'Mesajınızı yazın'}" required></textarea>
                                <div class="oh-form__actions">
                                    <button type="submit" class="oh-button oh-button--ghost">Gönder</button>
                                </div>
                            </form>
                        </article>
                    `;
                })
                .join('');
        };

        const loadTickets = () => {
            if (!ticketsEndpoint) {
                return;
            }

            request(ticketsEndpoint, {}, nonce)
                .then((response) => response.json())
                .then((payload) => {
                    tickets = payload.items || [];
                    renderTickets();
                })
                .catch(() => {
                    tickets = [];
                    renderTickets();
                });
        };

        if (createForm) {
            createForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(createForm);
                const payload = {
                    subject: formData.get('subject'),
                    message: formData.get('message'),
                    order_id: formData.get('order_id'),
                };

                request(
                    ticketsEndpoint,
                    {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    },
                    nonce
                )
                    .then((response) => response.json())
                    .then(() => {
                        createForm.reset();
                        loadTickets();
                    })
                    .catch(() => {});
            });
        }

        if (ticketsSection) {
            ticketsSection.addEventListener('submit', (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                if (form.getAttribute('data-role') !== 'ticket-reply') {
                    return;
                }

                event.preventDefault();
                const card = form.closest('.oh-ticket-card');
                if (!card) {
                    return;
                }

                const ticketId = card.getAttribute('data-ticket');
                const message = form.querySelector('textarea[name="message"]').value;
                if (!message) {
                    return;
                }

                request(
                    ticketsEndpoint,
                    {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ticket_id: Number.parseInt(ticketId, 10), message }),
                    },
                    nonce
                )
                    .then((response) => response.json())
                    .then(() => {
                        form.reset();
                        loadTickets();
                    })
                    .catch(() => {});
            });
        }

        loadTickets();
    }
})();
