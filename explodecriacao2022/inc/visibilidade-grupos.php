<?php
/**
 * Visibilidade dos desenhos por GRUPO
 * Tema: Explode Criação
 *
 * Antes a regra era fixa no código do template de votação: o colaborador só via os
 * desenhos que tivessem, entre os termos, o slug de um dos grupos dele. Não havia
 * como fazer um grupo enxergar outro sem editar PHP.
 *
 * Aqui essa decisão vira configuração. Para CADA grupo o administrador escolhe:
 *
 *   1. QUAIS GRUPOS aquele grupo enxerga (pode ser só ele, alguns, ou todos);
 *   2. QUAIS CATEGORIAS aquele grupo enxerga (Categoria A, B, C, D...);
 *   3. Se os desenhos PCD aparecem independentemente do grupo de origem.
 *
 * A configuração é por GRUPO, de propósito — e não por empresa, como acontece em
 * "Empresas e Etapas". São coisas diferentes: a empresa decide em que ETAPA o
 * colaborador está; aqui se decide O QUE ele enxerga dentro da etapa de votação.
 * Dois grupos da mesma empresa podem perfeitamente enxergar conjuntos diferentes.
 *
 * Quando um colaborador pertence a mais de um grupo, ele enxerga a UNIÃO do que
 * cada um dos grupos dele permite.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança
}

/** Nome da option onde a configuração fica gravada */
if ( ! defined( 'EXPLODE_VISIBILIDADE_OPTION' ) ) {
    define( 'EXPLODE_VISIBILIDADE_OPTION', 'explode_visibilidade_grupos' );
}

/* =====================================================================
 * LISTAS DE APOIO: GRUPOS E CATEGORIAS EXISTENTES
 * ===================================================================== */

/**
 * Garante que o mapa de termos da taxonomia esteja carregado
 */
if ( ! function_exists( 'explode_visibilidade_termos' ) ) {
    function explode_visibilidade_termos() {
        // Carrega o importador, dono do mapa de termos já testado
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }
        // Devolve o mapa completo
        return Explode_Admin_User_Importer::carregar_termos();
    }
}

/**
 * Lista os grupos cadastrados, no formato slug => array( slug, nome, id )
 */
if ( ! function_exists( 'explode_visibilidade_grupos_disponiveis' ) ) {
    function explode_visibilidade_grupos_disponiveis() {
        // Mapa de termos
        $termos = explode_visibilidade_termos();
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

        // Ordena pelo nome considerando números (Grupo 2 antes de Grupo 10)
        uasort( $lista, function( $a, $b ) {
            return strnatcasecmp( $a['nome'], $b['nome'] );
        } );

        // Devolve a lista pronta
        return $lista;
    }
}

/**
 * Lista as categorias cadastradas, no formato slug => array( slug, nome, id )
 * O termo PCD não entra aqui: ele é uma marcação, tratada por um interruptor próprio
 */
if ( ! function_exists( 'explode_visibilidade_categorias_disponiveis' ) ) {
    function explode_visibilidade_categorias_disponiveis() {
        // Mapa de termos
        $termos = explode_visibilidade_termos();
        // Lista de retorno
        $lista = array();

        // Percorre apenas os termos classificados como categoria
        foreach ( $termos['lista_cat'] as $item ) {
            // Indexa pelo slug
            $lista[ $item['slug'] ] = array(
                'slug' => $item['slug'],
                'nome' => $item['nome'],
                'id'   => (int) $item['id'],
            );
        }

        // Ordena pelo nome
        uasort( $lista, function( $a, $b ) {
            return strnatcasecmp( $a['nome'], $b['nome'] );
        } );

        // Devolve a lista pronta
        return $lista;
    }
}

/**
 * Slug do termo que marca os desenhos PCD
 */
if ( ! function_exists( 'explode_visibilidade_slug_pcd' ) ) {
    function explode_visibilidade_slug_pcd() {
        // O tema inteiro usa este slug para a marcação de PCD
        return 'pcd';
    }
}

/* =====================================================================
 * CONFIGURAÇÃO GRAVADA
 * ===================================================================== */

/**
 * Monta a configuração inicial na primeira execução
 *
 * O padrão reproduz a regra que existia no código, com uma diferença pedida pela
 * empresa: os grupos MAO passam a enxergar uns aos outros, porque a votação de
 * Manaus é conjunta. Todos os demais grupos continuam vendo apenas os próprios
 * desenhos, exatamente como antes.
 */
if ( ! function_exists( 'explode_visibilidade_semear' ) ) {
    function explode_visibilidade_semear() {
        // Grupos e categorias existentes
        $grupos     = explode_visibilidade_grupos_disponiveis();
        $categorias = array_keys( explode_visibilidade_categorias_disponiveis() );

        // Separa os grupos MAO usando o identificador oficial das regras MAO
        $grupos_mao = array();
        // Percorre os grupos cadastrados
        foreach ( $grupos as $slug => $g ) {
            // Usa o nome do termo, que é onde o marcador MAO aparece
            if ( function_exists( 'explode_grupo_e_mao' ) && explode_grupo_e_mao( $g['nome'] ) ) {
                $grupos_mao[] = $slug;
            }
        }

        // Configuração de saída
        $config = array();

        // Monta a regra de cada grupo
        foreach ( $grupos as $slug => $g ) {
            // Grupos MAO enxergam todos os grupos MAO; os demais só a si mesmos
            $visiveis = in_array( $slug, $grupos_mao, true ) ? $grupos_mao : array( $slug );

            // Registro do grupo
            $config[ $slug ] = array(
                'grupos'     => array_values( $visiveis ),
                'categorias' => $categorias,
                'pcd_global' => 1,
            );
        }

        // Devolve sem gravar: quem grava é o administrador ao salvar a tela
        return $config;
    }
}

/**
 * Devolve a configuração completa, semeando na primeira execução
 */
