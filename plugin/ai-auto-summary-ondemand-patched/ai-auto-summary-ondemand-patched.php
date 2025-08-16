<?php
/**
 * Plugin Name: AI Auto Summary (On-Demand, LangChain/Gemini Backend) — Patched
 * Description: Versi on-demand dengan dukungan endpoint langsung ke index.php (tanpa .htaccess) dan pesan error yang lebih jelas.
 * Version: 1.0.1
 * Author: ChatGPT
 * License: GPL2
 */

if (!defined('ABSPATH')) { exit; }

class AIAutoSummaryOnDemand {
    const OPT_GROUP     = 'ai_auto_summary_options';
    const OPT_ENDPOINT  = 'ai_summary_endpoint';
    const OPT_API_KEY   = 'ai_summary_api_key';
    const OPT_MIN_P     = 'ai_summary_min_paragraphs';
    const OPT_LEN_DEF   = 'ai_summary_default_length';

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_assets']);
        add_filter('the_content', [$this, 'inject_button_before_content'], 8);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_settings() {
        register_setting(self::OPT_GROUP, self::OPT_ENDPOINT);
        register_setting(self::OPT_GROUP, self::OPT_API_KEY);
        register_setting(self::OPT_GROUP, self::OPT_MIN_P);
        register_setting(self::OPT_GROUP, self::OPT_LEN_DEF);

        add_settings_section('ai_summary_main', 'AI Auto Summary (On-Demand)', function(){
            echo '<p>Atur endpoint backend AI. Jika hosting tidak mendukung .htaccess, gunakan URL langsung ke <code>index.php</code>.</p>';
        }, self::OPT_GROUP);

        add_settings_field(self::OPT_ENDPOINT, 'Backend Endpoint', function(){
            $val = esc_url(get_option(self::OPT_ENDPOINT, ''));
            echo '<input type="url" name="'.self::OPT_ENDPOINT.'" value="'.$val.'" class="regular-text" placeholder="https://domain/ai-summarize/ ATAU https://domain/ai-summarize/index.php" />';
        }, self::OPT_GROUP, 'ai_summary_main');

        add_settings_field(self::API_KEY, 'Backend API Key (opsional)', function(){
            $val = esc_attr(get_option(self::OPT_API_KEY, ''));
            echo '<input type="text" name="'.self::OPT_API_KEY.'" value="'.$val.'" class="regular-text" />';
        }, self::OPT_GROUP, 'ai_summary_main');

        add_settings_field(self::OPT_MIN_P, 'Ambang Paragraf (default 15)', function(){
            $val = intval(get_option(self::OPT_MIN_P, 15));
            echo '<input type="number" min="1" name="'.self::OPT_MIN_P.'" value="'.$val.'" class="small-text" />';
        }, self::OPT_GROUP, 'ai_summary_main');

        add_settings_field(self::OPT_LEN_DEF, 'Panjang Ringkasan Default (kata, default 100)', function(){
            $val = intval(get_option(self::OPT_LEN_DEF, 100));
            echo '<input type="number" min="30" step="10" name="'.self::OPT_LEN_DEF.'" value="'.$val.'" class="small-text" />';
        }, self::OPT_GROUP, 'ai_summary_main');
    }

    public function admin_menu() {
        add_options_page('AI Auto Summary', 'AI Auto Summary', 'manage_options', 'ai-auto-summary-ondemand', [$this, 'settings_page']);
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>AI Auto Summary (On-Demand) — Patched</h1><form method="post" action="options.php">';
        settings_fields(self::OPT_GROUP);
        do_settings_sections(self::OPT_GROUP);
        submit_button();
        echo '<p><strong>Catatan:</strong> Jika endpoint diakhiri <code>.php</code>, plugin akan memanggil URL tersebut langsung (tanpa menambah <code>/summarize</code>).</p>';
        echo '</div>';
    }

