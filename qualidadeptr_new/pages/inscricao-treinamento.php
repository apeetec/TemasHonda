<?php
/*
Template Name: Inscrição Treinamento
*/
get_header();

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('login') );
    exit;
}

$current_user = wp_get_current_user();
$user_id      = get_current_user_id();
$nome_usuario = $current_user->display_name;
$matricula    = $current_user->user_login;

// Buscar treinamentos com vagas > 0
$treinamentos = get_posts( array(
    'post_type'      => 'treinamentos',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
) );

// Filtrar treinamentos que possuem vagas disponíveis
$treinamentos_disponiveis = array();
foreach ( $treinamentos as $treinamento ) {
    $vagas = intval( get_post_meta( $treinamento->ID, 'treinamento_vagas', true ) );
    if ( $vagas > 0 ) {
        $treinamentos_disponiveis[] = $treinamento;
    }
}

// Buscar inscrições do usuário
$inscricoes = get_user_meta( $user_id, 'inscricoes_treinamentos', true );
if ( ! is_array( $inscricoes ) ) {
    $inscricoes = array();
}
?>

<div class="inscricao-treinamento-page">
    <div class="container">
        <h4 class="center-align">Inscrição em Treinamentos</h4>

        <!-- Mensagens -->
        <div id="inscricao-mensagem" class="inscricao-msg" style="display:none;"></div>

        <!-- Painel de sucesso (aparece após inscrição) -->
        <div id="painel-sucesso" class="painel-sucesso" style="display:none;">
            <div class="sucesso-icon"><i class="fa-solid fa-circle-check"></i></div>
            <h3>Cadastro realizado com sucesso!</h3>
            <p>Sua inscrição no treinamento foi confirmada.</p>
            <button type="button" id="btn-meus-cadastros-sucesso" class="inscricao-btn btn-cadastros">
                <i class="fa-solid fa-list"></i> Meus Cadastros
            </button>
        </div>

        <div class="inscricao-form-wrapper">
            <form id="form-inscricao-treinamento" method="POST">
                <?php wp_nonce_field( 'inscricao_treinamento_nonce', 'inscricao_nonce' ); ?>

                <!-- Nome do Colaborador -->
                <div class="inscricao-field">
                    <label for="nome_colaborador">Nome do Colaborador</label>
                    <input type="text" id="nome_colaborador" name="nome_colaborador" value="<?php echo esc_attr( $nome_usuario ); ?>" readonly>
                </div>

                <!-- Matrícula -->
                <div class="inscricao-field">
                    <label for="matricula_colaborador">Matrícula</label>
                    <input type="text" id="matricula_colaborador" name="matricula_colaborador" value="<?php echo esc_attr( $matricula ); ?>" readonly>
                </div>

                <!-- Função -->
                <div class="inscricao-field">
                    <label for="funcao_colaborador">Função</label>
                    <select id="funcao_colaborador" class="browser-default" name="funcao_colaborador" required>
                        <option value="" disabled selected>Selecione sua função</option>
                        <option value="producao">Produção</option>
                        <option value="manutencao">Manutenção</option>
                        <option value="qualidade">Qualidade</option>
                        <option value="grupo_tecnico">Grupo Técnico</option>
                    </select>
                </div>

                <!-- Sub-opções da Função (checkbox dinâmico) -->
                <div id="sub-funcao-box" class="inscricao-field" style="display:none;">
                    <label>Nível / Categoria</label>
                    <div id="sub-funcao-opcoes" class="inscricao-checkbox-group"></div>
                </div>

                <!-- Departamentos -->
                <div class="inscricao-field">
                    <label for="departamento_colaborador">Departamento</label>
                    <select id="departamento_colaborador" class="browser-default" name="departamento_colaborador" required>
                        <option value="" disabled selected>Selecione o departamento</option>
                        <option value="fnd">FND</option>
                        <option value="usi">USI</option>
                        <option value="mmo">MMO</option>
                    </select>
                </div>

                <!-- Módulo de Treinamento -->
                <div id="modulo-treinamento-box" class="inscricao-field" style="display:none;">
                    <label for="modulo_treinamento">Módulo de Treinamento</label>
                    <select id="modulo_treinamento" class="browser-default" name="modulo_treinamento" required>
                        <option value="" disabled selected>Selecione o treinamento</option>
                        <?php foreach ( $treinamentos_disponiveis as $t ) : ?>
                            <option value="<?php echo esc_attr( $t->ID ); ?>">
                                <?php echo esc_html( $t->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Mês do Treinamento -->
                <div id="mes-treinamento-box" class="inscricao-field" style="display:none;">
                    <label for="mes_treinamento"><i class="fa-solid fa-calendar-days"></i> Mês disponível</label>
                    <select id="mes_treinamento" class="browser-default" name="mes_treinamento" required>
                        <option value="" disabled selected>Selecione o mês</option>
                    </select>
                </div>

                <!-- Info Box do Treinamento selecionado -->
                <div id="treinamento-info-box" class="inscricao-info-box" style="display:none;">
                    <div class="info-item">
                        <span class="info-label"><i class="fa-solid fa-users"></i> Vagas disponíveis:</span>
                        <span id="treinamento-vagas" class="info-value vagas-badge"></span>
                    </div>
                </div>

                <!-- Botões -->
                <div class="inscricao-botoes">
                    <button type="submit" id="btn-confirmar" class="inscricao-btn btn-confirmar">
                        <i class="fa-solid fa-check"></i> Confirmar Inscrição
                    </button>
                    <button type="button" id="btn-meus-cadastros" class="inscricao-btn btn-cadastros">
                        <i class="fa-solid fa-list"></i> Meus Cadastros
                    </button>
                    <a href="<?php echo wp_logout_url( home_url() ); ?>" class="inscricao-btn btn-sair">
                        <i class="fa-solid fa-right-from-bracket"></i> Sair
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Meus Cadastros -->
<div id="modal-meus-cadastros" class="inscricao-modal-overlay" style="display:none;">
    <div class="inscricao-modal">
        <div class="inscricao-modal-header">
            <h5>Meus Treinamentos</h5>
            <button type="button" class="inscricao-modal-close" id="fechar-modal">&times;</button>
        </div>
        <div class="inscricao-modal-body">
            <div id="lista-inscricoes">
                <?php if ( empty( $inscricoes ) ) : ?>
                    <p class="sem-inscricoes">Você ainda não está inscrito em nenhum treinamento.</p>
                <?php else : ?>
                    <table class="inscricao-tabela">
                        <thead>
                            <tr>
                                <th>Treinamento</th>
                                <th>Função</th>
                                <th>Nível</th>
                                <th>Período</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $inscricoes as $idx => $insc ) :
                                $post_treinamento = get_post( $insc['treinamento_id'] );
                                if ( ! $post_treinamento ) continue;
                                $meses_agenda = get_post_meta( $insc['treinamento_id'], 'treinamento_agenda', true );
                                $meses_labels = array(
                                    'janeiro'=>'Janeiro','fevereiro'=>'Fevereiro','marco'=>'Março','abril'=>'Abril',
                                    'maio'=>'Maio','junho'=>'Junho','julho'=>'Julho','agosto'=>'Agosto',
                                    'setembro'=>'Setembro','outubro'=>'Outubro','novembro'=>'Novembro','dezembro'=>'Dezembro'
                                );
                                $periodo_arr = array();
                                if ( is_array( $meses_agenda ) ) {
                                    foreach ( $meses_agenda as $m ) {
                                        if ( isset( $meses_labels[ $m ] ) ) {
                                            $periodo_arr[] = $meses_labels[ $m ];
                                        }
                                    }
                                }
                                $nivel_labels = array(
                                    'operacional'=>'OPERACIONAL','especializados'=>'ESPECIALIZADOS',
                                    'lideres'=>'LÍDERES','chefe_analistas'=>'CHEFE / ANALISTAS'
                                );
                                $funcao_labels = array('producao'=>'Produção','manutencao'=>'Manutenção','qualidade'=>'Qualidade','grupo_tecnico'=>'Grupo Técnico');
                            ?>
                                <tr>
                                    <td><?php echo esc_html( $post_treinamento->post_title ); ?></td>
                                    <td><?php echo esc_html( isset($funcao_labels[$insc['funcao']]) ? $funcao_labels[$insc['funcao']] : $insc['funcao'] ); ?></td>
                                    <td><?php echo esc_html( isset($nivel_labels[$insc['nivel']]) ? $nivel_labels[$insc['nivel']] : $insc['nivel'] ); ?></td>
                                    <td><?php echo esc_html( implode(', ', $periodo_arr) ); ?></td>
                                    <td>
                                        <button type="button" class="btn-cancelar-inscricao" 
                                                data-index="<?php echo esc_attr( $idx ); ?>" 
                                                data-treinamento="<?php echo esc_attr( $insc['treinamento_id'] ); ?>">
                                            <i class="fa-solid fa-trash"></i> Cancelar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dados dos treinamentos para JS -->