if ( ! function_exists( 'explode_visibilidade_get' ) ) {
    function explode_visibilidade_get() {
        // Lê a option
        $config = get_option( EXPLODE_VISIBILIDADE_OPTION, null );

        // Primeira execução: monta o padrão
        if ( null === $config || ! is_array( $config ) ) {
            return explode_visibilidade_semear();
        }

        // Normaliza cada registro para evitar índices ausentes
        $saida = array();
        // Percorre a configuração gravada
        foreach ( $config as $slug => $regra ) {
            // Ignora entradas inválidas
            if ( ! is_array( $regra ) ) {
                continue;
            }
            // Monta o registro normalizado
            $saida[ sanitize_title( $slug ) ] = array(
                'grupos'     => isset( $regra['grupos'] ) && is_array( $regra['grupos'] )
                    ? array_values( array_filter( array_map( 'sanitize_title', $regra['grupos'] ) ) )
                    : array(),
                'categorias' => isset( $regra['categorias'] ) && is_array( $regra['categorias'] )
                    ? array_values( array_filter( array_map( 'sanitize_title', $regra['categorias'] ) ) )
                    : array(),
                'pcd_global' => ! empty( $regra['pcd_global'] ) ? 1 : 0,
            );
        }

        // Devolve a configuração normalizada
        return $saida;
    }
}

/**
 * Regra de um grupo específico, com o padrão seguro quando ele ainda não foi configurado
 *
 * Um grupo criado depois da última gravação cai aqui: em vez de ficar sem regra
 * nenhuma (e não ver nada, ou ver tudo), ele recebe o padrão histórico do tema —
 * enxerga apenas os próprios desenhos, em todas as categorias.
 */
if ( ! function_exists( 'explode_visibilidade_do_grupo' ) ) {
    function explode_visibilidade_do_grupo( $slug ) {
        // Normaliza o slug recebido
        $slug = sanitize_title( $slug );
        // Configuração completa
        $config = explode_visibilidade_get();

        // Grupo já configurado
        if ( isset( $config[ $slug ] ) ) {
            return $config[ $slug ];
        }

        // Padrão para grupo ainda não configurado
        return array(
            'grupos'     => array( $slug ),
            'categorias' => array_keys( explode_visibilidade_categorias_disponiveis() ),
            'pcd_global' => 1,
        );
    }
}

/**
 * Grava a configuração, validando grupos e categorias contra a taxonomia
 * Devolve array com a configuração salva e os avisos gerados
 */
if ( ! function_exists( 'explode_visibilidade_salvar' ) ) {
    function explode_visibilidade_salvar( $recebido ) {
        // Listas válidas
        $grupos_validos = array_keys( explode_visibilidade_grupos_disponiveis() );
        $cats_validas   = array_keys( explode_visibilidade_categorias_disponiveis() );

        // Acumuladores
        $saida   = array();
        $avisos  = array();

        // Percorre cada grupo enviado pelo formulário
        foreach ( (array) $recebido as $slug => $regra ) {
            // Normaliza o slug do grupo dono da regra
            $slug = sanitize_title( $slug );
            // Descarta grupo inexistente na taxonomia
            if ( ! in_array( $slug, $grupos_validos, true ) ) {
                continue;
            }
            // Descarta entradas malformadas
            if ( ! is_array( $regra ) ) {
                continue;
            }

            // Valida os grupos marcados
            $grupos = array();
            // Percorre os grupos recebidos
            foreach ( (array) ( isset( $regra['grupos'] ) ? $regra['grupos'] : array() ) as $g ) {
                // Normaliza
                $g = sanitize_title( $g );
                // Só aceita grupo existente e sem repetição
                if ( in_array( $g, $grupos_validos, true ) && ! in_array( $g, $grupos, true ) ) {
                    $grupos[] = $g;
                }
            }

            // Valida as categorias marcadas
            $categorias = array();
            // Percorre as categorias recebidas
            foreach ( (array) ( isset( $regra['categorias'] ) ? $regra['categorias'] : array() ) as $c ) {
                // Normaliza
                $c = sanitize_title( $c );
                // Só aceita categoria existente e sem repetição
                if ( in_array( $c, $cats_validas, true ) && ! in_array( $c, $categorias, true ) ) {
                    $categorias[] = $c;
                }
            }

            // Avisa quando o grupo ficou sem enxergar nada, para não virar tela vazia em silêncio
            if ( empty( $grupos ) ) {
                $avisos[] = sprintf( 'O grupo "%s" ficou sem nenhum grupo marcado: os colaboradores dele não verão desenho nenhum na votação.', $slug );
            } elseif ( empty( $categorias ) ) {
                $avisos[] = sprintf( 'O grupo "%s" ficou sem nenhuma categoria marcada: os colaboradores dele não verão desenho nenhum na votação.', $slug );
            }

            // Guarda a regra normalizada
            $saida[ $slug ] = array(
                'grupos'     => $grupos,
                'categorias' => $categorias,
                'pcd_global' => ! empty( $regra['pcd_global'] ) ? 1 : 0,
            );
        }

        // Persiste a configuração
        update_option( EXPLODE_VISIBILIDADE_OPTION, $saida );

        // Devolve o resultado da gravação
        return array( 'config' => $saida, 'avisos' => $avisos );
    }
}

/* =====================================================================
 * CONSULTA EM TEMPO DE EXIBIÇÃO
 * ===================================================================== */

/**
 * Slugs dos grupos do colaborador
 * Reaproveita a resolução já testada do módulo de empresas
 */
if ( ! function_exists( 'explode_visibilidade_grupos_do_usuario' ) ) {
    function explode_visibilidade_grupos_do_usuario( $user_id = 0 ) {
        // Usa a função oficial quando disponível
        if ( function_exists( 'explode_empresas_slugs_do_usuario' ) ) {
            return explode_empresas_slugs_do_usuario( $user_id );
        }

        // Alternativa mínima, caso o módulo de empresas não esteja carregado
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        // Sem usuário não há grupo
        if ( $user_id < 1 ) {
            return array();
        }
        // Lê o campo do perfil
        $meta = get_user_meta( $user_id, 'user_field_funcionario_grupo', true );
        // Campo vazio
        if ( '' === trim( (string) $meta ) ) {
            return array();
        }
        // Separa e normaliza
        $partes = preg_split( '/\s*[,;\|\r\n]+\s*/u', (string) $meta, -1, PREG_SPLIT_NO_EMPTY );
        // Converte em slugs
        return array_values( array_unique( array_map( 'sanitize_title', array_map( 'trim', (array) $partes ) ) ) );
    }
}

