<?php

/**
 * Template Name: Categorias de Vídeos
 *
 * Página pública que exibe categorias em cards. Usuários comuns apenas visualizam; administradores têm botões adicionais.
 */

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
		<!-- Header melhorado com ícones -->
		<header class="dashboard-header categorias-header">
			<div class="header-content">
				<div class="header-icon">
					<i class="fas fa-folder-open" aria-hidden="true"></i>
				</div>
				<div>
					<h1><i class="fas fa-video" style="margin-right:12px; color:#1976d2;" aria-hidden="true"></i>Vídeos</h1>
					<p class="text-muted"><i class="fas fa-info-circle" style="margin-right:6px;" aria-hidden="true"></i>Explore e navegue e clique nos cards para que consiga ver os vídeos disponíveis.</p>
				</div>
			</div>
			<div class="dashboard-actions">
				<div class="search-box-wrapper">
					<i class="fas fa-search search-icon" aria-hidden="true"></i>
					<input type="text" id="search-categorias" placeholder="Buscar categorias..." class="search-input-enhanced">
				</div>
			</div>
		</header>

		<!-- Card de informação/ajuda -->
		<div class="info-card">
			<div class="info-card-icon">
				<i class="fas fa-lightbulb" aria-hidden="true"></i>
			</div>
			<div class="info-card-content">
				<h3>Como usar</h3>
				<p>Clique no botão <strong>"Ver"</strong> em qualquer card para acessar os vídeos daquela categoria. Use a busca acima para filtrar rapidamente.</p>
			</div>
		</div>

		<!-- Controles de Seleção (Admin) -->
		<?php if (current_user_can('administrator') || current_user_can('editor')) : ?>
		<div id="batch-controls-bar" class="batch-controls-bar hidden">
			<div class="batch-controls-content">
				<div class="batch-info">
					<i class="fas fa-check-circle" aria-hidden="true"></i>
					<span id="count-selected-info"><strong id="count-selected-cards">0</strong> categoria(s) selecionada(s)</span>
				</div>
				<div class="batch-actions">
					<button id="btn-select-all-cards" class="btn-secondary">
						<i class="fas fa-check-double" aria-hidden="true"></i>
						<span>Selecionar Todos</span>
					</button>
					<button id="btn-deselect-all-cards" class="btn-secondary hidden">
						<i class="fas fa-times-circle" aria-hidden="true"></i>
						<span>Desmarcar Todos</span>
					</button>
					<button id="btn-delete-selected-cards" class="btn-danger hidden">
						<i class="fas fa-trash-alt" aria-hidden="true"></i>
						<span>Excluir Selecionadas</span>
					</button>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Grid de cards -->
		<div class="categorias-grid-section">
			<div class="section-header">
				<h2><i class="fas fa-th-large" style="margin-right:8px;" aria-hidden="true"></i>Todas as Categorias</h2>
				<span class="badge-count" id="categorias-count">0</span>
			</div>
			<div id="categorias-cards-grid" class="cards-grid-enhanced"></div>
		</div>
	</div>
	        <div class="rodape">
            <img src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/RODAPE-1.png">
        </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
	// Pequeno fallback: expõe se o usuário atual é admin para o JS
	var WP_CURRENT_USER_IS_ADMIN = <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>;
</script>

<?php get_footer(); ?>