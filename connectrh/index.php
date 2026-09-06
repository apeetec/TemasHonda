<?php

/**
 * Template Name: Dashboard central
 * 
 * Hub central administrativo com KPIs, estatísticas e atalhos rápidos
 * Apenas para administradores
 */

// Verificação de permissão


get_header();

?>
<?php
	$dirbase = get_template_directory();
    require_once $dirbase . '/template-parts/sidenav.php';
?>
<div class="control-panel">
	 <div class="banner">
        <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/BANNER-1.png">
    </div>
    <div class="dashboard-usuarios">
        <!-- Header do Dashboard -->
        <header class="dashboard-header admin-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                </div>
                <div>
                    <h1><i class="fas fa-home" style="margin-right:12px; color:#1976d2;" aria-hidden="true"></i>Dashboard Central</h1>
                    <p class="text-muted"><i class="fas fa-info-circle" style="margin-right:6px;" aria-hidden="true"></i>Visão geral e acesso rápido às funcionalidades do sistema</p>
                </div>
            </div>
        </header>

        <!-- Card informativo -->
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-chart-line" aria-hidden="true"></i>
            </div>
            <div class="info-card-content">
                <h3>Bem-vindo ao Dashboard</h3>
                <p>Aqui você visualiza as <strong>estatísticas principais</strong> do sistema e pode acessar rapidamente as funcionalidades de gestão.</p>
            </div>
        </div>

        <!-- Grid de KPIs -->
        <div class="dashboard-kpis-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-bar" style="margin-right:8px;" aria-hidden="true"></i>Estatísticas do Sistema</h2>
            </div>
            <div class="dashboard-kpis-grid" id="dashboard-kpis-grid">
                <!-- KPI Cards carregados via JS -->
                <div class="kpi-card kpi-loading">
                    <div class="spinner"></div>
                    <p class="mt-2">Carregando...</p>
                </div>
            </div>
        </div>

        <!-- Atalhos Rápidos -->
        <div class="dashboard-shortcuts-section">
            <div class="section-header">
                <h2><i class="fas fa-rocket" style="margin-right:8px;" aria-hidden="true"></i>Acesso Rápido</h2>
            </div>
            <div class="dashboard-shortcuts-grid">
                <!-- Usuários -->
                <a href="<?php echo get_permalink(get_page_by_path('usuarios')); ?>" class="shortcut-card">
                    <div class="shortcut-icon usuarios">
                        <i class="fas fa-users" aria-hidden="true"></i>
                    </div>
                    <div class="shortcut-content">
                        <h3>Gerenciar Usuários</h3>
                        <p>Criar, editar e organizar usuários</p>
                    </div>
                    <div class="shortcut-arrow">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </div>
                </a>

                <!-- Vídeos -->
                <a href="<?php echo get_permalink(get_page_by_path('criar-video')); ?>" class="shortcut-card">
                    <div class="shortcut-icon videos">
                        <i class="fas fa-video" aria-hidden="true"></i>
                    </div>
                    <div class="shortcut-content">
                        <h3>Gerenciar Vídeos</h3>
                        <p>Adicionar e organizar vídeos</p>
                    </div>
                    <div class="shortcut-arrow">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </div>
                </a>

                <!-- Categorias Admin -->
                <a href="<?php echo get_permalink(get_page_by_path('criar-categorias')); ?>" class="shortcut-card">
                    <div class="shortcut-icon categorias">
                        <i class="fas fa-folder-tree" aria-hidden="true"></i>
                    </div>
                    <div class="shortcut-content">
                        <h3>Gerenciar Categorias</h3>
                        <p>Criar e organizar categorias</p>
                    </div>
                    <div class="shortcut-arrow">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </div>
                </a>

                <!-- Categorias Públicas -->
                <a href="<?php echo get_permalink(get_page_by_path('categorias')); ?>" class="shortcut-card">
                    <div class="shortcut-icon visualizar">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </div>
                    <div class="shortcut-content">
                        <h3>Ver Categorias</h3>
                        <p>Visualizar categorias públicas</p>
                    </div>
                    <div class="shortcut-arrow">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- Alertas do Sistema -->
        <div class="dashboard-alerts-section">
            <div class="section-header">
                <h2><i class="fas fa-exclamation-triangle" style="margin-right:8px;" aria-hidden="true"></i>Alertas do Sistema</h2>
            </div>
            <div class="dashboard-alerts-grid" id="dashboard-alerts-grid">
                <!-- Alertas carregados via JS -->
                <div class="alert-card alert-loading">
                    <div class="spinner"></div>
                    <p class="mt-2">Carregando...</p>
                </div>
            </div>
        </div>

        <!-- Atividade Recente -->
        <div class="dashboard-activity-section">
            <div class="section-header">
                <h2><i class="fas fa-history" style="margin-right:8px;" aria-hidden="true"></i>Atividade Recente</h2>
            </div>
            <div class="dashboard-activity-grid">
                <!-- Últimos Usuários -->
                <div class="activity-card">
                    <div class="activity-card-header">
                        <div class="activity-icon usuarios">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                        </div>
                        <h3>Últimos Usuários</h3>
                    </div>
                    <div class="activity-list" id="recent-users-list">
                        <div class="activity-loading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Vídeos -->
                <div class="activity-card">
                    <div class="activity-card-header">
                        <div class="activity-icon videos">
                            <i class="fas fa-video" aria-hidden="true"></i>
                        </div>
                        <h3>Últimos Vídeos</h3>
                    </div>
                    <div class="activity-list" id="recent-videos-list">
                        <div class="activity-loading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>

                <!-- Últimas Categorias -->
                <div class="activity-card">
                    <div class="activity-card-header">
                        <div class="activity-icon categorias">
                            <i class="fas fa-folder-plus" aria-hidden="true"></i>
                        </div>
                        <h3>Últimas Categorias</h3>
                    </div>
                    <div class="activity-list" id="recent-categorias-list">
                        <div class="activity-loading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Distribuição -->
        <div class="dashboard-chart-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-pie" style="margin-right:8px;" aria-hidden="true"></i>Distribuição de Vídeos por Categoria</h2>
            </div>
            <div class="chart-card">
                <div class="chart-container" id="chart-container">
                    <div class="chart-loading">
                        <div class="spinner"></div>
                        <p class="mt-2">Carregando gráfico...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
	        <div class="rodape">
            <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/RODAPE-1.png">
        </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?php get_footer(); ?>