/**
 * Grupos que o colaborador pode enxergar
 * Quando ele pertence a mais de um grupo, vale a união das regras
 */
if ( ! function_exists( 'explode_visibilidade_grupos_visiveis' ) ) {
    function explode_visibilidade_grupos_visiveis( $user_id = 0 ) {
        // Grupos do próprio colaborador
        $meus = explode_visibilidade_grupos_do_usuario( $user_id );
        // Sem grupo não há nada a enxergar
        if ( empty( $meus ) ) {
            return array();
        }

        // Acumula a união das regras
        $visiveis = array();
        // Percorre cada grupo do colaborador
        foreach ( $meus as $slug ) {
            // Regra deste grupo
            $regra = explode_visibilidade_do_grupo( $slug );
            // Junta os grupos permitidos
            $visiveis = array_merge( $visiveis, $regra['grupos'] );
        }

        // Devolve sem repetições
        return array_values( array_unique( $visiveis ) );
    }
}

/**
 * Categorias que o colaborador pode enxergar
 */
if ( ! function_exists( 'explode_visibilidade_categorias_visiveis' ) ) {
    function explode_visibilidade_categorias_visiveis( $user_id = 0 ) {
        // Grupos do próprio colaborador
        $meus = explode_visibilidade_grupos_do_usuario( $user_id );
        // Sem grupo não há nada a enxergar
        if ( empty( $meus ) ) {
            return array();
        }

        // Acumula a união das regras
        $visiveis = array();
        // Percorre cada grupo do colaborador
        foreach ( $meus as $slug ) {
            // Regra deste grupo
            $regra = explode_visibilidade_do_grupo( $slug );
            // Junta as categorias permitidas
            $visiveis = array_merge( $visiveis, $regra['categorias'] );
        }

        // Devolve sem repetições
        return array_values( array_unique( $visiveis ) );
    }
}

/**
 * Indica se o colaborador enxerga desenhos PCD de qualquer grupo
 */
if ( ! function_exists( 'explode_visibilidade_pcd_liberado' ) ) {
    function explode_visibilidade_pcd_liberado( $user_id = 0 ) {
        // Grupos do próprio colaborador
        $meus = explode_visibilidade_grupos_do_usuario( $user_id );
        // Percorre cada grupo procurando a permissão
        foreach ( $meus as $slug ) {
            // Regra deste grupo
            $regra = explode_visibilidade_do_grupo( $slug );
            // Basta um grupo liberar
            if ( ! empty( $regra['pcd_global'] ) ) {
                return true;
            }
        }
        // Nenhum grupo liberou
        return false;
    }
}

/**
 * DECISÃO PRINCIPAL: este desenho pode aparecer para este colaborador?
 *
 * @param array $slugs_do_desenho Slugs dos termos do desenho (grupo, categoria e pcd)
 * @param int   $user_id          Colaborador que está vendo a tela
 */
if ( ! function_exists( 'explode_visibilidade_pode_ver' ) ) {
    function explode_visibilidade_pode_ver( $slugs_do_desenho, $user_id = 0 ) {
        // Normaliza a lista recebida
        $slugs = array_values( array_filter( array_map( 'sanitize_title', (array) $slugs_do_desenho ) ) );

        // Desenho sem nenhum termo: não há como classificar, então não aparece
        if ( empty( $slugs ) ) {
            return false;
        }

        // Desenho marcado como PCD
        $e_pcd = in_array( explode_visibilidade_slug_pcd(), $slugs, true );

        // PCD liberado passa direto, independentemente do grupo de origem.
        // É o comportamento histórico do tema, agora sob controle do painel.
        if ( $e_pcd && explode_visibilidade_pcd_liberado( $user_id ) ) {
            return true;
        }

        // O desenho precisa pertencer a um grupo que o colaborador enxerga
        $grupos_visiveis = explode_visibilidade_grupos_visiveis( $user_id );
        // Sem interseção o desenho não aparece
        if ( empty( array_intersect( $grupos_visiveis, $slugs ) ) ) {
            return false;
        }

        // Categorias existentes na taxonomia
        $todas_cats = array_keys( explode_visibilidade_categorias_disponiveis() );
        // Categorias que este desenho realmente possui
        $cats_do_desenho = array_values( array_intersect( $slugs, $todas_cats ) );

        // Desenho sem categoria reconhecida: o grupo já bateu, então aparece.
        // Esconder aqui faria um desenho legítimo sumir por causa de cadastro incompleto.
        if ( empty( $cats_do_desenho ) ) {
            return true;
        }

        // Precisa bater ao menos uma categoria liberada
        $cats_visiveis = explode_visibilidade_categorias_visiveis( $user_id );
        // Decisão final
        return ! empty( array_intersect( $cats_visiveis, $cats_do_desenho ) );
    }
}

/**
 * Conveniência para os templates: decide a partir do ID do desenho
 */
if ( ! function_exists( 'explode_visibilidade_pode_ver_post' ) ) {
    function explode_visibilidade_pode_ver_post( $post_id, $user_id = 0 ) {
        // Lê os slugs dos termos do desenho
        $slugs = wp_get_post_terms( (int) $post_id, 'desenhos_cat', array( 'fields' => 'slugs' ) );
        // Erro de taxonomia não deve esconder o desenho em silêncio
        if ( is_wp_error( $slugs ) ) {
            return true;
        }
        // Aplica a regra
        return explode_visibilidade_pode_ver( $slugs, $user_id );
    }
}

/* =====================================================================
 * PAINEL ADMINISTRATIVO
 * ===================================================================== */

class Explode_Admin_Visibilidade {

