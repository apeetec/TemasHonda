<?php
/*
 * Template Name: Criar categorias
 * Página administrativa para criar/editar/excluir categorias da taxonomia categoria_videos
 */

// Permissão: apenas administradores
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

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
        <!-- Header aprimorado com ícones -->
        <header class="dashboard-header admin-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-folder-tree" aria-hidden="true"></i>
                </div>
                <div>
                    <h1><i class="fas fa-tags" style="margin-right:12px; color:#1976d2;" aria-hidden="true"></i>Gerenciar Categorias de Vídeos</h1>
                    <p class="text-muted"><i class="fas fa-info-circle" style="margin-right:6px;" aria-hidden="true"></i>Organize e gerencie as categorias do sistema</p>
                </div>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="btn-primary" id="btn-nova-categoria">
                    <i class="fas fa-plus" style="font-size:18px; margin-right:6px;" aria-hidden="true"></i>
                    Nova Categoria
                </button>
                <button type="button" class="btn-secondary hidden" id="btn-select-all-categorias" style="margin-left:8px;">
                    <i class="fas fa-check-square" style="font-size:18px; margin-right:6px;" aria-hidden="true"></i>
                    Selecionar todos
                </button>
                <button type="button" class="btn-danger hidden" id="btn-excluir-categorias">
                    <i class="fas fa-trash" style="font-size:16px; margin-right:6px;" aria-hidden="true"></i>
                    Excluir Selecionadas (<span id="count-selected-categorias">0</span>)
                </button>
            </div>
        </header>

        <!-- Card informativo -->
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
            </div>
            <div class="info-card-content">
                <h3>Gerenciamento de Categorias</h3>
                <p>Crie categorias para organizar seus vídeos. Você pode <strong>editar</strong>, <strong>excluir</strong> ou selecionar múltiplas categorias para ações em lote. Use a busca para encontrar rapidamente.</p>
            </div>
        </div>

        <div class="filters-container">
            <div class="filters-row">
                <div class="filter-group" style="flex:1 1 400px;">
                    <label for="categoria-busca"><i class="fas fa-search" style="margin-right:6px;"></i>Buscar categorias</label>
                    <div class="search-box-wrapper">
                        <i class="fas fa-search search-icon" aria-hidden="true"></i>
                        <input type="text" id="categoria-busca" placeholder="Digite para filtrar..." class="search-input-enhanced">
                    </div>
                </div>
                <div class="filter-group" style="align-self:flex-end;">
                    <button type="button" class="btn-secondary" id="btn-recarregar-categorias">
                        <i class="fas fa-sync-alt" style="margin-right:6px;"></i>Recarregar
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">
                                <input type="checkbox" id="select-all-categorias" title="Selecionar todos">
                            </th>
                            <th>Nome</th>
                            <th>Slug</th>
                            <th>Descrição</th>
                            <th style="width:120px; text-align:center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="categorias-table-body">
                        <tr>
                            <td colspan="5" class="loading-spinner">
                                <div class="spinner"></div>
                                <p class="mt-2">Carregando categorias...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
	   <div class="rodape">
            <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/RODAPE-1.png">
        </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?php get_footer(); ?>