<?php
/**
 * Empresas e Etapas
 * Tema: Explode Criação
 *
 * Substitui o roteamento fixo por região (SUM / SAO / MAO) que existia no index.php
 * por um modelo configurável pelo administrador:
 *
 *   EMPRESA = um nome + os GRUPOS que pertencem a ela + a ETAPA em que ela está.
 *
 * A etapa determina o que o colaborador daquela empresa vê ao entrar no site:
 * envio de desenhos, votação, resultados, aviso ou nada.
 *
 * Cada grupo pertence a no máximo uma empresa, para que o roteamento nunca seja ambíguo.
 * Grupos que não estejam em nenhuma empresa aparecem destacados no painel para o
 * administrador resolver — em vez de sumirem em silêncio, como acontecia antes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança
}

/** Nome da option onde a configuração fica gravada */
if ( ! defined( 'EXPLODE_EMPRESAS_OPTION' ) ) {
    define( 'EXPLODE_EMPRESAS_OPTION', 'explode_empresas' );
}

/**
 * Catálogo das etapas disponíveis e do que cada uma exibe
 */
function explode_empresas_etapas() {
    return array(
        0 => array(
            'rotulo'    => 'Aguardando',
            'descricao' => 'Nada é exibido na página inicial. Use entre uma etapa e outra.',
            'icone'     => 'dashicons-clock',
        ),
        1 => array(
            'rotulo'    => 'Envio de desenhos',
            'descricao' => 'Mostra o formulário de envio dos desenhos dos dependentes.',
            'icone'     => 'dashicons-upload',
        ),
        2 => array(
            'rotulo'    => 'Votação',
            'descricao' => 'Leva o colaborador para a página de votação e libera o menu de seleção dos desenhos.',
            'icone'     => 'dashicons-thumbs-up',
        ),
        3 => array(
            'rotulo'    => 'Resultados',
            'descricao' => 'Mostra o cartaz com os desenhos vencedores.',
            'icone'     => 'dashicons-awards',
        ),
        4 => array(
            'rotulo'    => 'Aviso',
            'descricao' => 'Mostra apenas a mensagem de aviso cadastrada no tema.',
            'icone'     => 'dashicons-megaphone',
        ),
    );
}

/**
 * Devolve o rótulo de uma etapa
 */
function explode_empresas_rotulo_etapa( $etapa ) {
    // Carrega o catálogo
    $etapas = explode_empresas_etapas();
    // Devolve o rótulo quando a etapa existir
    return isset( $etapas[ (int) $etapa ] ) ? $etapas[ (int) $etapa ]['rotulo'] : 'Etapa desconhecida';
}

/**
 * Lista os grupos cadastrados na taxonomia, no formato usado pelo painel
 * Devolve array de slug => array( slug, nome, id )
 */
function explode_empresas_grupos_disponiveis() {
    // Garante que o mapa de termos do importador esteja carregado
    if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
        require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
    }

    // Reaproveita a classificação já testada de categorias e grupos
    $termos = Explode_Admin_User_Importer::carregar_termos();
    // Lista de retorno
    $lista = array();

    // Percorre apenas os termos classificados como grupo
    foreach ( $termos['lista_grp'] as $item ) {
        // Indexa pelo slug, que é estável mesmo se o termo for recriado
        $lista[ $item['slug'] ] = array(
            'slug' => $item['slug'],
            'nome' => $item['nome'],
            'id'   => (int) $item['id'],
        );
    }

    // Ordena pelo nome para exibição previsível
    uasort( $lista, function( $a, $b ) {
        // Compara considerando números dentro do nome (Grupo 2 antes de Grupo 10)
        return strnatcasecmp( $a['nome'], $b['nome'] );
    } );

    // Devolve a lista pronta
    return $lista;
}

/**
 * Monta a configuração inicial a partir das antigas opções SUM / SAO / MAO
 * Roda apenas uma vez, quando ainda não existe configuração de empresas
 */