    public function enqueue_front_assets() {
        if (!is_singular('post')) return;
        wp_enqueue_script('ai-summary-front', plugins_url('front.js', __FILE__), [], '1.0.1', true);
        wp_localize_script('ai-summary-front', 'AIAutoSummaryFront', [
            'restBase' => esc_url_raw(get_rest_url(null, 'ai-summary/v1/')),
            'defaultLen' => intval(get_option(self::OPT_LEN_DEF, 100)),
            'i18n' => [
                'seeSummary' => '🔎 Lihat Ringkasan',
                'making'     => 'Menyusun...'
            ]
        ]);
        wp_enqueue_style('ai-summary-style', plugins_url('style.css', __FILE__), [], '1.0.1');
    }

    public function inject_button_before_content($content) {
        if (is_admin() || !is_singular('post')) return $content;
        global $post;
        if (!$post) return $content;
        $btn = '<div class="ai-summary-box placeholder">'
             . '<button class="ai-summary-btn" data-post="'.$post->ID.'">🔎 Lihat Ringkasan</button>'
             . '<div class="ai-summary-target"></div>'
             . '</div>';
        return $btn . $content;
    }

    public function register_rest_routes() {
        register_rest_route('ai-summary/v1', '/summarize', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_summarize'],
            'permission_callback' => '__return_true',
            'args'                => [
                'post_id' => ['required' => true, 'type' => 'integer'],
                'len'     => ['required' => false, 'type' => 'integer', 'default' => intval(get_option(self::OPT_LEN_DEF, 100))],
            ]
        ]);
    }

    private function count_paragraphs($content) {
        $p_tags = preg_match_all('/<p[\s>]/i', $content, $m);
        if ($p_tags > 0) return $p_tags;
        $parts = preg_split("/(
||
){2,}/", wp_strip_all_tags($content));
        $parts = array_filter(array_map('trim', $parts));
        return max(1, count($parts));
    }

    public function rest_summarize(\WP_REST_Request $req) {
        $post_id = intval($req->get_param('post_id'));
        $len     = intval($req->get_param('len'));

        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return new \WP_Error('404', 'Post tidak ditemukan'); 
        }

        $min_p = intval(get_option(self::OPT_MIN_P, 15));
        $content = $post->post_content;
        if ($this->count_paragraphs($content) <= $min_p) {
            return ['html' => '<div class="ai-summary-box"><em>Artikel ini terlalu pendek.</em></div>'];
        }

        $base = trim(get_option(self::OPT_ENDPOINT, ''));
        if (!$base) return new \WP_Error('no_endpoint', 'Backend endpoint belum diatur di Settings > AI Auto Summary');
        $is_php = preg_match('/\.php(\?.*)?$/i', $base);
        $endpoint = $is_php ? $base : trailingslashit($base) . 'summarize';

        $hash = md5($post->post_modified_gmt . '|' . strlen(wp_strip_all_tags($content)) . '|len=' . $len);
        $meta_key = '_ai_summary_len_' . $len . '_' . $hash;
        $cached = get_post_meta($post_id, $meta_key, true);
        if ($cached) return ['html' => $cached];

        $payload = [
            'title'    => html_entity_decode(get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'text'     => wp_strip_all_tags($content),
            'language' => get_locale(),
            'length'   => $len,
        ];

        $args = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . get_option(self::OPT_API_KEY, ''),
            ],
            'timeout' => 45,
            'body'    => wp_json_encode($payload),
        ];

        $resp = wp_remote_post($endpoint, $args);
        if (is_wp_error($resp)) {
            return new \WP_Error('backend', 'Gagal terhubung ke backend: ' . $resp->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($resp);
        $body_raw = wp_remote_retrieve_body($resp);
        $body = json_decode($body_raw, true);

        if ($code !== 200) {
            return new \WP_Error('bad_status', 'Backend mengembalikan status ' . intval($code) . '. Body: ' . substr($body_raw, 0, 300));
        }
        if (!is_array($body) || empty($body['summary'])) {
            return new \WP_Error('bad_resp', 'Respon backend tidak valid. Body: ' . substr($body_raw, 0, 300));
        }

        $summary = sanitize_text_field($body['summary']);
        $html = '<div class="ai-summary-box">'
              . '<strong>Ringkasan</strong>'
              . '<p class="ai-summary-main">'. esc_html($summary) .'</p>'
              . '</div>';

        update_post_meta($post_id, $meta_key, $html);

        return ['html' => $html];
    }
}

new AIAutoSummaryOnDemand();
