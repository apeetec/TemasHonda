<?php
// REDIRECT 404
add_action('template_redirect', 'redirect_404');
function redirect_404()
{
    if (is_404()) {
        wp_redirect(home_url());
    }
}
// FIM REDIRECT 404

add_action('template_redirect', 'attachment_page_redirect', 10);
function attachment_page_redirect()
{
    if (is_attachment()) {
        $url = wp_get_attachment_url(get_queried_object_id());
        wp_redirect(home_url(), 301);
    }
    return;
}

// Disable use XML-RPC
add_filter('xmlrpc_enabled', '__return_false');
// Disable X-Pingback to header
add_filter('wp_headers', 'disable_x_pingback');
function disable_x_pingback($headers)
{
    unset($headers['X-Pingback']);
    return $headers;
}

// Disable comments
function disable_comments()
{
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
}
add_action('admin_init', 'disable_comments');

function remove_admin_login_header()
{
    remove_action('wp_head', '_admin_bar_bump_cb');
}
add_action('get_header', 'remove_admin_login_header');
add_theme_support('post-thumbnails');
show_admin_bar(false);

add_action('send_headers', 'add_header_xframeoptions');
function add_header_xframeoptions()
{
    header('X-Frame-Options: SAMEORIGIN');
}


////////// Disable some endpoints for unauthenticated users
add_filter('rest_endpoints', 'disable_default_endpoints');
function disable_default_endpoints($endpoints)
{
    $endpoints_to_remove = array(
        '/oembed/1.0',
        '/wp/v2',
        '/wp/v2/media',
        '/wp/v2/types',
        '/wp/v2/statuses',
        '/wp/v2/taxonomies',
        '/wp/v2/tags',
        '/wp/v2/users',
        '/wp/v2/comments',
        '/wp/v2/settings',
        '/wp/v2/themes',
        '/wp/v2/blocks',
        '/wp/v2/oembed',
        '/wp/v2/posts',
        '/wp/v2/pages',
        '/wp/v2/block-renderer',
        '/wp/v2/search',
        '/wp/v2/categories'
    );

    if (!is_user_logged_in()) {
        foreach ($endpoints_to_remove as $rem_endpoint) {
            // $base_endpoint = "/wp/v2/{$rem_endpoint}";
            foreach ($endpoints as $maybe_endpoint => $object) {
                if (stripos($maybe_endpoint, $rem_endpoint) !== false) {
                    unset($endpoints[$maybe_endpoint]);
                }
            }
        }
    }
    return $endpoints;
}



add_theme_support('menus'); // Menus
function wp_menu_custom_limit_one($hook)
{
    // Prevent child menu items
    $menu_ids_block_child = array(3); // Menu ids to be limited by 1 child menu item
    if (!empty($_GET['menu'])) {
        if ($hook == 'nav-menus.php' && !in_array($_GET['menu'], $menu_ids_block_child))
            return;
        // override default value right after 'nav-menu' JS
        wp_add_inline_script('nav-menu', 'wpNavMenu.options.globalMaxDepth = 1;', 'after');
    }
}
add_action('admin_enqueue_scripts', 'wp_menu_custom_limit_one');

// Taxonomia de usuário
require_once get_template_directory() . '/functions/taxonomias/taxonomia-usuario.php';
// Metabox de usuário
require_once get_template_directory() . '/functions/cmb2/cmb2-usuario.php';
// Post type de Perguntas
require_once get_template_directory() . '/functions/posttype/posttype-perguntas.php';
// Taxonomia de datas para as perguntas
require_once get_template_directory() . '/functions/taxonomias/taxonomia-datas-perguntas.php';
// Metabox de perguntas
require_once get_template_directory() . '/functions/cmb2/cmb2-perguntas.php';
// Metabox de datas para as perguntas
require_once get_template_directory() . '/functions/cmb2/cmb2-taxonomia-datas-perguntas.php';
// Taxonomia de datas para os sorteados
// require_once get_template_directory() . '/functions/taxonomias/taxonomia-datas-sorteados-perguntas.php';

// ==================== SIMON SAYS ====================
// Post type de logs do Simon Says
require_once get_template_directory() . '/functions/posttype/posttype-simon-log.php';
// Metabox de logs do Simon Says
require_once get_template_directory() . '/functions/cmb2/cmb2-simon-log.php';
// AJAX handler do Simon Says
require_once get_template_directory() . '/functions/simon-says-ajax.php';

// ==================== RESET DO SISTEMA ====================
// AJAX handler para resetar dados CMB2 dos usuários
require_once get_template_directory() . '/functions/reset-sistema-ajax.php';

// ==================== RELATÓRIO DE USUÁRIOS ====================
// AJAX handler para relatório de respostas e pontuações
require_once get_template_directory() . '/functions/relatorio-ajax.php';

?>