function explode_empresas_semear() {
    // Lê as opções antigas do tema
    $antigas = get_option( 'opcoes_gerais_box' );
    // Garante um array
    $antigas = is_array( $antigas ) ? $antigas : array();

    // Recupera os grupos existentes na taxonomia
    $grupos = explode_empresas_grupos_disponiveis();

    // Separa os grupos MAO dos demais pelo mesmo critério do módulo de regras MAO
    $grupos_mao = array();
    // Percorre os grupos cadastrados
    foreach ( $grupos as $slug => $g ) {
        // Usa o identificador oficial das regras MAO quando disponível
        if ( function_exists( 'explode_grupo_e_mao' ) && explode_grupo_e_mao( $g['nome'] ) ) {
            $grupos_mao[] = $slug;
        }
    }

    // Monta as três empresas equivalentes ao comportamento anterior
    $empresas = array(
        array(
            'id'     => 'sumare',
            'nome'   => 'Sumaré',
            'grupos' => array_values( array_intersect( array( 'grupo-1', 'grupo-2' ), array_keys( $grupos ) ) ),
            'etapa'  => isset( $antigas['opcoes_gerais_fase_sum'] ) ? (int) $antigas['opcoes_gerais_fase_sum'] : 1,
        ),
        array(
            'id'     => 'sao-paulo',
            'nome'   => 'São Paulo',
            'grupos' => array_values( array_intersect( array( 'grupo-3', 'grupo-4' ), array_keys( $grupos ) ) ),
            'etapa'  => isset( $antigas['opcoes_gerais_fase_sao'] ) ? (int) $antigas['opcoes_gerais_fase_sao'] : 1,
        ),
        array(
            'id'     => 'manaus',
            'nome'   => 'Manaus',
            'grupos' => $grupos_mao,
            'etapa'  => isset( $antigas['opcoes_gerais_fase_mao'] ) ? (int) $antigas['opcoes_gerais_fase_mao'] : 1,
        ),
    );

    // Grava a configuração inicial
    update_option( EXPLODE_EMPRESAS_OPTION, $empresas );

    // Devolve o que foi criado
    return $empresas;
}

/**
 * Devolve a lista de empresas configuradas, semeando na primeira execução
 */
function explode_empresas_get() {
    // Lê a option
    $empresas = get_option( EXPLODE_EMPRESAS_OPTION, null );

    // Primeira execução: converte a configuração antiga
    if ( null === $empresas || ! is_array( $empresas ) ) {
        return explode_empresas_semear();
    }

    // Normaliza cada registro para evitar índices ausentes
    $saida = array();
    // Percorre as empresas gravadas
    foreach ( $empresas as $e ) {
        // Ignora entradas inválidas
        if ( ! is_array( $e ) || empty( $e['nome'] ) ) {
            continue;
        }
        // Monta o registro normalizado
        $saida[] = array(
            'id'     => isset( $e['id'] ) && '' !== $e['id'] ? sanitize_key( $e['id'] ) : sanitize_key( $e['nome'] ),
            'nome'   => (string) $e['nome'],
            'grupos' => isset( $e['grupos'] ) && is_array( $e['grupos'] ) ? array_values( array_filter( array_map( 'sanitize_title', $e['grupos'] ) ) ) : array(),
            'etapa'  => isset( $e['etapa'] ) ? (int) $e['etapa'] : 0,
        );
    }

    // Devolve a lista normalizada
    return $saida;
}

/**
 * Grava a lista de empresas garantindo que cada grupo pertença a apenas uma delas
 * Devolve array com a lista salva e os conflitos que precisaram ser resolvidos
 */
function explode_empresas_salvar( $lista ) {
    // Etapas válidas
    $etapas_validas = array_keys( explode_empresas_etapas() );
    // Grupos existentes na taxonomia
    $grupos_validos = array_keys( explode_empresas_grupos_disponiveis() );

    // Acumuladores
    $saida      = array();
    $usados     = array();
    $conflitos  = array();
    $ids_usados = array();

    // Percorre cada empresa recebida
    foreach ( (array) $lista as $e ) {
        // Nome é obrigatório
        $nome = isset( $e['nome'] ) ? trim( wp_strip_all_tags( (string) $e['nome'] ) ) : '';
        // Ignora linhas sem nome
        if ( '' === $nome ) {
            continue;
        }

        // Gera um identificador estável e único
        $id = isset( $e['id'] ) && '' !== $e['id'] ? sanitize_key( $e['id'] ) : sanitize_title( $nome );
        // Resolve duplicidade de identificador
        $base = $id;
        $n    = 2;
        while ( in_array( $id, $ids_usados, true ) ) {
            $id = $base . '-' . $n;
            $n++;
        }
        $ids_usados[] = $id;

        // Valida a etapa escolhida
        $etapa = isset( $e['etapa'] ) ? (int) $e['etapa'] : 0;
        $etapa = in_array( $etapa, $etapas_validas, true ) ? $etapa : 0;

        // Valida os grupos marcados
        $grupos = array();
        // Percorre os grupos recebidos
        foreach ( (array) ( isset( $e['grupos'] ) ? $e['grupos'] : array() ) as $slug ) {
            // Normaliza o slug
            $slug = sanitize_title( $slug );
            // Descarta grupo inexistente na taxonomia
            if ( ! in_array( $slug, $grupos_validos, true ) ) {
                continue;
            }
            // Um grupo só pode pertencer a uma empresa
            if ( isset( $usados[ $slug ] ) ) {
                // Registra o conflito para avisar o administrador
                $conflitos[] = sprintf( 'O grupo "%s" já pertencia a "%s" e foi mantido lá.', $slug, $usados[ $slug ] );
                continue;
            }
            // Reserva o grupo para esta empresa
            $usados[ $slug ] = $nome;
            $grupos[]        = $slug;
        }

        // Guarda a empresa normalizada
        $saida[] = array(
            'id'     => $id,
            'nome'   => $nome,
            'grupos' => $grupos,
            'etapa'  => $etapa,
        );
    }

    // Persiste a configuração
    update_option( EXPLODE_EMPRESAS_OPTION, $saida );

    // Devolve o resultado da gravação
    return array( 'empresas' => $saida, 'conflitos' => $conflitos );
}

