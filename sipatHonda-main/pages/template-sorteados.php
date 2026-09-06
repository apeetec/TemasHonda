<?php
/**
 * Template Name: Ganhadores e Sorteados
 * 
 * Exibe os brindes sorteados e os colaboradores contemplados na SIPAT
 */

get_header();

// Carrega os sorteios realizados
$sorteios = sipat_get_all_sorteios_list();
$all_dates = sipat_get_all_event_dates();

// Calcula totais
$total_sorteios = count($sorteios);
$total_ganhadores = 0;
foreach ($sorteios as $s) {
    $total_ganhadores += count($s['ganhadores']);
}
?>

<!-- Biblioteca Canvas Confetti para celebração -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<div class="sipat-winners-page">
    
    <!-- Hero Section Comemorativo -->
    <section class="winners-hero">
        <div class="container hero-container">
            <div class="hero-badge">
                <i class="fa-solid fa-trophy"></i> SIPAT HONDA • PREMIAÇÕES
            </div>
            <h1 class="hero-title">🎉 Galeria de Ganhadores & Sorteios</h1>
            <p class="hero-subtitle">
                Parabéns a todos os colaboradores que participaram, gabaritaram os questionários diários e foram contemplados nos nossos sorteios exclusivos por Grupo e Unidade!
            </p>
            
            <!-- Cards de Estatísticas -->
            <div class="hero-stats">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_sorteios; ?></span>
                    <span class="stat-label"><i class="fa-solid fa-gift"></i> Sorteios Realizados</span>
                </div>
                <div class="stat-box highlight">
                    <span class="stat-number"><?php echo $total_ganhadores; ?></span>
                    <span class="stat-label"><i class="fa-solid fa-users"></i> Colaboradores Premiados</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo count($all_dates); ?></span>
                    <span class="stat-label"><i class="fa-solid fa-calendar-check"></i> Dias de Palestra</span>
                </div>
            </div>
        </div>
    </section>

    <div class="container winners-content">
        
        <!-- Barra de Filtros por Data (Interativa via JavaScript) -->
        <div class="filters-container">
            <div class="filters-title">
                <i class="fa-solid fa-filter"></i> Filtrar por Dia da SIPAT:
            </div>
            <div class="filters-buttons" id="filtersNav">
                <button type="button" class="filter-pill active" data-filter="todos">
                    <i class="fa-solid fa-border-all"></i> Todos os Sorteios (<?php echo $total_sorteios; ?>)
                </button>
                <button type="button" class="filter-pill special" data-filter="todas">
                    🌟 Semana Completa
                </button>
                <?php foreach ($all_dates as $d): ?>
                    <button type="button" class="filter-pill" data-filter="<?php echo esc_attr($d['slug']); ?>">
                        📅 <?php echo esc_html($d['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grid de Cards de Sorteios -->
        <?php if (empty($sorteios)): ?>
            <div class="empty-state-card">
                <div class="empty-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <h2>Nenhum sorteio divulgado ainda!</h2>
                <p>Os sorteios são realizados diariamente após as palestras com todos os colaboradores que acertaram 100% das questões. Fique atento!</p>
            </div>
        <?php else: ?>
            <div class="prizes-grid" id="prizesGrid">
                <?php foreach ($sorteios as $sorteio): 
                    $date_slug = !empty($sorteio['date_slug']) ? $sorteio['date_slug'] : 'todas';
                    $data_formatada = date('d/m/Y', strtotime($sorteio['data_hora']));
                    $nome_grupo = !empty($sorteio['nome_grupo']) ? $sorteio['nome_grupo'] : 'Grupo Geral';
                ?>
                    <article class="prize-card" data-date="<?php echo esc_attr($date_slug); ?>">
                        
                        <!-- Topo do Card com Badge da Data -->
                        <div class="prize-card-header">
                            <span class="prize-date-badge <?php echo $date_slug === 'todas' ? 'badge-special' : ''; ?>">
                                <i class="fa-solid fa-calendar-day"></i> <?php echo esc_html($sorteio['date_name']); ?>
                            </span>
                            <span class="prize-count-badge">
                                <i class="fa-solid fa-user-check"></i> <?php echo count($sorteio['ganhadores']); ?> Ganhador(es)
                            </span>
                        </div>

                        <!-- Banner com ÊNFASE MÁXIMA NO NOME DO GRUPO -->
                        <div class="prize-group-highlight-banner">
                            <div class="group-banner-icon">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div class="group-banner-text">
                                <span class="group-banner-label">GRUPO CONTEMPLADO</span>
                                <h3 class="group-banner-name"><?php echo esc_html($nome_grupo); ?></h3>
                            </div>
                        </div>

                        <!-- Foto do Brinde -->
                        <div class="prize-image-wrapper">
                            <?php if (!empty($sorteio['img_url'])): ?>
                                <img src="<?php echo esc_url($sorteio['img_url']); ?>" alt="<?php echo esc_attr($sorteio['premio_nome']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="prize-no-img">
                                    <i class="fa-solid fa-gift"></i>
                                    <span>Brinde Oficial Honda</span>
                                </div>
                            <?php endif; ?>
                            <div class="prize-badge-ribbon">
                                <i class="fa-solid fa-star"></i> PREMIADO
                            </div>
                        </div>

                        <!-- Detalhes do Prêmio -->
                        <div class="prize-details">
                            <h2 class="prize-title"><?php echo esc_html($sorteio['premio_nome']); ?></h2>
                            
                            <!-- Lista de Ganhadores -->
                            <div class="winners-section">
                                <div class="winners-section-title">
                                    <i class="fa-solid fa-medal text-gold"></i> Colaboradores Sorteados:
                                </div>
                                <div class="winners-pill-list">
                                    <?php foreach ($sorteio['ganhadores'] as $idx => $g): ?>
                                        <div class="winner-pill-item">
                                            <div class="winner-avatar">
                                                <?php echo ($idx === 0) ? '🥇' : (($idx === 1) ? '🥈' : (($idx === 2) ? '🥉' : '🎖️')); ?>
                                            </div>
                                            <div class="winner-info">
                                                <strong class="winner-name"><?php echo esc_html($g['nome']); ?></strong>
                                                <div class="winner-meta">
                                                    <span class="winner-mat">Mat: <?php echo esc_html($g['matricula']); ?></span>
                                                    <span class="winner-group-pill"><i class="fa-solid fa-layer-group"></i> <?php echo esc_html($nome_grupo); ?></span>
                                                    <span class="winner-unit-pill"><i class="fa-solid fa-location-dot"></i> <?php echo esc_html($g['unidade']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="prize-footer">
                                <span><i class="fa-solid fa-clock"></i> Sorteio: <?php echo esc_html($data_formatada); ?></span>
                                <button type="button" class="btn-celebrate" onclick="celebrateSingleCard(this)" title="Comemorar!">
                                    🎉 Celebrar
                                </button>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Estado de busca vazia nos filtros -->
            <div id="noFilterResults" class="empty-state-card" style="display: none;">
                <div class="empty-icon"><i class="fa-solid fa-filter-circle-xmark"></i></div>
                <h2>Nenhum sorteio encontrado para este dia</h2>
                <p>Ainda não há sorteios cadastrados para o dia selecionado. Escolha outro dia acima.</p>
                <div style="margin-top: 20px;">
                    <button type="button" class="filter-pill active" onclick="resetFilters()">
                        <i class="fa-solid fa-arrow-left"></i> Ver Todos os Sorteios
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
/* ==========================================================================
   ESTILOS PREMIUM - PÁGINA PÚBLICA DE GANHADORES SIPAT
   ========================================================================== */

.sipat-winners-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background-color: #f1f5f9;
    min-height: 100vh;
    padding-bottom: 60px;
    color: #1e293b;
}

/* Hero */
.winners-hero {
    background: linear-gradient(135deg, #028056 0%, #014730 100%);
    color: #ffffff;
    padding: 60px 20px 70px;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(2, 128, 86, 0.2);
}

.winners-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 10%, transparent 60%);
    pointer-events: none;
}

.hero-container {
    max-width: 900px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.18);
    padding: 6px 18px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 16px;
    backdrop-filter: blur(6px);
}

