<?php
/**
 * Outputs the "My Licenses" account endpoint.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

class Licenses_Renderer
{
    private Delivery_Service $delivery_service;

    public function __construct(Delivery_Service $delivery_service)
    {
        $this->delivery_service = $delivery_service;
    }

    public function render_account_view(): void
    {
        if (! is_user_logged_in()) {
            echo '<p>' . esc_html__('Bu alanı görmek için giriş yapmalısınız.', 'oh-digital-delivery') . '</p>';

            return;
        }

        $rest_url = esc_url_raw(rest_url('oh/v1/licenses'));
        $nonce    = wp_create_nonce('wp_rest');
        ?>
        <div class="oh-licenses" data-rest="<?php echo esc_attr($rest_url); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
            <h2><?php esc_html_e('Lisanslarım', 'oh-digital-delivery'); ?></h2>
            <p><?php esc_html_e('Satın aldığınız kodlar ve hesap bilgileri bu alanda listelenir. Kodları göstermek için ilgili sipariş satırını açın.', 'oh-digital-delivery'); ?></p>
            <div class="oh-licenses__actions">
                <button type="button" class="button oh-licenses__download" data-action="download">
                    <?php esc_html_e('PDF olarak indir', 'oh-digital-delivery'); ?>
                </button>
            </div>
            <table class="shop_table shop_table_responsive">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Ürün', 'oh-digital-delivery'); ?></th>
                        <th><?php esc_html_e('Sipariş', 'oh-digital-delivery'); ?></th>
                        <th><?php esc_html_e('Kod', 'oh-digital-delivery'); ?></th>
                        <th><?php esc_html_e('Durum', 'oh-digital-delivery'); ?></th>
                        <th><?php esc_html_e('İşlemler', 'oh-digital-delivery'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <script>
            (function () {
                const wrapper = document.querySelector('.oh-licenses');
                if (!wrapper) {
                    return;
                }

                const endpoint = wrapper.getAttribute('data-rest');
                const nonce = wrapper.getAttribute('data-nonce');
                const tableBody = wrapper.querySelector('tbody');

                const headers = new Headers({
                    'X-WP-Nonce': nonce,
                });

                const formatRow = (item) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td data-title="<?php echo esc_js(__('Ürün', 'oh-digital-delivery')); ?>">${item.product_name}</td>
                        <td data-title="<?php echo esc_js(__('Sipariş', 'oh-digital-delivery')); ?>">#${item.order_id}</td>
                        <td data-title="<?php echo esc_js(__('Kod', 'oh-digital-delivery')); ?>"><span class="oh-licenses__code" data-code-id="${item.id}" data-order-id="${item.order_id}">${item.masked_code}</span></td>
                        <td data-title="<?php echo esc_js(__('Durum', 'oh-digital-delivery')); ?>">${item.status}</td>
                        <td data-title="<?php echo esc_js(__('İşlemler', 'oh-digital-delivery')); ?>">
                            <button class="button button-small" data-action="reveal" data-order-id="${item.order_id}" data-code-id="${item.id}"><?php echo esc_js(__('Göster', 'oh-digital-delivery')); ?></button>
                            <button class="button button-small" data-action="copy" data-code-id="${item.id}"><?php echo esc_js(__('Kopyala', 'oh-digital-delivery')); ?></button>
                        </td>
                    `;

                    return tr;
                };

                const fetchList = () => {
                    fetch(endpoint, { headers })
                        .then((response) => response.json())
                        .then((payload) => {
                            tableBody.innerHTML = '';
                            if (!payload.data || !payload.data.length) {
                                tableBody.innerHTML = `<tr><td colspan="5"><?php echo esc_js(__('Henüz teslim edilmiş lisans bulunmuyor.', 'oh-digital-delivery')); ?></td></tr>`;
                                return;
                            }

                            payload.data.forEach((item) => {
                                tableBody.appendChild(formatRow(item));
                            });
                        })
                        .catch(() => {
                            tableBody.innerHTML = `<tr><td colspan="5"><?php echo esc_js(__('Lisanslar yüklenirken hata oluştu.', 'oh-digital-delivery')); ?></td></tr>`;
                        });
                };

                const revealCodes = (orderId) => {
                    return fetch(`${endpoint}/${orderId}`, { headers })
                        .then((response) => response.json())
                        .then((payload) => payload.data || []);
                };

                wrapper.addEventListener('click', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }

                    const action = target.getAttribute('data-action');
                    if ('reveal' === action) {
                        const orderId = target.getAttribute('data-order-id');
                        if (!orderId) {
                            return;
                        }

                        revealCodes(orderId).then((codes) => {
                            codes.forEach((code) => {
                                if (!code || !code.id) {
                                    return;
                                }

                                const span = wrapper.querySelector(`span[data-code-id="${code.id}"]`);
                                if (span) {
                                    span.textContent = code.code;
                                }
                            });
                        });
                    }

                    if ('copy' === action) {
                        const codeId = target.getAttribute('data-code-id');
                        const span = codeId ? wrapper.querySelector(`span[data-code-id="${codeId}"]`) : null;
                        if (!span) {
                            return;
                        }

                        if (!navigator.clipboard || !navigator.clipboard.writeText) {
                            return;
                        }

                        navigator.clipboard.writeText(span.textContent || '').then(() => {
                            target.textContent = '<?php echo esc_js(__('Kopyalandı', 'oh-digital-delivery')); ?>';
                            setTimeout(() => {
                                target.textContent = '<?php echo esc_js(__('Kopyala', 'oh-digital-delivery')); ?>';
                            }, 2000);
                        });
                    }

                    if ('download' === action) {
                        const rows = Array.from(wrapper.querySelectorAll('tbody tr'));
                        if (!rows.length) {
                            return;
                        }

                        let html = '<h1><?php echo esc_js(__('Lisanslarım', 'oh-digital-delivery')); ?></h1>';
                        html += '<table border="1" cellspacing="0" cellpadding="6"><thead><tr>';
                        html += '<th><?php echo esc_js(__('Ürün', 'oh-digital-delivery')); ?></th>';
                        html += '<th><?php echo esc_js(__('Sipariş', 'oh-digital-delivery')); ?></th>';
                        html += '<th><?php echo esc_js(__('Kod', 'oh-digital-delivery')); ?></th>';
                        html += '<th><?php echo esc_js(__('Durum', 'oh-digital-delivery')); ?></th>';
                        html += '</tr></thead><tbody>';

                        rows.forEach((row) => {
                            const cells = Array.from(row.querySelectorAll('td'));
                            html += '<tr>';
                            cells.slice(0, 4).forEach((cell) => {
                                html += `<td>${cell.textContent || ''}</td>`;
                            });
                            html += '</tr>';
                        });

                        html += '</tbody></table>';

                        const printWindow = window.open('', '', 'width=900,height=700');
                        if (printWindow) {
                            printWindow.document.write('<html><head><title><?php echo esc_js(__('Lisanslarım', 'oh-digital-delivery')); ?></title></head><body>' + html + '</body></html>');
                            printWindow.document.close();
                            printWindow.focus();
                            printWindow.print();
                            printWindow.close();
                        }
                    }
                });

                fetchList();
            })();
        </script>
        <?php
    }
}