/**
 * Converte o conteúdo de user_field_funcionario_grupo em slugs de grupo
 * Aceita mais de um grupo separado por vírgula, ponto e vírgula ou barra
 */
function explode_empresas_slugs_do_usuario( $user_id = 0 ) {
    // Usa o usuário logado quando nenhum for informado
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    // Sem usuário não há grupo
    if ( $user_id < 1 ) {
        return array();
    }

    // Lê o grupo gravado no perfil
    $meta = get_user_meta( $user_id, 'user_field_funcionario_grupo', true );
    // Campo vazio
    if ( '' === trim( (string) $meta ) ) {
        return array();
    }

    // Garante o mapa de termos
    if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
        require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
    }
    // Carrega os termos
    $termos = Explode_Admin_User_Importer::carregar_termos();

    // Separa os grupos informados no campo
    $partes = preg_split( '/\s*[,;\|\r\n]+\s*/u', (string) $meta, -1, PREG_SPLIT_NO_EMPTY );
    // Slugs encontrados
    $slugs = array();

    // Resolve cada parte contra a taxonomia
    foreach ( (array) $partes as $parte ) {
        // Limpa o texto
        $texto = trim( $parte );
        // Ignora vazios
        if ( '' === $texto ) {
            continue;
        }
        // Normaliza para a chave do mapa de termos
        $chave = Explode_Admin_User_Importer::chave( $texto );
        // Usa o slug oficial do termo quando encontrado
        if ( isset( $termos['grupos'][ $chave ] ) ) {
            $slugs[] = $termos['grupos'][ $chave ]['slug'];
        } else {
            // Sem termo correspondente, tenta o próprio texto como slug
            $slugs[] = sanitize_title( $texto );
        }
    }

    // Remove repetições preservando a ordem
    return array_values( array_unique( $slugs ) );
}

/**
 * Descobre a empresa do colaborador a partir dos grupos dele
 * Devolve o registro da empresa ou null quando nenhum grupo estiver vinculado
 */
function explode_empresa_do_usuario( $user_id = 0 ) {
    // Slugs dos grupos do colaborador
    $slugs = explode_empresas_slugs_do_usuario( $user_id );
    // Sem grupo não há empresa
    if ( empty( $slugs ) ) {
        return null;
    }

    // Percorre as empresas procurando o primeiro grupo que bata
    foreach ( explode_empresas_get() as $empresa ) {
        // Verifica a interseção entre os grupos do colaborador e os da empresa
        if ( array_intersect( $slugs, $empresa['grupos'] ) ) {
            return $empresa;
        }
    }

    // Nenhuma empresa contém os grupos deste colaborador
    return null;
}

/**
 * Etapa em que o colaborador se encontra
 * Devolve -1 quando ele não pertence a nenhuma empresa configurada
 */
function explode_etapa_do_usuario( $user_id = 0 ) {
    // Localiza a empresa
    $empresa = explode_empresa_do_usuario( $user_id );
    // Sem empresa devolve o código de não configurado
    return $empresa ? (int) $empresa['etapa'] : -1;
}

/**
 * Lista os grupos que não foram atribuídos a nenhuma empresa
 */
function explode_empresas_grupos_sem_empresa() {
    // Todos os grupos da taxonomia
    $todos = explode_empresas_grupos_disponiveis();
    // Grupos já atribuídos
    $atribuidos = array();
    // Percorre as empresas somando os grupos
    foreach ( explode_empresas_get() as $e ) {
        $atribuidos = array_merge( $atribuidos, $e['grupos'] );
    }
    // Devolve a diferença
    return array_diff_key( $todos, array_flip( $atribuidos ) );
}

/**
 * Conta quantos colaboradores existem em cada grupo, para orientar o administrador
 */