.hero-title {
    font-size: 2.8rem;
    font-weight: 900;
    color: #ffffff;
    margin: 0 0 14px 0;
    letter-spacing: -0.5px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.15rem;
    color: #d1fae5;
    line-height: 1.6;
    margin: 0 auto 35px;
    max-width: 720px;
}

/* Stats */
.hero-stats {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-box {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 16px 28px;
    border-radius: 14px;
    backdrop-filter: blur(8px);
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 170px;
    transition: transform 0.2s ease;
}

.stat-box:hover {
    transform: translateY(-3px);
    background: rgba(255, 255, 255, 0.18);
}

.stat-box.highlight {
    background: rgba(245, 158, 11, 0.25);
    border-color: rgba(245, 158, 11, 0.5);
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 6px;
}

.stat-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #d1fae5;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Conteúdo */
.winners-content {
    max-width: 1200px;
    margin: -30px auto 0;
    position: relative;
    z-index: 3;
    padding: 0 15px;
}

/* Filtros */
.filters-container {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.06);
    margin-bottom: 35px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.filters-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filters-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    flex: 1;
}

.filter-pill {
    background: #f1f5f9;
    border: 1.5px solid #e2e8f0;
    color: #475569;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.filter-pill:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-1px);
}

.filter-pill.active {
    background: #028056;
    color: #ffffff !important;
    border-color: #028056;
    box-shadow: 0 4px 12px rgba(2, 128, 86, 0.3);
}

.filter-pill.special {
    border-color: #f59e0b;
    color: #b45309;
    background: #fef3c7;
}