<script>
var treinamentosData = {};
<?php foreach ( $treinamentos_disponiveis as $t ) :
    $vagas     = intval( get_post_meta( $t->ID, 'treinamento_vagas', true ) );
    $agenda    = get_post_meta( $t->ID, 'treinamento_agenda', true );
    $producao  = get_post_meta( $t->ID, 'treinamento_producao', true );
    $manutencao = get_post_meta( $t->ID, 'treinamento_manutencao', true );
    $qualidade = get_post_meta( $t->ID, 'treinamento_qualidade', true );
    $grupo_tecnico = get_post_meta( $t->ID, 'treinamento_grupo_tecnico', true );
    $departamentos = get_post_meta( $t->ID, 'treinamento_departamentos', true );
?>
treinamentosData[<?php echo $t->ID; ?>] = {
    vagas: <?php echo $vagas; ?>,
    agenda: <?php echo json_encode( is_array($agenda) ? $agenda : array() ); ?>,
    producao: <?php echo json_encode( is_array($producao) ? $producao : array() ); ?>,
    manutencao: <?php echo json_encode( is_array($manutencao) ? $manutencao : array() ); ?>,
    qualidade: <?php echo json_encode( is_array($qualidade) ? $qualidade : array() ); ?>,
    grupo_tecnico: <?php echo json_encode( is_array($grupo_tecnico) ? $grupo_tecnico : array() ); ?>,
    departamentos: <?php echo json_encode( is_array($departamentos) ? $departamentos : array() ); ?>,
    titulo: <?php echo json_encode( $t->post_title ); ?>
};
<?php endforeach; ?>

var inscricoesUsuario = <?php
    $ids_inscritos = array();
    foreach ( $inscricoes as $insc ) {
        $ids_inscritos[] = intval( $insc['treinamento_id'] );
    }
    echo json_encode( $ids_inscritos );
?>;

var inscricaoAjax = {
    url: "<?php echo admin_url('admin-ajax.php'); ?>",
    nonce: "<?php echo wp_create_nonce('inscricao_treinamento_nonce'); ?>"
};
</script>

<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/inscricao-treinamento.css?<?php echo time(); ?>">
<script src="<?php bloginfo('template_url'); ?>/js/inscricao-treinamento.js?<?php echo time(); ?>"></script>

<?php get_footer(); ?>
