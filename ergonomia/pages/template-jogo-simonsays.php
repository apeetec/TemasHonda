<?php
/**
 * ============================================================================
 * Template: Simon Says — Jogo de Memorização
 * ============================================================================
 *
 * Template Page do WordPress para o jogo Simon Says.
 *
 * Funcionalidades:
 * - Verificação de autenticação do usuário
 * - Controle de tentativas diárias (3 por dia, reset automático à meia-noite)
 * - Tabuleiro do jogo com 4 pads coloridos (verde, vermelho, amarelo, azul)
 * - Cronômetro em tempo real
 * - Banco de dados de ranking — Top 5 geral
 * - Integração AJAX para salvamento automático de dados
 *
 * Arquivo: pages/template-jogo-simonsays.php
 * Usado em: Painel WordPress → Páginas → Selecionar template "Simon Says"
 * ============================================================================
 */

/*
Template Name: Simon Says
*/

// Carrega o header padrão do tema
get_header();

// ============================================================================
// VERIFICAÇÃO DE AUTENTICAÇÃO
// ============================================================================
// O header.php já redireciona usuários não logados para a página de login.
// Esta verificação adicional garante segurança no template.
if (!is_user_logged_in()) {
    wp_redirect(home_url('login'));
    exit;
}

// ============================================================================
// DADOS DO USUÁRIO
// ============================================================================
$user_id = get_current_user_id();
$user_info = get_userdata($user_id);
$user_name = $user_info->first_name ? $user_info->first_name : $user_info->display_name;

// Dados de tentativas (com reset diário automático)
$hoje = date('Y-m-d');
$data_ultima = get_user_meta($user_id, 'simon_data_ultima_tentativa', true);
$tentativas_rest = get_user_meta($user_id, 'simon_tentativas_restantes_hoje', true);
$tentativas_usadas = intval(get_user_meta($user_id, 'numero_tentativas', true));

// Reset automático: se a data da última tentativa é diferente de hoje, reseta
if (empty($data_ultima) || $data_ultima !== $hoje) {
    $tentativas_rest = 3;
    $tentativas_usadas = 0;
    update_user_meta($user_id, 'simon_tentativas_restantes_hoje', 3);
    update_user_meta($user_id, 'simon_data_ultima_tentativa', $hoje);
    update_user_meta($user_id, 'numero_tentativas', 0);
}

// Se o valor não existir, assume 3
if ($tentativas_rest === '' || $tentativas_rest === false) {
    $tentativas_rest = 3;
}
$tentativas_rest = intval($tentativas_rest);

// Dados de ranking do usuário
$pontuacao_max = intval(get_user_meta($user_id, 'pontuacao_obtida', true));
$posicao_ranking = intval(get_user_meta($user_id, 'simon_posicao_ranking', true));

// ============================================================================
// RANKING TOP 5 (preparado no PHP, passado ao JS)
// ============================================================================
$ranking_top5 = function_exists('simon_get_top5_ranking') ? simon_get_top5_ranking() : array();

// ============================================================================
// ENFILEIRAR SCRIPTS E ESTILOS
// ============================================================================
// O script e os dados são registrados aqui no template para garantir
// que só são carregados nesta página específica.

// Registra e enfileira o JS do jogo
wp_enqueue_script(
    'simon-says-js',                                                    // Handle
    get_template_directory_uri() . '/js/simon-says.js',                 // Caminho do arquivo
    array(),                                                            // Dependências (nenhuma — vanilla JS)
    time(),                                                             // Versão (cache busting em dev)
    true                                                                // Carregar no footer
);

// Passa dados do PHP para o JavaScript via wp_localize_script
wp_localize_script('simon-says-js', 'simonSaysData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),                        // URL do endpoint AJAX do WordPress
    'nonce' => wp_create_nonce('simon_says_nonce'),                 // Nonce de segurança
    'userId' => $user_id,                                             // ID do usuário logado
    'userName' => $user_name,                                           // Nome do usuário
    'ranking' => $ranking_top5,                                        // Ranking Top 5 inicial
    'attemptsRemaining' => $tentativas_rest,                            // Tentativas restantes
    'attemptsUsed' => $tentativas_usadas,                          // Tentativas já usadas hoje
));
?>