.filter-pill.special.active {
    background: #f59e0b;
    color: #ffffff !important;
    border-color: #f59e0b;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Grid de Prêmios */
.prizes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 28px;
}

@media (max-width: 768px) {
    .prizes-grid {
        grid-template-columns: 1fr;
    }
}

/* Card do Prêmio */
.prize-card {
    background: #ffffff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    flex-direction: column;
}

.prize-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.12);
}

.prize-card-header {
    padding: 12px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.prize-date-badge {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.prize-date-badge.badge-special {
    background: #fef3c7;
    color: #b45309;
    border-color: #fde68a;
}

.prize-count-badge {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 600;
}

/* BANNER DE DESTAQUE DO NOME DO GRUPO (ÊNFASE MÁXIMA) */
.prize-group-highlight-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-left: 5px solid #10b981;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.2);
}

.group-banner-icon {
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid rgba(16, 185, 129, 0.4);
    color: #34d399;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.group-banner-text {
    display: flex;
    flex-direction: column;
}

.group-banner-label {
    font-size: 0.7rem;
    letter-spacing: 1px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
}

.group-banner-name {
    font-size: 1.15rem;
    font-weight: 900;
    color: #34d399;
    margin: 2px 0 0 0;
    line-height: 1.2;
    letter-spacing: -0.3px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.3);
}

/* Imagem */
.prize-image-wrapper {
    height: 220px;
    background: #e2e8f0;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.prize-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.prize-card:hover .prize-image-wrapper img {
    transform: scale(1.05);
}

.prize-no-img {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #94a3b8;
}

.prize-no-img i {
    font-size: 52px;
    color: #cbd5e1;
}

.prize-badge-ribbon {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    padding: 4px 10px;
    border-radius: 6px;
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.4);
}

/* Detalhes */
.prize-details {
    padding: 22px;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.prize-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 16px 0;
    line-height: 1.3;
}

/* Vencedores */
.winners-section {
    flex: 1;
    margin-bottom: 20px;
}

.winners-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.winners-pill-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.winner-pill-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: background 0.15s ease;
}

.winner-pill-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.winner-avatar {
    font-size: 1.6rem;
    line-height: 1;
}

.winner-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.winner-name {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 4px;
}

.winner-meta {
    display: flex;
    gap: 8px;
    font-size: 0.78rem;
    color: #64748b;
    flex-wrap: wrap;
    align-items: center;
}

.winner-mat {
    font-weight: 700;
    background: #e2e8f0;
    color: #334155;
    padding: 2px 6px;
    border-radius: 4px;
}

.winner-group-pill {
    background: #ecfdf5;
    color: #047857;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid #a7f3d0;
}

.winner-unit-pill {
    color: #64748b;
    font-weight: 600;
}

/* Footer do Card */
.prize-footer {
    border-top: 1px solid #f1f5f9;
    padding-top: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #94a3b8;
}

.btn-celebrate {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-celebrate:hover {
    background: #028056;
    color: #ffffff;
    border-color: #028056;
    transform: scale(1.05);
}

/* Empty State */
.empty-state-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 60px 20px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.05);
    max-width: 600px;
    margin: 40px auto;
}

.empty-icon i {
    font-size: 54px;
    color: #94a3b8;
    margin-bottom: 16px;
}

.empty-state-card h2 {
    font-size: 1.4rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px 0;
}

.empty-state-card p {
    color: #64748b;
    font-size: 0.95rem;
    margin: 0;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .filters-container {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
/**
 * Lógica do Frontend - Filtros Interativos Client-Side & Efeito de Confetes
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Dispara celebração automática de confetes suave ao abrir a página
    if (typeof confetti === 'function') {
        setTimeout(function() {
            confetti({
                particleCount: 60,
                spread: 70,
                origin: { y: 0.4 }
            });
        }, 600);
    }

    // Filtros de Data
    const filterButtons = document.querySelectorAll('#filtersNav .filter-pill');
    const prizeCards = document.querySelectorAll('.prize-card');
    const noResults = document.getElementById('noFilterResults');

    function applyFilter(selectedDate) {
        let visibleCount = 0;

        filterButtons.forEach(b => {
            if (b.dataset.filter === selectedDate) {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });

        prizeCards.forEach(card => {
            const cardDate = card.dataset.date;
            if (selectedDate === 'todos' || cardDate === selectedDate) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) {
            noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedDate = this.dataset.filter;
            applyFilter(selectedDate);
        });
    });

    window.resetFilters = function() {
        applyFilter('todos');
    };

});

// Função de celebrar ao clicar no botão individual
window.celebrateSingleCard = function(btn) {
    if (typeof confetti === 'function') {
        const rect = btn.getBoundingClientRect();
        const x = (rect.left + rect.width / 2) / window.innerWidth;
        const y = (rect.top + rect.height / 2) / window.innerHeight;

        confetti({
            particleCount: 45,
            spread: 60,
            origin: { x: x, y: y }
        });
    }
};
</script>

<?php get_footer(); ?>
