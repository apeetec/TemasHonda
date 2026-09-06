<?php
################################ VERIFICAÇÕES
// if (is_user_logged_in() && is_page('login')) {
//     if (user_can($user_verify, 'administrator')) {
//         wp_redirect(home_url());
//         exit;
//     } else {
//         wp_redirect(home_url('categorias-dos-videos'));
//         exit;
//     }
// }

// else if (!is_user_logged_in() && !is_page('login')) {
//     wp_redirect(home_url('login'));
//     exit;
// }
// else if (is_user_logged_in() && !current_user_can('manage_options') && is_page('usuarios')) {
//     wp_redirect(home_url());
//     exit;
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

    <title><?php echo get_bloginfo('name'); ?></title>

    <!-- Estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/materialize.css" type="text/css">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/style.css?<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/admin-usuarios.css?<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
