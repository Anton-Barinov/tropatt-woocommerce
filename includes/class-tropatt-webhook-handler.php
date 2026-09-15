<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tropatt_Webhook_Handler {
    /** Route used by the CRM for status pushes. */
    const ROUTE = '/status';
    /** Kept for stores configured before the route was renamed. */
    const LEGACY_ROUTE = '/webhook';

    public static function init() {
        add_action('rest_api_init', function () {
            $args = array(
                'methods' => 'POST',
                'callback' => array('Tropatt_Webhook_Handler', 'handle_webhook'),
                'permission_callback' => '__return_true'
            );

            register_rest_route('tropatt/v1', self::ROUTE, $args);
            register_rest_route('tropatt/v1', self::LEGACY_ROUTE, $args);
        });
    }

    /**
     * Build a response that no page cache may store. Without this a cached 200
     * answer can be replayed to the CRM and a genuine status push gets lost.
     */
    private static function respond($data, $status = 200) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        $response = new WP_REST_Response($data, $status);
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        // WP Rocket, LiteSpeed Cache and W3 Total Cache hints.
        $response->header('X-Cache-Enabled', 'False');
        $response->header('X-LiteSpeed-Cache-Control', 'no-cache');
        $response->header('X-Accel-Expires', '0');

        return $response;
    }

    public static function handle_webhook(WP_REST_Request $request) {
        $raw_body = $request->get_body();
        $timestamp = $request->get_header('x-tropatt-timestamp');
        $signature = $request->get_header('x-tropatt-signature');
        $event = $request->get_header('x-tropatt-event');

        $store_secret = (string)get_option('tropatt_store_secret', '');

        if (empty($store_secret) || empty($signature) || empty($timestamp)) {
            return self::respond(array('error' => 'Missing authentication headers'), 401);
        }

        if (abs(time() - (int)$timestamp) > 300) {
            return self::respond(array('error' => 'Timestamp out of tolerance window'), 401);
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp . '.' . $raw_body, $store_secret, true));
        if (!hash_equals($expected, $signature)) {
            return self::respond(array('error' => 'Invalid cryptographic signature'), 401);
        }

        // Replay protection: the same signed packet may only be applied once
        // inside the tolerance window.
        $replay_key = 'tropatt_wh_' . md5((string)$signature . '|' . (string)$timestamp);
        if (get_transient($replay_key)) {
            return self::respond(array('error' => 'Replay detected: this packet was already processed'), 409);
        }
        set_transient($replay_key, 1, 600);

        $data = json_decode($raw_body, true);
        if (!is_array($data)) {
            return self::respond(array('error' => 'Invalid JSON payload'), 400);
        }

        if ($event === 'ping') {
            return self::respond(array('success' => true, 'code' => 'PONG'), 200);
        }

        $order_id = (int)($data['external_order_id'] ?? 0);
        $external_status = $data['external_status'] ?? null;
        $crm_task_public_id = (string)($data['crm_task_public_id'] ?? '');

        if ($order_id <= 0) {
            return self::respond(array('error' => 'Missing external_order_id'), 422);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return self::respond(array('error' => 'Order not found: ' . $order_id), 404);
        }

        $target_status = null;
        if (!empty($external_status)) {
            $target_status = sanitize_text_field($external_status);
        } else {
            $mappings = (array)get_option('tropatt_status_mappings', array());
            $new_crm_status = (string)($data['new_status'] ?? '');
            foreach ($mappings as $wc_st => $crm_st) {
                if (strcasecmp($crm_st, $new_crm_status) === 0) {
                    $target_status = $wc_st;
                    break;
                }
            }
        }

        if (empty($target_status)) {
            return self::respond(array('success' => true, 'notice' => 'Ignored: no mapping for status'), 200);
        }

        Tropatt_Client::$suppress_echo = true;
        try {
            $note = 'Статус обновлен из TropaTT CRM';
            if (!empty($crm_task_public_id)) {
                $note .= ' (Задача: ' . $crm_task_public_id . ')';
            }
            $order->update_status($target_status, $note);
        } finally {
            Tropatt_Client::$suppress_echo = false;
        }

        return self::respond(array('success' => true, 'order_id' => $order_id, 'new_status' => $target_status), 200);
    }
}
