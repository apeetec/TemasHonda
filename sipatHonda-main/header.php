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
    <!-- CORREÇÃO: Usa filemtime() ao invés de time() para cachear o CSS corretamente -->
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/style.css?ver=<?php echo filemtime(get_template_directory() . '/style.css'); ?>">
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
        <img class="hide-mobile" height="100%" width="100%" src="https://sipathonda2025.com.br/wp-content/uploads/2025/11/banner-site.png" alt="Logo Honda qualidade">
		<img class="show-mobile" height="100%" width="100%" src="https://sipathonda2025.com.br/wp-content/uploads/2025/11/mobile.png" alt="Logo Honda qualidade">
    </section>
    <nav style="background-color: #fff;">
        <div class="container nav-wrapper">
            <ul class="right hide-on-med-and-down">
                <?php
                // CORREÇÃO: Função única para processar o menu (elimina código duplicado)
                function processar_menu_items($menu_id) {
                    $array_menu = wp_get_nav_menu_items($menu_id);
                    if (!$array_menu) return [];
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

                // Carrega o menu uma única vez para desktop e mobile
                $menu_items = processar_menu_items(92);
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
                // CORREÇÃO: Reutiliza o mesmo $menu_items do desktop (sem duplicar a função)
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
