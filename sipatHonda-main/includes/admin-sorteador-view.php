<?php
/**
 * Interface Administrativa do Sorteador SIPAT
 */

if (!defined('ABSPATH')) {
    exit;
}

$all_dates = sipat_get_all_event_dates();
$all_unidades = sipat_get_all_unidades();
$sorteios_historico = sipat_get_all_sorteios_list();
$suggested_groups = sipat_get_previously_used_groups();
?>

<div class="wrap sipat-sorteador-wrap">
    <div class="sipat-admin-header">
        <div class="header-content">
            <div class="header-icon">
                <i class="fa-solid fa-gift"></i>
            </div>
            <div class="header-text">
                <h1>🎰 Sorteador Oficial - SIPAT Honda</h1>
                <p>Sorteie colaboradores que gabaritaram 100% das perguntas por data, unidade e grupo de forma justa, animada e transparente.</p>
            </div>
        </div>
        <div class="header-badges">
            <span class="badge-item"><i class="fa-solid fa-calendar-check"></i> <?php echo count($all_dates); ?> Datas Ativas</span>
            <span class="badge-item"><i class="fa-solid fa-building"></i> <?php echo count($all_unidades); ?> Unidades</span>
            <span class="badge-item"><i class="fa-solid fa-trophy"></i> <?php echo count($sorteios_historico); ?> Sorteios Realizados</span>
        </div>
    </div>

    <!-- Navegação de Abas -->
    <div class="sipat-nav-tabs">
        <button class="tab-btn active" data-tab="tab-novo-sorteio">
            <i class="fa-solid fa-dice"></i> Realizar Novo Sorteio
        </button>
        <button class="tab-btn" data-tab="tab-historico">
            <i class="fa-solid fa-clock-rotate-left"></i> Histórico de Sorteios (<?php echo count($sorteios_historico); ?>)
        </button>
    </div>

    <!-- ABA 1: NOVO SORTEIO -->
    <div id="tab-novo-sorteio" class="sipat-tab-content active">
        <div class="sipat-draw-grid">
            
            <!-- Coluna 1: Formulário de Configuração -->
            <div class="sipat-panel config-panel">
                <div class="panel-header">
                    <h2><i class="fa-solid fa-sliders"></i> 1. Configurar Sorteio</h2>
                    <span class="panel-subtitle">Defina o brinde, data, grupo e unidades participantes</span>
                </div>

                <form id="formSorteioConfig" onsubmit="return false;">
                    
                    <!-- Nome do Prêmio -->
                    <div class="form-group">
                        <label for="premioNome">
                            <i class="fa-solid fa-award"></i> Nome do Brinde / Prêmio <span class="required">*</span>
                        </label>
                        <input type="text" id="premioNome" class="sipat-input" placeholder="Ex: Mochila Executiva Honda, Caixa JBL, Alexa..." required>
                    </div>

                    <!-- Nome do Grupo (Autonomia para Agrupar Unidades) -->
                    <div class="form-group group-config-box">
                        <div class="label-row">
                            <label for="nomeGrupo">
                                <i class="fa-solid fa-layer-group"></i> Nome do Grupo / Agrupamento de Unidades <span class="required">*</span>
                            </label>
                            <span class="label-hint" title="Permite agrupar unidades específicas sob um nome oficial de sorteio">
                                <i class="fa-solid fa-circle-info"></i> Autonomia de Grupos
                            </span>
                        </div>
                        <input type="text" id="nomeGrupo" class="sipat-input" list="groupSuggestionsList" placeholder="Ex: Grupo Produção, Grupo Administrativo, Grupo Matriz..." value="Grupo Geral">
                        <datalist id="groupSuggestionsList">
                            <?php foreach ($suggested_groups as $grp): ?>
                                <option value="<?php echo esc_attr($grp); ?>">
                            <?php endforeach; ?>
                        </datalist>

                        <div class="quick-group-chips">
                            <span class="group-chip-label">Sugestões rápidas:</span>
                            <?php foreach (array_slice($suggested_groups, 0, 4) as $s_grp): ?>
                                <span class="group-chip" onclick="setGroupName('<?php echo esc_js($s_grp); ?>')"><?php echo esc_html($s_grp); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Foto do Brinde -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-image"></i> Foto do Brinde</label>
                        <div class="prize-image-uploader">
                            <div id="prizeImagePreview" class="image-preview" style="display: none;">
                                <img id="previewImg" src="" alt="Prévia do Brinde">
                                <button type="button" id="btnRemoveImage" class="btn-remove-img" title="Remover Imagem">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                            <div id="uploadPlaceholder" class="upload-placeholder">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Nenhuma foto selecionada</span>
                                <button type="button" id="btnUploadPrizeImage" class="btn-select-media">
                                    <i class="fa-solid fa-photo-film"></i> Escolher / Enviar Foto
                                </button>
                            </div>
                            <input type="hidden" id="premioImagemId" value="">
                            <input type="hidden" id="premioImagemUrl" value="">
                        </div>
                    </div>

                    <!-- Seleção da Data -->
                    <div class="form-group">
                        <label for="selectDate">
                            <i class="fa-solid fa-calendar-day"></i> Data da SIPAT (100% de Acertos) <span class="required">*</span>
                        </label>
                        <select id="selectDate" class="sipat-select" required>
                            <option value="">-- Selecione a data --</option>
                            <option value="todas" data-name="Semana Completa (Gabaritou Todas)">🌟 SEMANA COMPLETA (Gabaritou Todas as Datas)</option>
                            <?php foreach ($all_dates as $d): ?>
                                <option value="<?php echo esc_attr($d['slug']); ?>" data-name="<?php echo esc_attr($d['name']); ?>">
                                    📅 <?php echo esc_html($d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Seleção Múltipla de Unidades do Grupo -->
                    <div class="form-group">
                        <div class="label-row">
                            <label><i class="fa-solid fa-building-user"></i> Unidades Participantes deste Grupo</label>
                            <div class="unit-toggles">
                                <a href="javascript:void(0)" id="btnSelectAllUnits">Marcar Todas</a> | 
                                <a href="javascript:void(0)" id="btnUnselectAllUnits">Desmarcar</a>
                            </div>
                        </div>
                        <div class="units-checkbox-grid">
                            <label class="unit-checkbox-label special">
                                <input type="checkbox" name="sipat_units[]" value="todas" id="chkAllUnits" checked>
                                <span class="custom-checkbox"></span>
                                <strong>Todas as Unidades</strong>
                            </label>
                            <?php foreach ($all_unidades as $u): ?>
                                <label class="unit-checkbox-label">
                                    <input type="checkbox" name="sipat_units[]" value="<?php echo esc_attr($u['slug']); ?>" class="chk-unit">
                                    <span class="custom-checkbox"></span>
                                    <span><?php echo esc_html($u['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quantidade de Ganhadores -->
                    <div class="form-group">
                        <label for="qtdGanhadores">
                            <i class="fa-solid fa-users-viewfinder"></i> Quantidade de Sorteados nesta rodada
                        </label>
                        <div class="number-stepper">
                            <button type="button" class="btn-step" onclick="adjustQty(-1)"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" id="qtdGanhadores" class="sipat-input-number" value="1" min="1" max="100">
                            <button type="button" class="btn-step" onclick="adjustQty(1)"><i class="fa-solid fa-plus"></i></button>
                            <div class="quick-qty-chips">
                                <span class="qty-chip" onclick="setQty(1)">1</span>
                                <span class="qty-chip" onclick="setQty(2)">2</span>
                                <span class="qty-chip" onclick="setQty(3)">3</span>
                                <span class="qty-chip" onclick="setQty(5)">5</span>
                                <span class="qty-chip" onclick="setQty(10)">10</span>
                            </div>
                        </div>
                    </div>

                    <!-- Opções Extras -->
                    <div class="form-group extra-options">
                        <label class="toggle-option">
                            <input type="checkbox" id="chkExcludeDrawn" checked>
                            <span class="toggle-switch"></span>
                            <span class="toggle-text">Excluir colaboradores que <strong>já ganharam</strong> brindes em sorteios anteriores</span>
                        </label>
                    </div>

                    <!-- Ações -->
                    <div class="form-actions">
                        <button type="button" id="btnBuscarElegiveis" class="btn-secondary">
                            <i class="fa-solid fa-magnifying-glass"></i> Atualizar Lista de Elegíveis
                        </button>
                        <button type="button" id="btnIniciarSorteio" class="btn-primary-draw">
                            <i class="fa-solid fa-dice"></i> SORTEAR AGORA!
                        </button>
                    </div>
                </form>
            </div>

            <!-- Coluna 2: Pool de Elegíveis em Tempo Real -->
            <div class="sipat-panel pool-panel">
                <div class="panel-header">
                    <h2><i class="fa-solid fa-users"></i> 2. Participantes Aptos</h2>
                    <span id="poolCountBadge" class="count-badge">0 elegíveis</span>
                </div>

                <div class="pool-search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="poolSearch" placeholder="Filtrar por matrícula, nome ou unidade..." onkeyup="filterPoolTable()">
                </div>

                <div class="pool-table-container">
                    <div id="poolLoading" class="pool-loading" style="display: none;">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <span>Buscando colaboradores com 100% de acertos...</span>
                    </div>

                    <div id="poolEmpty" class="pool-empty">
                        <i class="fa-solid fa-filter"></i>
                        <h3>Selecione uma data para carregar os participantes elegíveis</h3>
                        <p>Apenas colaboradores que acertaram 100% das perguntas na data escolhida aparecerão aqui.</p>
                    </div>

                    <table id="poolTable" class="sipat-table" style="display: none;">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nome do Colaborador</th>
                                <th>Unidade</th>
                                <th>Desempenho</th>
                            </tr>
                        </thead>
                        <tbody id="poolTableBody">
                            <!-- Inserido dinamicamente via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- ABA 2: HISTÓRICO DE SORTEIOS -->
    <div id="tab-historico" class="sipat-tab-content">
        <div class="sipat-panel history-panel">
            <div class="panel-header">
                <h2><i class="fa-solid fa-clock-rotate-left"></i> Histórico de Sorteios Realizados</h2>
                <span class="panel-subtitle">Todos os sorteios cadastrados são organizados na taxonomia oficial de sorteios</span>
            </div>

            <?php if (empty($sorteios_historico)): ?>
                <div class="history-empty">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>Nenhum sorteio realizado até o momento</h3>
                    <p>Realize seu primeiro sorteio na aba "Realizar Novo Sorteio".</p>
                </div>
            <?php else: ?>
                <div class="history-grid">
                    <?php foreach ($sorteios_historico as $sorteio): ?>
                        <div class="history-card" id="sorteio-card-<?php echo esc_attr($sorteio['id']); ?>">
                            <div class="card-media">
                                <?php if (!empty($sorteio['img_url'])): ?>
                                    <img src="<?php echo esc_url($sorteio['img_url']); ?>" alt="<?php echo esc_attr($sorteio['premio_nome']); ?>">
                                <?php else: ?>
                                    <div class="no-img-placeholder">
                                        <i class="fa-solid fa-gift"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="card-date-badge">
                                    <i class="fa-solid fa-calendar"></i> <?php echo esc_html($sorteio['date_name']); ?>
                                </span>
                                <?php if (!empty($sorteio['nome_grupo'])): ?>
                                    <span class="card-group-badge">
                                        <i class="fa-solid fa-layer-group"></i> <?php echo esc_html($sorteio['nome_grupo']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo esc_html($sorteio['premio_nome']); ?></h3>
                                <div class="card-meta">
                                    <span><i class="fa-solid fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($sorteio['data_hora'])); ?></span>
                                    <span><i class="fa-solid fa-users"></i> <?php echo count($sorteio['ganhadores']); ?> Ganhador(es)</span>
                                </div>

                                <div class="winners-list">
                                    <h4><i class="fa-solid fa-trophy text-gold"></i> Contemplados:</h4>
                                    <ul>
                                        <?php foreach ($sorteio['ganhadores'] as $g): ?>
                                            <li>
                                                <span class="winner-mat"><?php echo esc_html($g['matricula']); ?></span>
                                                <strong class="winner-name"><?php echo esc_html($g['nome']); ?></strong>
                                                <span class="winner-unit"><?php echo esc_html($g['unidade']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <div class="card-actions">
                                    <button type="button" class="btn-delete-sorteio" onclick="excluirSorteio(<?php echo esc_attr($sorteio['id']); ?>, '<?php echo esc_js($sorteio['premio_nome']); ?>')">
                                        <i class="fa-solid fa-trash-can"></i> Excluir Sorteio
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- MODAL DE SORTEIO AO VIVO (ROLETA & CELEBRAÇÃO) -->
<div id="modalSorteioVivo" class="sipat-modal-overlay" style="display: none;">
    <div class="sipat-modal-container">
        
        <div class="roulette-box" id="rouletteBox">
            <div class="roulette-header">
                <span class="roulette-tag">🎰 SORTEIO AO VIVO</span>
                <h2 id="roulettePrizeName">Sorteando...</h2>
                <div id="rouletteGroupNameBadge" class="roulette-group-badge"></div>
            </div>

            <!-- Animação de nomes girando -->
            <div class="roulette-display">
                <div class="roulette-pointer"></div>
                <div class="roulette-names-window">
                    <div id="rouletteNameText" class="roulette-name-anim">Preparando roleta...</div>
                    <div id="rouletteSubText" class="roulette-sub-anim">Honda SIPAT 2025</div>
                </div>
            </div>

            <div class="roulette-progress-bar">
                <div id="rouletteProgressFill" class="progress-fill-anim"></div>
            </div>
        </div>

        <!-- Tela de Revelação dos Vencedores -->
        <div class="winners-reveal-box" id="winnersRevealBox" style="display: none;">
            <div class="reveal-header">
                <div class="trophy-sparkle">🏆</div>
                <h2>PARABÉNS AOS GANHADORES!</h2>
                <p id="revealPrizeTitle" class="reveal-prize"></p>
                <div id="revealGroupTitle" class="reveal-group-tag"></div>
            </div>

            <div id="winnersCardsGrid" class="winners-cards-grid">
                <!-- Cards dos vencedores inseridos via JS -->
            </div>

            <div class="reveal-actions">
                <button type="button" class="btn-finish-draw" onclick="fecharModalSorteio(true)">
                    <i class="fa-solid fa-check-circle"></i> Concluir e Ver no Histórico
                </button>
            </div>
        </div>

    </div>
</div>

<style>
/* ==========================================================================
   ESTILOS PREMIUM - SORTEADOR SIPAT HONDA
   ========================================================================== */

.sipat-sorteador-wrap {
    margin: 20px 20px 0 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #1e293b;
}

/* Header */
.sipat-admin-header {
    background: linear-gradient(135deg, #028056 0%, #015237 100%);
    color: #fff;
    padding: 30px;
    border-radius: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 10px 25px rgba(2, 128, 86, 0.25);
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 20px;
}

.sipat-admin-header .header-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.sipat-admin-header .header-icon {
    font-size: 42px;
    background: rgba(255, 255, 255, 0.15);
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    backdrop-filter: blur(5px);
}

.sipat-admin-header h1 {
    color: #fff;
    margin: 0 0 6px 0;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.sipat-admin-header p {
    color: #d1fae5;
    margin: 0;
    font-size: 14px;
    max-width: 600px;
    line-height: 1.5;
}

.header-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.badge-item {
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 14px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
    backdrop-filter: blur(4px);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Tabs */
.sipat-nav-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 12px;
}

.sipat-nav-tabs .tab-btn {
    background: #fff;
    border: 1px solid #cbd5e1;
    color: #64748b;
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.sipat-nav-tabs .tab-btn:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
}

.sipat-nav-tabs .tab-btn.active {
    background: #028056;
    color: #fff;
    border-color: #028056;
    box-shadow: 0 4px 12px rgba(2, 128, 86, 0.3);
}

.sipat-tab-content {
    display: none;
}

.sipat-tab-content.active {
    display: block;
}

/* Grid Layout */
.sipat-draw-grid {
    display: grid;
    grid-template-columns: 490px 1fr;
    gap: 25px;
}

@media (max-width: 1200px) {
    .sipat-draw-grid {
        grid-template-columns: 1fr;
    }
}

/* Painéis */
.sipat-panel {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    padding: 24px;
}

.panel-header {
    margin-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.panel-subtitle {
    font-size: 12px;
    color: #64748b;
}

/* Formulário */
.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}

.form-group label .required {
    color: #ef4444;
}

.sipat-input, .sipat-select {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: #f8fafc;
}

.sipat-input:focus, .sipat-select:focus {
    border-color: #028056;
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(2, 128, 86, 0.15);
}

/* Group Config Box */
.group-config-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #028056;
    padding: 12px 14px;
    border-radius: 8px;
}

.label-hint {
    font-size: 11px;
    color: #028056;
    font-weight: 600;
    cursor: help;
}

.quick-group-chips {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.group-chip-label {
    font-size: 11px;
    color: #64748b;
}

.group-chip {
    background: #e2e8f0;
    color: #334155;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.group-chip:hover {
    background: #028056;
    color: #fff;
}

/* Image Uploader */
.prize-image-uploader {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 13px;
}

.upload-placeholder i {
    font-size: 28px;
    color: #94a3b8;
}

.btn-select-media {
    background: #0f172a;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
    margin-top: 4px;
}

.btn-select-media:hover {
    background: #1e293b;
}

.image-preview {
    position: relative;
    display: inline-block;
    max-width: 180px;
}

.image-preview img {
    max-width: 100%;
    max-height: 140px;
    border-radius: 8px;
    object-fit: cover;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.btn-remove-img {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ef4444;
    color: #fff;
    border: none;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Unidades Grid */
.label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.unit-toggles a {
    font-size: 11px;
    color: #028056;
    text-decoration: none;
    font-weight: 600;
}

.unit-toggles a:hover {
    text-decoration: underline;
}

.units-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 10px;
    border-radius: 8px;
    max-height: 140px;
    overflow-y: auto;
}

.unit-checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #334155;
    cursor: pointer;
    background: #fff;
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    transition: all 0.15s ease;
}

.unit-checkbox-label:hover {
    border-color: #028056;
}

.unit-checkbox-label.special {
    background: #ecfdf5;
    border-color: #a7f3d0;
    grid-column: 1 / -1;
}

/* Stepper de quantidade */
.number-stepper {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-step {
    background: #e2e8f0;
    border: none;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    color: #334155;
}

.btn-step:hover {
    background: #cbd5e1;
}

.sipat-input-number {
    width: 70px;
    text-align: center;
    font-size: 16px;
    font-weight: 700;
    padding: 8px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
}

.quick-qty-chips {
    display: flex;
    gap: 6px;
}

.qty-chip {
    background: #f1f5f9;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}

.qty-chip:hover {
    background: #028056;
    color: #fff;
}

/* Toggle Option */
.extra-options {
    background: #f8fafc;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.toggle-option {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 13px;
    color: #334155;
}

/* Botões de Ação */
.form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 25px;
}

.btn-secondary {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.btn-primary-draw {
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    color: #fff;
    border: none;
    padding: 16px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 800;
    letter-spacing: 0.5px;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-primary-draw:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
    background: linear-gradient(135deg, #059669 0%, #065f46 100%);
}

.btn-primary-draw:disabled {
    background: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    transform: none;
}

/* Pool Panel */
.count-badge {
    background: #ecfdf5;
    color: #047857;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    border: 1px solid #a7f3d0;
}

.pool-search-box {
    position: relative;
    margin-bottom: 15px;
}

.pool-search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.pool-search-box input {
    width: 100%;
    padding: 9px 12px 9px 36px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
}

.pool-table-container {
    max-height: 520px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.pool-empty, .pool-loading {
    padding: 40px 20px;
    text-align: center;
    color: #64748b;
}

.pool-empty i, .pool-loading i {
    font-size: 36px;
    color: #94a3b8;
    margin-bottom: 12px;
}

.pool-empty h3 {
    margin: 0 0 6px 0;
    font-size: 16px;
    color: #1e293b;
}

.sipat-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.sipat-table thead th {
    background: #f8fafc;
    padding: 10px 12px;
    text-align: left;
    font-weight: 700;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
}

.sipat-table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}

.sipat-table tbody tr:hover {
    background: #f8fafc;
}

.badge-100 {
    background: #dcfce7;
    color: #15803d;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

/* Histórico Cards */
.history-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.history-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    transition: transform 0.2s ease;
}

.history-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
}

.card-media {
    height: 180px;
    background: #f1f5f9;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-img-placeholder {
    font-size: 48px;
    color: #cbd5e1;
}

.card-date-badge {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(15, 23, 42, 0.85);
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    backdrop-filter: blur(4px);
}

.card-group-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(2, 128, 86, 0.9);
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    backdrop-filter: blur(4px);
}

.card-body {
    padding: 16px;
}

.card-title {
    margin: 0 0 8px 0;
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
}

.card-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
    margin-bottom: 14px;
}

.winners-list h4 {
    margin: 0 0 8px 0;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.winners-list ul {
    margin: 0;
    padding: 0;
    list-style: none;
    max-height: 140px;
    overflow-y: auto;
}

.winners-list li {
    padding: 6px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.winner-mat {
    background: #e2e8f0;
    color: #475569;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 11px;
}

.winner-name {
    flex: 1;
    color: #0f172a;
}

.winner-unit {
    color: #64748b;
    font-size: 11px;
}

.card-actions {
    margin-top: 14px;
    border-top: 1px solid #f1f5f9;
    padding-top: 12px;
    text-align: right;
}

.btn-delete-sorteio {
    background: transparent;
    color: #ef4444;
    border: 1px solid #fecaca;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-delete-sorteio:hover {
    background: #fee2e2;
    border-color: #ef4444;
}

/* ==========================================================================
   MODAL DE SORTEIO AO VIVO
   ========================================================================== */

.sipat-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(8px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.sipat-modal-container {
    background: #ffffff;
    border-radius: 20px;
    max-width: 680px;
    width: 100%;
    padding: 35px;
    text-align: center;
    box-shadow: 0 25px 60px rgba(0,0,0,0.4);
    animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes modalPop {
    from { opacity: 0; transform: scale(0.85); }
    to { opacity: 1; transform: scale(1); }
}

.roulette-tag {
    background: #fee2e2;
    color: #dc2626;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1px;
}

.roulette-header h2 {
    margin: 12px 0 6px 0;
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
}

.roulette-group-badge {
    display: inline-block;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 20px;
}

.roulette-display {
    background: #0f172a;
    color: #fff;
    border-radius: 16px;
    padding: 35px 20px;
    margin-bottom: 25px;
    box-shadow: inset 0 4px 15px rgba(0,0,0,0.5);
    border: 3px solid #028056;
    position: relative;
    overflow: hidden;
}

.roulette-pointer {
    width: 0; 
    height: 0; 
    border-left: 14px solid transparent;
    border-right: 14px solid transparent;
    border-top: 14px solid #10b981;
    position: absolute;
    top: 6px;
    left: 50%;
    transform: translateX(-50%);
}

.roulette-name-anim {
    font-size: 32px;
    font-weight: 900;
    color: #34d399;
    text-shadow: 0 0 20px rgba(52, 211, 153, 0.6);
    letter-spacing: 0.5px;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.roulette-sub-anim {
    font-size: 14px;
    color: #94a3b8;
    margin-top: 6px;
}

.roulette-progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill-anim {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #028056);
    width: 0%;
    transition: width 0.1s linear;
}

/* Revelação dos Vencedores */
.winners-reveal-box {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

.trophy-sparkle {
    font-size: 64px;
    margin-bottom: 10px;
    animation: bounce 1s infinite alternate;
}

@keyframes bounce {
    from { transform: scale(1); }
    to { transform: scale(1.15); }
}

.reveal-header h2 {
    color: #0f172a;
    font-size: 26px;
    font-weight: 900;
    margin: 0 0 6px 0;
}

.reveal-prize {
    font-size: 18px;
    font-weight: 700;
    color: #028056;
    margin: 0 0 6px 0;
}

.reveal-group-tag {
    display: inline-block;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 25px;
}

.winners-cards-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 25px;
}

.winner-card-item {
    background: #f8fafc;
    border: 2px solid #a7f3d0;
    border-radius: 12px;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.winner-info-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.winner-medal {
    font-size: 24px;
}

.winner-details h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    text-align: left;
}

.winner-details span {
    font-size: 12px;
    color: #64748b;
}

.winner-unit-badge {
    background: #028056;
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.btn-finish-draw {
    background: #0f172a;
    color: #fff;
    border: none;
    padding: 14px 30px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-finish-draw:hover {
    background: #028056;
    box-shadow: 0 6px 15px rgba(2, 128, 86, 0.4);
}
</style>

<script>
/**
 * Lógica JavaScript do Sorteador SIPAT
 */
(function() {
    let eligibleUsersCache = [];
    let isDrawing = false;

    document.addEventListener('DOMContentLoaded', function() {
        initTabs();
        initMediaUploader();
        initUnitCheckboxes();
        initEventTriggers();
    });

    // 1. Gerenciamento de Abas
    function initTabs() {
        const tabBtns = document.querySelectorAll('.sipat-nav-tabs .tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                tabBtns.forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.sipat-tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                const targetTab = document.getElementById(this.dataset.tab);
                if (targetTab) targetTab.classList.add('active');
            });
        });
    }

    // 2. Upload de Imagem do Brinde via WordPress Media
    function initMediaUploader() {
        const btnUpload = document.getElementById('btnUploadPrizeImage');
        const btnRemove = document.getElementById('btnRemoveImage');
        const previewBox = document.getElementById('prizeImagePreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const previewImg = document.getElementById('previewImg');
        const inputId = document.getElementById('premioImagemId');
        const inputUrl = document.getElementById('premioImagemUrl');

        if (!btnUpload) return;

        let mediaFrame;

        btnUpload.addEventListener('click', function(e) {
            e.preventDefault();

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: 'Selecione a Foto do Brinde',
                button: { text: 'Usar Esta Imagem' },
                multiple: false
            });

            mediaFrame.on('select', function() {
                const attachment = mediaFrame.state().get('selection').first().toJSON();
                const url = attachment.url;
                const id = attachment.id;

                inputId.value = id;
                inputUrl.value = url;
                previewImg.src = url;

                placeholder.style.display = 'none';
                previewBox.style.display = 'inline-block';
            });

            mediaFrame.open();
        });

        btnRemove.addEventListener('click', function() {
            inputId.value = '';
            inputUrl.value = '';
            previewImg.src = '';
            previewBox.style.display = 'none';
            placeholder.style.display = 'flex';
        });
    }

    // 3. Checkboxes de Unidades
    function initUnitCheckboxes() {
        const chkAll = document.getElementById('chkAllUnits');
        const unitCheckboxes = document.querySelectorAll('.chk-unit');
        const btnSelectAll = document.getElementById('btnSelectAllUnits');
        const btnUnselectAll = document.getElementById('btnUnselectAllUnits');

        if (!chkAll) return;

        chkAll.addEventListener('change', function() {
            if (this.checked) {
                unitCheckboxes.forEach(chk => { chk.checked = false; });
            }
            buscarElegiveis();
        });

        unitCheckboxes.forEach(chk => {
            chk.addEventListener('change', function() {
                if (this.checked) {
                    chkAll.checked = false;
                } else {
                    const anyChecked = Array.from(unitCheckboxes).some(c => c.checked);
                    if (!anyChecked) {
                        chkAll.checked = true;
                    }
                }
                buscarElegiveis();
            });
        });

        btnSelectAll.addEventListener('click', function() {
            chkAll.checked = true;
            unitCheckboxes.forEach(chk => { chk.checked = false; });
            buscarElegiveis();
        });

        btnUnselectAll.addEventListener('click', function() {
            chkAll.checked = false;
            unitCheckboxes.forEach(chk => { chk.checked = false; });
            buscarElegiveis();
        });
    }

    // 4. Gatilhos de Busca e Ação
    function initEventTriggers() {
        const selectDate = document.getElementById('selectDate');
        const chkExclude = document.getElementById('chkExcludeDrawn');
        const btnBuscar = document.getElementById('btnBuscarElegiveis');
        const btnSortear = document.getElementById('btnIniciarSorteio');

        selectDate.addEventListener('change', function() {
            buscarElegiveis();
        });

        chkExclude.addEventListener('change', function() {
            buscarElegiveis();
        });

        btnBuscar.addEventListener('click', function() {
            buscarElegiveis();
        });

        btnSortear.addEventListener('click', function() {
            executarSorteio();
        });
    }

    // 5. Helpers de Grupo e Quantidade
    window.setGroupName = function(name) {
        document.getElementById('nomeGrupo').value = name;
    };

    window.adjustQty = function(delta) {
        const input = document.getElementById('qtdGanhadores');
        let val = parseInt(input.value) || 1;
        val = Math.max(1, val + delta);
        input.value = val;
    };

    window.setQty = function(val) {
        document.getElementById('qtdGanhadores').value = val;
    };

    // 6. Obter unidades selecionadas
    function getSelectedUnits() {
        const chkAll = document.getElementById('chkAllUnits');
        if (chkAll && chkAll.checked) {
            return ['todas'];
        }
        const selected = [];
        document.querySelectorAll('.chk-unit:checked').forEach(chk => {
            selected.push(chk.value);
        });
        return selected.length > 0 ? selected : ['todas'];
    }

    // 7. Buscar Elegíveis via AJAX
    function buscarElegiveis() {
        const dateSlug = document.getElementById('selectDate').value;
        const countBadge = document.getElementById('poolCountBadge');
        const poolLoading = document.getElementById('poolLoading');
        const poolEmpty = document.getElementById('poolEmpty');
        const poolTable = document.getElementById('poolTable');
        const poolBody = document.getElementById('poolTableBody');

        if (!dateSlug) {
            poolEmpty.style.display = 'block';
            poolTable.style.display = 'none';
            poolLoading.style.display = 'none';
            countBadge.textContent = '0 elegíveis';
            eligibleUsersCache = [];
            return;
        }

        poolEmpty.style.display = 'none';
        poolTable.style.display = 'none';
        poolLoading.style.display = 'block';

        const units = getSelectedUnits();
        const excludeDrawn = document.getElementById('chkExcludeDrawn').checked;

        const formData = new FormData();
        formData.append('action', 'sipat_get_elegiveis');
        formData.append('nonce', sipatSorteadorData.nonce);
        formData.append('date_slug', dateSlug);
        formData.append('exclude_drawn', excludeDrawn);
        units.forEach(u => formData.append('units[]', u));

        fetch(sipatSorteadorData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            poolLoading.style.display = 'none';
            if (res.success) {
                eligibleUsersCache = res.data.usuarios || [];
                countBadge.textContent = res.data.total + ' elegíveis';

                if (eligibleUsersCache.length === 0) {
                    poolEmpty.innerHTML = '<i class="fa-solid fa-user-slash"></i><h3>Nenhum colaborador com 100% de acertos encontrado</h3><p>Tente alterar a data ou as unidades selecionadas.</p>';
                    poolEmpty.style.display = 'block';
                    poolTable.style.display = 'none';
                } else {
                    poolBody.innerHTML = '';
                    eligibleUsersCache.forEach(u => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><strong>${escapeHtml(u.matricula)}</strong></td>
                            <td>${escapeHtml(u.nome)}</td>
                            <td><span class="badge-unit">${escapeHtml(u.unidade)}</span></td>
                            <td><span class="badge-100"><i class="fa-solid fa-check-double"></i> 100% Acertos</span></td>
                        `;
                        poolBody.appendChild(tr);
                    });
                    poolEmpty.style.display = 'none';
                    poolTable.style.display = 'table';
                }
            } else {
                alert('Erro ao buscar elegíveis: ' + (res.data ? res.data.message : 'Erro desconhecido'));
            }
        })
        .catch(err => {
            poolLoading.style.display = 'none';
            console.error(err);
            alert('Erro de conexão ao buscar elegíveis.');
        });
    }

    // 8. Filtro da Tabela de Elegíveis
    window.filterPoolTable = function() {
        const query = document.getElementById('poolSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#poolTableBody tr');
        rows.forEach(r => {
            const text = r.textContent.toLowerCase();
            r.style.display = text.includes(query) ? '' : 'none';
        });
    };

    // 9. Execução do Sorteio com Roleta Animada
    function executarSorteio() {
        if (isDrawing) return;

        const premioNome = document.getElementById('premioNome').value.trim();
        const nomeGrupo = document.getElementById('nomeGrupo').value.trim();
        const selectDate = document.getElementById('selectDate');
        const dateSlug = selectDate.value;
        const dateName = selectDate.options[selectDate.selectedIndex].dataset.name || dateSlug;
        const qtdGanhadores = parseInt(document.getElementById('qtdGanhadores').value) || 1;
        const premioImgId = document.getElementById('premioImagemId').value;
        const premioImgUrl = document.getElementById('premioImagemUrl').value;
        const excludeDrawn = document.getElementById('chkExcludeDrawn').checked;
        const units = getSelectedUnits();

        if (!premioNome) {
            alert('⚠️ Por favor, informe o nome do brinde/prêmio!');
            document.getElementById('premioNome').focus();
            return;
        }

        if (!dateSlug) {
            alert('⚠️ Por favor, selecione a data do sorteio!');
            document.getElementById('selectDate').focus();
            return;
        }

        if (eligibleUsersCache.length === 0) {
            alert('⚠️ Não há participantes elegíveis (100% de acertos) para os filtros selecionados!');
            return;
        }

        if (qtdGanhadores > eligibleUsersCache.length) {
            if (!confirm(`Apenas ${eligibleUsersCache.length} participantes estão elegíveis. Deseja sortear todos os ${eligibleUsersCache.length}?`)) {
                return;
            }
        }

        isDrawing = true;

        // Abre o Modal da Roleta
        const modal = document.getElementById('modalSorteioVivo');
        const rouletteBox = document.getElementById('rouletteBox');
        const winnersReveal = document.getElementById('winnersRevealBox');
        const roulettePrize = document.getElementById('roulettePrizeName');
        const rouletteGroupBadge = document.getElementById('rouletteGroupNameBadge');
        const rouletteNameText = document.getElementById('rouletteNameText');
        const rouletteSubText = document.getElementById('rouletteSubText');
        const progressFill = document.getElementById('rouletteProgressFill');

        roulettePrize.textContent = premioNome;
        rouletteGroupBadge.innerHTML = `<i class="fa-solid fa-layer-group"></i> ${escapeHtml(nomeGrupo || 'Geral')}`;
        rouletteBox.style.display = 'block';
        winnersReveal.style.display = 'none';
        modal.style.display = 'flex';

        // Prepara dados do backend
        const formData = new FormData();
        formData.append('action', 'sipat_executar_sorteio');
        formData.append('nonce', sipatSorteadorData.nonce);
        formData.append('premio_nome', premioNome);
        formData.append('nome_grupo', nomeGrupo);
        formData.append('premio_imagem_id', premioImgId);
        formData.append('premio_imagem_url', premioImgUrl);
        formData.append('date_slug', dateSlug);
        formData.append('date_name', dateName);
        formData.append('qtd_ganhadores', qtdGanhadores);
        formData.append('exclude_drawn', excludeDrawn);
        units.forEach(u => formData.append('units[]', u));

        // Animação de Roleta Visual
        let progress = 0;
        let speed = 60; // ms
        let rouletteTimer;

        function spinRoulette() {
            const randomUser = eligibleUsersCache[Math.floor(Math.random() * eligibleUsersCache.length)];
            rouletteNameText.textContent = randomUser.nome;
            rouletteSubText.textContent = `Matrícula: ${randomUser.matricula} • ${randomUser.unidade}`;
        }

        rouletteTimer = setInterval(spinRoulette, speed);

        const progressTimer = setInterval(function() {
            progress += 2;
            progressFill.style.width = progress + '%';
            if (progress >= 100) {
                clearInterval(progressTimer);
            }
        }, 80);

        // Dispara requisição AJAX oficial no backend
        fetch(sipatSorteadorData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            setTimeout(function() {
                clearInterval(rouletteTimer);
                clearInterval(progressTimer);
                progressFill.style.width = '100%';

                if (res.success) {
                    mostrarVencedores(res.data);
                } else {
                    alert('Erro no sorteio: ' + (res.data ? res.data.message : 'Erro'));
                    modal.style.display = 'none';
                    isDrawing = false;
                }
            }, 4000); // 4 segundos de suspense emocionante
        })
        .catch(err => {
            clearInterval(rouletteTimer);
            clearInterval(progressTimer);
            console.error(err);
            alert('Erro de conexão ao realizar sorteio.');
            modal.style.display = 'none';
            isDrawing = false;
        });
    }

    // 10. Revelar Vencedores com Confetes
    function mostrarVencedores(data) {
        const rouletteBox = document.getElementById('rouletteBox');
        const winnersReveal = document.getElementById('winnersRevealBox');
        const revealPrizeTitle = document.getElementById('revealPrizeTitle');
        const revealGroupTitle = document.getElementById('revealGroupTitle');
        const winnersCardsGrid = document.getElementById('winnersCardsGrid');

        rouletteBox.style.display = 'none';
        winnersReveal.style.display = 'block';
        revealPrizeTitle.innerHTML = `<i class="fa-solid fa-gift"></i> ${escapeHtml(data.premio_nome)} <small>(${escapeHtml(data.date_name)})</small>`;
        
        if (data.nome_grupo) {
            revealGroupTitle.innerHTML = `<i class="fa-solid fa-layer-group"></i> ${escapeHtml(data.nome_grupo)}`;
            revealGroupTitle.style.display = 'inline-block';
        } else {
            revealGroupTitle.style.display = 'none';
        }

        winnersCardsGrid.innerHTML = '';
        data.ganhadores.forEach((w, idx) => {
            const card = document.createElement('div');
            card.className = 'winner-card-item';
            card.innerHTML = `
                <div class="winner-info-left">
                    <span class="winner-medal">${idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : '🎖️'}</span>
                    <div class="winner-details">
                        <h3>${escapeHtml(w.nome)}</h3>
                        <span>Matrícula: <strong>${escapeHtml(w.matricula)}</strong></span>
                    </div>
                </div>
                <div class="winner-unit-badge">${escapeHtml(w.unidade)}</div>
            `;
            winnersCardsGrid.appendChild(card);
        });

        // Dispara Chuva de Confetes
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 120,
                spread: 80,
                origin: { y: 0.6 }
            });
            setTimeout(function() {
                confetti({
                    particleCount: 80,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0 }
                });
                confetti({
                    particleCount: 80,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1 }
                });
            }, 300);
        }

        isDrawing = false;
    }

    // 11. Fechar Modal
    window.fecharModalSorteio = function(reload) {
        document.getElementById('modalSorteioVivo').style.display = 'none';
        if (reload) {
            window.location.reload();
        }
    };

    // 12. Excluir Sorteio
    window.excluirSorteio = function(sorteioId, premioNome) {
        if (!confirm(`Tem certeza que deseja excluir o sorteio "${premioNome}"? Esta ação removerá os ganhadores do histórico.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sipat_excluir_sorteio');
        formData.append('nonce', sipatSorteadorData.nonce);
        formData.append('sorteio_id', sorteioId);

        fetch(sipatSorteadorData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const card = document.getElementById('sorteio-card-' + sorteioId);
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => card.remove(), 300);
                }
            } else {
                alert('Erro ao excluir: ' + (res.data ? res.data.message : 'Erro'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão ao excluir sorteio.');
        });
    };

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

})();
</script>