function explode_empresas_contagem_por_grupo() {
    // Acesso direto ao banco
    global $wpdb;

    // Lê todos os valores do campo de grupo
    $linhas = $wpdb->get_col(
        "SELECT meta_value FROM {$wpdb->usermeta}
         WHERE meta_key = 'user_field_funcionario_grupo' AND meta_value <> ''"
    );

    // Garante o mapa de termos
    if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
        require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
    }
    // Carrega os termos
    $termos = Explode_Admin_User_Importer::carregar_termos();
    // Contadores por slug
    $contagem = array();

    // Percorre cada valor encontrado
    foreach ( (array) $linhas as $valor ) {
        // Separa múltiplos grupos do mesmo colaborador
        $partes = preg_split( '/\s*[,;\|\r\n]+\s*/u', (string) $valor, -1, PREG_SPLIT_NO_EMPTY );
        // Conta cada grupo
        foreach ( (array) $partes as $parte ) {
            // Normaliza para a chave do mapa
            $chave = Explode_Admin_User_Importer::chave( trim( $parte ) );
            // Descobre o slug oficial
            $slug  = isset( $termos['grupos'][ $chave ] ) ? $termos['grupos'][ $chave ]['slug'] : sanitize_title( trim( $parte ) );
            // Incrementa o contador
            if ( ! isset( $contagem[ $slug ] ) ) {
                $contagem[ $slug ] = 0;
            }
            $contagem[ $slug ]++;
        }
    }

    // Devolve os contadores
    return $contagem;
}

/**
 * Leva o colaborador para a página de votação quando a empresa dele está na etapa 2
 * Roda antes de qualquer saída, evitando o problema de cabeçalhos já enviados
 */
add_action( 'template_redirect', 'explode_empresas_redirecionar_votacao' );
function explode_empresas_redirecionar_votacao() {
    // Só age na página inicial de um colaborador logado
    if ( ! is_user_logged_in() || ! is_home() ) {
        return;
    }

    // Administradores não são redirecionados
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    // Respeita os fluxos obrigatórios de troca de senha e leitura do regulamento
    $senha_alterada = get_user_meta( get_current_user_id(), 'user_field_senha_alterada', true );
    $leitura_reg    = get_user_meta( get_current_user_id(), 'user_field_leitura_reg', true );
    // Deixa o header cuidar desses casos primeiro
    if ( 'Sim' !== $senha_alterada || 'Sim' !== $leitura_reg ) {
        return;
    }

    // Redireciona apenas quem está na etapa de votação
    if ( 2 !== explode_etapa_do_usuario() ) {
        return;
    }

    // Envia para a página de votação
    wp_safe_redirect( home_url( 'filtros' ) );
    exit;
}

/**
 * Bloqueia o acesso direto à página de votação se a empresa/grupo não estiver na etapa 2
 */
add_action( 'template_redirect', 'explode_empresas_bloquear_votacao' );
function explode_empresas_bloquear_votacao() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Administradores têm passe livre
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    // Verifica se está acessando as páginas/templates de votação
    $is_voting_page = is_page( array( 100, 933, 'filtros' ) ) || is_page_template( array(
        'template-page/template-filtro.php',
        'template-page/template-filtro-combinacao.php',
        'template-page/template-filtro-combinacao-comissao.php'
    ) );

    if ( $is_voting_page ) {
        // Se a etapa NÃO for a 2, bloqueia e redireciona pra home
        if ( 2 !== explode_etapa_do_usuario() ) {
            wp_safe_redirect( home_url() );
            exit;
        }
    }
}

/* =====================================================================
 * PAINEL ADMINISTRATIVO DAS EMPRESAS
 * ===================================================================== */

class Explode_Admin_Empresas {

    /** Slug da página administrativa */
    const MENU_SLUG = 'explode-empresas';

    /** Nome do nonce do formulário */
    const NONCE = 'explode_empresas_nonce';

    /**
     * Inicialização dos hooks
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_post' ) );
    }

    /**
     * Registra o menu no WP-Admin
     */
    public static function register_admin_menu() {
        // Menu de nível superior, logo abaixo de Opções gerais
        add_menu_page(
            'Empresas e Etapas',
            'Empresas',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render' ),
            'dashicons-building',
            3
        );
    }

