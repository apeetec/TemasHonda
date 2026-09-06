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
$unidade = get_user_meta( get_current_user_id(), 'empresa_usuario', true);
if($unidade == 'SUM-HAB-ITAJAI'){
    echo '<style>';
    echo  'li.msc{display:inline-block;}';
    echo '</style>';
}
else{
    echo '<style>';
    echo  'li.msc{display:none;}';
    echo '</style>';   
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

    <title><?php if(is_home()) { echo get_bloginfo('name') . ' | ' . get_bloginfo('description'); } else { echo single_term_title() . ' | ' . get_bloginfo('name'); } ?></title>

    <!-- Estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/materialize.css" type="text/css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/style.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/magnify.css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/game.css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/8erros.css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/arrastar-e-soltar.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/datables.min.css">
        <!-- SLICK SLIDE 1/3 -->
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css">

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php if (is_user_logged_in()): ?>
    <section class="banner-top">
        <img class="desktop-banner" height="" width="100%" src="https://qualidadeptr.com.br/wp-content/uploads/2026/01/02-BANNER_SITE1920x450px_A_MAIO_2025.png" alt="Logo Honda qualidade">
		<img class="mobile-banner" height="" width="100%" src="https://qualidadeptr.com.br/wp-content/uploads/2026/01/02_banner_site_1920x750px_mobile_maio_2025.png" alt="Logo Honda qualidade">
    </section>
    <nav class="white z-depth-0">
        <div class="nav-wrapper">
            <ul class="hide-on-med-and-down">
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
// 						$aux = 'oi';
				        $aux = "class='dropdown-trigger btn '";
                        if ($m->menu_item_parent) {
                            $menu[$m->menu_item_parent]['children'][] = [
                                'title' => $m->title,
                                'url' => $m->url
                            ];
// 							$aux = "class='dropdown-trigger btn'";
                        }
                    }
                    return $menu;
                }

                $menu_items = get_custom_menu(4);
                foreach ($menu_items as $item) : ?>
                    <li class="<?php echo implode(' ', $item['classes']); echo sanitize_title($item['title']);?>">
                       <a <?php if (!empty($item['children'])){echo 'class="dropdown-trigger btn btn-large"'.' '.'data-target="'.esc_html(sanitize_title($item['title'])).'_mobile"';} ?> href="<?php echo esc_url($item['url']); ?>">

                            <?php echo esc_html($item['title']); ?>
                        </a>
                        <?php if (!empty($item['children'])): ?>
                            <ul class="dropdown-content" id='<?php echo esc_html(sanitize_title($item['title'])).'_mobile'; ?>'>
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><a href="<?php echo esc_url($child['url']); ?>" target="!_blank"><?php echo esc_html($child['title']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li>
                    <a href="<?php echo wp_logout_url(home_url()); ?>">Sair</a>
                </li>
                <li style="background-color:#fff!important;">
                    <a href="<?php echo get_permalink(865); ?>" style="background-color:#fff!important;">
                        <img src="<?php echo get_template_directory_uri(); ?>/img/logo-1.png" alt="Descrição da imagem">
                    </a>
                </li>
            </ul>
            <!-- <a href="#" data-target="slide-out" class="sidenav-trigger black-text"><i class="fa-solid fa-bars"></i></a> -->
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

                $menu_items = sidenav(4);
                foreach ($menu_items as $item) : ?>
                    <li class="<?php echo implode(' ', $item['classes']); ?>">
                        <a <?php if (!empty($item['children'])){echo 'class="dropdown-trigger btn"'.' '.'data-target="'.esc_html(sanitize_title($item['title'])).'"';} ?> href="<?php echo esc_url($item['url']); ?>">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                        <?php if (!empty($item['children'])): ?>
                            <ul class="dropdown-content" id='<?php echo esc_html(sanitize_title($item['title'])); ?>'>
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

<main class="white">
