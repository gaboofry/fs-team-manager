<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

$fs_tm_ids = get_posts( array(
    'post_type'   => 'fs_tm_team',
    'post_status' => 'any',
    'fields'      => 'ids',
    'numberposts' => -1,
) );
foreach ($fs_tm_ids as $fs_tm_id) {
    wp_delete_post((int) $fs_tm_id, true);
}

delete_option('fs_tm_teams');
delete_option('fs_tm_teams_pre_cpt');
delete_option('fs_tm_version');
delete_option('fs_tm_click_to_load');
delete_option('fs_tm_show_attribution');
delete_option('fs_tm_teams_restore_point');
delete_transient('fs_tm_pages_tree');
wp_cache_delete('all_teams', 'fs_tm');
