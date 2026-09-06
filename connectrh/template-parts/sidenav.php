<?php
// Obtém o usuário atual
$current_user = wp_get_current_user();
$is_admin = current_user_can('administrator') || current_user_can('editor');
$user_name = $current_user->display_name;
$user_email = $current_user->user_email;
?>

<ul id="slide-out" class="sidenav sidenav-fixed sidenav-enhanced">
    <!-- Header do Sidenav -->
    <li class="sidenav-header">
        <div class="sidenav-logo-wrapper">
            <div class="sidenav-logo-icon">
                <i class="fas fa-video" aria-hidden="true"></i>
            </div>
            <div class="sidenav-logo-text">
                <h2>ConnectRH</h2>
                <span>Sistema de Vídeos</span>
            </div>
        </div>
        <?php if ($is_admin) : ?>
        <div class="sidenav-user-info">
            <div class="user-avatar">
                <i class="fas fa-user-shield" aria-hidden="true"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo esc_html($user_name); ?></span>
                <span class="user-role">Administrador</span>
            </div>
        </div>
        <?php endif; ?>
    </li>

    <li class="sidenav-divider"></li>

    <!-- Menu de Navegação -->
    <li class="sidenav-section-title">
        <span><i class="fas fa-compass" aria-hidden="true"></i> Navegação</span>
    </li>

    <?php if ($is_admin) : ?>
    <li class="sidenav-item">
        <a href="<?php echo esc_url(get_site_url()); ?>/" class="sidenav-link">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <li class="sidenav-item">
        <a href="<?php echo esc_url(get_site_url()); ?>/usuarios" class="sidenav-link">
            <i class="fas fa-users" aria-hidden="true"></i>
            <span>Usuários</span>
        </a>
    </li>
    <li class="sidenav-item">
        <a href="<?php echo esc_url(get_site_url()); ?>/criar-categorias" class="sidenav-link">
            <i class="fas fa-folder-tree" aria-hidden="true"></i>
            <span>Criar Categorias</span>
        </a>
    </li>
    <li class="sidenav-item">
        <a href="<?php echo esc_url(get_site_url()); ?>/criar-video" class="sidenav-link">
            <i class="fas fa-play-circle" aria-hidden="true"></i>
            <span>Inserir Vídeos</span>
        </a>
    </li>
    <?php endif; ?>

    <!-- Link destacado - visível para todos -->
    <li class="sidenav-item sidenav-item-highlighted">
        <a href="<?php echo esc_url(get_site_url()); ?>/categorias-dos-videos" class="sidenav-link">
            <i class="fas fa-film" aria-hidden="true"></i>
            <span>Vídeos</span>
            <span class="sidenav-badge">Novo</span>
        </a>
    </li>

    <?php if ($is_admin) : ?>
    <li class="sidenav-divider"></li>
    
    <li class="sidenav-section-title">
        <span><i class="fas fa-cog" aria-hidden="true"></i> Sistema</span>
    </li>
    
    <li class="sidenav-item">
        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="sidenav-link sidenav-link-logout">
            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            <span>Sair</span>
        </a>
    </li>
    <?php else : ?>
    <li class="sidenav-divider"></li>
    
    <li class="sidenav-item">
        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="sidenav-link sidenav-link-logout">
            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            <span>Sair</span>
        </a>
    </li>
    <?php endif; ?>
</ul>