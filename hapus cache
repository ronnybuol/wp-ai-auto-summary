/**
 * Tools → AI Summary Cache (ON-DEMAND ONLY)
 * Hanya menghapus meta _ai_summary_len_* (mode on-demand).
 * Letakkan di Code Snippets (Admin area), theme child, atau mu-plugin.
 */

add_action('admin_menu', function(){
    add_management_page(
        'AI Summary Cache (On-Demand)',
        'AI Summary Cache (On-Demand)',
        'manage_options',
        'ai-summary-cache-ondemand',
        'ai_summary_render_tools_page_ondemand'
    );
});

function ai_summary_render_tools_page_ondemand(){
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $prefix = $wpdb->prefix;
    $notice = '';

    // Proses form
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['_wpnonce'])
        && wp_verify_nonce($_POST['_wpnonce'], 'ai_summary_purge_ondemand')) {

        // Purge SEMUA cache on-demand
        if (isset($_POST['purge_all_on_demand'])) {
            // Hapus semua meta yang diawali _ai_summary_len_
            $deleted = $wpdb->query("DELETE FROM {$prefix}postmeta WHERE meta_key REGEXP '^_ai_summary_len_'");
            $notice  = 'Purge selesai. Baris meta terhapus: ' . intval($deleted);
        }

        // Purge on-demand per Post ID (hanya jika sudah ada cache)
        if (isset($_POST['purge_post_on_demand'])) {
            $post_id = intval($_POST['post_id'] ?? 0);
            if ($post_id > 0) {
                // Cek apakah post memang punya cache on-demand
                $has = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$prefix}postmeta WHERE post_id=%d AND meta_key REGEXP '^_ai_summary_len_'",
                        $post_id
                    )
                );
                if ($has) {
                    $deleted = $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM {$prefix}postmeta WHERE post_id=%d AND meta_key REGEXP '^_ai_summary_len_'",
                            $post_id
                        )
                    );
                    $notice = "Cache on-demand untuk post #{$post_id} dihapus. Baris meta terhapus: " . intval($deleted);
                } else {
                    $notice = "Post #{$post_id} belum memiliki cache ringkasan on-demand.";
                }
            } else {
                $notice = 'Masukkan Post ID yang valid.';
            }
        }

        // (Opsional) flush object cache
        if (function_exists('wp_cache_flush')) { wp_cache_flush(); }

        echo '<div class="updated"><p>'.esc_html($notice).'</p></div>';
    }

    // UI
    echo '<div class="wrap"><h1>AI Summary Cache (On-Demand)</h1>';
    echo '<p>Snippet ini <strong>hanya</strong> menghapus cache on-demand (meta <code>_ai_summary_len_*</code>). ';
    echo 'Konten post tidak diubah. Klik berikutnya pada tombol “Lihat Ringkasan” akan membuat cache baru.</p>';

    // Form: Purge semua on-demand
    echo '<form method="post" style="margin-bottom:20px;">';
    wp_nonce_field('ai_summary_purge_ondemand');
    echo '<h2 class="title">Purge Semua Cache On-Demand</h2>';
    submit_button('Purge Semua (On-Demand)', 'delete', 'purge_all_on_demand', false);
    echo '</form>';

    // Form: Purge per post (on-demand)
    echo '<form method="post">';
    wp_nonce_field('ai_summary_purge_ondemand');
    echo '<h2 class="title">Purge On-Demand per Post</h2>';
    echo '<p><label>Post ID: <input type="number" name="post_id" min="1" class="small-text" /></label></p>';
    submit_button('Purge Cache On-Demand untuk Post Ini', 'secondary', 'purge_post_on_demand', false);
    echo '</form>';

    echo '<p style="margin-top:1em;color:#666">Meta yang dihapus: <code>_ai_summary_len_*</code> saja.</p>';
    echo '</div>';
}

// (Opsional) Link cepat di daftar post → menuju halaman Tools
add_filter('post_row_actions', function($actions, $post){
    if (current_user_can('manage_options')) {
        $url = admin_url('tools.php?page=ai-summary-cache-ondemand');
        $actions['ai_summary_purge'] = '<a href="'.esc_url($url).'" title="Buka halaman Purge Cache On-Demand">Purge AI Summary</a>';
    }
    return $actions;
}, 10, 2);
