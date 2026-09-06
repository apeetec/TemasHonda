<?php
################################ VERIFICAÇÕES
if (is_user_logged_in() && is_page('login')) {
    wp_redirect(home_url());
    exit;
}

if (!is_user_logged_in() && !is_page('login')) {
    wp_redirect(home_url('login'));
    exit;
}

// if (!is_page('login')) {
//     $senhaAlterada = get_user_meta(get_current_user_id(), 'user_field_senha_alterada', true);

//     if (is_page('nova-senha')) {
//         if ($senhaAlterada === 'Sim' && empty($_POST)) {
//             wp_redirect(home_url());
//             exit;
//         }
//     } elseif ($senhaAlterada === 'Não' || empty($senhaAlterada)) {
//         wp_redirect(home_url('nova-senha'));
//         exit;
//     }
// }
################################ FIM VERIFICAÇÕES
?>

<!DOCTYPE html>
<html lang="pt-BR" theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Descrição.">
    <meta name="keywords" content="Palavras-chave.">
    <meta name="author" content="Gabriel Batista">

    <title><?php if(is_home()) { echo get_bloginfo('name') . ' | ' . get_bloginfo('description'); } else { echo get_the_title() . ' | ' . get_bloginfo('name'); } ?></title>

    <!-- Estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/materialize.css" type="text/css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/style.css?<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php if (is_user_logged_in()): ?>
    <section class="banner-top">
        <img height="" width="100%" src="https://www.campaq.com.br/wp-content/uploads/2026/02/BANNER_SITE26.png" alt="Logo Honda qualidade">
    </section>
    <nav style="background-color: #fff;">
        <div class="container nav-wrapper teste">
            <ul class="right hide-on-med-and-down">
                <?php
                function get_custom_menu($menu_id) {
                    $array_menu = wp_get_nav_menu_items($menu_id);
                    $menu = [];
                    foreach ($array_menu as $m) {
                        if (empty($m->menu_item_parent)) {
                            $menu[$m->ID] = [
                                'ID' => $m->ID,
                                'title' => $m->title,
                                'url' => $m->url,
                                'classes' => $m->classes,
                                'children' => []
                            ];
                        }
                    }
                    foreach ($array_menu as $m) {
                        if ($m->menu_item_parent) {
                            $menu[$m->menu_item_parent]['children'][] = [
                                'title' => $m->title,
                                'url' => $m->url
                            ];
                        }
                    }
                    return $menu;
                }

                $menu_items = get_custom_menu(12);
                foreach ($menu_items as $item) : ?>
                    <li class="<?php echo implode(' ', $item['classes']); ?>">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                        <?php if (!empty($item['children'])): ?>
                            <ul class="dropdown-content">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><a href="<?php echo esc_url($child['url']); ?>"><?php echo esc_html($child['title']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li>
                    <a href="<?php echo wp_logout_url(home_url()); ?>">Sair</a>
                </li>
            </ul>
            <a href="#" data-target="slide-out" class="sidenav-trigger black-text"><i class="fa-solid fa-bars"></i></a>
            <!-- Sidenav -->
            <ul id="slide-out" class="sidenav">
                <?php
                    function sidenav($menu_id) {
                    $array_menu = wp_get_nav_menu_items($menu_id);
                    $menu = [];
                    foreach ($array_menu as $m) {
                        if (empty($m->menu_item_parent)) {
                            $menu[$m->ID] = [
                                'ID' => $m->ID,
                                'title' => $m->title,
                                'url' => $m->url,
                                'classes' => $m->classes,
                                'children' => []
                            ];
                        }
                    }
                    foreach ($array_menu as $m) {
                        if ($m->menu_item_parent) {
                            $menu[$m->menu_item_parent]['children'][] = [
                                'title' => $m->title,
                                'url' => $m->url
                            ];
                        }
                    }
                    return $menu;
                }

                $menu_items = sidenav(92);
                foreach ($menu_items as $item) : ?>
                    <li class="<?php echo implode(' ', $item['classes']); ?>">
                        <a href="<?php echo esc_url($item['url']); ?>">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                        <?php if (!empty($item['children'])): ?>
                            <ul class="dropdown-content">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><a href="<?php echo esc_url($child['url']); ?>"><?php echo esc_html($child['title']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
<?php endif; ?>

<main>
    <?php
    if(is_home()){
    ?>
<!-- <div id="chamada" class="modal">
  <div class="modal-content">
    <video class="responsive-video" width="100%" id="videoId" autoplay muted controls>
        <source src="<?php echo get_template_directory_uri(); ?>/videos/CHAMADA_ANDRE_LEGENDADO.mp4" type="video/mp4">
    </video>
  </div>
    <a href="#!" class="modal-close waves-effect btn-flat"><i class="fa-solid fa-xmark"></i></a>
</div> -->
<?php
    }
?>
