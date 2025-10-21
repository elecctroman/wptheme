<?php
/**
 * Provides reporting and dashboard level aggregations for the delivery system.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use OH\DigitalDelivery\Storage\Codes_Repository;
use WC_Order;
use wpdb;
use function absint;
use function sanitize_text_field;
use function wc_get_order_statuses;
use function wc_get_orders;
use function wc_get_product;
use function wp_date;
use function wp_timezone;
use function __;
use function WC;

class Analytics_Service
{
    private Codes_Repository $repository;

    private wpdb $db;

    public function __construct(Codes_Repository $repository, ?wpdb $db = null)
    {
        global $wpdb;

        $this->repository = $repository;
        $this->db         = $db ?: $wpdb;
    }

    public function get_dashboard_payload(?DateTimeImmutable $range_start = null, ?DateTimeImmutable $range_end = null): array
    {
        $timezone    = wp_timezone();
        $now         = new DateTimeImmutable('now', $timezone);
        $today_start = $now->setTime(0, 0);
        $today_end   = $today_start->modify('+1 day');
        $yesterday_start = $today_start->modify('-1 day');
        $yesterday_end   = $today_start;

        $today      = $this->calculate_range_metrics($today_start, $today_end);
        $yesterday  = $this->calculate_range_metrics($yesterday_start, $yesterday_end);
        $seven_days = $this->calculate_range_metrics($today_start->modify('-6 days'), $today_end);
        $thirty     = $this->calculate_range_metrics($today_start->modify('-29 days'), $today_end);

        $range_start = $range_start ?: $today_start->modify('-29 days');
        $range_end   = $range_end ?: $today_end;

        $range_interval = $range_end->diff($range_start);
        $range_days     = max(1, (int) $range_interval->days + 1);

        $charts = [
            'monthly_trend'   => $this->get_monthly_trend($today_start, 12),
            'status_breakdown'=> $this->get_status_breakdown($range_start, $range_end),
            'daily_sales'     => $this->get_daily_sales($range_start, $range_end),
        ];

        $top_products   = $this->get_top_products($range_days, $range_start, $range_end);
        $recent_orders  = $this->get_recent_orders(5);
        $stock_overview = $this->repository->get_stock_overview();

        return [
            'today'      => $today,
            'yesterday'  => $yesterday,
            'short_term' => [
                'seven_days'  => $seven_days,
                'thirty_days' => $thirty,
            ],
            'totals'     => [
                'pending_orders'       => $this->count_orders_by_delivery_status('pending'),
                'total_revenue'        => $this->get_total_revenue(),
                'stock_waiting'        => $this->repository->count_by_status('reserved'),
                'completed_deliveries' => $this->count_orders_by_delivery_status('completed'),
            ],
            'charts'         => $charts,
            'top_products'   => $top_products,
            'recent_orders'  => $recent_orders,
            'stock_overview' => $this->hydrate_stock_overview($stock_overview),
            'customer'       => $this->get_customer_metrics($range_start, $range_end),
            'payments'       => $this->get_payment_breakdown($range_start, $range_end),
            'range'          => [
                'start' => $range_start->format('Y-m-d'),
                'end'   => $range_end->format('Y-m-d'),
            ],
        ];
    }

    public function get_orders(array $args = []): array
    {
        $per_page   = max(1, (int) ($args['per_page'] ?? 20));
        $page       = max(1, (int) ($args['page'] ?? 1));
        $status     = isset($args['status']) ? sanitize_text_field((string) $args['status']) : '';
        $product_id = isset($args['product_id']) ? absint($args['product_id']) : 0;
        $payment    = isset($args['payment_method']) ? sanitize_text_field((string) $args['payment_method']) : '';
        $date_start = isset($args['date_start']) ? sanitize_text_field((string) $args['date_start']) : '';
        $date_end   = isset($args['date_end']) ? sanitize_text_field((string) $args['date_end']) : '';

        $query_args = [
            'type'      => 'shop_order',
            'paginate'  => true,
            'limit'     => $per_page,
            'page'      => $page,
            'orderby'   => 'date',
            'order'     => 'DESC',
            'status'    => array_keys(wc_get_order_statuses()),
        ];

        if ($product_id) {
            $query_args['product_id'] = $product_id;
        }

        if ($payment) {
            $query_args['payment_method'] = $payment;
        }

        if ($date_start || $date_end) {
            $range = [];
            if ($date_start) {
                $range['after'] = $date_start;
            }
            if ($date_end) {
                $range['before']    = $date_end;
                $range['inclusive'] = true;
            }
            $query_args['date_created'] = $range;
        }

        if ($status) {
            $query_args['meta_query'] = [
                [
                    'key'   => 'oh_delivery_status',
                    'value' => $status,
                ],
            ];
        }

        $results = wc_get_orders($query_args);
        $orders  = [];

        foreach ($results['orders'] as $order) {
            if (! $order instanceof WC_Order) {
                continue;
            }

            $assigned_codes    = (array) $order->get_meta('oh_assigned_codes');
            $assigned_accounts = (array) $order->get_meta('oh_assigned_accounts');
            $delivery_status   = $order->get_meta('oh_delivery_status') ?: 'pending';
            $items             = [];

            foreach ($order->get_items() as $item) {
                $items[] = [
                    'name'     => $item->get_name(),
                    'quantity' => (int) $item->get_quantity(),
                ];
            }

            $orders[] = [
                'id'              => $order->get_id(),
                'number'          => $order->get_order_number(),
                'customer_name'   => $order->get_formatted_billing_full_name(),
                'customer_email'  => $order->get_billing_email(),
                'items'           => $items,
                'delivery_status' => $delivery_status,
                'assigned_count'  => count($assigned_codes) + count($assigned_accounts),
                'item_count'      => array_reduce($items, static fn($carry, $item) => $carry + (int) $item['quantity'], 0),
                'total'           => (float) $order->get_total(),
                'currency'        => $order->get_currency(),
                'date_created'    => $order->get_date_created() ? $order->get_date_created()->date_i18n('Y-m-d H:i') : '',
                'payment_method'  => $order->get_payment_method_title(),
            ];
        }

        return [
            'orders' => $orders,
            'total'  => (int) $results['total'],
            'pages'  => (int) $results['max_num_pages'],
        ];
    }

    public function get_top_products(int $days = 30, ?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): array
    {
        $timezone = wp_timezone();
        $end      = $end ? $end->setTime(23, 59, 59) : new DateTimeImmutable('now', $timezone);
        $start    = $start ? $start->setTime(0, 0) : $end->modify(sprintf('-%d days', max(1, $days - 1)))->setTime(0, 0);

        $stats_table   = $this->db->prefix . 'wc_order_stats';
        $products_table = $this->db->prefix . 'wc_order_product_lookup';

        $query = $this->db->prepare(
            "SELECT lookup.product_id, SUM(lookup.product_qty) AS quantity, SUM(lookup.product_net_revenue) AS revenue
             FROM {$products_table} AS lookup
             INNER JOIN {$stats_table} AS stats ON stats.order_id = lookup.order_id
             WHERE stats.status NOT IN ('trash')
             AND stats.date_created BETWEEN %s AND %s
             GROUP BY lookup.product_id
             ORDER BY revenue DESC
             LIMIT 5",
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        );

        $rows = $this->db->get_results($query, ARRAY_A);

        return array_map(
            static function (array $row) {
                $product = wc_get_product((int) $row['product_id']);

                return [
                    'product_id'   => (int) $row['product_id'],
                    'product_name' => $product ? $product->get_name() : __('Silinmiş ürün', 'oh-digital-delivery'),
                    'quantity'     => (int) $row['quantity'],
                    'revenue'      => (float) $row['revenue'],
                ];
            },
            $rows ?: []
        );
    }

    private function calculate_range_metrics(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';
        $meta_table  = $this->db->postmeta;

        $orders = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(order_id) FROM {$stats_table}
                 WHERE status NOT IN ('trash')
                 AND date_created BETWEEN %s AND %s",
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s')
            )
        );

        $revenue = (float) $this->db->get_var(
            $this->db->prepare(
                "SELECT COALESCE(SUM(net_total), 0) FROM {$stats_table}
                 WHERE status NOT IN ('trash')
                 AND date_created BETWEEN %s AND %s",
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s')
            )
        );

        $deliveries = (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(DISTINCT stats.order_id)
                 FROM {$stats_table} AS stats
                 LEFT JOIN {$meta_table} AS meta ON meta.post_id = stats.order_id AND meta.meta_key = 'oh_delivery_status'
                 WHERE stats.status NOT IN ('trash')
                 AND stats.date_created BETWEEN %s AND %s
                 AND COALESCE(meta.meta_value, 'pending') = 'completed'",
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s')
            )
        );

        return [
            'orders'     => $orders,
            'revenue'    => $revenue,
            'deliveries' => $deliveries,
        ];
    }

    private function get_monthly_trend(DateTimeImmutable $anchor, int $months): array
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';
        $start       = $anchor->modify(sprintf('-%d months', $months - 1))->setTime(0, 0);

        $query = $this->db->prepare(
            "SELECT DATE_FORMAT(date_created, '%%Y-%%m-01') AS period,
                    COUNT(order_id) AS orders,
                    SUM(net_total) AS revenue
             FROM {$stats_table}
             WHERE status NOT IN ('trash')
             AND date_created >= %s
             GROUP BY period
             ORDER BY period ASC",
            $start->format('Y-m-d H:i:s')
        );

        $rows = $this->db->get_results($query, ARRAY_A);

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['period']] = [
                'orders'  => (int) $row['orders'],
                'revenue' => (float) $row['revenue'],
            ];
        }

        $periods = [];
        for ($i = 0; $i < $months; $i++) {
            $month_start = $start->modify(sprintf('+%d months', $i));
            $key         = $month_start->format('Y-m-01');
            $periods[]   = [
                'label'  => wp_date('M Y', $month_start->getTimestamp()),
                'orders' => $indexed[$key]['orders'] ?? 0,
                'revenue'=> $indexed[$key]['revenue'] ?? 0.0,
            ];
        }

        return $periods;
    }

    private function get_status_breakdown(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';
        $meta_table  = $this->db->postmeta;

        $query = $this->db->prepare(
            "SELECT COALESCE(meta.meta_value, 'pending') AS status, COUNT(DISTINCT stats.order_id) AS total
             FROM {$stats_table} AS stats
             LEFT JOIN {$meta_table} AS meta ON meta.post_id = stats.order_id AND meta.meta_key = 'oh_delivery_status'
             WHERE stats.status NOT IN ('trash')
             AND stats.date_created BETWEEN %s AND %s
             GROUP BY status",
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        );

        $rows = $this->db->get_results($query, ARRAY_A);

        $defaults = [
            'pending'  => 0,
            'completed'=> 0,
            'partial'  => 0,
            'failed'   => 0,
        ];

        foreach ($rows as $row) {
            $key = $row['status'] ?: 'pending';
            if (! isset($defaults[$key])) {
                $defaults[$key] = 0;
            }
            $defaults[$key] += (int) $row['total'];
        }

        return $defaults;
    }

    private function get_daily_sales(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';

        $query = $this->db->prepare(
            "SELECT DATE(date_created) AS day,
                    COUNT(order_id) AS orders,
                    SUM(net_total) AS revenue,
                    SUM(num_items_sold) AS items
             FROM {$stats_table}
             WHERE status NOT IN ('trash')
             AND date_created BETWEEN %s AND %s
             GROUP BY day
             ORDER BY day ASC",
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        );

        $rows = $this->db->get_results($query, ARRAY_A);
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['day']] = [
                'orders'  => (int) $row['orders'],
                'revenue' => (float) $row['revenue'],
                'items'   => (int) $row['items'],
            ];
        }

        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
        $series = [];
        foreach ($period as $day) {
            $key     = $day->format('Y-m-d');
            $series[] = [
                'label'  => wp_date('d M', $day->getTimestamp()),
                'orders' => $indexed[$key]['orders'] ?? 0,
                'revenue'=> $indexed[$key]['revenue'] ?? 0.0,
                'items'  => $indexed[$key]['items'] ?? 0,
            ];
        }

        return $series;
    }

    private function get_recent_orders(int $limit): array
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';
        $meta_table  = $this->db->postmeta;

        $query = $this->db->prepare(
            "SELECT stats.order_id, COALESCE(meta.meta_value, 'pending') AS delivery_status
             FROM {$stats_table} AS stats
             LEFT JOIN {$meta_table} AS meta ON meta.post_id = stats.order_id AND meta.meta_key = 'oh_delivery_status'
             WHERE stats.status NOT IN ('trash')
             ORDER BY stats.date_created DESC
             LIMIT %d",
            max(1, $limit)
        );

        $rows = $this->db->get_results($query, ARRAY_A);
        $orders = [];

        foreach ($rows as $row) {
            $order = wc_get_order((int) $row['order_id']);
            if (! $order instanceof WC_Order) {
                continue;
            }

            $items = [];
            foreach ($order->get_items() as $item) {
                $items[] = [
                    'name'     => $item->get_name(),
                    'quantity' => (int) $item->get_quantity(),
                ];
            }

            $orders[] = [
                'id'              => $order->get_id(),
                'number'          => $order->get_order_number(),
                'total'           => (float) $order->get_total(),
                'currency'        => $order->get_currency(),
                'date_created'    => $order->get_date_created() ? $order->get_date_created()->date_i18n('Y-m-d H:i') : '',
                'delivery_status' => $row['delivery_status'] ?: 'pending',
                'items'           => $items,
            ];
        }

        return $orders;
    }

    private function get_total_revenue(): float
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';

        return (float) $this->db->get_var("SELECT COALESCE(SUM(net_total), 0) FROM {$stats_table} WHERE status NOT IN ('trash')");
    }

    private function count_orders_by_delivery_status(string $status): int
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';
        $meta_table  = $this->db->postmeta;

        return (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(DISTINCT stats.order_id)
                 FROM {$stats_table} AS stats
                 LEFT JOIN {$meta_table} AS meta ON meta.post_id = stats.order_id AND meta.meta_key = 'oh_delivery_status'
                 WHERE stats.status NOT IN ('trash')
                 AND COALESCE(meta.meta_value, 'pending') = %s",
                $status
            )
        );
    }

    private function hydrate_stock_overview(array $overview): array
    {
        return array_map(
            static function (array $row) {
                $product = wc_get_product($row['product_id']);

                return [
                    'product_id'   => (int) $row['product_id'],
                    'product_name' => $product ? $product->get_formatted_name() : __('Silinmiş ürün', 'oh-digital-delivery'),
                    'free'         => (int) ($row['free'] ?? 0),
                    'reserved'     => (int) ($row['reserved'] ?? 0),
                    'used'         => (int) ($row['used'] ?? 0),
                    'revoked'      => (int) ($row['revoked'] ?? 0),
                ];
            },
            $overview
        );
    }

    private function get_customer_metrics(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';

        $query = $this->db->prepare(
            "SELECT customer_id, COUNT(order_id) AS total
             FROM {$stats_table}
             WHERE status NOT IN ('trash')
             AND customer_id > 0
             AND date_created BETWEEN %s AND %s
             GROUP BY customer_id",
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        );

        $rows            = $this->db->get_results($query, ARRAY_A);
        $total_customers = count($rows);
        $repeaters       = array_reduce(
            $rows,
            static function ($carry, $row) {
                return $carry + ((int) $row['total'] > 1 ? 1 : 0);
            },
            0
        );

        return [
            'total_customers' => $total_customers,
            'repeat_rate'     => $total_customers ? round(($repeaters / $total_customers) * 100, 2) : 0.0,
        ];
    }

    private function get_payment_breakdown(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $stats_table = $this->db->prefix . 'wc_order_stats';

        $query = $this->db->prepare(
            "SELECT payment_method, COUNT(order_id) AS orders, SUM(net_total) AS revenue
             FROM {$stats_table}
             WHERE status NOT IN ('trash')
             AND date_created BETWEEN %s AND %s
             GROUP BY payment_method",
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        );

        $rows = $this->db->get_results($query, ARRAY_A) ?: [];

        return array_map(
            static function (array $row) {
                $label = $row['payment_method'];

                if (function_exists('WC')) {
                    $controller = WC()->payment_gateways();
                    $gateways   = $controller ? $controller->payment_gateways() : [];
                    if (isset($gateways[$row['payment_method']])) {
                        $label = $gateways[$row['payment_method']]->get_title();
                    }
                }

                return [
                    'method'  => $row['payment_method'],
                    'label'   => $label,
                    'orders'  => (int) $row['orders'],
                    'revenue' => (float) $row['revenue'],
                ];
            },
            $rows
        );
    }
}
