set -e

mkdir -p {{ $path }}/wp-content/mu-plugins
cat <<'PHP' > {{ $path }}/wp-content/mu-plugins/hitechcloudpanel-auto-login.php
<?php
/**
 * Plugin Name: HiTechCloudPanel Auto Login
 */

add_action('init', function () {
    if (! isset($_GET['hitechcloudpanel_auto_login']) || $_GET['hitechcloudpanel_auto_login'] !== '1') {
        return;
    }

    if (! function_exists('wp_set_auth_cookie')) {
        return;
    }

    $users = get_users([
        'role__in' => ['administrator'],
        'number' => 1,
        'fields' => 'ids',
    ]);

    if ($users === []) {
        return;
    }

    wp_set_current_user($users[0]);
    wp_set_auth_cookie($users[0], true);

    wp_safe_redirect(admin_url());
    exit;
});
PHP
