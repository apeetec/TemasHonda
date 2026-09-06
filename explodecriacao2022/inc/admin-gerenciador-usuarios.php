<?php
/**
 * Módulo de Gestão e Exclusão em Massa de Usuários por Grupo
 * Tema: Explode Criação
 * 
 * Funcionalidades:
 * - Exclusão de usuários por grupo específico (Grupo 1 ao 10 ou personalizados)
 * - Exclusão seletiva de usuários em lote com checkboxes
 * - Exclusão total de colaboradores (mantendo administradores protegidos)
 * - Exclusão opcional de desenhos e anexos vinculados aos usuários
 * - Processamento em lote (Batching) via AJAX com barra de progresso em tempo real
 * - Estatísticas dinâmicas e interface moderna nas cores da marca
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança
}

class Explode_Admin_User_Manager {

    /**
     * Inicialização dos hooks
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'wp_ajax_explode_obter_dados_gerenciador', array( __CLASS__, 'ajax_obter_dados' ) );
        add_action( 'wp_ajax_explode_excluir_usuarios_batch', array( __CLASS__, 'ajax_excluir_usuarios_batch' ) );
        add_action( 'wp_ajax_explode_obter_ids_grupo', array( __CLASS__, 'ajax_obter_ids_grupo' ) );
        add_action( 'wp_ajax_explode_obter_todos_ids_colaboradores', array( __CLASS__, 'ajax_obter_todos_ids_colaboradores' ) );
        add_action( 'wp_ajax_explode_analisar_csv_exclusao', array( __CLASS__, 'ajax_analisar_csv_exclusao' ) );
    }

    /** Tamanho máximo aceito para o CSV de exclusão (10 MB) */
    const MAX_CSV = 10485760;

    /**
     * Registra o menu no WP-Admin
     */
    public static function register_admin_menu() {
        // Menu de nível superior destacado
        add_menu_page(
            'Gestão & Exclusão de Usuários',
            'Exclusão de Usuários',
            'manage_options',
            'explode-gerenciar-usuarios',
            array( __CLASS__, 'render_admin_page' ),
            'dashicons-trash',
            71
        );

        // Também acessível dentro de Usuários
        add_submenu_page(
            'users.php',
            'Exclusão em Massa de Usuários',
            'Exclusão em Massa',
            'manage_options',
            'explode-gerenciar-usuarios',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    /**
     * Obtém a lista de grupos disponíveis e contagem de usuários
     */
    public static function get_groups_summary() {
        global $wpdb;

        // Lista padrão de grupos conhecidos
        $default_groups = array(
            'Grupo 1'  => 'Grupo 1 (SUM-HAB / Paulínia / Itajaí / Cariacica / Xangri-lá)',
            'Grupo 2'  => 'Grupo 2 (SUM-HAB-Itirapina)',
            'Grupo 3'  => 'Grupo 3 (SAO-HDA CT / ADM / SUM-CO / Peças / Previhonda)',
            'Grupo 4'  => 'Grupo 4 (SAO-CNH / Morumbi / Recife / Indaiatuba / SP2 / Jaboatão)',
            'Grupo 5'  => 'Grupo 5 (Estamparia / Solda)',
            'Grupo 6'  => 'Grupo 6 (Fundição / Usinagem / Mont. Motor)',
            'Grupo 7'  => 'Grupo 7 (Pintura / Inj. Plástica)',
            'Grupo 8'  => 'Grupo 8 (Mont. Motocicleta / CTP)',
            'Grupo 9'  => 'Grupo 9 (Logística / Infraestrutura)',
            'Grupo 10' => 'Grupo 10 (CDT / RH / CQ / Compras / TI / Jurídico / Outros)',
        );

        // Busca todos os valores de meta 'user_field_funcionario_grupo'
        $results = $wpdb->get_results(
            "SELECT user_id, meta_value 
             FROM {$wpdb->usermeta} 
             WHERE meta_key = 'user_field_funcionario_grupo' 
               AND meta_value != ''",
            ARRAY_A
        );

        $group_counts = array();
        $user_groups_map = array();

        foreach ( $results as $row ) {
            $uid = (int)$row['user_id'];
            
            // Ignora administradores na contagem de grupos para exclusão
            if ( user_can( $uid, 'administrator' ) ) {
                continue;
            }

            $raw_val = $row['meta_value'];
            // Suporta múltiplos grupos separados por vírgula ou ponto e vírgula
            $parts = preg_split( '/\s*[,;\|\r\n]+\s*/u', (string)$raw_val, -1, PREG_SPLIT_NO_EMPTY );
            
            foreach ( $parts as $grp ) {
                $grp = trim( $grp );
                if ( $grp === '' ) continue;

                if ( ! isset( $group_counts[ $grp ] ) ) {
                    $group_counts[ $grp ] = 0;
                }
                $group_counts[ $grp ]++;
                $user_groups_map[ $uid ][] = $grp;
            }
        }

        // Garante que grupos padrão existam no array mesmo que com 0 usuários
        $groups_data = array();
        foreach ( $default_groups as $k => $label ) {
            $groups_data[ $k ] = array(
                'key'   => $k,
                'label' => $label,
                'count' => isset( $group_counts[ $k ] ) ? $group_counts[ $k ] : 0,
            );
        }

        // Adiciona grupos extras que possam ter sido cadastrados na base
        foreach ( $group_counts as $k => $cnt ) {
            if ( ! isset( $groups_data[ $k ] ) ) {
                $groups_data[ $k ] = array(
                    'key'   => $k,
                    'label' => $k,
                    'count' => $cnt,
                );
            }
        }

        // Usuários sem grupo definido
        $all_subscribers_count = count( get_users( array( 'role__not_in' => array( 'administrator' ), 'fields' => 'ID' ) ) );
        $users_with_group = count( array_keys( $user_groups_map ) );
        $no_group_count = max( 0, $all_subscribers_count - $users_with_group );

        $groups_data['_sem_grupo'] = array(
            'key'   => '_sem_grupo',
            'label' => 'Sem Grupo Atribuído',
            'count' => $no_group_count,
        );

        return $groups_data;
    }

    /**
     * AJAX: Obter IDs de usuários de um grupo específico
     */
    public static function ajax_obter_ids_grupo() {
        check_ajax_referer( 'explode_user_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'delete_users' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ) );
        }

        $target_group = isset( $_POST['group'] ) ? sanitize_text_field( wp_unslash( $_POST['group'] ) ) : '';

        if ( empty( $target_group ) ) {
            wp_send_json_error( array( 'message' => 'Grupo não informado.' ) );
        }

        global $wpdb;
        $current_user_id = get_current_user_id();

        $user_ids = array();

        if ( $target_group === '_sem_grupo' ) {
            // Busca usuários que não possuem o meta ou o meta está vazio
            $all_users = get_users( array(
                'role__not_in' => array( 'administrator' ),
                'exclude'      => array( $current_user_id ),
                'fields'       => 'ID',
            ) );

            foreach ( $all_users as $uid ) {
                $grp = get_user_meta( $uid, 'user_field_funcionario_grupo', true );
                if ( empty( $grp ) ) {
                    $user_ids[] = (int)$uid;
                }
            }
        } else {
            // Busca por like no meta
            $query = $wpdb->prepare(
                "SELECT user_id, meta_value 
                 FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'user_field_funcionario_grupo' 
                   AND meta_value LIKE %s",
                '%' . $wpdb->esc_like( $target_group ) . '%'
            );

            $rows = $wpdb->get_results( $query, ARRAY_A );

            foreach ( $rows as $r ) {
                $uid = (int)$r['user_id'];
                
                // Proteção: Nunca incluir administradores ou o usuário atual
                if ( $uid === $current_user_id || user_can( $uid, 'administrator' ) ) {
                    continue;
                }

                // Validação exata do token de grupo
                $parts = preg_split( '/\s*[,;\|\r\n]+\s*/u', (string)$r['meta_value'], -1, PREG_SPLIT_NO_EMPTY );
                $parts = array_map( 'trim', $parts );
                if ( in_array( $target_group, $parts, true ) || stripos( $r['meta_value'], $target_group ) !== false ) {
                    $user_ids[] = $uid;
                }
            }
        }

        $user_ids = array_values( array_unique( $user_ids ) );

        wp_send_json_success( array(
            'group'    => $target_group,
            'user_ids' => $user_ids,
            'total'    => count( $user_ids ),
        ) );
    }

    /**
     * AJAX: Obter todos os IDs de colaboradores (não-administradores)
     */
    public static function ajax_obter_todos_ids_colaboradores() {
        check_ajax_referer( 'explode_user_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'delete_users' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ) );
        }

        $current_user_id = get_current_user_id();

        $users = get_users( array(
            'role__not_in' => array( 'administrator' ),
            'exclude'      => array( $current_user_id ),
            'fields'       => 'ID',
        ) );

        $user_ids = array_map( 'intval', $users );

        wp_send_json_success( array(
            'user_ids' => $user_ids,
            'total'    => count( $user_ids ),
        ) );
    }

    /* =====================================================================
     * EXCLUSAO A PARTIR DE UM CSV DE MATRICULAS
     *
     * O gerenciador só permitia escolher por grupo, tudo, ou marcando um a um.
     * Para desligar um lote específico de colaboradores era preciso caçar cada
     * matrícula na tela. Aqui o mesmo CSV usado na importação (ou uma lista
     * simples de matrículas) passa a servir para a exclusão.
     *
     * Nada é apagado nesta etapa: o arquivo é apenas lido e conferido. A exclusão
     * continua acontecendo no fluxo já existente, com as mesmas salvaguardas.
     * ===================================================================== */

    /**
     * Normaliza uma matrícula do mesmo jeito que o importador faz
     * Garante que "1.001", " 1001 " e "1001" sejam a mesma coisa
     */
    private static function chave_matricula( $valor ) {
        // Reaproveita a normalização oficial do importador quando disponível
        if ( class_exists( 'Explode_Admin_User_Importer' ) ) {
            return Explode_Admin_User_Importer::chave_matricula( $valor );
        }
        // Alternativa equivalente, caso o importador não esteja carregado
        $v = trim( preg_replace( '/\s+/u', ' ', (string) $valor ) );
        $v = str_replace( array( ' ', '.', '-' ), '', $v );
        return strtoupper( $v );
    }

    /**
     * Extrai a lista de matrículas de um arquivo CSV
     *
     * Aceita as duas formas que aparecem na prática:
     *   1. O mesmo CSV da importação, com cabeçalho e uma coluna de matrícula;
     *   2. Uma lista solta, com uma matrícula por linha e sem cabeçalho.
     *
     * Devolve array( 'matriculas' => array, 'coluna' => string, 'linhas' => int )
     */
    public static function extrair_matriculas_do_csv( $caminho ) {
        // Abre o arquivo
        $fp = @fopen( $caminho, 'r' );
        // Falha de leitura
        if ( ! $fp ) {
            return new WP_Error( 'abertura', 'Não foi possível ler o arquivo enviado.' );
        }

        // Lê a primeira linha crua para descobrir o separador
        $primeira = fgets( $fp );
        // Arquivo vazio
        if ( false === $primeira ) {
            fclose( $fp );
            return new WP_Error( 'vazio', 'O arquivo enviado está vazio.' );
        }

        // Remove o BOM que o Excel costuma gravar
        $primeira = preg_replace( '/^\xEF\xBB\xBF/', '', $primeira );

        // Descobre o separador reaproveitando a detecção do importador
        if ( class_exists( 'Explode_Admin_User_Importer' ) ) {
            $delim = Explode_Admin_User_Importer::detectar_delimitador( $primeira );
        } else {
            // Alternativa simples: o candidato mais frequente na primeira linha
            $cand = array( ';' => substr_count( $primeira, ';' ), ',' => substr_count( $primeira, ',' ), "\t" => substr_count( $primeira, "\t" ), '|' => substr_count( $primeira, '|' ) );
            arsort( $cand );
            $delim = key( $cand );
            $delim = $cand[ $delim ] > 0 ? $delim : ';';
        }

        // Volta ao início para ler como CSV
        rewind( $fp );

        // Lê o cabeçalho
        $cabecalho = fgetcsv( $fp, 0, $delim );
        // Cabeçalho ilegível
        if ( ! is_array( $cabecalho ) ) {
            fclose( $fp );
            return new WP_Error( 'cabecalho', 'Não foi possível interpretar o arquivo como CSV.' );
        }

        // Limpa o BOM da primeira célula
        if ( isset( $cabecalho[0] ) ) {
            $cabecalho[0] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $cabecalho[0] );
        }

        // Procura a coluna de matrícula pelo mapeamento do importador
        $indice = null;
        $rotulo = '';
        if ( class_exists( 'Explode_Admin_User_Importer' ) ) {
            // Reaproveita o reconhecimento de colunas já testado
            $mapa = Explode_Admin_User_Importer::mapear_colunas( $cabecalho );
            // Coluna encontrada
            if ( isset( $mapa['colaborador']['matricula'] ) ) {
                $indice = (int) $mapa['colaborador']['matricula'];
                $rotulo = isset( $mapa['rotulos'][ $indice ] ) ? $mapa['rotulos'][ $indice ] : '';
            }
        }

        // Acumuladores
        $matriculas = array();
        $linhas     = 0;

        // Sem coluna reconhecida, o arquivo é tratado como lista simples:
        // a primeira coluna vale como matrícula e o cabeçalho também é lido,
        // porque em uma lista solta a primeira linha já é um dado.
        if ( null === $indice ) {
            $indice = 0;
            $rotulo = '';
            // A primeira linha entra como dado
            $valor = isset( $cabecalho[0] ) ? self::limpar_celula( $cabecalho[0] ) : '';
            // Só aproveita se parecer uma matrícula, e não um título de coluna
            if ( '' !== $valor && ! preg_match( '/[a-z]{4,}/i', $valor ) ) {
                $matriculas[] = $valor;
                $linhas++;
            }
        }

        // Percorre o restante do arquivo
        while ( false !== ( $linha = fgetcsv( $fp, 0, $delim ) ) ) {
            // Ignora linhas totalmente vazias
            if ( ! is_array( $linha ) ) {
                continue;
            }
            // Lê a célula da matrícula
            $valor = isset( $linha[ $indice ] ) ? self::limpar_celula( $linha[ $indice ] ) : '';
            // Descarta vazios
            if ( '' === $valor ) {
                continue;
            }
            // Guarda o valor bruto; a normalização acontece na resolução
            $matriculas[] = $valor;
            $linhas++;
        }

        // Fecha o arquivo
        fclose( $fp );

        // Remove repetições preservando a ordem de aparição
        $unicas = array();
        // Percorre as matrículas lidas
        foreach ( $matriculas as $mat ) {
            // Chave normalizada evita contar "1.001" e "1001" duas vezes
            $k = self::chave_matricula( $mat );
            // Guarda a primeira grafia encontrada
            if ( '' !== $k && ! isset( $unicas[ $k ] ) ) {
                $unicas[ $k ] = $mat;
            }
        }

        // Devolve o resultado da leitura
        return array(
            'matriculas' => $unicas,
            'coluna'     => $rotulo,
            'linhas'     => $linhas,
        );
    }

    /**
     * Limpa uma célula do CSV: BOM, espaços invisíveis e espaços das pontas
     */
    private static function limpar_celula( $valor ) {
        // Remove BOM e espaço não separável
        $v = str_replace( array( "\xEF\xBB\xBF", "\xC2\xA0" ), array( '', ' ' ), (string) $valor );
        // Colapsa espaços repetidos
        $v = preg_replace( '/\s+/u', ' ', $v );
        // Tira os espaços das pontas
        return trim( (string) $v );
    }

    /**
     * Resolve matrículas em usuários, separando o que pode e o que não pode ser excluído
     */
    public static function resolver_matriculas( $matriculas ) {
        // Acesso direto ao banco para uma única leitura de todos os logins
        global $wpdb;

        // Usuário logado, que nunca pode ser excluído
        $atual = get_current_user_id();

        // Índice de login normalizado => ID
        $indice = array();
        // Lê todos os logins de uma vez, evitando uma consulta por matrícula
        $linhas = $wpdb->get_results( "SELECT ID, user_login FROM {$wpdb->users}", ARRAY_A );
        // Monta o índice
        if ( is_array( $linhas ) ) {
            foreach ( $linhas as $l ) {
                // Chave normalizada do login
                $k = self::chave_matricula( $l['user_login'] );
                // Guarda o primeiro ID encontrado para a chave
                if ( '' !== $k && ! isset( $indice[ $k ] ) ) {
                    $indice[ $k ] = (int) $l['ID'];
                }
            }
        }

        // Listas de saída
        $encontrados    = array();
        $nao_encontrados = array();
        $protegidos     = array();

        // Percorre cada matrícula pedida
        foreach ( $matriculas as $chave => $bruta ) {
            // Sem correspondência no WordPress
            if ( ! isset( $indice[ $chave ] ) ) {
                $nao_encontrados[] = $bruta;
                continue;
            }

            // ID do usuário correspondente
            $uid  = $indice[ $chave ];
            // Dados do usuário
            $user = get_userdata( $uid );

            // Usuário sumiu entre a leitura e agora
            if ( ! $user ) {
                $nao_encontrados[] = $bruta;
                continue;
            }

            // SALVAGUARDA: o administrador logado nunca entra na lista
            if ( $uid === $atual ) {
                $protegidos[] = array( 'matricula' => $bruta, 'login' => $user->user_login, 'nome' => $user->display_name, 'motivo' => 'É o seu próprio usuário.' );
                continue;
            }

            // SALVAGUARDA: administradores nunca entram na lista
            if ( user_can( $uid, 'administrator' ) ) {
                $protegidos[] = array( 'matricula' => $bruta, 'login' => $user->user_login, 'nome' => $user->display_name, 'motivo' => 'Possui perfil de Administrador.' );
                continue;
            }

            // Conta os desenhos vinculados, para o administrador saber o que perde
            $desenhos = get_posts( array(
                'post_type'   => 'desenhos',
                'author'      => $uid,
                'numberposts' => -1,
                'post_status' => 'any',
                'fields'      => 'ids',
            ) );

            // Registro pronto para a conferência
            $encontrados[] = array(
                'id'        => $uid,
                'matricula' => $bruta,
                'login'     => $user->user_login,
                'nome'      => $user->display_name,
                'grupo'     => (string) get_user_meta( $uid, 'user_field_funcionario_grupo', true ),
                'desenhos'  => count( $desenhos ),
            );
        }

        // Devolve as três listas
        return array(
            'encontrados'     => $encontrados,
            'nao_encontrados' => $nao_encontrados,
            'protegidos'      => $protegidos,
        );
    }

    /**
     * AJAX: recebe o CSV, lê as matrículas e devolve a conferência
     * Esta chamada NÃO exclui nada: ela apenas monta a lista para o administrador conferir
     */
    public static function ajax_analisar_csv_exclusao() {
        // Confere o nonce
        check_ajax_referer( 'explode_user_manager_nonce', 'nonce' );

        // Confere a permissão
        if ( ! current_user_can( 'delete_users' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ) );
        }

        // Valida a presença do arquivo
        if ( empty( $_FILES['arquivo_csv'] ) || ! isset( $_FILES['arquivo_csv']['tmp_name'] ) || '' === $_FILES['arquivo_csv']['tmp_name'] ) {
            wp_send_json_error( array( 'message' => 'Selecione um arquivo CSV.' ) );
        }

        // Atalho para os dados do arquivo
        $arquivo = $_FILES['arquivo_csv'];

        // Erros de upload do PHP
        if ( ! empty( $arquivo['error'] ) ) {
            wp_send_json_error( array( 'message' => 'Falha no envio do arquivo (código ' . (int) $arquivo['error'] . ').' ) );
        }

        // Tamanho máximo
        if ( (int) $arquivo['size'] > self::MAX_CSV ) {
            wp_send_json_error( array( 'message' => 'O arquivo excede o limite de ' . size_format( self::MAX_CSV ) . '.' ) );
        }

        // Extensão aceita
        $ext = strtolower( pathinfo( $arquivo['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, array( 'csv', 'txt' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Envie um arquivo .csv (ou .txt separado por ponto e vírgula).' ) );
        }

        // Confirma que veio mesmo de um upload HTTP
        if ( ! is_uploaded_file( $arquivo['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => 'Arquivo temporário inválido.' ) );
        }

        // Lê as matrículas
        $leitura = self::extrair_matriculas_do_csv( $arquivo['tmp_name'] );

        // Erro de leitura
        if ( is_wp_error( $leitura ) ) {
            wp_send_json_error( array( 'message' => $leitura->get_error_message() ) );
        }

        // Nenhuma matrícula utilizável
        if ( empty( $leitura['matriculas'] ) ) {
            wp_send_json_error( array( 'message' => 'Nenhuma matrícula foi encontrada no arquivo. Confira se existe uma coluna "matricula" ou uma matrícula por linha.' ) );
        }

        // Resolve as matrículas em usuários
        $resolvido = self::resolver_matriculas( $leitura['matriculas'] );

        // Soma os desenhos que seriam apagados junto
        $total_desenhos = 0;
        foreach ( $resolvido['encontrados'] as $e ) {
            $total_desenhos += (int) $e['desenhos'];
        }

        // Devolve a conferência completa
        wp_send_json_success( array(
            'arquivo'         => sanitize_file_name( $arquivo['name'] ),
            'coluna'          => $leitura['coluna'],
            'linhas'          => (int) $leitura['linhas'],
            'matriculas'      => count( $leitura['matriculas'] ),
            'encontrados'     => $resolvido['encontrados'],
            'nao_encontrados' => $resolvido['nao_encontrados'],
            'protegidos'      => $resolvido['protegidos'],
            'user_ids'        => wp_list_pluck( $resolvido['encontrados'], 'id' ),
            'total_desenhos'  => $total_desenhos,
        ) );
    }

    /**
     * AJAX: Excluir lote de usuários de forma segura com batching
     */
    public static function ajax_excluir_usuarios_batch() {
        check_ajax_referer( 'explode_user_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'delete_users' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Você não tem permissão para excluir usuários.' ) );
        }

        require_once( ABSPATH . 'wp-admin/includes/user.php' );
        require_once( ABSPATH . 'wp-admin/includes/post.php' );

        $user_ids = isset( $_POST['user_ids'] ) ? array_map( 'intval', (array)$_POST['user_ids'] ) : array();
        $delete_drawings = isset( $_POST['delete_drawings'] ) && $_POST['delete_drawings'] === 'true';

        if ( empty( $user_ids ) ) {
            wp_send_json_error( array( 'message' => 'Nenhum usuário fornecido para exclusão.' ) );
        }

        $current_user_id = get_current_user_id();
        $deleted_count = 0;
        $drawings_deleted_count = 0;
        $errors = array();

        foreach ( $user_ids as $uid ) {
            // SALVAGUARDA 1: Nunca excluir o usuário logado
            if ( $uid === $current_user_id ) {
                $errors[] = "Usuário ID {$uid} não pôde ser excluído: É o administrador atualmente logado.";
                continue;
            }

            // SALVAGUARDA 2: Nunca excluir administradores
            if ( user_can( $uid, 'administrator' ) ) {
                $errors[] = "Usuário ID {$uid} não pôde ser excluído: Possui perfil de Administrador.";
                continue;
            }

            $user_obj = get_userdata( $uid );
            if ( ! $user_obj ) {
                continue; // Já não existe
            }

            // Exclusão dos desenhos e arquivos anexados se solicitado
            if ( $delete_drawings ) {
                $user_drawings = get_posts( array(
                    'post_type'   => 'desenhos',
                    'author'      => $uid,
                    'numberposts' => -1,
                    'post_status' => 'any',
                    'fields'      => 'ids',
                ) );

                foreach ( $user_drawings as $pid ) {
                    // Exclui anexo vinculado no meta desenhos_box_desenho se for ID
                    $attach_meta = get_post_meta( $pid, 'desenhos_box_desenho', true );
                    if ( is_numeric( $attach_meta ) ) {
                        wp_delete_attachment( (int)$attach_meta, true );
                    }
                    
                    // Exclui o post do desenho permanentemente
                    wp_delete_post( $pid, true );
                    $drawings_deleted_count++;
                }
            }

            // Executa a exclusão oficial do WordPress (limpa usermeta e associações)
            $deleted = wp_delete_user( $uid );

            if ( $deleted ) {
                $deleted_count++;
            } else {
                $errors[] = "Falha ao excluir o usuário {$user_obj->user_login} (ID: {$uid}).";
            }
        }

        wp_send_json_success( array(
            'deleted_count'          => $deleted_count,
            'drawings_deleted_count' => $drawings_deleted_count,
            'errors'                 => $errors,
        ) );
    }

    /**
     * Renderiza a página administrativa moderna e responsiva
     */
    public static function render_admin_page() {
        if ( ! current_user_can( 'delete_users' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'explode' ) );
        }

        $groups_summary = self::get_groups_summary();
        $nonce = wp_create_nonce( 'explode_user_manager_nonce' );

        // Estatísticas gerais
        $total_admins = count( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) );
        $all_colaboradores = get_users( array( 'role__not_in' => array( 'administrator' ) ) );
        $total_colaboradores = count( $all_colaboradores );

        $total_votaram = 0;
        $total_desenhos_enviados = 0;

        foreach ( $all_colaboradores as $u ) {
            $votou = get_user_meta( $u->ID, 'user_field_votacao', true );
            if ( $votou === 'Sim' ) {
                $total_votaram++;
            }

            // Percorre todos os slots de dependente do tema, e não apenas os quatro
            // primeiros: com o limite maior, os últimos ficavam de fora da contagem
            $max_dep = function_exists( 'explode_max_dependentes' ) ? explode_max_dependentes() : 6;
            // Marca se este colaborador tem ao menos um desenho enviado
            $tem_desenho = false;
            // Verifica cada slot
            for ( $s = 1; $s <= $max_dep; $s++ ) {
                if ( 'Sim' === get_user_meta( $u->ID, 'user_field_dependente_' . $s . '_desenho', true ) ) {
                    $tem_desenho = true;
                    break;
                }
            }

            if ( $tem_desenho ) {
                $total_desenhos_enviados++;
            }
        }

        $total_desenhos_posts = wp_count_posts( 'desenhos' );
        $total_posts_publicados = isset( $total_desenhos_posts->publish ) ? $total_desenhos_posts->publish : 0;
        ?>
        <div class="wrap explode-admin-wrap">
            
            <!-- CABEÇALHO HERO -->
            <div class="explode-hero">
                <div class="explode-hero-left">
                    <div class="explode-hero-badge"><i class="dashicons dashicons-shield-alt"></i> Módulo Administrativo Oficial</div>
                    <h1 class="explode-hero-title">Gestão e Exclusão em Massa de Usuários</h1>
                    <p class="explode-hero-desc">
                        Gerencie, filtre e remova colaboradores por <strong>Grupo de Funcionário</strong>, <strong>Unidade</strong> ou <strong>Seleção Personalizada</strong> com salvaguardas automáticas e processamento seguro em lote.
                    </p>
                </div>
                <div class="explode-hero-right">
                    <div class="explode-hero-actions">
                        <button type="button" id="btn-recarregar-dados" class="explode-btn explode-btn-ghost">
                            <i class="dashicons dashicons-update"></i> Atualizar Dados
                        </button>
                        <a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" class="explode-btn explode-btn-secondary">
                            <i class="dashicons dashicons-admin-users"></i> Usuários do WordPress
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARDS DE ESTATÍSTICAS (KPIS) -->
            <div class="explode-kpi-grid">
                <div class="explode-kpi-card">
                    <div class="explode-kpi-icon icon-blue">
                        <i class="dashicons dashicons-groups"></i>
                    </div>
                    <div class="explode-kpi-info">
                        <span class="explode-kpi-label">Total de Colaboradores</span>
                        <strong class="explode-kpi-value" id="kpi-total-colaboradores"><?php echo esc_html( number_format_i18n( $total_colaboradores ) ); ?></strong>
                        <span class="explode-kpi-sub">Elegíveis à exclusão</span>
                    </div>
                </div>

                <div class="explode-kpi-card">
                    <div class="explode-kpi-icon icon-cyan">
                        <i class="dashicons dashicons-category"></i>
                    </div>
                    <div class="explode-kpi-info">
                        <span class="explode-kpi-label">Grupos Ativos</span>
                        <strong class="explode-kpi-value"><?php echo esc_html( count( $groups_summary ) ); ?></strong>
                        <span class="explode-kpi-sub">Grupos 1 ao 10 + extras</span>
                    </div>
                </div>

                <div class="explode-kpi-card">
                    <div class="explode-kpi-icon icon-teal">
                        <i class="dashicons dashicons-format-image"></i>
                    </div>
                    <div class="explode-kpi-info">
                        <span class="explode-kpi-label">Desenhos Postados</span>
                        <strong class="explode-kpi-value"><?php echo esc_html( number_format_i18n( $total_posts_publicados ) ); ?></strong>
                        <span class="explode-kpi-sub"><?php echo esc_html( $total_desenhos_enviados ); ?> colaboradores enviaram</span>
                    </div>
                </div>

                <div class="explode-kpi-card">
                    <div class="explode-kpi-icon icon-green">
                        <i class="dashicons dashicons-yes-alt"></i>
                    </div>
                    <div class="explode-kpi-info">
                        <span class="explode-kpi-label">Votos Computados</span>
                        <strong class="explode-kpi-value"><?php echo esc_html( number_format_i18n( $total_votaram ) ); ?></strong>
                        <span class="explode-kpi-sub">Participaram da votação</span>
                    </div>
                </div>

                <div class="explode-kpi-card">
                    <div class="explode-kpi-icon icon-gold">
                        <i class="dashicons dashicons-lock"></i>
                    </div>
                    <div class="explode-kpi-info">
                        <span class="explode-kpi-label">Admins Protegidos</span>
                        <strong class="explode-kpi-value"><?php echo esc_html( $total_admins ); ?></strong>
                        <span class="explode-kpi-sub text-success">100% blindados de remoção</span>
                    </div>
                </div>
            </div>

            <!-- PAINEL DE CONTROLE DE EXCLUSÃO RÁPIDA -->
            <div class="explode-panels-grid">
                
                <!-- CARD: EXCLUSÃO POR GRUPO -->
                <div class="explode-card explode-card-action">
                    <div class="explode-card-header">
                        <div class="explode-card-icon icon-danger">
                            <i class="dashicons dashicons-networking"></i>
                        </div>
                        <div>
                            <h2 class="explode-card-title">1. Excluir Colaboradores por Grupo</h2>
                            <p class="explode-card-subtitle">Selecione um grupo para remover todos os usuários vinculados de forma imediata.</p>
                        </div>
                    </div>
                    <div class="explode-card-body">
                        <div class="explode-form-group">
                            <label for="select-grupo-excluir" class="explode-label">Selecione o Grupo:</label>
                            <select id="select-grupo-excluir" class="explode-select">
                                <option value="">-- Selecione o Grupo desejado --</option>
                                <?php foreach ( $groups_summary as $k => $grp ) : ?>
                                    <option value="<?php echo esc_attr( $grp['key'] ); ?>" data-count="<?php echo esc_attr( $grp['count'] ); ?>">
                                        <?php echo esc_html( $grp['label'] ); ?> (<?php echo esc_html( $grp['count'] ); ?> usuários)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="explode-checkbox-wrap">
                            <label class="explode-checkbox-label">
                                <input type="checkbox" id="chk-apagar-desenhos-grupo" checked>
                                <span>Excluir também os desenhos e imagens postados por esses usuários</span>
                            </label>
                        </div>

                        <div class="explode-group-stats-preview" id="preview-grupo-selecionado" style="display: none;">
                            <div class="explode-preview-box">
                                <i class="dashicons dashicons-warning"></i>
                                <span>Serão excluídos <strong><span id="txt-qtd-grupo">0</span> colaboradores</strong> pertencentes ao grupo selecionado.</span>
                            </div>
                        </div>
                    </div>
                    <div class="explode-card-footer">
                        <button type="button" id="btn-executar-exclusao-grupo" class="explode-btn explode-btn-danger" disabled>
                            <i class="dashicons dashicons-trash"></i> Excluir Todos deste Grupo
                        </button>
                    </div>
                </div>

                <!-- CARD: EXCLUSÃO GERAL / ZERAR BASE -->
                <div class="explode-card explode-card-action">
                    <div class="explode-card-header">
                        <div class="explode-card-icon icon-danger-dark">
                            <i class="dashicons dashicons-dismiss"></i>
                        </div>
                        <div>
                            <h2 class="explode-card-title">2. Excluir Todos os Colaboradores</h2>
                            <p class="explode-card-subtitle">Limpa toda a base de colaboradores (Assinantes) de uma só vez para um novo ciclo.</p>
                        </div>
                    </div>
                    <div class="explode-card-body">
                        <div class="explode-alert explode-alert-warning">
                            <i class="dashicons dashicons-shield"></i>
                            <div>
                                <strong>Salvaguarda Automática Ativa:</strong> Usuários com perfil de Administrador e o seu usuário atual <u>nunca serão apagados</u>.
                            </div>
                        </div>

                        <div class="explode-checkbox-wrap" style="margin-top: 15px;">
                            <label class="explode-checkbox-label">
                                <input type="checkbox" id="chk-apagar-desenhos-todos" checked>
                                <span>Excluir também todos os posts de desenhos e mídias vinculadas</span>
                            </label>
                        </div>

                        <p class="explode-tip-text">
                            Total de <strong><?php echo esc_html( $total_colaboradores ); ?> colaboradores</strong> serão removidos permanentemente.
                        </p>
                    </div>
                    <div class="explode-card-footer">
                        <button type="button" id="btn-executar-exclusao-todos" class="explode-btn explode-btn-danger-outline" <?php echo $total_colaboradores === 0 ? 'disabled' : ''; ?>>
                            <i class="dashicons dashicons-warning"></i> Zerar Todos os Colaboradores
                        </button>
                    </div>
                </div>

                <!-- CARD: EXCLUSAO A PARTIR DE UM CSV DE MATRICULAS -->
                <div class="explode-card explode-card-action explode-card-csv">
                    <div class="explode-card-header">
                        <div class="explode-card-icon icon-danger">
                            <i class="dashicons dashicons-media-spreadsheet"></i>
                        </div>
                        <div>
                            <h2 class="explode-card-title">3. Excluir por Planilha (CSV de matrículas)</h2>
                            <p class="explode-card-subtitle">Envie um CSV e exclua exatamente os colaboradores daquela lista, pela matrícula.</p>
                        </div>
                    </div>
                    <div class="explode-card-body">

                        <div class="explode-alert explode-alert-info">
                            <i class="dashicons dashicons-info"></i>
                            <div>
                                Serve tanto o <strong>mesmo CSV usado na importação</strong> (basta ter uma coluna
                                <code>matricula</code>) quanto uma <strong>lista simples</strong>, com uma matrícula por linha.
                                O arquivo é apenas conferido: <u>nada é excluído antes de você revisar e confirmar</u>.
                            </div>
                        </div>

                        <div class="explode-csv-campo">
                            <label class="explode-csv-label" for="ipt-csv-exclusao">
                                <i class="dashicons dashicons-upload"></i> Arquivo CSV com as matrículas
                            </label>
                            <input type="file" id="ipt-csv-exclusao" accept=".csv,.txt,text/csv" class="explode-csv-input">
                        </div>

                        <div class="explode-checkbox-wrap" style="margin-top: 15px;">
                            <label class="explode-checkbox-label">
                                <input type="checkbox" id="chk-apagar-desenhos-csv" checked>
                                <span>Excluir também os desenhos e mídias desses colaboradores</span>
                            </label>
                        </div>

                        <!-- RESULTADO DA CONFERENCIA -->
                        <div id="csv-resultado" style="display:none;">
                            <div class="explode-csv-resumo" id="csv-resumo"></div>
                            <div class="explode-csv-listas" id="csv-listas"></div>
                        </div>

                    </div>
                    <div class="explode-card-footer">
                        <button type="button" id="btn-analisar-csv" class="explode-btn explode-btn-secondary">
                            <i class="dashicons dashicons-search"></i> Conferir arquivo
                        </button>
                        <button type="button" id="btn-excluir-csv" class="explode-btn explode-btn-danger" disabled>
                            <i class="dashicons dashicons-trash"></i> Excluir os colaboradores da lista
                        </button>
                    </div>
                </div>

            </div>

            <!-- SEÇÃO DE TABELA E SELEÇÃO INTERATIVA -->
            <div class="explode-card explode-table-card">
                <div class="explode-card-header table-header-flex">
                    <div>
                        <h2 class="explode-card-title">4. Seleção Personalizada &amp; Listagem de Usuários</h2>
                        <p class="explode-card-subtitle">Filtre, pesquise e marque caixas individuais para excluir somente os usuários desejados.</p>
                    </div>
                    <div class="explode-table-actions-top">
                        <button type="button" id="btn-selecionar-todos-filtro" class="explode-btn explode-btn-sm explode-btn-secondary">
                            <i class="dashicons dashicons-yes"></i> Marcar Todos Visíveis
                        </button>
                        <button type="button" id="btn-desmarcar-todos" class="explode-btn explode-btn-sm explode-btn-ghost">
                            <i class="dashicons dashicons-no-alt"></i> Desmarcar Todos
                        </button>
                    </div>
                </div>

                <!-- FILTROS DA TABELA -->
                <div class="explode-filter-bar">
                    <div class="explode-filter-item explode-filter-search">
                        <label for="filtro-busca" class="explode-sr-only">Pesquisar</label>
                        <div class="explode-input-icon-wrap">
                            <i class="dashicons dashicons-search"></i>
                            <input type="text" id="filtro-busca" class="explode-input" placeholder="Buscar por matrícula, nome ou e-mail...">
                        </div>
                    </div>

                    <div class="explode-filter-item">
                        <select id="filtro-grupo-tabela" class="explode-select">
                            <option value="">Todos os Grupos</option>
                            <?php foreach ( $groups_summary as $k => $grp ) : ?>
                                <option value="<?php echo esc_attr( $grp['key'] ); ?>"><?php echo esc_html( $grp['key'] ); ?> (<?php echo esc_html( $grp['count'] ); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="explode-filter-item">
                        <select id="filtro-unidade-tabela" class="explode-select">
                            <option value="">Todas as Unidades</option>
                            <?php
                            $unidades = get_terms( array( 'taxonomy' => 'user_unidade', 'hide_empty' => false ) );
                            if ( ! empty( $unidades ) && ! is_wp_error( $unidades ) ) {
                                foreach ( $unidades as $u_term ) {
                                    echo '<option value="' . esc_attr( $u_term->term_id ) . '">' . esc_html( $u_term->name ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="explode-filter-item">
                        <select id="filtro-votacao-tabela" class="explode-select">
                            <option value="">Status de Voto</option>
                            <option value="sim">Já Votaram</option>
                            <option value="nao">Não Votaram</option>
                        </select>
                    </div>

                    <div class="explode-filter-item">
                        <select id="filtro-desenho-tabela" class="explode-select">
                            <option value="">Status de Desenho</option>
                            <option value="com_desenho">Com Desenho Enviado</option>
                            <option value="sem_desenho">Sem Desenho</option>
                        </select>
                    </div>
                </div>

                <!-- TABELA RESPONSIVA -->
                <div class="explode-table-responsive">
                    <table class="explode-table" id="tabela-usuarios-gerenciador">
                        <thead>
                            <tr>
                                <th style="width: 44px; text-align: center;">
                                    <input type="checkbox" id="chk-master-tabela" title="Marcar/Desmarcar Todos">
                                </th>
                                <th style="width: 130px;">Matrícula</th>
                                <th>Nome do Colaborador</th>
                                <th>E-mail</th>
                                <th>Grupo</th>
                                <th>Unidade</th>
                                <th style="text-align: center; width: 100px;">Desenhos</th>
                                <th style="text-align: center; width: 90px;">Votou?</th>
                                <th style="text-align: center; width: 100px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-usuarios">
                            <?php
                            if ( empty( $all_colaboradores ) ) {
                                echo '<tr><td colspan="9" class="explode-empty-state"><i class="dashicons dashicons-info"></i> Nenhum colaborador encontrado.</td></tr>';
                            } else {
                                foreach ( $all_colaboradores as $colab ) {
                                    $cid = $colab->ID;
                                    $c_nome = get_user_meta( $cid, 'first_name', true );
                                    if ( empty( $c_nome ) ) {
                                        $c_nome = $colab->display_name;
                                    }
                                    
                                    $c_grupo = get_user_meta( $cid, 'user_field_funcionario_grupo', true );
                                    $c_unidade_id = get_user_meta( $cid, 'user_field_unidade', true );
                                    $c_unidade_nome = '-';
                                    if ( ! empty( $c_unidade_id ) ) {
                                        $term_obj = get_term( (int)$c_unidade_id, 'user_unidade' );
                                        if ( $term_obj && ! is_wp_error( $term_obj ) ) {
                                            $c_unidade_nome = $term_obj->name;
                                        }
                                    }

                                    $c_votou = get_user_meta( $cid, 'user_field_votacao', true );
                                    $c_votou_bool = ( $c_votou === 'Sim' );

                                    // Contagem de desenhos do colaborador em todos os slots do tema
                                    $max_dep_linha = function_exists( 'explode_max_dependentes' ) ? explode_max_dependentes() : 6;
                                    // Acumulador de desenhos enviados
                                    $qtd_des = 0;
                                    // Soma um por slot marcado como enviado
                                    for ( $s = 1; $s <= $max_dep_linha; $s++ ) {
                                        if ( 'Sim' === get_user_meta( $cid, 'user_field_dependente_' . $s . '_desenho', true ) ) {
                                            $qtd_des++;
                                        }
                                    }

                                    // Tags para busca / filtro
                                    $search_haystack = strtolower( $colab->user_login . ' ' . $c_nome . ' ' . $colab->user_email . ' ' . $c_grupo . ' ' . $c_unidade_nome );
                                    ?>
                                    <tr class="user-row" 
                                        data-id="<?php echo esc_attr( $cid ); ?>" 
                                        data-login="<?php echo esc_attr( $colab->user_login ); ?>"
                                        data-nome="<?php echo esc_attr( $c_nome ); ?>"
                                        data-email="<?php echo esc_attr( $colab->user_email ); ?>"
                                        data-grupo="<?php echo esc_attr( $c_grupo ); ?>"
                                        data-unidade-id="<?php echo esc_attr( $c_unidade_id ); ?>"
                                        data-votou="<?php echo $c_votou_bool ? 'sim' : 'nao'; ?>"
                                        data-desenho="<?php echo $qtd_des > 0 ? 'com_desenho' : 'sem_desenho'; ?>"
                                        data-search="<?php echo esc_attr( $search_haystack ); ?>">
                                        
                                        <td style="text-align: center;">
                                            <input type="checkbox" class="chk-user-item" value="<?php echo esc_attr( $cid ); ?>">
                                        </td>
                                        <td>
                                            <span class="explode-matricula"><?php echo esc_html( $colab->user_login ); ?></span>
                                        </td>
                                        <td>
                                            <strong class="explode-user-name"><?php echo esc_html( $c_nome ); ?></strong>
                                        </td>
                                        <td>
                                            <span class="explode-user-email"><?php echo esc_html( $colab->user_email ? $colab->user_email : 'Não cadastrado' ); ?></span>
                                        </td>
                                        <td>
                                            <?php if ( ! empty( $c_grupo ) ) : ?>
                                                <span class="explode-badge badge-group"><?php echo esc_html( $c_grupo ); ?></span>
                                            <?php else : ?>
                                                <span class="explode-badge badge-muted">Sem grupo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="explode-unidade-text"><?php echo esc_html( $c_unidade_nome ); ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ( $qtd_des > 0 ) : ?>
                                                <span class="explode-badge badge-cyan" title="<?php echo esc_attr( $qtd_des ); ?> desenho(s) cadastrado(s)"><?php echo esc_html( $qtd_des ); ?> desenho(s)</span>
                                            <?php else : ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ( $c_votou_bool ) : ?>
                                                <span class="explode-badge badge-success"><i class="dashicons dashicons-yes"></i> Sim</span>
                                            <?php else : ?>
                                                <span class="explode-badge badge-warning">Não</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="explode-btn-icon btn-delete-single" data-id="<?php echo esc_attr( $cid ); ?>" data-login="<?php echo esc_attr( $colab->user_login ); ?>" title="Excluir este usuário">
                                                <i class="dashicons dashicons-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- RODAPÉ DA TABELA -->
                <div class="explode-table-footer">
                    <div class="explode-table-count">
                        Mostrando <strong id="cnt-visiveis"><?php echo esc_html( $total_colaboradores ); ?></strong> de <strong><?php echo esc_html( $total_colaboradores ); ?></strong> colaboradores.
                    </div>
                </div>
            </div>

            <!-- BARRA FLUTUANTE DE AÇÕES PARA ITENS SELECIONADOS -->
            <div class="explode-floating-bar" id="floating-action-bar" style="display: none;">
                <div class="explode-floating-content">
                    <div class="explode-floating-left">
                        <div class="explode-floating-badge">
                            <span id="cnt-selecionados-badge">0</span>
                        </div>
                        <span class="explode-floating-text">usuário(s) selecionado(s)</span>
                    </div>
                    <div class="explode-floating-center">
                        <label class="explode-checkbox-label">
                            <input type="checkbox" id="chk-apagar-desenhos-selecionados" checked>
                            <span>Excluir desenhos vinculados</span>
                        </label>
                    </div>
                    <div class="explode-floating-right">
                        <button type="button" id="btn-cancelar-selecao" class="explode-btn explode-btn-ghost-light">
                            Cancelar
                        </button>
                        <button type="button" id="btn-excluir-selecionados" class="explode-btn explode-btn-danger">
                            <i class="dashicons dashicons-trash"></i> Excluir Selecionados
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL DE CONFIRMAÇÃO DE EXCLUSÃO -->
            <div class="explode-modal-overlay" id="modal-confirmacao" style="display: none;">
                <div class="explode-modal">
                    <div class="explode-modal-header">
                        <div class="explode-modal-icon icon-danger">
                            <i class="dashicons dashicons-warning"></i>
                        </div>
                        <div>
                            <h3 class="explode-modal-title" id="modal-confirm-title">Confirmar Exclusão</h3>
                            <span class="explode-modal-subtitle">Esta ação é irreversível e removerá dados do banco.</span>
                        </div>
                        <button type="button" class="explode-modal-close" id="btn-fechar-modal-confirm">&times;</button>
                    </div>
                    <div class="explode-modal-body">
                        <p id="modal-confirm-msg" class="explode-modal-desc">
                            Tem certeza de que deseja prosseguir com a exclusão dos usuários selecionados?
                        </p>
                        
                        <div class="explode-modal-details-box" id="modal-confirm-details">
                            <!-- Detalhes preenchidos dinamicamente -->
                        </div>

                        <div id="modal-confirm-input-wrap" style="display: none; margin-top: 15px;">
                            <label class="explode-label" for="ipt-confirmar-palavra">Para confirmar a exclusão em massa, digite <strong>EXCLUIR</strong> abaixo:</label>
                            <input type="text" id="ipt-confirmar-palavra" class="explode-input" placeholder="Digite EXCLUIR para liberar o botão">
                        </div>
                    </div>
                    <div class="explode-modal-footer">
                        <button type="button" class="explode-btn explode-btn-ghost" id="btn-cancelar-modal-confirm">Cancelar</button>
                        <button type="button" class="explode-btn explode-btn-danger" id="btn-confirmar-acao-definitiva">
                            <i class="dashicons dashicons-trash"></i> Sim, Excluir Agora
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL DE PROGRESSO EM LOTE (BATCH AJAX) -->
            <div class="explode-modal-overlay" id="modal-progresso" style="display: none;">
                <div class="explode-modal">
                    <div class="explode-modal-header">
                        <div class="explode-modal-icon icon-cyan">
                            <i class="dashicons dashicons-update explode-spin"></i>
                        </div>
                        <div>
                            <h3 class="explode-modal-title">Processando Exclusão em Lote</h3>
                            <span class="explode-modal-subtitle">Por favor, não feche nem recarregue esta janela...</span>
                        </div>
                    </div>
                    <div class="explode-modal-body">
                        <div class="explode-progress-wrapper">
                            <div class="explode-progress-bar">
                                <div class="explode-progress-fill" id="progress-fill" style="width: 0%;"></div>
                            </div>
                            <div class="explode-progress-labels">
                                <span id="progress-text">Iniciando exclusão...</span>
                                <strong id="progress-percentage">0%</strong>
                            </div>
                        </div>

                        <div class="explode-log-box" id="progress-log-box">
                            <div class="log-entry">Aguardando início das operações...</div>
                        </div>
                    </div>
                    <div class="explode-modal-footer" id="modal-progresso-footer" style="display: none;">
                        <button type="button" class="explode-btn explode-btn-primary" id="btn-concluir-progresso">
                            <i class="dashicons dashicons-yes"></i> Concluir e Recarregar
                        </button>
                    </div>
                </div>
            </div>

            <!-- NOTIFICAÇÃO TOAST FLUTUANTE -->
            <div class="explode-toast-container" id="toast-container"></div>

        </div>

        <!-- CSS MODERNO E ENCAPSULADO DO PAINEL -->
        <style>
            :root {
                --exp-navy: #143240;
                --exp-navy-dark: #0b1d26;
                --exp-teal: #5b9b99;
                --exp-cyan: #54c5cf;
                --exp-mint: #dffdc2;
                --exp-light-bg: #f4f7f9;
                --exp-card-bg: #ffffff;
                --exp-text-main: #1f2937;
                --exp-text-muted: #6b7280;
                --exp-border: #e5e7eb;
                --exp-danger: #e11d48;
                --exp-danger-dark: #be123c;
                --exp-danger-bg: #ffe4e6;
                --exp-success: #10b981;
                --exp-success-bg: #d1fae5;
                --exp-warning: #f59e0b;
                --exp-warning-bg: #fef3c7;
                --exp-radius: 12px;
                --exp-shadow: 0 4px 20px -2px rgba(20, 50, 64, 0.08);
            }

            .explode-admin-wrap {
                margin: 20px 20px 80px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                color: var(--exp-text-main);
            }

            /* HERO */
            .explode-hero {
                background: linear-gradient(135deg, var(--exp-navy) 0%, #1c4b61 50%, var(--exp-teal) 100%);
                border-radius: var(--exp-radius);
                padding: 32px 36px;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: var(--exp-shadow);
                margin-bottom: 24px;
                position: relative;
                overflow: hidden;
            }
            .explode-hero::after {
                content: "";
                position: absolute;
                right: -60px;
                top: -60px;
                width: 260px;
                height: 260px;
                background: radial-gradient(circle, rgba(84, 197, 207, 0.2) 0%, rgba(255,255,255,0) 70%);
                border-radius: 50%;
                pointer-events: none;
            }
            .explode-hero-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(8px);
                border: 1px solid rgba(255, 255, 255, 0.25);
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 12px;
            }
            .explode-hero-title {
                color: #ffffff;
                font-size: 26px;
                font-weight: 700;
                margin: 0 0 8px 0;
                line-height: 1.2;
            }
            .explode-hero-desc {
                font-size: 14px;
                color: rgba(255, 255, 255, 0.85);
                margin: 0;
                max-width: 650px;
                line-height: 1.5;
            }
            .explode-hero-actions {
                display: flex;
                gap: 12px;
            }

            /* BOTOES */
            .explode-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 18px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                border: 1px solid transparent;
                text-decoration: none;
                line-height: 1;
            }
            .explode-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed !important;
                pointer-events: none;
            }
            .explode-btn-primary {
                background: var(--exp-cyan);
                color: var(--exp-navy-dark);
                border-color: var(--exp-cyan);
            }
            .explode-btn-primary:hover {
                background: #44b7c1;
                color: var(--exp-navy-dark);
            }
            .explode-btn-secondary {
                background: #ffffff;
                color: var(--exp-navy);
                border-color: #ffffff;
            }
            .explode-btn-secondary:hover {
                background: #f0fdf4;
                color: var(--exp-navy);
            }
            .explode-btn-ghost {
                background: rgba(255, 255, 255, 0.12);
                color: #ffffff;
                border-color: rgba(255, 255, 255, 0.25);
            }
            .explode-btn-ghost:hover {
                background: rgba(255, 255, 255, 0.22);
                color: #ffffff;
            }
            .explode-btn-ghost-light {
                background: transparent;
                color: #ffffff;
                border-color: rgba(255, 255, 255, 0.3);
            }
            .explode-btn-ghost-light:hover {
                background: rgba(255, 255, 255, 0.15);
            }
            .explode-btn-danger {
                background: var(--exp-danger);
                color: #ffffff;
                border-color: var(--exp-danger);
            }
            .explode-btn-danger:hover {
                background: var(--exp-danger-dark);
                color: #ffffff;
            }
            .explode-btn-danger-outline {
                background: #ffffff;
                color: var(--exp-danger);
                border-color: var(--exp-danger);
            }
            .explode-btn-danger-outline:hover {
                background: var(--exp-danger-bg);
                color: var(--exp-danger-dark);
            }
            .explode-btn-sm {
                padding: 6px 12px;
                font-size: 12px;
            }
            .explode-btn-icon {
                background: #fee2e2;
                color: var(--exp-danger);
                border: none;
                border-radius: 6px;
                width: 32px;
                height: 32px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s;
            }
            .explode-btn-icon:hover {
                background: var(--exp-danger);
                color: #ffffff;
            }

            /* KPIS */
            .explode-kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }
            .explode-kpi-card {
                background: var(--exp-card-bg);
                border: 1px solid var(--exp-border);
                border-radius: var(--exp-radius);
                padding: 18px 20px;
                display: flex;
                align-items: center;
                gap: 16px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            }
            .explode-kpi-icon {
                width: 48px;
                height: 48px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                flex-shrink: 0;
            }
            .icon-blue { background: #e0f2fe; color: #0284c7; }
            .icon-cyan { background: #ecfeff; color: #0891b2; }
            .icon-teal { background: #e6fffa; color: #0d9488; }
            .icon-green { background: var(--exp-success-bg); color: var(--exp-success); }
            .icon-gold { background: #fef9c3; color: #ca8a04; }
            .icon-danger { background: var(--exp-danger-bg); color: var(--exp-danger); }
            .icon-danger-dark { background: #ffe4e6; color: #9f1239; }

            .explode-kpi-info {
                display: flex;
                flex-direction: column;
            }
            .explode-kpi-label {
                font-size: 12px;
                color: var(--exp-text-muted);
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.4px;
            }
            .explode-kpi-value {
                font-size: 24px;
                font-weight: 700;
                color: var(--exp-navy);
                line-height: 1.1;
                margin: 4px 0 2px 0;
            }
            .explode-kpi-sub {
                font-size: 11px;
                color: var(--exp-text-muted);
            }
            .text-success { color: var(--exp-success) !important; font-weight: 600; }

            /* PAINÉIS */
            .explode-panels-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 24px;
            }
            @media (max-width: 1080px) {
                .explode-panels-grid { grid-template-columns: 1fr; }
                .explode-hero { flex-direction: column; align-items: flex-start; gap: 20px; }
            }
            .explode-card {
                background: var(--exp-card-bg);
                border: 1px solid var(--exp-border);
                border-radius: var(--exp-radius);
                box-shadow: var(--exp-shadow);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            .explode-card-header {
                padding: 20px 24px;
                border-bottom: 1px solid var(--exp-border);
                display: flex;
                align-items: center;
                gap: 16px;
                background: #fbfcfd;
            }
            .explode-card-icon {
                width: 42px;
                height: 42px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .explode-card-title {
                font-size: 16px;
                font-weight: 700;
                color: var(--exp-navy);
                margin: 0 0 4px 0;
            }
            .explode-card-subtitle {
                font-size: 12px;
                color: var(--exp-text-muted);
                margin: 0;
            }
            .explode-card-body {
                padding: 24px;
                flex: 1;
            }
            .explode-card-footer {
                padding: 16px 24px;
                background: #fbfcfd;
                border-top: 1px solid var(--exp-border);
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }

            /* FORMULÁRIOS */
            .explode-form-group {
                margin-bottom: 16px;
            }
            .explode-label {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: var(--exp-navy);
                margin-bottom: 6px;
            }
            .explode-select, .explode-input {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid var(--exp-border);
                border-radius: 8px;
                font-size: 13px;
                color: var(--exp-text-main);
                background-color: #ffffff;
                transition: border-color 0.2s;
            }
            .explode-select:focus, .explode-input:focus {
                outline: none;
                border-color: var(--exp-teal);
                box-shadow: 0 0 0 3px rgba(91, 155, 153, 0.15);
            }
            .explode-checkbox-wrap {
                margin-top: 10px;
            }
            .explode-checkbox-label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                color: var(--exp-text-main);
                cursor: pointer;
            }
            .explode-preview-box {
                background: #fff1f2;
                border: 1px solid #fecdd3;
                border-radius: 8px;
                padding: 12px 14px;
                margin-top: 16px;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 13px;
                color: #9f1239;
            }
            .explode-alert {
                border-radius: 8px;
                padding: 12px 16px;
                display: flex;
                align-items: flex-start;
                gap: 10px;
                font-size: 13px;
            }
            .explode-alert-warning {
                background: #fefce8;
                border: 1px solid #fef08a;
                color: #854d0e;
            }
            .explode-tip-text {
                font-size: 13px;
                color: var(--exp-text-muted);
                margin: 14px 0 0 0;
            }

            /* TABELA */
            .explode-table-card {
                margin-bottom: 24px;
            }
            .table-header-flex {
                justify-content: space-between;
            }
            .explode-table-actions-top {
                display: flex;
                gap: 8px;
            }
            .explode-filter-bar {
                padding: 16px 24px;
                border-bottom: 1px solid var(--exp-border);
                background: #ffffff;
                display: grid;
                grid-template-columns: 2fr 1.2fr 1.2fr 1fr 1fr;
                gap: 12px;
            }
            @media (max-width: 900px) {
                .explode-filter-bar { grid-template-columns: 1fr 1fr; }
            }
            .explode-input-icon-wrap {
                position: relative;
            }
            .explode-input-icon-wrap .dashicons {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--exp-text-muted);
            }
            .explode-input-icon-wrap input {
                padding-left: 36px;
            }
            .explode-sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                border: 0;
            }
            .explode-table-responsive {
                overflow-x: auto;
                max-height: 550px;
            }
            .explode-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
                font-size: 13px;
            }
            .explode-table th {
                background: #f8fafc;
                color: var(--exp-navy);
                font-weight: 600;
                padding: 12px 16px;
                border-bottom: 2px solid var(--exp-border);
                position: sticky;
                top: 0;
                z-index: 2;
            }
            .explode-table td {
                padding: 12px 16px;
                border-bottom: 1px solid var(--exp-border);
                vertical-align: middle;
            }
            .explode-table tr:hover {
                background-color: #f8fafc;
            }
            .explode-table tr.selected-row {
                background-color: #f0fdfa;
            }
            .explode-matricula {
                font-family: monospace;
                font-size: 12px;
                background: #f1f5f9;
                padding: 2px 6px;
                border-radius: 4px;
                color: var(--exp-navy);
                font-weight: 600;
            }
            .explode-user-name {
                color: var(--exp-navy);
            }
            .explode-user-email {
                color: var(--exp-text-muted);
                font-size: 12px;
            }

            /* BADGES */
            .explode-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                line-height: 1;
            }
            .badge-group { background: #e0e7ff; color: #3730a3; }
            .badge-muted { background: #f3f4f6; color: #6b7280; }
            .badge-cyan { background: #cffafe; color: #0e7490; }
            .badge-success { background: var(--exp-success-bg); color: #065f46; }
            .badge-warning { background: var(--exp-warning-bg); color: #92400e; }
            .text-muted { color: var(--exp-text-muted); }

            .explode-table-footer {
                padding: 14px 24px;
                background: #fbfcfd;
                border-top: 1px solid var(--exp-border);
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 13px;
                color: var(--exp-text-muted);
            }

            /* BARRA FLUTUANTE */
            .explode-floating-bar {
                position: fixed;
                bottom: 24px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 9999;
                animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            @keyframes slideUp {
                from { transform: translate(-50%, 50px); opacity: 0; }
                to { transform: translate(-50%, 0); opacity: 1; }
            }
            .explode-floating-content {
                background: var(--exp-navy);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 40px;
                padding: 8px 12px 8px 18px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
                display: flex;
                align-items: center;
                gap: 20px;
                color: #ffffff;
            }
            .explode-floating-left {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .explode-floating-badge {
                background: var(--exp-cyan);
                color: var(--exp-navy-dark);
                font-weight: 700;
                font-size: 12px;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .explode-floating-text {
                font-size: 13px;
                font-weight: 500;
            }
            .explode-floating-center .explode-checkbox-label {
                color: rgba(255, 255, 255, 0.9);
                font-size: 12px;
            }
            .explode-floating-right {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            /* MODAL */
            .explode-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(11, 29, 38, 0.65);
                backdrop-filter: blur(4px);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .explode-modal {
                background: #ffffff;
                border-radius: var(--exp-radius);
                width: 100%;
                max-width: 520px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.25);
                overflow: hidden;
                animation: popModal 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            }
            @keyframes popModal {
                from { transform: scale(0.95); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .explode-modal-header {
                padding: 20px 24px;
                display: flex;
                align-items: center;
                gap: 14px;
                border-bottom: 1px solid var(--exp-border);
                position: relative;
            }
            .explode-modal-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .explode-modal-title {
                margin: 0;
                font-size: 16px;
                font-weight: 700;
                color: var(--exp-navy);
            }
            .explode-modal-subtitle {
                font-size: 12px;
                color: var(--exp-text-muted);
            }
            .explode-modal-close {
                position: absolute;
                right: 18px;
                top: 18px;
                background: none;
                border: none;
                font-size: 24px;
                line-height: 1;
                color: var(--exp-text-muted);
                cursor: pointer;
            }
            .explode-modal-body {
                padding: 24px;
            }
            .explode-modal-desc {
                font-size: 14px;
                color: var(--exp-text-main);
                margin: 0 0 16px 0;
                line-height: 1.5;
            }
            .explode-modal-details-box {
                background: #f8fafc;
                border: 1px solid var(--exp-border);
                border-radius: 8px;
                padding: 14px;
                font-size: 13px;
            }
            .explode-modal-footer {
                padding: 16px 24px;
                background: #f8fafc;
                border-top: 1px solid var(--exp-border);
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
            }

            /* PROGRESS BAR */
            .explode-progress-wrapper {
                margin-bottom: 20px;
            }
            .explode-progress-bar {
                height: 12px;
                background: #e2e8f0;
                border-radius: 6px;
                overflow: hidden;
                margin-bottom: 8px;
            }
            .explode-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, var(--exp-teal), var(--exp-cyan));
                border-radius: 6px;
                transition: width 0.3s ease;
            }
            .explode-progress-labels {
                display: flex;
                justify-content: space-between;
                font-size: 12px;
                color: var(--exp-navy);
                font-weight: 500;
            }
            .explode-log-box {
                background: #0f172a;
                color: #e2e8f0;
                font-family: monospace;
                font-size: 12px;
                border-radius: 8px;
                padding: 12px 14px;
                max-height: 180px;
                overflow-y: auto;
                line-height: 1.6;
            }
            .log-entry {
                margin-bottom: 4px;
            }
            .log-success { color: #4ade80; }
            .log-danger { color: #f87171; }
            .log-info { color: #38bdf8; }

            .explode-spin {
                animation: spin 1.2s linear infinite;
            }
            @keyframes spin {
                100% { transform: rotate(360deg); }
            }

            /* TOAST */
            .explode-toast-container {
                position: fixed;
                top: 40px;
                right: 20px;
                z-index: 100001;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .explode-toast {
                background: var(--exp-navy);
                color: #ffffff;
                padding: 12px 18px;
                border-radius: 8px;
                font-size: 13px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.18);
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideInRight 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }

            /* EXCLUSAO POR CSV */
            .explode-csv-campo { margin-top: 16px; }
            .explode-csv-label {
                display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
                font-size: 13px; font-weight: 700; color: var(--exp-navy);
            }
            .explode-csv-input {
                display: block; width: 100%; padding: 10px 12px;
                border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;
                font-size: 13px; cursor: pointer;
            }
            .explode-csv-input:hover { border-color: var(--exp-cyan); background: #f0fbfc; }

            .explode-csv-resumo {
                display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 10px; margin-top: 18px;
            }
            .explode-csv-bloco {
                padding: 12px 14px; border-radius: 8px; text-align: center;
                border: 1px solid #e2e8f0; background: #f8fafc;
            }
            .explode-csv-bloco strong { display: block; font-size: 22px; line-height: 1.2; }
            .explode-csv-bloco span { font-size: 11.5px; color: #64748b; }
            .explode-csv-bloco.ok      { border-color: #fca5a5; background: #fef2f2; }
            .explode-csv-bloco.ok strong      { color: #b91c1c; }
            .explode-csv-bloco.aviso   { border-color: #fcd34d; background: #fffbeb; }
            .explode-csv-bloco.aviso strong   { color: #b45309; }
            .explode-csv-bloco.neutro strong  { color: var(--exp-navy); }

            .explode-csv-listas { margin-top: 16px; }
            .explode-csv-lista {
                margin-bottom: 14px; border: 1px solid #e2e8f0;
                border-radius: 8px; overflow: hidden;
            }
            .explode-csv-lista h4 {
                margin: 0; padding: 10px 14px; font-size: 12.5px; font-weight: 700;
                background: #f1f5f9; color: var(--exp-navy);
                display: flex; align-items: center; gap: 8px;
            }
            .explode-csv-lista.perigo h4 { background: #fef2f2; color: #b91c1c; }
            .explode-csv-lista.aviso h4  { background: #fffbeb; color: #b45309; }
            .explode-csv-scroll { max-height: 260px; overflow-y: auto; }
            .explode-csv-tabela { width: 100%; border-collapse: collapse; font-size: 12.5px; }
            .explode-csv-tabela th, .explode-csv-tabela td {
                padding: 7px 14px; text-align: left; border-top: 1px solid #eef2f6;
            }
            .explode-csv-tabela th { font-weight: 700; color: #475569; background: #fbfcfd; }
            .explode-csv-tabela code {
                padding: 1px 6px; border-radius: 4px; background: #eef2f6; font-size: 11.5px;
            }
            .explode-csv-chips { padding: 12px 14px; display: flex; flex-wrap: wrap; gap: 6px; }
            .explode-csv-chip {
                padding: 4px 10px; border-radius: 20px; background: #f1f5f9;
                border: 1px solid #e2e8f0; font-size: 11.5px; font-family: monospace;
            }
            .explode-card-csv .explode-card-footer { display: flex; gap: 10px; flex-wrap: wrap; }
        </style>

        <!-- JAVASCRIPT REATIVO DO GERENCIADOR -->
        <script>
        (function($) {
            'use strict';

            var explodeNonce = '<?php echo esc_js( $nonce ); ?>';
            var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

            var pendingAction = null; // Armazena a ação a ser executada no modal de confirmação
            var isProcessing = false;

            $(document).ready(function() {
                initTableFilters();
                initSelectionHandlers();
                initGroupDeletion();
                initAllUsersDeletion();
                initSingleDeletion();
                initCsvDeletion();
                initModalHandlers();
            });

            /* NOTIFICAÇÕES TOAST */
            function showToast(message, type) {
                type = type || 'info';
                var icon = type === 'success' ? 'dashicons-yes' : (type === 'danger' ? 'dashicons-warning' : 'dashicons-info');
                var $toast = $('<div class="explode-toast"><i class="dashicons ' + icon + '"></i> <span>' + message + '</span></div>');
                
                if (type === 'success') {
                    $toast.css('border-left', '4px solid var(--exp-success)');
                } else if (type === 'danger') {
                    $toast.css('border-left', '4px solid var(--exp-danger)');
                }

                $('#toast-container').append($toast);
                setTimeout(function() {
                    $toast.fadeOut(300, function() { $(this).remove(); });
                }, 4000);
            }

            /* FILTROS DA TABELA */
            function initTableFilters() {
                function applyFilters() {
                    var searchVal = $('#filtro-busca').val().toLowerCase().trim();
                    var grupoVal = $('#filtro-grupo-tabela').val().toLowerCase().trim();
                    var unidadeVal = $('#filtro-unidade-tabela').val();
                    var votouVal = $('#filtro-votacao-tabela').val();
                    var desenhoVal = $('#filtro-desenho-tabela').val();

                    var visibleCount = 0;

                    $('#tbody-usuarios tr.user-row').each(function() {
                        var $row = $(this);
                        var matchSearch = !searchVal || $row.attr('data-search').indexOf(searchVal) !== -1;
                        
                        var rowGrupo = ($row.attr('data-grupo') || '').toLowerCase();
                        var matchGrupo = true;
                        if (grupoVal) {
                            if (grupoVal === '_sem_grupo') {
                                matchGrupo = !rowGrupo;
                            } else {
                                matchGrupo = rowGrupo.indexOf(grupoVal) !== -1;
                            }
                        }

                        var matchUnidade = !unidadeVal || $row.attr('data-unidade-id') === unidadeVal;
                        var matchVotou = !votouVal || $row.attr('data-votou') === votouVal;
                        var matchDesenho = !desenhoVal || $row.attr('data-desenho') === desenhoVal;

                        if (matchSearch && matchGrupo && matchUnidade && matchVotou && matchDesenho) {
                            $row.show();
                            visibleCount++;
                        } else {
                            $row.hide();
                            $row.find('.chk-user-item').prop('checked', false);
                            $row.removeClass('selected-row');
                        }
                    });

                    $('#cnt-visiveis').text(visibleCount);
                    updateFloatingBar();
                }

                $('#filtro-busca').on('keyup input', applyFilters);
                $('#filtro-grupo-tabela, #filtro-unidade-tabela, #filtro-votacao-tabela, #filtro-desenho-tabela').on('change', applyFilters);

                $('#btn-recarregar-dados').on('click', function() {
                    location.reload();
                });
            }

            /* SELEÇÃO COM CHECKBOXES */
            function updateFloatingBar() {
                var selectedCount = $('.chk-user-item:checked').length;
                if (selectedCount > 0) {
                    $('#cnt-selecionados-badge').text(selectedCount);
                    $('#floating-action-bar').fadeIn(200);
                } else {
                    $('#floating-action-bar').fadeOut(200);
                }
            }

            function initSelectionHandlers() {
                // Checkbox Master
                $('#chk-master-tabela').on('change', function() {
                    var isChecked = $(this).is(':checked');
                    $('#tbody-usuarios tr.user-row:visible').each(function() {
                        var $chk = $(this).find('.chk-user-item');
                        $chk.prop('checked', isChecked);
                        if (isChecked) {
                            $(this).addClass('selected-row');
                        } else {
                            $(this).removeClass('selected-row');
                        }
                    });
                    updateFloatingBar();
                });

                // Checkbox individual
                $(document).on('change', '.chk-user-item', function() {
                    var $row = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        $row.addClass('selected-row');
                    } else {
                        $row.removeClass('selected-row');
                        $('#chk-master-tabela').prop('checked', false);
                    }
                    updateFloatingBar();
                });

                // Botão selecionar visíveis
                $('#btn-selecionar-todos-filtro').on('click', function() {
                    $('#tbody-usuarios tr.user-row:visible').each(function() {
                        $(this).find('.chk-user-item').prop('checked', true);
                        $(this).addClass('selected-row');
                    });
                    $('#chk-master-tabela').prop('checked', true);
                    updateFloatingBar();
                    showToast('Todos os usuários visíveis foram selecionados.', 'info');
                });

                // Botão desmarcar todos
                $('#btn-desmarcar-todos, #btn-cancelar-selecao').on('click', function() {
                    $('.chk-user-item').prop('checked', false);
                    $('#chk-master-tabela').prop('checked', false);
                    $('#tbody-usuarios tr').removeClass('selected-row');
                    updateFloatingBar();
                });

                // Botão Excluir Selecionados
                $('#btn-excluir-selecionados').on('click', function() {
                    var selectedIds = [];
                    $('.chk-user-item:checked').each(function() {
                        selectedIds.push(parseInt($(this).val()));
                    });

                    if (selectedIds.length === 0) {
                        showToast('Selecione ao menos um usuário.', 'danger');
                        return;
                    }

                    var deleteDrawings = $('#chk-apagar-desenhos-selecionados').is(':checked');

                    openConfirmModal({
                        title: 'Excluir Usuários Selecionados',
                        message: 'Você está prestes a excluir permanentemente os usuários marcados.',
                        detailsHtml: '<ul>' +
                            '<li>Total de Usuários a excluir: <strong>' + selectedIds.length + '</strong></li>' +
                            '<li>Exclusão de desenhos vinculados: <strong>' + (deleteDrawings ? 'SIM' : 'NÃO') + '</strong></li>' +
                            '</ul>',
                        requiresWord: false,
                        onConfirm: function() {
                            runBatchDeletion(selectedIds, deleteDrawings);
                        }
                    });
                });
            }

            /* EXCLUSÃO POR GRUPO */
            function initGroupDeletion() {
                $('#select-grupo-excluir').on('change', function() {
                    var $selected = $(this).find(':selected');
                    var val = $(this).val();
                    var count = parseInt($selected.attr('data-count') || 0);

                    if (val && count > 0) {
                        $('#txt-qtd-grupo').text(count);
                        $('#preview-grupo-selecionado').slideDown(200);
                        $('#btn-executar-exclusao-grupo').prop('disabled', false);
                    } else if (val && count === 0) {
                        $('#txt-qtd-grupo').text(0);
                        $('#preview-grupo-selecionado').slideDown(200);
                        $('#btn-executar-exclusao-grupo').prop('disabled', true);
                    } else {
                        $('#preview-grupo-selecionado').slideUp(200);
                        $('#btn-executar-exclusao-grupo').prop('disabled', true);
                    }
                });

                $('#btn-executar-exclusao-grupo').on('click', function() {
                    var grupo = $('#select-grupo-excluir').val();
                    var grupoTexto = $('#select-grupo-excluir option:selected').text();
                    var deleteDrawings = $('#chk-apagar-desenhos-grupo').is(':checked');

                    if (!grupo) return;

                    // Busca IDs do grupo via AJAX
                    $('#btn-executar-exclusao-grupo').prop('disabled', true).text('Consultando usuários...');

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'explode_obter_ids_grupo',
                            group: grupo,
                            nonce: explodeNonce
                        },
                        success: function(resp) {
                            $('#btn-executar-exclusao-grupo').prop('disabled', false).html('<i class="dashicons dashicons-trash"></i> Excluir Todos deste Grupo');

                            if (resp.success && resp.data.user_ids.length > 0) {
                                var userIds = resp.data.user_ids;

                                openConfirmModal({
                                    title: 'Excluir Todos os Usuários do Grupo',
                                    message: 'Atenção! Esta ação removerá todos os colaboradores do grupo selecionado.',
                                    detailsHtml: '<ul>' +
                                        '<li>Grupo selecionado: <strong>' + grupoTexto + '</strong></li>' +
                                        '<li>Total de Colaboradores: <strong>' + userIds.length + '</strong></li>' +
                                        '<li>Excluir desenhos vinculados: <strong>' + (deleteDrawings ? 'SIM' : 'NÃO') + '</strong></li>' +
                                        '</ul>',
                                    requiresWord: userIds.length >= 10,
                                    onConfirm: function() {
                                        runBatchDeletion(userIds, deleteDrawings);
                                    }
                                });
                            } else {
                                showToast('Nenhum usuário encontrado para este grupo.', 'info');
                            }
                        },
                        error: function() {
                            $('#btn-executar-exclusao-grupo').prop('disabled', false).html('<i class="dashicons dashicons-trash"></i> Excluir Todos deste Grupo');
                            showToast('Erro de comunicação com o servidor.', 'danger');
                        }
                    });
                });
            }

            /* EXCLUSÃO TOTAL / ZERAR BASE */
            function initAllUsersDeletion() {
                $('#btn-executar-exclusao-todos').on('click', function() {
                    var deleteDrawings = $('#chk-apagar-desenhos-todos').is(':checked');

                    $('#btn-executar-exclusao-todos').prop('disabled', true).text('Consultando base...');

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'explode_obter_todos_ids_colaboradores',
                            nonce: explodeNonce
                        },
                        success: function(resp) {
                            $('#btn-executar-exclusao-todos').prop('disabled', false).html('<i class="dashicons dashicons-warning"></i> Zerar Todos os Colaboradores');

                            if (resp.success && resp.data.user_ids.length > 0) {
                                var userIds = resp.data.user_ids;

                                openConfirmModal({
                                    title: 'Zerar Toda a Base de Colaboradores',
                                    message: 'PERIGO! Você está prestes a excluir TODOS os colaboradores (Assinantes) cadastrados no Explode Criação.',
                                    detailsHtml: '<div style="color: #9f1239; font-weight: 600;">' +
                                        'Total de ' + userIds.length + ' colaboradores serão removidos permanentemente.<br>' +
                                        'Os administradores do sistema continuarão intactos.' +
                                        '</div>',
                                    requiresWord: true,
                                    onConfirm: function() {
                                        runBatchDeletion(userIds, deleteDrawings);
                                    }
                                });
                            } else {
                                showToast('Nenhum colaborador encontrado na base.', 'info');
                            }
                        },
                        error: function() {
                            $('#btn-executar-exclusao-todos').prop('disabled', false).html('<i class="dashicons dashicons-warning"></i> Zerar Todos os Colaboradores');
                            showToast('Erro ao consultar usuários.', 'danger');
                        }
                    });
                });
            }

            /* EXCLUSÃO INDIVIDUAL */
            function initSingleDeletion() {
                $(document).on('click', '.btn-delete-single', function() {
                    var uid = parseInt($(this).attr('data-id'));
                    var login = $(this).attr('data-login');

                    openConfirmModal({
                        title: 'Excluir Colaborador ' + login,
                        message: 'Deseja realmente excluir este colaborador?',
                        detailsHtml: '<ul><li>Matrícula / Usuário: <strong>' + login + ' (ID: ' + uid + ')</strong></li></ul>',
                        requiresWord: false,
                        onConfirm: function() {
                            runBatchDeletion([uid], true);
                        }
                    });
                });
            }

            /* EXCLUSAO A PARTIR DE UM CSV DE MATRICULAS */
            function initCsvDeletion() {

                // Lista de IDs devolvida pela conferencia do arquivo
                var csvIds = [];
                // Guarda o resumo para montar o texto da confirmacao
                var csvDados = null;

                // Escapa texto vindo do arquivo antes de jogar no HTML
                function esc(txt) {
                    return $('<div>').text(txt === null || typeof txt === 'undefined' ? '' : String(txt)).html();
                }

                // Zera a conferencia sempre que o arquivo muda
                function limparConferencia() {
                    csvIds = [];
                    csvDados = null;
                    $('#csv-resultado').hide();
                    $('#csv-resumo').empty();
                    $('#csv-listas').empty();
                    $('#btn-excluir-csv').prop('disabled', true);
                }

                // Trocar o arquivo invalida a conferencia anterior
                $('#ipt-csv-exclusao').on('change', limparConferencia);

                // CONFERIR O ARQUIVO: le o CSV e mostra quem sera excluido
                $('#btn-analisar-csv').on('click', function() {
                    // Arquivo escolhido
                    var input = document.getElementById('ipt-csv-exclusao');

                    // Sem arquivo nao ha o que conferir
                    if (!input || !input.files || input.files.length === 0) {
                        showToast('Selecione um arquivo CSV primeiro.', 'warning');
                        return;
                    }

                    // Limpa o resultado anterior
                    limparConferencia();

                    // Monta o envio com o arquivo
                    var dados = new FormData();
                    dados.append('action', 'explode_analisar_csv_exclusao');
                    dados.append('nonce', explodeNonce);
                    dados.append('arquivo_csv', input.files[0]);

                    // Trava o botao durante a leitura
                    var $btn = $(this);
                    $btn.prop('disabled', true).html('<i class="dashicons dashicons-update explode-spin"></i> Conferindo...');

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: dados,
                        processData: false,
                        contentType: false,
                        success: function(resp) {
                            // Libera o botao
                            $btn.prop('disabled', false).html('<i class="dashicons dashicons-search"></i> Conferir arquivo');

                            // Erro devolvido pelo servidor
                            if (!resp || !resp.success) {
                                showToast((resp && resp.data && resp.data.message) ? resp.data.message : 'Não foi possível ler o arquivo.', 'danger');
                                return;
                            }

                            // Guarda o resultado
                            csvDados = resp.data;
                            csvIds = resp.data.user_ids || [];

                            // Desenha a conferencia na tela
                            montarConferencia(resp.data);

                            // Só libera o botao de excluir quando ha alguem para excluir
                            $('#btn-excluir-csv').prop('disabled', csvIds.length === 0);

                            // Mensagem de resumo
                            if (csvIds.length === 0) {
                                showToast('Nenhum colaborador do arquivo pôde ser excluído. Confira a lista abaixo.', 'warning');
                            } else {
                                showToast(csvIds.length + ' colaborador(es) prontos para exclusão. Revise antes de confirmar.', 'info');
                            }
                        },
                        error: function() {
                            $btn.prop('disabled', false).html('<i class="dashicons dashicons-search"></i> Conferir arquivo');
                            showToast('Erro de comunicação ao enviar o arquivo.', 'danger');
                        }
                    });
                });

                // Desenha os blocos de resumo e as tabelas de conferencia
                function montarConferencia(d) {
                    // Blocos numericos do topo
                    var resumo = '';
                    resumo += '<div class="explode-csv-bloco neutro"><strong>' + d.matriculas + '</strong><span>matrículas no arquivo</span></div>';
                    resumo += '<div class="explode-csv-bloco ok"><strong>' + d.encontrados.length + '</strong><span>serão excluídos</span></div>';
                    resumo += '<div class="explode-csv-bloco aviso"><strong>' + d.nao_encontrados.length + '</strong><span>não encontrados</span></div>';
                    resumo += '<div class="explode-csv-bloco aviso"><strong>' + d.protegidos.length + '</strong><span>protegidos</span></div>';
                    resumo += '<div class="explode-csv-bloco neutro"><strong>' + d.total_desenhos + '</strong><span>desenhos vinculados</span></div>';
                    $('#csv-resumo').html(resumo);

                    // Tabelas detalhadas
                    var html = '';

                    // Quem sera excluido
                    if (d.encontrados.length > 0) {
                        html += '<div class="explode-csv-lista perigo">';
                        html += '<h4><i class="dashicons dashicons-trash"></i> Serão excluídos (' + d.encontrados.length + ')</h4>';
                        html += '<div class="explode-csv-scroll"><table class="explode-csv-tabela">';
                        html += '<thead><tr><th>Matrícula no CSV</th><th>Login</th><th>Nome</th><th>Grupo</th><th>Desenhos</th></tr></thead><tbody>';
                        $.each(d.encontrados, function(i, u) {
                            html += '<tr>';
                            html += '<td><code>' + esc(u.matricula) + '</code></td>';
                            html += '<td>' + esc(u.login) + '</td>';
                            html += '<td>' + esc(u.nome) + '</td>';
                            html += '<td>' + (u.grupo ? esc(u.grupo) : '<em>sem grupo</em>') + '</td>';
                            html += '<td>' + u.desenhos + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table></div></div>';
                    }

                    // Quem esta protegido pelas salvaguardas
                    if (d.protegidos.length > 0) {
                        html += '<div class="explode-csv-lista aviso">';
                        html += '<h4><i class="dashicons dashicons-shield"></i> Protegidos, não serão excluídos (' + d.protegidos.length + ')</h4>';
                        html += '<div class="explode-csv-scroll"><table class="explode-csv-tabela">';
                        html += '<thead><tr><th>Matrícula no CSV</th><th>Login</th><th>Nome</th><th>Motivo</th></tr></thead><tbody>';
                        $.each(d.protegidos, function(i, u) {
                            html += '<tr>';
                            html += '<td><code>' + esc(u.matricula) + '</code></td>';
                            html += '<td>' + esc(u.login) + '</td>';
                            html += '<td>' + esc(u.nome) + '</td>';
                            html += '<td>' + esc(u.motivo) + '</td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table></div></div>';
                    }

                    // Matriculas do arquivo que nao existem no site
                    if (d.nao_encontrados.length > 0) {
                        html += '<div class="explode-csv-lista aviso">';
                        html += '<h4><i class="dashicons dashicons-warning"></i> Não encontrados no site (' + d.nao_encontrados.length + ')</h4>';
                        html += '<div class="explode-csv-chips">';
                        $.each(d.nao_encontrados, function(i, m) {
                            html += '<span class="explode-csv-chip">' + esc(m) + '</span>';
                        });
                        html += '</div></div>';
                    }

                    // Origem da leitura, para o administrador conferir a coluna usada
                    html += '<p class="explode-tip-text">Arquivo <strong>' + esc(d.arquivo) + '</strong> &middot; ';
                    html += d.linhas + ' linha(s) lida(s) &middot; ';
                    html += (d.coluna ? 'coluna <code>' + esc(d.coluna) + '</code>' : 'lista simples, primeira coluna');
                    html += '</p>';

                    $('#csv-listas').html(html);
                    $('#csv-resultado').show();
                }

                // EXCLUIR: pede a confirmacao final e entrega ao fluxo de lotes ja existente
                $('#btn-excluir-csv').on('click', function() {
                    // Sem conferencia valida nao ha exclusao
                    if (!csvDados || csvIds.length === 0) {
                        showToast('Confira o arquivo antes de excluir.', 'warning');
                        return;
                    }

                    // Apagar desenhos junto?
                    var deleteDrawings = $('#chk-apagar-desenhos-csv').is(':checked');

                    // Monta o detalhamento da confirmacao
                    var detalhes = '<div style="color:#9f1239;font-weight:600;">';
                    detalhes += csvIds.length + ' colaborador(es) do arquivo <u>' + esc(csvDados.arquivo) + '</u> serão removidos permanentemente.';
                    if (deleteDrawings && csvDados.total_desenhos > 0) {
                        detalhes += '<br>' + csvDados.total_desenhos + ' desenho(s) e as mídias deles também serão apagados.';
                    }
                    detalhes += '</div>';
                    if (csvDados.protegidos.length > 0) {
                        detalhes += '<div style="margin-top:8px;">' + csvDados.protegidos.length + ' usuário(s) protegidos serão ignorados.</div>';
                    }
                    if (csvDados.nao_encontrados.length > 0) {
                        detalhes += '<div style="margin-top:4px;">' + csvDados.nao_encontrados.length + ' matrícula(s) do arquivo não existem no site e serão ignoradas.</div>';
                    }

                    // Reaproveita o modal de confirmacao com a palavra de seguranca
                    openConfirmModal({
                        title: 'Excluir colaboradores da planilha',
                        message: 'Esta ação é definitiva e não pode ser desfeita.',
                        detailsHtml: detalhes,
                        requiresWord: true,
                        onConfirm: function() {
                            runBatchDeletion(csvIds, deleteDrawings);
                        }
                    });
                });
            }

            /* MODAL DE CONFIRMAÇÃO */
            function openConfirmModal(config) {
                $('#modal-confirm-title').text(config.title);
                $('#modal-confirm-msg').text(config.message);
                $('#modal-confirm-details').html(config.detailsHtml);

                if (config.requiresWord) {
                    $('#modal-confirm-input-wrap').show();
                    $('#ipt-confirmar-palavra').val('').focus();
                    $('#btn-confirmar-acao-definitiva').prop('disabled', true);
                } else {
                    $('#modal-confirm-input-wrap').hide();
                    $('#btn-confirmar-acao-definitiva').prop('disabled', false);
                }

                pendingAction = config.onConfirm;
                $('#modal-confirmacao').fadeIn(200);
            }

            function initModalHandlers() {
                $('#ipt-confirmar-palavra').on('keyup input', function() {
                    var val = $(this).val().trim().toUpperCase();
                    if (val === 'EXCLUIR') {
                        $('#btn-confirmar-acao-definitiva').prop('disabled', false);
                    } else {
                        $('#btn-confirmar-acao-definitiva').prop('disabled', true);
                    }
                });

                $('#btn-fechar-modal-confirm, #btn-cancelar-modal-confirm').on('click', function() {
                    $('#modal-confirmacao').fadeOut(200);
                    pendingAction = null;
                });

                $('#btn-confirmar-acao-definitiva').on('click', function() {
                    $('#modal-confirmacao').fadeOut(200);
                    if (typeof pendingAction === 'function') {
                        pendingAction();
                    }
                });

                $('#btn-concluir-progresso').on('click', function() {
                    location.reload();
                });
            }

            /* PROCESSAMENTO EM LOTE (BATCH AJAX) */
            function runBatchDeletion(userIds, deleteDrawings) {
                if (!userIds || userIds.length === 0) return;

                isProcessing = true;
                $('#modal-progresso').fadeIn(200);
                $('#modal-progresso-footer').hide();

                var totalUsers = userIds.length;
                var batchSize = 25; // Processa em blocos de 25 para evitar timeouts
                var processedCount = 0;
                var totalDeleted = 0;
                var totalDrawingsDeleted = 0;

                var batches = [];
                for (var i = 0; i < userIds.length; i += batchSize) {
                    batches.push(userIds.slice(i, i + batchSize));
                }

                var $logBox = $('#progress-log-box');
                $logBox.html('<div class="log-entry log-info">Iniciando exclusão de ' + totalUsers + ' usuário(s) em ' + batches.length + ' lote(s)...</div>');

                function processNextBatch(batchIndex) {
                    if (batchIndex >= batches.length) {
                        // Concluído!
                        $('#progress-fill').css('width', '100%');
                        $('#progress-percentage').text('100%');
                        $('#progress-text').text('Processamento concluído com sucesso!');
                        
                        $logBox.append('<div class="log-entry log-success"><strong>✓ Finalizado com sucesso! Total de ' + totalDeleted + ' colaboradores removidos.</strong></div>');
                        if (deleteDrawings) {
                            $logBox.append('<div class="log-entry log-success"><strong>✓ ' + totalDrawingsDeleted + ' post(s) de desenhos foram removidos.</strong></div>');
                        }
                        $logBox.scrollTop($logBox[0].scrollHeight);

                        $('#modal-progresso-footer').fadeIn(300);
                        isProcessing = false;
                        return;
                    }

                    var currentBatch = batches[batchIndex];
                    var batchNum = batchIndex + 1;

                    $logBox.append('<div class="log-entry">Processando lote ' + batchNum + ' de ' + batches.length + ' (' + currentBatch.length + ' usuários)...</div>');
                    $logBox.scrollTop($logBox[0].scrollHeight);

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'explode_excluir_usuarios_batch',
                            user_ids: currentBatch,
                            delete_drawings: deleteDrawings ? 'true' : 'false',
                            nonce: explodeNonce
                        },
                        success: function(resp) {
                            if (resp.success) {
                                totalDeleted += (resp.data.deleted_count || 0);
                                totalDrawingsDeleted += (resp.data.drawings_deleted_count || 0);

                                if (resp.data.errors && resp.data.errors.length > 0) {
                                    resp.data.errors.forEach(function(err) {
                                        $logBox.append('<div class="log-entry log-danger">⚠️ ' + err + '</div>');
                                    });
                                }
                            } else {
                                $logBox.append('<div class="log-entry log-danger">Erro no lote ' + batchNum + ': ' + (resp.data.message || 'Falha desconhecida') + '</div>');
                            }

                            processedCount += currentBatch.length;
                            var pct = Math.min(100, Math.round((processedCount / totalUsers) * 100));
                            $('#progress-fill').css('width', pct + '%');
                            $('#progress-percentage').text(pct + '%');
                            $('#progress-text').text('Excluídos ' + processedCount + ' de ' + totalUsers + ' usuários...');
                            $logBox.scrollTop($logBox[0].scrollHeight);

                            // Próximo lote
                            setTimeout(function() {
                                processNextBatch(batchIndex + 1);
                            }, 150);
                        },
                        error: function() {
                            $logBox.append('<div class="log-entry log-danger">Falha crítica de conexão no lote ' + batchNum + '. Continuando...</div>');
                            processedCount += currentBatch.length;
                            processNextBatch(batchIndex + 1);
                        }
                    });
                }

                processNextBatch(0);
            }

        })(jQuery);
        </script>
        <?php
    }
}

// Inicializa a classe
Explode_Admin_User_Manager::init();
