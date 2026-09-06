<?php

/**
 * Template Name: Criar Vídeo
 * Página administrativa para criar/editar/excluir vídeos - Apenas administradores
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
                    <i class="fas fa-play-circle" aria-hidden="true"></i>
                </div>
                <div>
                    <h1><i class="fas fa-video" style="margin-right:12px; color:#1976d2;" aria-hidden="true"></i>Gerenciar Vídeos</h1>
                    <p class="text-muted"><i class="fas fa-info-circle" style="margin-right:6px;" aria-hidden="true"></i>Adicione, edite e organize todo o conteúdo em vídeo</p>
                </div>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="btn-primary" id="btn-novo-video">
                    <i class="fas fa-plus" style="font-size:18px; margin-right:6px;" aria-hidden="true"></i>
                    Novo Vídeo
                </button>
                <button type="button" class="btn-secondary" id="btn-select-all-videos" style="margin-left:8px;">
                    <i class="fas fa-check-square" style="font-size:18px; margin-right:6px;" aria-hidden="true"></i>
                    Selecionar todos
                </button>
                <button type="button" class="btn-danger hidden" id="btn-excluir-videos">
                    <i class="fas fa-trash" style="font-size:16px; margin-right:6px;" aria-hidden="true"></i>
                    Excluir Selecionados (<span id="count-selected-videos">0</span>)
                </button>
            </div>
        </header>

        <!-- Card informativo -->
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-video" aria-hidden="true"></i>
            </div>
            <div class="info-card-content">
                <h3>Biblioteca de Vídeos</h3>
                <p>Gerencie todo o conteúdo em vídeo do sistema. Cada vídeo pode ter <strong>múltiplas seções</strong>, ser organizado por <strong>categoria</strong> e ter seus metadados editados. Use os filtros para localizar rapidamente.</p>
            </div>
        </div>

        <div class="filters-container">
            <div class="filters-row">
                <div class="filter-group">
                    <label for="search-video"><i class="fas fa-search" style="margin-right:6px;"></i>Buscar vídeos</label>
                    <div class="search-box-wrapper">
                        <i class="fas fa-search search-icon" aria-hidden="true"></i>
                        <input type="text" id="search-video" placeholder="Digite para buscar..." class="search-input-enhanced" style="width:100%;">
                    </div>
                </div>
                <div class="filter-group">
                    <label for="filter-categoria"><i class="fas fa-filter" style="margin-right:6px;"></i>Filtrar por categoria</label>
                    <select id="filter-categoria">
                        <option value="">Todas as categorias</option>
                        <?php
                        // Popular select diretamente no PHP para garantir que categorias apareçam
                        $categorias_videos = get_terms(array(
                            'taxonomy' => 'categoria_videos',
                            'hide_empty' => false,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        
                        if (!empty($categorias_videos) && !is_wp_error($categorias_videos)) {
                            foreach ($categorias_videos as $cat) {
                                echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="per-page-videos"><i class="fas fa-list-ol" style="margin-right:6px;"></i>Vídeos por página</label>
                    <select id="per-page-videos">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn-secondary" id="btn-limpar-filtros-videos">
                        <i class="fas fa-eraser" style="margin-right:6px;"></i>Limpar Filtros
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
                                <input type="checkbox" id="select-all-videos" title="Selecionar todos">
                            </th>
                            <th>Título</th>
                            <th>Categorias</th>
                            <th style="width:100px; text-align:center;">Seções</th>
                            <th>Data de Criação</th>
                            <th style="width:120px; text-align:center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="videos-table-body">
                        <tr>
                            <td colspan="6" class="loading-spinner">
                                <div class="spinner"></div>
                                <p class="mt-2">Carregando vídeos...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <span id="showing-start-videos">0</span> a <span id="showing-end-videos">0</span> de <span id="total-videos">0</span> vídeos
                </div>
                <div class="pagination" id="pagination-controls-videos">
                    <!-- Controles de paginação carregados via JS -->
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