<?php
/**
 * ============================================================================
 * Template: Ranking do Jogo Simon Says
 * ============================================================================
 *
 * Página dedicada ao ranking Top 5 do jogo Simon Says.
 * Exibe uma tabela com os melhores jogadores ordenados por:
 * 1. Maior pontuação
 * 2. Menor tempo (desempate)
 * 3. Quem atingiu primeiro (data/hora)
 *
 * Arquivo: pages/template-ranking.php
 * Usado em: Painel WordPress → Páginas → Selecionar template "Ranking do jogo Simon Says"
 * ============================================================================
 */

/* Template Name: Ranking do jogo Simon Says */

get_header();

// ============================================================================
// VERIFICAÇÃO DE AUTENTICAÇÃO
// ============================================================================
if (!is_user_logged_in()) {
    wp_redirect(home_url('login'));
    exit;
}

// ============================================================================
// DADOS DO RANKING TOP 5
// ============================================================================
$ranking_top5 = function_exists('simon_get_top5_ranking') ? simon_get_top5_ranking() : array();
?>

<!-- ============================================================================ -->
<!-- PÁGINA DE RANKING                                                            -->
<!-- ============================================================================ -->
<section class="simon-says-page">
    <div class="container">

        <!-- ================================================================ -->
        <!-- CABEÇALHO DA PÁGINA                                              -->
        <!-- ================================================================ -->
        <header class="simon-header">
            <h1 class="simon-title">🏆 Rankings</h1>
            <p class="simon-subtitle">Os melhores pontuadores da dinâmica</p>
        </header>

        <!-- ================================================================ -->
        <!-- RANKING — TOP 5 GERAL                                            -->
        <!-- ================================================================ -->
        <div class="simon-ranking-section">
            <div class="simon-ranking-table-wrapper">
                <table class="simon-ranking-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Unidade</th>
                            <th>Pontuação</th>
                            <th>Tempo</th>
                        </tr>
                    </thead>
                    <tbody id="simon-ranking-body">
                        <?php if (!empty($ranking_top5)): ?>
                                <?php foreach ($ranking_top5 as $index => $player): ?>
                                        <?php
                                        // Medalhas para top 3
                                        $pos_text = $index + 1;
                                        if ($index === 0)
                                            $pos_text = '🥇';
                                        if ($index === 1)
                                            $pos_text = '🥈';
                                        if ($index === 2)
                                            $pos_text = '🥉';
                                        ?>
                                        <tr>
                                            <td class="simon-rank-pos"><?php echo $pos_text; ?></td>
                                            <td><?php echo esc_html($player['nome']); ?></td>
                                            <td><?php echo esc_html($player['unidade']); ?></td>
                                            <td class="simon-rank-score"><?php echo intval($player['pontuacao']); ?></td>
                                            <td><?php echo esc_html($player['tempo']); ?></td>
                                        </tr>
                                <?php endforeach; ?>
                        <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;opacity:.6;">Nenhuma pontuação registrada ainda</td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- CRITÉRIOS DE CLASSIFICAÇÃO                                       -->
        <!-- ================================================================ -->
        <div class="simon-instructions" style="margin-top:24px;">
            <h3><i class="fa-solid fa-circle-info"></i> Critérios de Classificação</h3>
            <ol>
                <li><strong>Maior pontuação</strong> — quem acertou mais sequências</li>
                <li><strong>Menor tempo</strong> — em caso de empate, quem foi mais rápido</li>
                <li><strong>Ordem de chegada</strong> — quem atingiu a pontuação primeiro</li>
            </ol>
        </div>

    </div><!-- /.container -->
</section>

<?php get_footer(); ?>