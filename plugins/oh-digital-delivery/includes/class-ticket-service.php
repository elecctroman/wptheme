<?php
/**
 * Simple ticket service that powers the customer support inbox.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use DateTimeImmutable;
use WP_Post;
use WP_Query;

class Ticket_Service
{
    public const POST_TYPE = 'oh_ticket';
    private const META_ORDER    = '_oh_ticket_order_id';
    private const META_MESSAGES = '_oh_ticket_thread';

    public function register_post_type(): void
    {
        if (post_type_exists(self::POST_TYPE)) {
            return;
        }

        register_post_type(
            self::POST_TYPE,
            [
                'label'               => __('Destek Talepleri', 'oh-digital-delivery'),
                'labels'              => [
                    'name'          => __('Destek Talepleri', 'oh-digital-delivery'),
                    'singular_name' => __('Destek Talebi', 'oh-digital-delivery'),
                ],
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => 'oh-digital-delivery',
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'supports'            => ['title'],
                'rewrite'             => false,
                'show_in_rest'        => false,
            ]
        );

        register_taxonomy(
            self::POST_TYPE . '_status',
            self::POST_TYPE,
            [
                'labels'            => [
                    'name' => __('Durumlar', 'oh-digital-delivery'),
                ],
                'public'            => false,
                'show_ui'           => true,
                'show_in_menu'      => false,
                'show_admin_column' => true,
                'rewrite'           => false,
            ]
        );

        $this->ensure_default_status_terms();
    }

    public function get_tickets_for_user(int $user_id): array
    {
        $query = new WP_Query(
            [
                'post_type'      => self::POST_TYPE,
                'author'         => $user_id,
                'posts_per_page' => 50,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]
        );

        return array_map([$this, 'map_ticket'], $query->posts);
    }

    public function get_ticket(int $ticket_id, int $user_id): ?array
    {
        $post = get_post($ticket_id);
        if (! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type) {
            return null;
        }

        if ((int) $post->post_author !== $user_id && ! current_user_can('manage_woocommerce')) {
            return null;
        }

        return $this->map_ticket($post);
    }

    public function create_ticket(int $user_id, string $subject, string $message, ?int $order_id = null): int
    {
        $ticket_id = wp_insert_post(
            [
                'post_type'    => self::POST_TYPE,
                'post_title'   => wp_strip_all_tags($subject),
                'post_status'  => 'publish',
                'post_author'  => $user_id,
                'post_content' => '',
            ]
        );

        if ($ticket_id && ! is_wp_error($ticket_id)) {
            if ($order_id) {
                update_post_meta($ticket_id, self::META_ORDER, $order_id);
            }

            $this->append_message($ticket_id, $user_id, $message, 'open');
        }

        return (int) $ticket_id;
    }

    public function append_message(int $ticket_id, int $author_id, string $message, ?string $status = null): void
    {
        $message = wp_kses_post($message);
        if ('' === trim($message)) {
            return;
        }

        $thread = get_post_meta($ticket_id, self::META_MESSAGES, true);
        if (! is_array($thread)) {
            $thread = [];
        }

        $thread[] = [
            'author' => $author_id,
            'body'   => $message,
            'time'   => (new DateTimeImmutable('now', wp_timezone()))->format('Y-m-d H:i:s'),
        ];

        update_post_meta($ticket_id, self::META_MESSAGES, $thread);

        if ($status) {
            wp_set_post_terms($ticket_id, [$status], self::POST_TYPE . '_status', false);
        }
    }

    public function get_messages(int $ticket_id): array
    {
        $thread = get_post_meta($ticket_id, self::META_MESSAGES, true);
        if (! is_array($thread)) {
            return [];
        }

        return $thread;
    }

    private function map_ticket($post): array
    {
        if (! $post instanceof WP_Post) {
            return [];
        }

        $messages = $this->get_messages($post->ID);
        $status   = wp_get_post_terms($post->ID, self::POST_TYPE . '_status');
        $status   = $status ? $status[0]->name : 'open';

        return [
            'id'        => (int) $post->ID,
            'subject'   => $post->post_title,
            'status'    => $status,
            'order_id'  => (int) get_post_meta($post->ID, self::META_ORDER, true),
            'updated'   => $post->post_modified,
            'messages'  => $messages,
        ];
    }

    private function ensure_default_status_terms(): void
    {
        $taxonomy = self::POST_TYPE . '_status';
        $defaults = [
            'open'    => __('Açık', 'oh-digital-delivery'),
            'pending' => __('Beklemede', 'oh-digital-delivery'),
            'closed'  => __('Kapalı', 'oh-digital-delivery'),
        ];

        foreach ($defaults as $slug => $label) {
            if (! term_exists($slug, $taxonomy)) {
                wp_insert_term($label, $taxonomy, ['slug' => $slug]);
            }
        }
    }
}