    /**
     * Processa o envio do formulário
     */
    public static function handle_post() {
        // Só age nesta página
        $pagina = isset( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';
        // Fora da página não faz nada
        if ( self::MENU_SLUG !== $pagina || ! isset( $_POST['explode_empresas_salvar'] ) ) {
            return;
        }

        // Verifica a permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Você não tem permissão para alterar esta configuração.' );
        }

        // Verifica o nonce
        check_admin_referer( self::NONCE );

        // Lê as empresas enviadas
        $recebidas = isset( $_POST['empresas'] ) && is_array( $_POST['empresas'] ) ? wp_unslash( $_POST['empresas'] ) : array();

        // Grava a configuração
        $resultado = explode_empresas_salvar( $recebidas );

        // Monta a URL de retorno com o resumo
        $url = add_query_arg(
            array(
                'page'      => self::MENU_SLUG,
                'salvo'     => count( $resultado['empresas'] ),
                'conflitos' => count( $resultado['conflitos'] ),
            ),
            admin_url( 'admin.php' )
        );

        // Volta para a página
        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Renderiza a página de configuração
     */
    public static function render() {
        // Bloqueia quem não tem permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Você não tem permissão para acessar esta página.' );
        }

        // Dados da tela
        $empresas   = explode_empresas_get();
        $grupos     = explode_empresas_grupos_disponiveis();
        $etapas     = explode_empresas_etapas();
        $orfaos     = explode_empresas_grupos_sem_empresa();
        $contagem   = explode_empresas_contagem_por_grupo();
        $salvo      = isset( $_GET['salvo'] ) ? (int) $_GET['salvo'] : -1;
        $conflitos  = isset( $_GET['conflitos'] ) ? (int) $_GET['conflitos'] : 0;

        // Abre o container e imprime o estilo
        echo '<div class="wrap explode-emp-wrap">';
        self::estilo();
        ?>

        <div class="eem-hero">
            <div class="eem-hero-left">
                <div class="eem-hero-badge"><span class="dashicons dashicons-building"></span> Módulo Administrativo Oficial</div>
                <h1 class="eem-hero-title">Empresas e Etapas</h1>
                <p class="eem-hero-desc">
                    Defina quais <strong>grupos</strong> pertencem a cada <strong>empresa</strong> e em que
                    <strong>etapa</strong> cada uma está. A etapa decide o que o colaborador vê ao entrar no site:
                    enviar desenho, votar, ver resultados ou apenas um aviso.
                </p>
            </div>
            <div class="eem-hero-right">
                <a class="eem-btn eem-btn-secondary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=desenhos_cat&post_type=desenhos' ) ); ?>">
                    <span class="dashicons dashicons-category"></span> Gerenciar grupos
                </a>
            </div>
        </div>

        <?php if ( $salvo >= 0 ) : ?>
            <div class="eem-alert eem-alert-sucesso">
                <span class="dashicons dashicons-yes-alt"></span>
                <div>
                    <strong>Configuração salva.</strong>
                    <?php echo esc_html( $salvo ); ?> empresa(s) gravada(s).
                    <?php if ( $conflitos > 0 ) : ?>
                        <?php echo esc_html( $conflitos ); ?> grupo(s) estavam marcados em mais de uma empresa e foram mantidos apenas na primeira.
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $orfaos ) ) : ?>
            <div class="eem-alert eem-alert-warning">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong><?php echo esc_html( count( $orfaos ) ); ?> grupo(s) sem empresa.</strong>
                    Os colaboradores destes grupos <u>não veem nada</u> na página inicial. Marque cada um em alguma empresa abaixo:
                    <div class="eem-orfaos">
                        <?php foreach ( $orfaos as $slug => $g ) : ?>
                            <span class="eem-chip eem-chip-alerta">
                                <?php echo esc_html( $g['nome'] ); ?>
                                <em><?php echo esc_html( number_format_i18n( isset( $contagem[ $slug ] ) ? $contagem[ $slug ] : 0 ) ); ?> colaborador(es)</em>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- REFERENCIA DAS ETAPAS -->
        <div class="eem-card">
            <div class="eem-card-head">
                <div class="eem-card-icon icon-navy"><span class="dashicons dashicons-visibility"></span></div>
                <div>
                    <h2 class="eem-card-title">O que cada etapa exibe</h2>
                    <p class="eem-card-sub">A etapa é escolhida por empresa e vale para todos os grupos dela.</p>
                </div>
            </div>
            <div class="eem-card-body">
                <div class="eem-etapas">
                    <?php foreach ( $etapas as $num => $et ) : ?>
                        <div class="eem-etapa-ref">
                            <span class="eem-etapa-num"><span class="dashicons <?php echo esc_attr( $et['icone'] ); ?>"></span></span>
                            <div>
                                <strong><?php echo esc_html( $et['rotulo'] ); ?></strong>
                                <span><?php echo esc_html( $et['descricao'] ); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>">
            <?php wp_nonce_field( self::NONCE ); ?>
            <input type="hidden" name="explode_empresas_salvar" value="1">

            <div id="eem-lista">
                <?php
                // Renderiza cada empresa configurada
                $indice = 0;
                foreach ( $empresas as $empresa ) {
                    self::render_empresa( $indice, $empresa, $grupos, $etapas, $contagem );
                    $indice++;
                }
                ?>
            </div>

            <div class="eem-acoes">
                <button type="button" class="eem-btn eem-btn-ghost-dark" id="eem-adicionar">
                    <span class="dashicons dashicons-plus-alt2"></span> Adicionar empresa
                </button>
                <button type="submit" class="eem-btn eem-btn-primary">
                    <span class="dashicons dashicons-yes"></span> Salvar configuração
                </button>
            </div>
        </form>

        <!-- MOLDE USADO PELO BOTAO DE ADICIONAR -->
        <script type="text/template" id="eem-molde">
            <?php self::render_empresa( '__IDX__', array( 'id' => '', 'nome' => '', 'grupos' => array(), 'etapa' => 0 ), $grupos, $etapas, $contagem ); ?>
        </script>

        <script type="text/javascript">
        (function($){
            // Proximo indice disponivel para uma nova empresa
            var proximo = <?php echo (int) count( $empresas ); ?>;

            // Acrescenta uma empresa em branco
            $('#eem-adicionar').on('click', function(){
                // Recupera o molde e troca o marcador pelo indice real
                var html = $('#eem-molde').html().split('__IDX__').join(proximo);
                // Insere no fim da lista
                $('#eem-lista').append(html);
                // Avanca o contador
                proximo++;
                // Leva o foco para o nome da nova empresa
                $('#eem-lista .eem-empresa').last().find('.eem-nome').trigger('focus');
            });

            // Remove uma empresa da tela
            $(document).on('click', '.eem-remover', function(){
                // Pede confirmacao porque os grupos ficarao sem empresa
                if (!window.confirm('Remover esta empresa? Os grupos dela ficarão sem empresa até você marcá-los em outra.')) { return; }
                // Retira o bloco
                $(this).closest('.eem-empresa').remove();
            });

            // Impede que o mesmo grupo seja marcado em duas empresas
            $(document).on('change', '.eem-grupo-check', function(){
                // Slug do grupo alterado
                var slug = $(this).val();
                // Quando marcado, desmarca o mesmo grupo nas outras empresas
                if ($(this).is(':checked')) {
                    $('.eem-grupo-check[value="' + slug + '"]').not(this).prop('checked', false);
                }
                // Atualiza o resumo de cada empresa
                atualizarResumos();
            });

            // Mostra quantos grupos cada empresa tem selecionados
            function atualizarResumos(){
                $('.eem-empresa').each(function(){
                    var n = $(this).find('.eem-grupo-check:checked').length;
                    $(this).find('.eem-resumo-grupos').text(n + (n === 1 ? ' grupo' : ' grupos'));
                });
            }

            // Estado inicial
            atualizarResumos();
        })(jQuery);
        </script>
        <?php
        // Fecha o container
        echo '</div>';
    }

    /**
     * Renderiza o bloco de uma empresa
     */
    private static function render_empresa( $indice, $empresa, $grupos, $etapas, $contagem ) {
        // Prefixo dos campos deste bloco
        $base = 'empresas[' . $indice . ']';
        ?>
        <div class="eem-card eem-empresa">
            <div class="eem-card-head">
                <div class="eem-card-icon icon-teal"><span class="dashicons dashicons-building"></span></div>
                <div class="eem-empresa-campos">
                    <label class="eem-label" for="eem-nome-<?php echo esc_attr( $indice ); ?>">Nome da empresa</label>
                    <input class="eem-input eem-nome" type="text" id="eem-nome-<?php echo esc_attr( $indice ); ?>"
                           name="<?php echo esc_attr( $base ); ?>[nome]"
                           value="<?php echo esc_attr( $empresa['nome'] ); ?>" placeholder="Ex.: Manaus" required>
                    <input type="hidden" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $empresa['id'] ); ?>">
                </div>
                <div class="eem-empresa-campos">
                    <label class="eem-label" for="eem-etapa-<?php echo esc_attr( $indice ); ?>">Etapa atual</label>
                    <select class="eem-input" id="eem-etapa-<?php echo esc_attr( $indice ); ?>" name="<?php echo esc_attr( $base ); ?>[etapa]">
                        <?php foreach ( $etapas as $num => $et ) : ?>
                            <option value="<?php echo esc_attr( $num ); ?>" <?php selected( (int) $empresa['etapa'], (int) $num ); ?>>
                                <?php echo esc_html( $et['rotulo'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="eem-btn eem-btn-perigo eem-remover">
                    <span class="dashicons dashicons-trash"></span> Remover
                </button>
            </div>
            <div class="eem-card-body">
                <div class="eem-grupos-head">
                    <span class="eem-label">Grupos desta empresa</span>
                    <span class="eem-resumo-grupos">0 grupos</span>
                </div>
                <div class="eem-grupos">
                    <?php foreach ( $grupos as $slug => $g ) : ?>
                        <label class="eem-grupo">
                            <input type="checkbox" class="eem-grupo-check"
                                   name="<?php echo esc_attr( $base ); ?>[grupos][]"
                                   value="<?php echo esc_attr( $slug ); ?>"
                                   <?php checked( in_array( $slug, $empresa['grupos'], true ) ); ?>>
                            <span>
                                <strong><?php echo esc_html( $g['nome'] ); ?></strong>
                                <em><?php echo esc_html( number_format_i18n( isset( $contagem[ $slug ] ) ? $contagem[ $slug ] : 0 ) ); ?> colaborador(es)</em>
                            </span>
                        </label>
                    <?php endforeach; ?>
                    <?php if ( empty( $grupos ) ) : ?>
                        <p class="eem-help">Nenhum grupo cadastrado na taxonomia de categorias.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Estilo da página, alinhado à identidade visual do painel Explode
     */
    private static function estilo() {
        ?>
        <style>
            .explode-emp-wrap {
                --exp-navy: #143240;
                --exp-teal: #5b9b99;
                --exp-cyan: #54c5cf;
                --exp-text: #1f2937;
                --exp-muted: #6b7280;
                --exp-border: #e5e7eb;
                --exp-danger: #e11d48;
                --exp-success: #10b981;
                --exp-warning: #f59e0b;
                --exp-radius: 12px;
                --exp-shadow: 0 4px 20px -2px rgba(20, 50, 64, 0.08);
                margin: 20px 20px 80px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                color: var(--exp-text);
            }
            .explode-emp-wrap * { box-sizing: border-box; }

            /* HERO */
            .eem-hero {
                background: linear-gradient(135deg, var(--exp-navy) 0%, #1c4b61 50%, var(--exp-teal) 100%);
                border-radius: var(--exp-radius); padding: 30px 34px; color: #fff;
                display: flex; align-items: center; justify-content: space-between; gap: 24px;
                box-shadow: var(--exp-shadow); margin-bottom: 22px; position: relative; overflow: hidden;
            }
            .eem-hero::after {
                content: ""; position: absolute; right: -60px; top: -60px; width: 260px; height: 260px;
                background: radial-gradient(circle, rgba(84,197,207,.2) 0%, rgba(255,255,255,0) 70%);
                border-radius: 50%; pointer-events: none;
            }
            .eem-hero-left { max-width: 780px; }
            .eem-hero-badge {
                display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.15);
                border: 1px solid rgba(255,255,255,.25); padding: 4px 12px; border-radius: 20px;
                font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;
            }
            .eem-hero-title { color: #fff; font-size: 26px; line-height: 1.2; margin: 0 0 10px; font-weight: 700; }
            .eem-hero-desc { color: rgba(255,255,255,.9); font-size: 14px; line-height: 1.65; margin: 0; }
            .eem-hero-right { flex-shrink: 0; }

            /* BOTOES */
            .eem-btn {
                display: inline-flex; align-items: center; justify-content: center; gap: 7px;
                padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600;
                text-decoration: none; border: 1px solid transparent; cursor: pointer;
                transition: all .18s ease; line-height: 1.2;
            }
            .eem-btn .dashicons { font-size: 17px; width: 17px; height: 17px; }
            .eem-btn-primary { background: linear-gradient(90deg, var(--exp-teal), var(--exp-cyan)); color: #fff !important; }
            .eem-btn-primary:hover { filter: brightness(1.07); }
            .eem-btn-secondary { background: #fff; color: var(--exp-navy) !important; border-color: var(--exp-border); }
            .eem-btn-secondary:hover { border-color: var(--exp-cyan); }
            .eem-btn-ghost-dark { background: #fff; color: var(--exp-muted) !important; border-color: var(--exp-border); }
            .eem-btn-ghost-dark:hover { color: var(--exp-navy) !important; border-color: var(--exp-cyan); }
            .eem-btn-perigo { background: #fff; color: var(--exp-danger) !important; border-color: #fecdd3; margin-left: auto; align-self: flex-start; }
            .eem-btn-perigo:hover { background: #fff1f2; }

            /* ALERTAS */
            .eem-alert {
                display: flex; gap: 12px; align-items: flex-start; padding: 14px 18px;
                border-radius: var(--exp-radius); margin-bottom: 18px; font-size: 13.5px; line-height: 1.6;
                border-left: 4px solid var(--exp-cyan); background: #eef8fa;
            }
            .eem-alert .dashicons { flex-shrink: 0; margin-top: 1px; }
            .eem-alert-warning { background: #fef3c7; border-left-color: var(--exp-warning); }
            .eem-alert-sucesso { background: #d1fae5; border-left-color: var(--exp-success); }
            .eem-orfaos { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
            .eem-chip {
                display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px;
                background: #fff; border: 1px solid var(--exp-border); font-size: 12px; font-weight: 600;
            }
            .eem-chip em { font-style: normal; font-weight: 400; color: var(--exp-muted); font-size: 11px; }
            .eem-chip-alerta { border-color: #fcd34d; }

            /* CARTOES */
            .eem-card {
                background: #fff; border: 1px solid var(--exp-border); border-radius: var(--exp-radius);
                box-shadow: var(--exp-shadow); margin-bottom: 18px; overflow: hidden;
            }
            .eem-card-head {
                display: flex; align-items: flex-start; gap: 16px; padding: 18px 22px;
                border-bottom: 1px solid var(--exp-border); background: #fbfcfd; flex-wrap: wrap;
            }
            .eem-card-icon {
                width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
                justify-content: center; color: #fff; flex-shrink: 0;
            }
            .eem-card-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }
            .icon-teal { background: linear-gradient(135deg, var(--exp-teal), var(--exp-cyan)); }
            .icon-navy { background: linear-gradient(135deg, var(--exp-navy), #1c4b61); }
            .eem-card-title { margin: 0; font-size: 16px; font-weight: 700; color: var(--exp-navy); }
            .eem-card-sub { margin: 3px 0 0; font-size: 12.5px; color: var(--exp-muted); }
            .eem-card-body { padding: 20px 22px; }
            .eem-help { font-size: 12.5px; color: var(--exp-muted); margin: 0; }

            /* CAMPOS */
            .eem-empresa-campos { display: flex; flex-direction: column; gap: 5px; min-width: 220px; }
            .eem-label { font-size: 11.5px; font-weight: 700; color: var(--exp-navy); text-transform: uppercase; letter-spacing: .4px; }
            .eem-input {
                padding: 9px 12px; border: 1px solid var(--exp-border); border-radius: 8px;
                font-size: 13px; background: #fff; color: var(--exp-text); line-height: 1.4; min-width: 220px;
            }
            .eem-input:focus { border-color: var(--exp-cyan); outline: none; box-shadow: 0 0 0 3px rgba(84,197,207,.16); }

            /* GRUPOS */
            .eem-grupos-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
            .eem-resumo-grupos {
                font-size: 11.5px; font-weight: 700; color: var(--exp-navy);
                background: #eef8fa; border: 1px solid #cdeef2; padding: 3px 12px; border-radius: 20px;
            }
            .eem-grupos { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; }
            .eem-grupo {
                display: flex; align-items: flex-start; gap: 9px; padding: 10px 12px;
                border: 1px solid var(--exp-border); border-radius: 8px; background: #fbfcfd;
                font-size: 12.5px; cursor: pointer; transition: all .15s ease;
            }
            .eem-grupo:hover { border-color: var(--exp-cyan); }
            .eem-grupo input { margin: 2px 0 0; flex-shrink: 0; }
            .eem-grupo span { display: flex; flex-direction: column; gap: 2px; }
            .eem-grupo strong { color: var(--exp-navy); }
            .eem-grupo em { font-style: normal; font-size: 11px; color: var(--exp-muted); }
            .eem-grupo input:checked + span strong { color: var(--exp-teal); }

            /* ETAPAS */
            .eem-etapas { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; }
            .eem-etapa-ref {
                display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px;
                border: 1px solid var(--exp-border); border-radius: 10px; background: #fbfcfd;
            }
            .eem-etapa-num {
                width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
                display: flex; align-items: center; justify-content: center;
                background: #eef8fa; color: var(--exp-navy); border: 1px solid #cdeef2;
            }
            .eem-etapa-num .dashicons { font-size: 17px; width: 17px; height: 17px; }
            .eem-etapa-ref div { display: flex; flex-direction: column; gap: 3px; }
            .eem-etapa-ref strong { font-size: 13px; color: var(--exp-navy); }
            .eem-etapa-ref span { font-size: 11.5px; color: var(--exp-muted); line-height: 1.5; }

            /* ACOES */
            .eem-acoes {
                display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
                padding: 18px 22px; background: #fff; border: 1px solid var(--exp-border);
                border-radius: var(--exp-radius); box-shadow: var(--exp-shadow);
            }
        </style>
        <?php
    }
}

// Inicializa o painel
Explode_Admin_Empresas::init();