<!-- ============================================================================ -->
<!-- SEÇÃO PRINCIPAL DO JOGO                                                      -->
<!-- ============================================================================ -->
<section class="simon-says-page">
    <div class="container">

        <!-- ================================================================ -->
        <!-- CABEÇALHO DA PÁGINA                                              -->
        <!-- ================================================================ -->
        <header class="simon-header">
            <h1 class="simon-title">🎮 Dinâmica</h1>
        </header>

        <!-- ================================================================ -->
        <!-- INSTRUÇÕES DE COMO JOGAR                                         -->
        <!-- ================================================================ -->
        <div id="simon-instructions" class="simon-instructions">
            <h3><i class="fa-solid fa-circle-info"></i> Instruções</h3>
            <ol>
                <li><strong>Observe</strong> — Vai iluminar uma sequência de cores. Preste atenção!</li>
                <li><strong>Repita</strong> — Quando for sua vez, clique nos botões na mesma ordem exibida.</li>
                <li><strong>Avance</strong> — A cada acerto, uma nova cor é adicionada à sequência.</li>
                <li><strong>Atenção</strong> — Se errar a sequência, a partida termina e sua pontuação é salva.</li>
            </ol>
            <p class="simon-rules-note">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Você tem <strong>3 tentativas por dia</strong>. As tentativas são renovadas automaticamente à
                meia-noite.
            </p>
        </div>

        <!-- ================================================================ -->
        <!-- PAINEL DE STATUS (Cronômetro, Tentativa, Pontuação)              -->
        <!-- ================================================================ -->
        <div class="simon-status-bar">
            <!-- Cronômetro -->
            <div class="simon-status-item">
                <span class="simon-status-icon">⏱️</span>
                <span class="simon-status-label">Tempo</span>
                <span id="simon-timer" class="simon-status-value">00:00</span>
            </div>
            <!-- Tentativa atual -->
            <div class="simon-status-item">
                <span class="simon-status-icon">🔁</span>
                <span class="simon-status-label">Tentativa</span>
                <span id="simon-attempt" class="simon-status-value"><?php echo $tentativas_usadas; ?> / 3</span>
            </div>
            <!-- Acertos (score) -->
            <div class="simon-status-item">
                <span class="simon-status-icon">✅</span>
                <span class="simon-status-label">Acertos</span>
                <span id="simon-score" class="simon-status-value">0</span>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- MENSAGEM DE STATUS (dinâmica via JS)                             -->
        <!-- ================================================================ -->
        <div id="simon-status-message" class="simon-status-message simon-status-info">
            Clique em "Iniciar" para começar!
        </div>

        <!-- ================================================================ -->
        <!-- BOTÃO DE INICIAR                                                 -->
        <!-- ================================================================ -->
        <?php if ($tentativas_rest > 0): ?>
            <div class="simon-start-container">
                <button id="simon-start-btn" class="simon-start-btn" type="button">
                    <i class="fa-solid fa-play"></i> Iniciar
                </button>
            </div>
        <?php endif; ?>

        <!-- ================================================================ -->
        <!-- TABULEIRO DO JOGO (4 pads circulares)                            -->
        <!-- ================================================================ -->
        <div class="simon-board-wrapper">
            <div id="simon-game-board" class="simon-game-board">
                <!-- Pad Verde (quadrante superior esquerdo) -->
                <div id="simon-pad-green" class="simon-pad simon-pad-green simon-pad-disabled" data-color="green"></div>
                <!-- Pad Vermelho (quadrante superior direito) -->
                <div id="simon-pad-red" class="simon-pad simon-pad-red simon-pad-disabled" data-color="red"></div>
                <!-- Pad Amarelo (quadrante inferior esquerdo) -->
                <div id="simon-pad-yellow" class="simon-pad simon-pad-yellow simon-pad-disabled" data-color="yellow">
                </div>
                <!-- Pad Azul (quadrante inferior direito) -->
                <div id="simon-pad-blue" class="simon-pad simon-pad-blue simon-pad-disabled" data-color="blue"></div>
                <!-- Centro do tabuleiro (exibe score em tempo real) -->
                <div class="simon-center-circle">
                    <span id="simon-center-score">0</span>
                </div>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- OVERLAY DE JOGO BLOQUEADO (tentativas esgotadas)                 -->
        <!-- ================================================================ -->
        <div id="simon-blocked-overlay" class="simon-blocked-overlay" <?php echo ($tentativas_rest > 0) ? 'style="display:none;"' : ''; ?>>
            <div class="simon-blocked-content">
                <i class="fa-solid fa-lock simon-blocked-icon"></i>
                <h3>Tentativas Esgotadas!</h3>
                <p>Você já utilizou suas <strong>3 tentativas</strong> de hoje.</p>
                <p>Volte amanhã para jogar novamente! As tentativas são renovadas à meia-noite.</p>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- INFORMAÇÕES PESSOAIS DO JOGADOR                                  -->
        <!-- ================================================================ -->
        <!-- <div class="simon-player-info">
            <p>
                <strong>Olá, <?php echo esc_html($user_name); ?>!</strong>
                <?php if ($pontuacao_max > 0): ?>
                    | Sua melhor pontuação: <strong><?php echo $pontuacao_max; ?></strong>
                    <?php if ($posicao_ranking > 0): ?>
                        | Posição no ranking: <strong>#<?php echo $posicao_ranking; ?></strong>
                    <?php endif; ?>
                <?php endif; ?>
                | Tentativas restantes hoje: <strong id="simon-attempts-remaining"><?php echo $tentativas_rest; ?></strong>
            </p>
        </div> -->

    </div><!-- /.container -->
</section>

<!-- ============================================================================ -->
<!-- MODAL DE GAME OVER                                                           -->
<!-- ============================================================================ -->
<div id="simon-gameover-modal" class="simon-gameover-modal">
    <div class="simon-gameover-content">
        <h2>🏁 Fim da Partida!</h2>
        <!-- Badge de novo recorde (oculto por padrão) -->
        <div id="simon-new-record" class="simon-new-record" style="display:none;">
            🌟 Novo Recorde Pessoal! 🌟
        </div>
        <div class="simon-gameover-stats">
            <div class="simon-gameover-stat">
                <span class="simon-gameover-stat-label">Pontuação</span>
                <span id="simon-final-score" class="simon-gameover-stat-value">0</span>
            </div>
            <div class="simon-gameover-stat">
                <span class="simon-gameover-stat-label">Tempo</span>
                <span id="simon-final-time" class="simon-gameover-stat-value">00:00</span>
            </div>
        </div>
        <button id="simon-play-again" class="simon-start-btn" type="button">
            <i class="fa-solid fa-rotate-right"></i> Jogar Novamente
        </button>
        <a id="simon-go-home" href="<?php echo esc_url(home_url('/')); ?>" class="simon-start-btn simon-btn-home" style="display:none;">
            <i class="fa-solid fa-house"></i> Voltar à Página Inicial
        </a>
    </div>
</div>

<?php
// Carrega o footer padrão do tema
get_footer();
?>