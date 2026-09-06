<?php

/**
 * Template Name: Usuários
 * 
 * Dashboard de gerenciamento de usuários - Apenas para administradores
 */

// Verificação de permissão
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url());
    exit;
}

get_header();

// Buscar todas as unidades (taxonomia)
$unidades = get_terms(array(
    'taxonomy' => 'unidades',
    'hide_empty' => false
));
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
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <div>
                    <h1><i class="fas fa-user-cog" style="margin-right:12px; color:#1976d2;" aria-hidden="true"></i>Gerenciamento de Usuários</h1>
                    <p class="text-muted"><i class="fas fa-info-circle" style="margin-right:6px;" aria-hidden="true"></i>Gerencie todos os usuários e suas permissões no sistema</p>
                </div>
            </div>
            <div class="dashboard-actions">
                <button type="button" class="btn-primary" id="btn-novo-usuario">
                    <i class="fas fa-plus" style="font-size:18px; margin-right:6px;" aria-hidden="true"></i>
                    Novo Usuário
                </button>
                <button type="button" class="btn-secondary" id="btn-select-all" title="Selecionar todos da página" style="margin-left:8px;">
                    <i class="fas fa-check-square" style="font-size:18px; margin-right:6px;" aria-hidden="true"></i>
                    Selecionar todos
                </button>
                <button type="button" class="btn-danger hidden" id="btn-excluir-selecionados">
                    <i class="fas fa-trash" style="font-size:16px; margin-right:6px;" aria-hidden="true"></i>
                    Excluir Selecionados (<span id="count-selected">0</span>)
                </button>
            </div>
        </header>

        <!-- Card informativo -->
        <div class="info-card">
            <div class="info-card-icon">
                <i class="fas fa-user-shield" aria-hidden="true"></i>
            </div>
            <div class="info-card-content">
                <h3>Central de Usuários</h3>
                <p>Visualize e gerencie todos os usuários cadastrados. Você pode <strong>criar</strong>, <strong>editar</strong>, <strong>excluir</strong> e filtrar usuários por unidade. Use a ordenação nas colunas para facilitar a navegação.</p>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div class="filters-container">
            <div class="filters-row">
                <div class="filter-group">
                    <label for="search-usuario"><i class="fas fa-search" style="margin-right:6px;"></i>Buscar por nome ou matrícula</label>
                    <div class="search-box-wrapper">
                        <i class="fas fa-search search-icon" aria-hidden="true"></i>
                        <input type="text" id="search-usuario" placeholder="Digite para buscar..." class="search-input-enhanced" style="width:100%;">
                    </div>
                </div>
                <div class="filter-group">
                    <label for="filter-unidade"><i class="fas fa-building" style="margin-right:6px;"></i>Filtrar por unidade</label>
                    <select id="filter-unidade">
                        <option value="">Todas as unidades</option>
                        <?php if (!empty($unidades) && !is_wp_error($unidades)) : ?>
                            <?php foreach ($unidades as $unidade) : ?>
                                <option value="<?php echo esc_attr($unidade->slug); ?>">
                                    <?php echo esc_html($unidade->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="per-page"><i class="fas fa-list-ol" style="margin-right:6px;"></i>Usuários por página</label>
                    <select id="per-page">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn-secondary" id="btn-limpar-filtros">
                        <i class="fas fa-eraser" style="margin-right:6px;"></i>Limpar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabela de Usuários -->
        <div class="table-container">
            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="select-all" title="Selecionar todos">
                            </th>
                            <th class="sortable" data-sort="matricula">Matrícula</th>
                            <th class="sortable" data-sort="nome">Nome</th>
                            <th class="sortable" data-sort="email">Email</th>
                            <th class="sortable" data-sort="unidade">Unidade</th>
                            <th class="sortable" data-sort="data">Data de Cadastro</th>
                            <th style="width: 120px; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <!-- Conteúdo carregado via AJAX -->
                        <tr>
                            <td colspan="7" class="loading-spinner">
                                <div class="spinner"></div>
                                <p class="mt-2">Carregando usuários...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <span id="showing-start">0</span> a <span id="showing-end">0</span> de <span id="total-users">0</span> usuários
                </div>
                <div class="pagination" id="pagination-controls">
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