    /** Slug da página administrativa */
    const MENU_SLUG = 'explode-visibilidade-grupos';

    /** Nome do nonce do formulário */
    const NONCE = 'explode_visibilidade_nonce';

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
        // Menu de nível superior, logo abaixo de Empresas
        add_menu_page(
            'Visibilidade dos Desenhos',
            'Visibilidade',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render' ),
            'dashicons-visibility',
            4
        );
    }

    /**
     * Processa o envio do formulário
     */
    public static function handle_post() {
        // Só age nesta página
        $pagina = isset( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';
        // Fora da página não faz nada
        if ( self::MENU_SLUG !== $pagina || ! isset( $_POST['explode_visibilidade_salvar'] ) ) {
            return;
        }

        // Verifica a permissão
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Você não tem permissão para alterar esta configuração.' );
        }

        // Verifica o nonce
        check_admin_referer( self::NONCE );

        // Lê a configuração enviada
        $recebido = isset( $_POST['visibilidade'] ) && is_array( $_POST['visibilidade'] )
            ? wp_unslash( $_POST['visibilidade'] )
            : array();

        // Grava
        $resultado = explode_visibilidade_salvar( $recebido );

        // Monta a URL de retorno com o resumo
        $url = add_query_arg(
            array(
                'page'   => self::MENU_SLUG,
                'salvo'  => count( $resultado['config'] ),
                'vazios' => count( $resultado['avisos'] ),
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
        $grupos     = explode_visibilidade_grupos_disponiveis();
        $categorias = explode_visibilidade_categorias_disponiveis();
        $config     = explode_visibilidade_get();
        $contagem   = function_exists( 'explode_empresas_contagem_por_grupo' ) ? explode_empresas_contagem_por_grupo() : array();
        $salvo      = isset( $_GET['salvo'] ) ? (int) $_GET['salvo'] : -1;
        $vazios     = isset( $_GET['vazios'] ) ? (int) $_GET['vazios'] : 0;
        // Indica se a tela está mostrando o padrão sugerido, ainda não gravado
        $nunca_salvo = ( null === get_option( EXPLODE_VISIBILIDADE_OPTION, null ) );

        // Abre o container e imprime o estilo
        echo '<div class="wrap explode-vis-wrap">';
        self::estilo();
        ?>

        <div class="evg-hero">
            <div class="evg-hero-left">
                <div class="evg-hero-badge"><span class="dashicons dashicons-visibility"></span> Módulo Administrativo Oficial</div>
                <h1 class="evg-hero-title">Visibilidade dos Desenhos</h1>
                <p class="evg-hero-desc">
                    Para cada <strong>grupo</strong>, escolha <strong>quais grupos</strong> e <strong>quais categorias</strong>
                    os colaboradores dele enxergam na votação. Esta tela é independente de
                    <strong>Empresas e Etapas</strong>: lá se define em que etapa cada empresa está,
                    aqui se define o que cada grupo vê dentro da votação.
                </p>
            </div>
            <div class="evg-hero-right">
                <a class="evg-btn evg-btn-secondary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=desenhos_cat&post_type=desenhos' ) ); ?>">
                    <span class="dashicons dashicons-category"></span> Gerenciar grupos e categorias
                </a>
            </div>
        </div>

        <?php if ( $salvo >= 0 ) : ?>
            <div class="evg-alert evg-alert-sucesso">
                <span class="dashicons dashicons-yes-alt"></span>
                <div>
                    <strong>Configuração salva.</strong>
                    Regras gravadas para <?php echo esc_html( $salvo ); ?> grupo(s).
                    <?php if ( $vazios > 0 ) : ?>
                        <br><?php echo esc_html( $vazios ); ?> grupo(s) ficaram sem nada marcado e não verão desenho nenhum.
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( $nunca_salvo ) : ?>
            <div class="evg-alert evg-alert-info">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong>Esta é a sugestão inicial, ainda não gravada.</strong>
                    Os grupos <strong>MAO</strong> já vêm marcados para enxergar uns aos outros, e cada
                    grupo comum vem vendo apenas os próprios desenhos — que era a regra antiga do site.
                    Confira e clique em <em>Salvar configuração</em> para valer.
                </div>
            </div>
        <?php endif; ?>

        <?php if ( empty( $grupos ) ) : ?>
            <div class="evg-alert evg-alert-warning">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong>Nenhum grupo cadastrado.</strong>
                    Cadastre os grupos na taxonomia de categorias dos desenhos para configurar a visibilidade.
                </div>
            </div>
        <?php else : ?>

        <!-- COMO A REGRA FUNCIONA -->
        <div class="evg-card evg-card-ref">
            <div class="evg-card-head">
                <div class="evg-card-icon icon-navy"><span class="dashicons dashicons-info-outline"></span></div>
                <div>
                    <h2 class="evg-card-title">Como a regra é aplicada</h2>
                    <p class="evg-card-sub">Um desenho aparece para o colaborador quando as duas condições batem.</p>
                </div>
            </div>
            <div class="evg-card-body">
                <div class="evg-regras">
                    <div class="evg-regra"><span class="evg-regra-num">1</span><div><strong>O grupo do desenho está marcado</strong><span>O desenho precisa ser de um dos grupos que este grupo enxerga.</span></div></div>
                    <div class="evg-regra"><span class="evg-regra-num">2</span><div><strong>A categoria do desenho está marcada</strong><span>E precisa estar em uma das categorias liberadas para este grupo.</span></div></div>
                    <div class="evg-regra"><span class="evg-regra-num">+</span><div><strong>Exceção do PCD</strong><span>Com a chave de PCD ligada, desenhos marcados como PCD aparecem mesmo vindo de um grupo não marcado.</span></div></div>
                </div>
                <p class="evg-help">
                    Quem pertence a mais de um grupo enxerga a soma do que cada grupo dele permite.
                </p>
            </div>
        </div>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>">
            <?php wp_nonce_field( self::NONCE ); ?>
            <input type="hidden" name="explode_visibilidade_salvar" value="1">

            <div class="evg-atalhos-topo">
                <button type="button" class="evg-btn evg-btn-ghost" id="evg-todos-proprio">
                    <span class="dashicons dashicons-admin-users"></span> Todos veem só o próprio grupo
                </button>
                <button type="button" class="evg-btn evg-btn-ghost" id="evg-todos-tudo">
                    <span class="dashicons dashicons-visibility"></span> Todos veem todos
                </button>
                <button type="button" class="evg-btn evg-btn-ghost" id="evg-mao-junto">
                    <span class="dashicons dashicons-groups"></span> Grupos MAO veem uns aos outros
                </button>
            </div>

            <div id="evg-lista">
                <?php
                // Renderiza um cartão por grupo
                foreach ( $grupos as $slug => $grupo ) {
                    // Regra atual deste grupo
                    $regra = isset( $config[ $slug ] ) ? $config[ $slug ] : explode_visibilidade_do_grupo( $slug );
                    // Desenha o cartão
                    self::render_grupo( $grupo, $regra, $grupos, $categorias, $contagem );
                }
                ?>
            </div>

            <div class="evg-acoes">
                <button type="submit" class="evg-btn evg-btn-primary">
                    <span class="dashicons dashicons-yes"></span> Salvar configuração
                </button>
            </div>
        </form>

        <script type="text/javascript">
        (function($){
            // Marca ou desmarca todas as caixas de um conjunto dentro de um cartão
            $(document).on('click', '.evg-mini', function(){
                // Cartão em que o botao foi clicado
                var $card  = $(this).closest('.evg-grupo');
                // Conjunto alvo: grupos ou categorias
                var alvo   = $(this).data('alvo');
                // Acao pedida
                var acao   = $(this).data('acao');
                // Caixas do conjunto
                var $itens = $card.find('.evg-check-' + alvo);

                // Marca todas
                if (acao === 'todos') { $itens.prop('checked', true); }
                // Desmarca todas
                if (acao === 'nenhum') { $itens.prop('checked', false); }
                // Deixa apenas o proprio grupo marcado
                if (acao === 'proprio') {
                    var meu = $card.data('slug');
                    $itens.each(function(){ $(this).prop('checked', $(this).val() === meu); });
                }
                // Marca apenas os grupos MAO
                if (acao === 'mao') {
                    $itens.each(function(){ $(this).prop('checked', $(this).data('mao') === 1); });
                }

                // Atualiza o resumo do cartao
                atualizarResumo($card);
            });

            // Atalhos que valem para a tela inteira
            $('#evg-todos-proprio').on('click', function(){
                $('.evg-grupo').each(function(){
                    var $card = $(this), meu = $card.data('slug');
                    $card.find('.evg-check-grupos').each(function(){ $(this).prop('checked', $(this).val() === meu); });
                    atualizarResumo($card);
                });
            });
            $('#evg-todos-tudo').on('click', function(){
                $('.evg-grupo').each(function(){
                    var $card = $(this);
                    $card.find('.evg-check-grupos, .evg-check-categorias').prop('checked', true);
                    atualizarResumo($card);
                });
            });
            $('#evg-mao-junto').on('click', function(){
                $('.evg-grupo').each(function(){
                    var $card = $(this);
                    // Só mexe nos cartoes dos proprios grupos MAO
                    if ($card.data('mao') !== 1) { return; }
                    $card.find('.evg-check-grupos').each(function(){ $(this).prop('checked', $(this).data('mao') === 1); });
                    atualizarResumo($card);
                });
            });

            // Recalcula o resumo sempre que uma caixa muda
            $(document).on('change', '.evg-check-grupos, .evg-check-categorias', function(){
                atualizarResumo($(this).closest('.evg-grupo'));
            });

            // Escreve quantos grupos e categorias estao marcados no cartao
            function atualizarResumo($card){
                // Contagens
                var g = $card.find('.evg-check-grupos:checked').length;
                var c = $card.find('.evg-check-categorias:checked').length;
                // Texto do resumo
                $card.find('.evg-resumo-grupos').text(g + (g === 1 ? ' grupo' : ' grupos'));
                $card.find('.evg-resumo-cats').text(c + (c === 1 ? ' categoria' : ' categorias'));
                // Destaca o cartao que nao enxerga nada
                $card.toggleClass('evg-vazio', g === 0 || c === 0);
            }

            // Estado inicial de todos os cartoes
            $('.evg-grupo').each(function(){ atualizarResumo($(this)); });
        })(jQuery);
        </script>

        <?php endif; ?>
        <?php
        // Fecha o container
        echo '</div>';
    }

    /**
     * Renderiza o cartão de um grupo
     */
    private static function render_grupo( $grupo, $regra, $grupos, $categorias, $contagem ) {
        // Prefixo dos campos deste cartão
        $base = 'visibilidade[' . $grupo['slug'] . ']';
        // Indica se este é um grupo MAO, usado pelos atalhos
        $e_mao = function_exists( 'explode_grupo_e_mao' ) && explode_grupo_e_mao( $grupo['nome'] );
        // Quantidade de colaboradores neste grupo
        $qtd = isset( $contagem[ $grupo['slug'] ] ) ? (int) $contagem[ $grupo['slug'] ] : 0;
        ?>
        <div class="evg-card evg-grupo" data-slug="<?php echo esc_attr( $grupo['slug'] ); ?>" data-mao="<?php echo $e_mao ? '1' : '0'; ?>">

            <div class="evg-card-head">
                <div class="evg-card-icon <?php echo $e_mao ? 'icon-amber' : 'icon-teal'; ?>">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="evg-grupo-titulo">
                    <h2 class="evg-card-title">
                        <?php echo esc_html( $grupo['nome'] ); ?>
                        <?php if ( $e_mao ) : ?><span class="evg-tag evg-tag-mao">MAO</span><?php endif; ?>
                    </h2>
                    <p class="evg-card-sub">
                        <code><?php echo esc_html( $grupo['slug'] ); ?></code>
                        &middot; <?php echo esc_html( number_format_i18n( $qtd ) ); ?> colaborador(es)
                    </p>
                </div>
                <div class="evg-resumos">
                    <span class="evg-pill evg-resumo-grupos">0 grupos</span>
                    <span class="evg-pill evg-resumo-cats">0 categorias</span>
                </div>
            </div>

            <div class="evg-card-body">
                <div class="evg-colunas">

                    <!-- GRUPOS QUE ESTE GRUPO ENXERGA -->
                    <div class="evg-coluna">
                        <div class="evg-coluna-head">
                            <span class="evg-label"><span class="dashicons dashicons-groups"></span> Enxerga os desenhos destes grupos</span>
                            <div class="evg-minis">
                                <button type="button" class="evg-mini" data-alvo="grupos" data-acao="todos">Todos</button>
                                <button type="button" class="evg-mini" data-alvo="grupos" data-acao="proprio">Só o próprio</button>
                                <?php if ( $e_mao ) : ?>
                                    <button type="button" class="evg-mini" data-alvo="grupos" data-acao="mao">Só MAO</button>
                                <?php endif; ?>
                                <button type="button" class="evg-mini" data-alvo="grupos" data-acao="nenhum">Nenhum</button>
                            </div>
                        </div>
                        <div class="evg-checks">
                            <?php foreach ( $grupos as $slug_g => $g ) :
                                // Marca se este alvo é um grupo MAO, para o atalho "Só MAO"
                                $alvo_mao = function_exists( 'explode_grupo_e_mao' ) && explode_grupo_e_mao( $g['nome'] );
                            ?>
                                <label class="evg-check <?php echo $slug_g === $grupo['slug'] ? 'evg-check-proprio' : ''; ?>">
                                    <input type="checkbox" class="evg-check-grupos"
                                           data-mao="<?php echo $alvo_mao ? '1' : '0'; ?>"
                                           name="<?php echo esc_attr( $base ); ?>[grupos][]"
                                           value="<?php echo esc_attr( $slug_g ); ?>"
                                           <?php checked( in_array( $slug_g, $regra['grupos'], true ) ); ?>>
                                    <span>
                                        <?php echo esc_html( $g['nome'] ); ?>
                                        <?php if ( $slug_g === $grupo['slug'] ) : ?><em>o próprio grupo</em><?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- CATEGORIAS QUE ESTE GRUPO ENXERGA -->
                    <div class="evg-coluna">
                        <div class="evg-coluna-head">
                            <span class="evg-label"><span class="dashicons dashicons-category"></span> Enxerga estas categorias</span>
                            <div class="evg-minis">
                                <button type="button" class="evg-mini" data-alvo="categorias" data-acao="todos">Todas</button>
                                <button type="button" class="evg-mini" data-alvo="categorias" data-acao="nenhum">Nenhuma</button>
                            </div>
                        </div>
                        <div class="evg-checks">
                            <?php foreach ( $categorias as $slug_c => $c ) : ?>
                                <label class="evg-check">
                                    <input type="checkbox" class="evg-check-categorias"
                                           name="<?php echo esc_attr( $base ); ?>[categorias][]"
                                           value="<?php echo esc_attr( $slug_c ); ?>"
                                           <?php checked( in_array( $slug_c, $regra['categorias'], true ) ); ?>>
                                    <span><?php echo esc_html( $c['nome'] ); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if ( empty( $categorias ) ) : ?>
                                <p class="evg-help">Nenhuma categoria cadastrada na taxonomia.</p>
                            <?php endif; ?>
                        </div>

                        <label class="evg-pcd">
                            <input type="checkbox" name="<?php echo esc_attr( $base ); ?>[pcd_global]" value="1"
                                   <?php checked( ! empty( $regra['pcd_global'] ) ); ?>>
                            <span>
                                <strong>Mostrar desenhos PCD de qualquer grupo</strong>
                                <em>Desmarque para que os desenhos PCD sigam as mesmas regras de grupo acima.</em>
                            </span>
                        </label>
                    </div>

                </div>

                <div class="evg-aviso-vazio">
                    <span class="dashicons dashicons-warning"></span>
                    Sem grupo ou sem categoria marcada, os colaboradores deste grupo não verão desenho nenhum na votação.
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Estilo da página
     */
    private static function estilo() {
        ?>
        <style>
            .explode-vis-wrap { max-width: 1180px; }
            .explode-vis-wrap * { box-sizing: border-box; }

            /* Cabecalho */
            .evg-hero {
                display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap;
                margin: 18px 0 22px; padding: 26px 30px; border-radius: 12px;
                background: linear-gradient(120deg, #143240, #1d4a5c);
                color: #fff; box-shadow: 0 10px 30px -14px rgba(20, 50, 64, .8);
            }
            .evg-hero-badge {
                display: inline-flex; align-items: center; gap: 7px; margin-bottom: 10px;
                padding: 5px 13px; border-radius: 20px; background: rgba(84, 197, 207, .22);
                border: 1px solid rgba(84, 197, 207, .5); font-size: 11px; font-weight: 700;
                text-transform: uppercase; letter-spacing: .6px;
            }
            .evg-hero-title { margin: 0 0 8px; font-size: 26px; font-weight: 800; color: #fff; }
            .evg-hero-desc { margin: 0; max-width: 760px; font-size: 13.5px; line-height: 1.65; opacity: .93; }

            /* Avisos */
            .evg-alert {
                display: flex; gap: 12px; align-items: flex-start;
                margin: 0 0 18px; padding: 14px 18px; border-radius: 9px; border-left: 4px solid;
                font-size: 13.5px; line-height: 1.6; background: #fff;
            }
            .evg-alert .dashicons { flex-shrink: 0; margin-top: 1px; }
            .evg-alert-sucesso { border-color: #46b450; background: #f0f9f1; }
            .evg-alert-warning { border-color: #ffb900; background: #fff8e5; }
            .evg-alert-info    { border-color: #54c5cf; background: #eefafc; }

            /* Cartoes */
            .evg-card {
                margin: 0 0 18px; border-radius: 11px; border: 1px solid #dfe4e8;
                background: #fff; box-shadow: 0 2px 8px -4px rgba(20, 50, 64, .22); overflow: hidden;
            }
            .evg-card-head {
                display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
                padding: 18px 22px; border-bottom: 1px solid #eef1f4; background: #fafbfc;
            }
            .evg-card-icon {
                display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                width: 42px; height: 42px; border-radius: 10px; color: #fff;
            }
            .evg-card-icon .dashicons { font-size: 21px; width: 21px; height: 21px; }
            .icon-teal  { background: linear-gradient(135deg, #5b9b99, #54c5cf); }
            .icon-navy  { background: linear-gradient(135deg, #143240, #1d4a5c); }
            .icon-amber { background: linear-gradient(135deg, #d99100, #ffc107); }
            .evg-card-title { margin: 0; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 9px; }
            .evg-card-sub   { margin: 3px 0 0; font-size: 12.5px; color: #667; }
            .evg-card-sub code { padding: 1px 6px; border-radius: 4px; background: #eef1f4; font-size: 11.5px; }
            .evg-card-body  { padding: 20px 22px; }
            .evg-grupo-titulo { flex: 1 1 220px; }

            /* Etiquetas e pilulas */
            .evg-tag {
                padding: 3px 10px; border-radius: 20px; font-size: 10.5px;
                font-weight: 800; letter-spacing: .5px; text-transform: uppercase;
            }
            .evg-tag-mao { background: #fff3cd; color: #8a6100; border: 1px solid #ffd970; }
            .evg-resumos { display: flex; gap: 8px; flex-wrap: wrap; margin-left: auto; }
            .evg-pill {
                padding: 6px 14px; border-radius: 20px; background: #eef7f8;
                border: 1px solid #bfe4e8; color: #14606b; font-size: 12px; font-weight: 700; white-space: nowrap;
            }

            /* Referencia da regra */
            .evg-regras { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
            .evg-regra { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 9px; background: #f7f9fa; border: 1px solid #e6ebee; }
            .evg-regra-num {
                display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                width: 26px; height: 26px; border-radius: 50%; background: #143240; color: #fff;
                font-size: 13px; font-weight: 800;
            }
            .evg-regra strong { display: block; font-size: 13.5px; margin-bottom: 3px; }
            .evg-regra span   { font-size: 12.5px; color: #667; line-height: 1.5; }
            .evg-help { margin: 14px 0 0; font-size: 12.5px; color: #667; font-style: italic; }

            /* Colunas de selecao */
            .evg-colunas { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
            @media (max-width: 900px) { .evg-colunas { grid-template-columns: 1fr; } }
            .evg-coluna-head {
                display: flex; align-items: center; justify-content: space-between; gap: 10px;
                flex-wrap: wrap; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #eef1f4;
            }
            .evg-label { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 700; color: #143240; }
            .evg-label .dashicons { font-size: 17px; width: 17px; height: 17px; color: #54c5cf; }
            .evg-minis { display: flex; gap: 6px; flex-wrap: wrap; }
            .evg-mini {
                padding: 4px 11px; border-radius: 5px; border: 1px solid #cfd8dd; background: #fff;
                color: #445; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all .15s ease;
            }
            .evg-mini:hover { border-color: #54c5cf; color: #14606b; background: #f2fbfc; }

            .evg-checks { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 7px; }
            .evg-check {
                display: flex; align-items: center; gap: 9px; margin: 0; padding: 10px 12px;
                border-radius: 7px; border: 1px solid #e2e7ea; background: #fbfcfd;
                font-size: 13px; cursor: pointer; transition: all .15s ease;
            }
            .evg-check:hover { border-color: #9fd8de; background: #f4fbfc; }
            .evg-check input { margin: 0; flex-shrink: 0; }
            .evg-check span { display: flex; flex-direction: column; gap: 1px; line-height: 1.35; }
            .evg-check em { font-size: 10.5px; color: #7a8a91; font-style: normal; }
            .evg-check input:checked + span { font-weight: 700; color: #14606b; }
            .evg-check-proprio { border-color: #bfe4e8; background: #f2fbfc; }

            /* Chave do PCD */
            .evg-pcd {
                display: flex; align-items: flex-start; gap: 10px; margin: 16px 0 0; padding: 13px 15px;
                border-radius: 8px; border: 1px dashed #cfd8dd; background: #fafbfc; cursor: pointer;
            }
            .evg-pcd input { margin: 2px 0 0; flex-shrink: 0; }
            .evg-pcd span { display: flex; flex-direction: column; gap: 3px; }
            .evg-pcd strong { font-size: 13px; }
            .evg-pcd em { font-size: 11.5px; color: #7a8a91; font-style: normal; line-height: 1.5; }

            /* Aviso de cartao vazio */
            .evg-aviso-vazio {
                display: none; align-items: center; gap: 9px; margin-top: 16px; padding: 11px 15px;
                border-radius: 7px; background: #fff4f4; border: 1px solid #f3c2c2; color: #a12b2b;
                font-size: 12.5px; font-weight: 600;
            }
            .evg-grupo.evg-vazio { border-color: #f3c2c2; }
            .evg-grupo.evg-vazio .evg-aviso-vazio { display: flex; }
            .evg-grupo.evg-vazio .evg-pill { background: #fff4f4; border-color: #f3c2c2; color: #a12b2b; }

            /* Botoes */
            .evg-btn {
                display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
                border-radius: 7px; border: 1px solid transparent; font-size: 13.5px;
                font-weight: 700; cursor: pointer; text-decoration: none; transition: all .15s ease;
            }
            .evg-btn .dashicons { font-size: 17px; width: 17px; height: 17px; }
            .evg-btn-primary { background: linear-gradient(90deg, #5b9b99, #54c5cf); color: #fff; }
            .evg-btn-primary:hover { filter: brightness(1.08); color: #fff; }
            .evg-btn-secondary { background: rgba(255, 255, 255, .16); border-color: rgba(255, 255, 255, .45); color: #fff; }
            .evg-btn-secondary:hover { background: rgba(255, 255, 255, .26); color: #fff; }
            .evg-btn-ghost { background: #fff; border-color: #cfd8dd; color: #445; }
            .evg-btn-ghost:hover { border-color: #54c5cf; color: #14606b; }

            .evg-atalhos-topo { display: flex; gap: 9px; flex-wrap: wrap; margin: 0 0 18px; }
            .evg-acoes {
                display: flex; justify-content: flex-end; gap: 10px; position: sticky; bottom: 0;
                margin-top: 20px; padding: 14px 0; background: linear-gradient(to top, #f0f0f1 70%, rgba(240,240,241,0));
            }
        </style>
        <?php
    }
}

// Inicializa o painel
Explode_Admin_Visibilidade::init();

/* =====================================================================
 * APOIO À TELA DE VOTAÇÃO
 * ===================================================================== */

/**
 * Nome do primeiro grupo do colaborador, usado como referência das faixas etárias
 */
if ( ! function_exists( 'explode_visibilidade_nome_grupo_do_usuario' ) ) {
    function explode_visibilidade_nome_grupo_do_usuario( $user_id = 0 ) {
        // Slugs dos grupos do colaborador
        $slugs = explode_visibilidade_grupos_do_usuario( $user_id );
        // Sem grupo não há referência
        if ( empty( $slugs ) ) {
            return '';
        }
        // Lista de grupos cadastrados
        $grupos = explode_visibilidade_grupos_disponiveis();
        // Devolve o nome do primeiro que existir na taxonomia
        foreach ( $slugs as $slug ) {
            if ( isset( $grupos[ $slug ] ) ) {
                return $grupos[ $slug ]['nome'];
            }
        }
        // Nenhum encontrado
        return '';
    }
}

/**
 * Faixa etária de cada categoria, no formato slug => "04 a 06 anos"
 *
 * As faixas não são digitadas em lugar nenhum: são descobertas percorrendo as idades
 * e perguntando a categoria_por_idade() qual categoria cada uma gera. Como os grupos
 * MAO têm faixas próprias, o texto sai correto para cada tipo de grupo.
 */
if ( ! function_exists( 'explode_visibilidade_faixas_por_categoria' ) ) {
    function explode_visibilidade_faixas_por_categoria( $nome_grupo_ref = '' ) {
        // Garante o importador carregado
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }

        // Intervalo de idade de cada categoria
        $intervalos = array();
        // Percorre as idades possíveis
        for ( $i = 0; $i <= 20; $i++ ) {
            // Categoria correspondente a esta idade
            $cat = Explode_Admin_User_Importer::categoria_por_idade( $i, $nome_grupo_ref );
            // Idade fora das faixas
            if ( '' === $cat ) {
                continue;
            }
            // Abre ou estende o intervalo
            if ( ! isset( $intervalos[ $cat ] ) ) {
                $intervalos[ $cat ] = array( $i, $i );
            } else {
                $intervalos[ $cat ][1] = $i;
            }
        }

        // Converte para slug => texto
        $saida = array();
        // Percorre os intervalos encontrados
        foreach ( $intervalos as $cat => $faixa ) {
            // Texto no formato usado pela tela
            $saida[ sanitize_title( $cat ) ] = str_pad( $faixa[0], 2, '0', STR_PAD_LEFT ) . ' a ' . str_pad( $faixa[1], 2, '0', STR_PAD_LEFT ) . ' anos';
        }

        // Devolve as faixas prontas
        return $saida;
    }
}

/**
 * Categorias que o colaborador enxerga, já com nome e faixa etária, prontas para a tela
 * Devolve array de slug => array( slug, nome, faixa )
 */
if ( ! function_exists( 'explode_visibilidade_categorias_para_tela' ) ) {
    function explode_visibilidade_categorias_para_tela( $user_id = 0 ) {
        // Categorias existentes na taxonomia
        $todas = explode_visibilidade_categorias_disponiveis();
        // Categorias liberadas para este colaborador
        $liberadas = explode_visibilidade_categorias_visiveis( $user_id );
        // Faixas etárias conforme o tipo de grupo do colaborador
        $faixas = explode_visibilidade_faixas_por_categoria( explode_visibilidade_nome_grupo_do_usuario( $user_id ) );

        // Monta a lista final preservando a ordem da taxonomia
        $saida = array();
        // Percorre as categorias existentes
        foreach ( $todas as $slug => $cat ) {
            // Pula as que este colaborador não enxerga
            if ( ! in_array( $slug, $liberadas, true ) ) {
                continue;
            }
            // Registro pronto para exibição
            $saida[ $slug ] = array(
                'slug'  => $slug,
                'nome'  => $cat['nome'],
                'faixa' => isset( $faixas[ $slug ] ) ? $faixas[ $slug ] : '',
            );
        }

        // Devolve a lista
        return $saida;
    }
}

/**
 * Grupos que o colaborador enxerga, com nome, prontos para a tela
 * Devolve array de slug => array( slug, nome, proprio )
 */
if ( ! function_exists( 'explode_visibilidade_grupos_para_tela' ) ) {
    function explode_visibilidade_grupos_para_tela( $user_id = 0 ) {
        // Grupos existentes na taxonomia
        $todos = explode_visibilidade_grupos_disponiveis();
        // Grupos liberados para este colaborador
        $liberados = explode_visibilidade_grupos_visiveis( $user_id );
        // Grupos do próprio colaborador, para destacar na tela
        $meus = explode_visibilidade_grupos_do_usuario( $user_id );

        // Monta a lista final preservando a ordem da taxonomia
        $saida = array();
        // Percorre os grupos existentes
        foreach ( $todos as $slug => $g ) {
            // Pula os que este colaborador não enxerga
            if ( ! in_array( $slug, $liberados, true ) ) {
                continue;
            }
            // Registro pronto para exibição
            $saida[ $slug ] = array(
                'slug'    => $slug,
                'nome'    => $g['nome'],
                'proprio' => in_array( $slug, $meus, true ),
            );
        }

        // Devolve a lista
        return $saida;
    }
}
