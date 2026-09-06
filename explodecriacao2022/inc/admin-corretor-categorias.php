<?php
/**
 * Corretor de Categorias dos Dependentes
 * Tema: Explode Criação
 *
 * REGRA DE NEGÓCIO
 * O campo user_field_dependente_N_cat guarda DOIS IDs de termos da taxonomia desenhos_cat
 * separados por ponto, sempre nesta ordem:
 *
 *     <ID da categoria de idade>  .  <ID do grupo do colaborador>
 *
 * A categoria varia livremente de um dependente para outro do MESMO colaborador
 * (o filho 1 pode ser A, o filho 2 B, o filho 3 C), mas o segundo número
 * — o grupo — precisa ser SEMPRE o mesmo: o grupo do pai/mãe, gravado em
 * user_field_funcionario_grupo. É esse invariante que o front-end usa para decidir
 * quais desenhos o colaborador enxerga (template-filtro.php compara
 * sanitize_title do grupo do usuário com os slugs dos termos do desenho).
 *
 * Esta ferramenta varre a base, aponta cada dependente fora da regra e corrige em lote,
 * sempre gravando um arquivo de desfazer antes de alterar qualquer valor.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança
}

class Explode_Admin_Cat_Fixer {

    /** Slug da página administrativa */
    const MENU_SLUG = 'explode-corrigir-categorias';

    /** Nome do nonce usado nos formulários e chamadas AJAX */
    const NONCE = 'explode_corretor_nonce';

    /**
     * Quantidade de slots de dependente suportada pelo tema
     *
     * Lê o valor central definido em functions.php, para nunca divergir do
     * formulário de envio nem do importador de usuários.
     */
    public static function max_dep() {
        // Delega ao valor central do tema, com uma reserva segura
        return function_exists( 'explode_max_dependentes' ) ? explode_max_dependentes() : 6;
    }

    /** Quantidade de usuários analisados por requisição AJAX */
    const LOTE_ANALISE = 400;

    /** Quantidade de correções gravadas por requisição AJAX */
    const LOTE_CORRECAO = 150;

    /** Quantidade de linhas exibidas por página na tela de resultado */
    const POR_PAGINA = 100;

    /**
     * Catálogo dos problemas detectáveis
     * corrigivel = pode ser resolvido automaticamente sem adivinhar a categoria
     */
    public static function catalogo_problemas() {
        return array(
            'grupo_incorreto' => array(
                'rotulo'    => 'Grupo de outro colaborador',
                'descricao' => 'A categoria aponta para um grupo que não é o do colaborador. É o caso clássico: o dependente fica invisível na fase de votação.',
                'corrigivel' => true,
                'cor'       => 'red',
            ),
            'grupo_invalido' => array(
                'rotulo'    => 'Grupo inexistente',
                'descricao' => 'O número do grupo não corresponde a nenhum termo cadastrado (ex.: 320 no lugar de 36).',
                'corrigivel' => true,
                'cor'       => 'red',
            ),
            'grupo_ausente' => array(
                'rotulo'    => 'Grupo faltando',
                'descricao' => 'Só a categoria foi gravada, sem o grupo concatenado.',
                'corrigivel' => true,
                'cor'       => 'amber',
            ),
            'ordem_invertida' => array(
                'rotulo'    => 'Ordem invertida',
                'descricao' => 'O grupo foi gravado antes da categoria (ex.: 32.3 no lugar de 3.32).',
                'corrigivel' => true,
                'cor'       => 'amber',
            ),
            'multiplas_categorias' => array(
                'rotulo'    => 'Mais de uma categoria',
                'descricao' => 'O campo tem duas ou mais categorias de idade. Fica valendo a primeira.',
                'corrigivel' => true,
                'cor'       => 'amber',
            ),
            'formato' => array(
                'rotulo'    => 'Formatação irregular',
                'descricao' => 'Os IDs estão certos, mas a separação não é o ponto simples (ex.: vírgula, espaço ou texto solto).',
                'corrigivel' => true,
                'cor'       => 'cyan',
            ),
            'residuo_slot_vazio' => array(
                'rotulo'    => 'Resíduo em slot sem dependente',
                'descricao' => 'O slot não tem nome de dependente, mas ficou com categoria gravada. Deve ser limpo.',
                'corrigivel' => true,
                'cor'       => 'cyan',
            ),
            'categoria_ausente' => array(
                'rotulo'    => 'Categoria faltando',
                'descricao' => 'O dependente existe mas não tem categoria de idade. Só dá para corrigir deduzindo pela idade.',
                'corrigivel' => false,
                'cor'       => 'red',
            ),
            'categoria_invalida' => array(
                'rotulo'    => 'Categoria inexistente',
                'descricao' => 'O número gravado como categoria não é um termo de categoria válido.',
                'corrigivel' => false,
                'cor'       => 'red',
            ),
            'usuario_sem_grupo' => array(
                'rotulo'    => 'Colaborador sem grupo',
                'descricao' => 'O colaborador não tem grupo definido ou o grupo não existe na taxonomia. Corrija o cadastro dele primeiro.',
                'corrigivel' => false,
                'cor'       => 'red',
            ),
        );
    }

    /**
     * Inicialização dos hooks
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_requests' ) );
        add_action( 'wp_ajax_explode_cat_analisar', array( __CLASS__, 'ajax_analisar_lote' ) );
        add_action( 'wp_ajax_explode_cat_corrigir', array( __CLASS__, 'ajax_corrigir_lote' ) );
    }

    /**
     * Registra o menu no WP-Admin
     */
    public static function register_admin_menu() {
        // Menu de nível superior
        add_menu_page(
            'Corretor de Categorias dos Dependentes',
            'Corrigir Categorias',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_admin_page' ),
            'dashicons-image-filter',
            72
        );

        // Também acessível dentro do menu Usuários
        add_submenu_page(
            'users.php',
            'Corretor de Categorias dos Dependentes',
            'Corrigir Categorias',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_admin_page' )
        );
    }

    /**
     * Verifica se o usuário atual pode operar a ferramenta
     */
    private static function pode_operar() {
        return current_user_can( 'manage_options' ) && current_user_can( 'edit_users' );
    }

    /* =====================================================================
     * DIRETORIO PROTEGIDO E ARQUIVOS DE TRABALHO
     * ===================================================================== */

    /**
     * Retorna (criando se necessário) o diretório protegido dos arquivos de análise
     */
    private static function dir_trabalho() {
        // Pasta de uploads do WordPress
        $up = wp_upload_dir();
        // Caminho da pasta da ferramenta
        $dir = trailingslashit( $up['basedir'] ) . 'explode-corretor';

        // Cria a pasta quando ainda não existe
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        // Bloqueia o acesso direto pelo navegador
        if ( ! file_exists( $dir . '/.htaccess' ) ) {
            $regras  = 'Order Deny,Allow' . PHP_EOL . 'Deny from all' . PHP_EOL;
            $regras .= '<IfModule mod_authz_core.c>' . PHP_EOL . 'Require all denied' . PHP_EOL . '</IfModule>' . PHP_EOL;
            @file_put_contents( $dir . '/.htaccess', $regras );
        }

        // Impede a listagem do diretório
        if ( ! file_exists( $dir . '/index.php' ) ) {
            @file_put_contents( $dir . '/index.php', '<?php // Silence is golden.' );
        }

        // Devolve o caminho
        return $dir;
    }

    /**
     * Monta o caminho de um arquivo da análise a partir do token
     */
    private static function caminho( $token, $sufixo ) {
        // Valida o token contra travessia de diretório
        if ( ! preg_match( '/^[a-zA-Z0-9]{16,64}$/', (string) $token ) ) {
            return '';
        }
        // Devolve o caminho montado
        return trailingslashit( self::dir_trabalho() ) . 'analise-' . $token . '-' . $sufixo;
    }

    /**
     * Remove análises com mais de 24 horas
     */
    private static function limpar_antigos() {
        // Localiza os arquivos existentes
        $arquivos = glob( trailingslashit( self::dir_trabalho() ) . 'analise-*' );
        // Nada a fazer
        if ( ! is_array( $arquivos ) ) {
            return;
        }
        // Data limite
        $limite = time() - DAY_IN_SECONDS;
        // Remove os vencidos
        foreach ( $arquivos as $arq ) {
            if ( is_file( $arq ) && filemtime( $arq ) < $limite ) {
                @unlink( $arq );
            }
        }
    }

    /**
     * Grava o cabeçalho da análise
     */
    private static function salvar_cabecalho( $cab ) {
        // Caminho do arquivo
        $arq = self::caminho( $cab['token'], 'cab.json' );
        // Token inválido
        if ( '' === $arq ) {
            return false;
        }
        // Grava o JSON
        return false !== file_put_contents( $arq, wp_json_encode( $cab ) );
    }

    /**
     * Lê o cabeçalho da análise validando o autor
     */
    private static function ler_cabecalho( $token ) {
        // Caminho do arquivo
        $arq = self::caminho( $token, 'cab.json' );
        // Arquivo ausente
        if ( '' === $arq || ! file_exists( $arq ) ) {
            return null;
        }
        // Decodifica
        $dados = json_decode( file_get_contents( $arq ), true );
        // Estrutura inválida
        if ( ! is_array( $dados ) || ! isset( $dados['token'] ) ) {
            return null;
        }
        // Somente quem criou a análise pode usá-la
        if ( (int) $dados['autor'] !== get_current_user_id() ) {
            return null;
        }
        // Devolve o cabeçalho
        return $dados;
    }

    /* =====================================================================
     * RESOLUCAO DE TERMOS
     * ===================================================================== */

    /**
     * Devolve o mapa de termos da taxonomia reaproveitando o importador
     */
    public static function termos() {
        // Garante que a classe do importador esteja carregada
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }
        // Reaproveita o mapa já implementado e testado
        return Explode_Admin_User_Importer::carregar_termos();
    }

    /**
     * Resolve os IDs de grupo de um colaborador a partir do meta user_field_funcionario_grupo
     * O meta aceita mais de um grupo separado por vírgula, ponto e vírgula ou barra
     */
    public static function grupos_do_usuario( $meta_grupo, $termos ) {
        // Estrutura de retorno
        $saida = array( 'ids' => array(), 'nomes' => array(), 'nao_resolvidos' => array() );

        // Meta vazio
        if ( '' === trim( (string) $meta_grupo ) ) {
            return $saida;
        }

        // Separa os grupos informados
        $partes = preg_split( '/\s*[,;\|\r\n]+\s*/u', (string) $meta_grupo, -1, PREG_SPLIT_NO_EMPTY );

        // Resolve cada parte contra a taxonomia
        foreach ( (array) $partes as $parte ) {
            // Limpa o texto
            $texto = trim( $parte );
            // Ignora vazios
            if ( '' === $texto ) {
                continue;
            }
            // Normaliza para a chave usada no mapa de termos
            $chave = Explode_Admin_User_Importer::chave( $texto );
            // Procura o termo do grupo
            if ( isset( $termos['grupos'][ $chave ] ) ) {
                // Guarda o ID e o nome oficiais
                $saida['ids'][]   = (int) $termos['grupos'][ $chave ]['id'];
                $saida['nomes'][] = $termos['grupos'][ $chave ]['nome'];
            } else {
                // Registra o que não foi possível resolver
                $saida['nao_resolvidos'][] = $texto;
            }
        }

        // Remove repetições preservando a ordem
        $saida['ids']   = array_values( array_unique( $saida['ids'] ) );
        $saida['nomes'] = array_values( array_unique( $saida['nomes'] ) );

        // Devolve o resultado
        return $saida;
    }

    /* =====================================================================
     * MOTOR DE DIAGNOSTICO
     * ===================================================================== */

    /**
     * Avalia UM slot de dependente e devolve o diagnóstico
     *
     * @param int    $slot            Número do slot (1 a 4)
     * @param string $nome            Conteúdo de user_field_dependente_N_nome
     * @param string $idade           Conteúdo de user_field_dependente_N_idade
     * @param string $cat_raw         Conteúdo atual de user_field_dependente_N_cat
     * @param string $desenho         Conteúdo de user_field_dependente_N_desenho
     * @param array  $grupos_ids      IDs de grupo válidos para o colaborador
     * @param array  $termos          Mapa de termos da taxonomia
     * @param bool   $deduzir_idade   Se pode deduzir a categoria a partir da idade
     * @return array|null             null quando o slot está correto ou vazio
     */
    public static function avaliar_slot( $slot, $nome, $idade, $cat_raw, $desenho, $grupos_ids, $termos, $deduzir_idade ) {

        // Normaliza os valores recebidos
        $nome    = trim( (string) $nome );
        $raw     = trim( (string) $cat_raw );
        $idade_n = ( '' !== trim( (string) $idade ) && preg_match( '/(\d+)/', (string) $idade, $mi ) ) ? (int) $mi[1] : 0;
        $enviou  = ( 'Sim' === $desenho );

        // Base do diagnóstico
        $base = array(
            'slot'    => (int) $slot,
            'nome'    => $nome,
            'idade'   => $idade_n,
            'atual'   => $raw,
            'novo'    => '',
            'desenho' => $enviou ? 1 : 0,
            'aviso'   => '',
        );

        // ---------------------------------------------------------------
        // CASO 1: slot sem dependente
        // ---------------------------------------------------------------
        if ( '' === $nome ) {
            // Sem nome e sem categoria: nada a reportar
            if ( '' === $raw ) {
                return null;
            }
            // Sem nome mas com categoria gravada: é resíduo e deve ser limpo
            return array_merge( $base, array(
                'tipo'   => 'residuo_slot_vazio',
                'novo'   => '',
                'motivo' => 'O slot ' . $slot . ' não tem nome de dependente, mas guarda a categoria "' . $raw . '".',
            ) );
        }

        // ---------------------------------------------------------------
        // CASO 2: colaborador sem grupo resolvível
        // ---------------------------------------------------------------
        if ( empty( $grupos_ids ) ) {
            return array_merge( $base, array(
                'tipo'   => 'usuario_sem_grupo',
                'novo'   => '',
                'motivo' => 'Não foi possível descobrir o grupo do colaborador, então a categoria não pode ser montada.',
            ) );
        }

        // Grupo canônico do colaborador (o primeiro informado no cadastro)
        $grupo_canonico = (int) $grupos_ids[0];

        // ---------------------------------------------------------------
        // Separa o conteúdo atual em números e classifica cada um
        // ---------------------------------------------------------------
        $tokens = array();
        // Extrai todos os números presentes na string
        if ( preg_match_all( '/\d+/', $raw, $mm ) ) {
            $tokens = array_map( 'intval', $mm[0] );
        }

        // Baldes por tipo de termo
        $cats = array();   // IDs de categoria de idade
        $grps = array();   // IDs de grupo
        $desc = array();   // números que não são termos conhecidos
        $ordem = array();  // sequência dos tipos, para detectar inversão

        // Classifica cada número encontrado
        foreach ( $tokens as $tk ) {
            // Termo conhecido
            if ( isset( $termos['por_id'][ $tk ] ) ) {
                // Tipo do termo
                $tipo_termo = $termos['por_id'][ $tk ]['tipo'];
                // Categoria de faixa etária
                if ( 'categoria' === $tipo_termo ) {
                    $cats[] = $tk;
                    $ordem[] = 'c';
                } elseif ( 'grupo' === $tipo_termo ) {
                    // Grupo
                    $grps[] = $tk;
                    $ordem[] = 'g';
                } else {
                    // Termo PCD ou outro: não participa da concatenação
                    $ordem[] = 'x';
                }
            } else {
                // Número que não corresponde a termo algum
                $desc[] = $tk;
                $ordem[] = 'd';
            }
        }

        // Categoria e grupo atualmente identificados
        $cat_id = ! empty( $cats ) ? (int) $cats[0] : 0;
        $grp_id = ! empty( $grps ) ? (int) $grps[0] : 0;

        // ---------------------------------------------------------------
        // Define qual grupo deve valer
        // Se o grupo gravado é um dos grupos do colaborador, ele é mantido.
        // Caso contrário vale o grupo canônico do cadastro.
        // ---------------------------------------------------------------
        $grupo_alvo = in_array( $grp_id, array_map( 'intval', $grupos_ids ), true ) ? $grp_id : $grupo_canonico;

        // Encontra o nome do grupo alvo para passar para a função
        $nome_do_grupo = '';
        if ( isset( $termos['por_id'][ $grupo_alvo ] ) ) {
            $nome_do_grupo = $termos['por_id'][ $grupo_alvo ]['nome'];
        }

        // Categoria sugerida pela idade, usada como aviso e como último recurso
        $cat_idade_nome = ( $idade_n > 0 ) ? Explode_Admin_User_Importer::categoria_por_idade( $idade_n, $nome_do_grupo ) : '';
        $cat_idade_id   = 0;
        // Converte o nome sugerido em ID de termo
        if ( '' !== $cat_idade_nome ) {
            $chave_idade = Explode_Admin_User_Importer::chave( $cat_idade_nome );
            if ( isset( $termos['categorias'][ $chave_idade ] ) ) {
                $cat_idade_id = (int) $termos['categorias'][ $chave_idade ]['id'];
            }
        }

        // ---------------------------------------------------------------
        // CASO 3: sem categoria identificável
        // ---------------------------------------------------------------
        if ( $cat_id < 1 ) {
            // Tenta deduzir pela idade quando o administrador autorizou
            if ( $deduzir_idade && $cat_idade_id > 0 ) {
                return array_merge( $base, array(
                    'tipo'   => ( '' === $raw || empty( $tokens ) ) ? 'categoria_ausente' : 'categoria_invalida',
                    'novo'   => $cat_idade_id . '.' . $grupo_alvo,
                    'motivo' => 'Categoria deduzida pela idade (' . $idade_n . ' anos → ' . $cat_idade_nome . ').',
                    'deduzida' => 1,
                ) );
            }
            // Sem idade utilizável não há como adivinhar a categoria
            return array_merge( $base, array(
                'tipo'   => ( '' === $raw || empty( $tokens ) ) ? 'categoria_ausente' : 'categoria_invalida',
                'novo'   => '',
                'motivo' => ( '' === $raw )
                    ? 'O dependente está sem categoria e a idade não permite deduzir uma faixa válida.'
                    : 'O valor "' . $raw . '" não contém nenhuma categoria de idade válida.',
            ) );
        }

        // ---------------------------------------------------------------
        // Monta o valor correto e compara com o que está gravado
        // ---------------------------------------------------------------
        $novo = $cat_id . '.' . $grupo_alvo;

        // Aviso informativo quando a categoria não bate com a faixa etária
        $aviso = '';
        if ( $cat_idade_id > 0 && $cat_idade_id !== $cat_id ) {
            // Nome da categoria gravada
            $nome_cat_atual = isset( $termos['por_id'][ $cat_id ] ) ? $termos['por_id'][ $cat_id ]['nome'] : ( 'ID ' . $cat_id );
            // Monta a mensagem
            $aviso = 'A idade (' . $idade_n . ' anos) corresponde a ' . $cat_idade_nome . ', mas está gravado ' . $nome_cat_atual . '.';
        }

        // Valor já está correto
        if ( $novo === $raw ) {
            // Devolve apenas quando existe aviso de idade a reportar
            if ( '' !== $aviso ) {
                return array_merge( $base, array(
                    'tipo'   => 'aviso_idade',
                    'novo'   => $cat_idade_id . '.' . $grupo_alvo,
                    'motivo' => $aviso,
                    'aviso'  => $aviso,
                ) );
            }
            // Nada a fazer
            return null;
        }

        // ---------------------------------------------------------------
        // Classifica o motivo da divergência
        // ---------------------------------------------------------------
        if ( $grp_id > 0 && $grp_id !== $grupo_alvo ) {
            // O grupo gravado existe mas pertence a outro colaborador
            $nome_grp_errado = isset( $termos['por_id'][ $grp_id ] ) ? $termos['por_id'][ $grp_id ]['nome'] : ( 'ID ' . $grp_id );
            $nome_grp_certo  = isset( $termos['por_id'][ $grupo_alvo ] ) ? $termos['por_id'][ $grupo_alvo ]['nome'] : ( 'ID ' . $grupo_alvo );
            $tipo   = 'grupo_incorreto';
            $motivo = 'Está apontando para ' . $nome_grp_errado . ' e o colaborador é do ' . $nome_grp_certo . '.';
        } elseif ( empty( $grps ) && ! empty( $desc ) ) {
            // Há um número no lugar do grupo, mas ele não é um termo válido
            $tipo   = 'grupo_invalido';
            $motivo = 'O número ' . implode( ', ', $desc ) . ' não corresponde a nenhum grupo cadastrado.';
        } elseif ( empty( $grps ) && count( $cats ) > 1 ) {
            // Duas categorias e nenhum grupo
            $tipo   = 'multiplas_categorias';
            $motivo = 'O campo tem ' . count( $cats ) . ' categorias e nenhum grupo. Fica valendo a primeira categoria.';
        } elseif ( empty( $grps ) ) {
            // Só a categoria foi gravada
            $tipo   = 'grupo_ausente';
            $motivo = 'Falta concatenar o grupo do colaborador.';
        } elseif ( ! empty( $ordem ) && 'g' === $ordem[0] && in_array( 'c', $ordem, true ) ) {
            // O grupo aparece antes da categoria
            $tipo   = 'ordem_invertida';
            $motivo = 'O grupo foi gravado antes da categoria.';
        } elseif ( count( $cats ) > 1 ) {
            // Mais de uma categoria com o grupo certo
            $tipo   = 'multiplas_categorias';
            $motivo = 'O campo tem ' . count( $cats ) . ' categorias. Fica valendo a primeira.';
        } else {
            // Os IDs estão certos, só a escrita está fora do padrão
            $tipo   = 'formato';
            $motivo = 'Os IDs estão corretos, mas a formatação difere do padrão "categoria.grupo".';
        }

        // Devolve o diagnóstico completo
        return array_merge( $base, array(
            'tipo'   => $tipo,
            'novo'   => $novo,
            'motivo' => $motivo,
            'aviso'  => $aviso,
        ) );
    }

    /* =====================================================================
     * VARREDURA EM LOTES
     * ===================================================================== */

    /**
     * Monta a lista de chaves de usermeta que a varredura precisa ler
     */
    private static function chaves_meta() {
        // Começa com o grupo do colaborador
        $chaves = array( 'user_field_funcionario_grupo' );
        // Acrescenta os campos de cada slot
        for ( $s = 1; $s <= self::max_dep(); $s++ ) {
            $chaves[] = 'user_field_dependente_' . $s . '_nome';
            $chaves[] = 'user_field_dependente_' . $s . '_idade';
            $chaves[] = 'user_field_dependente_' . $s . '_cat';
            $chaves[] = 'user_field_dependente_' . $s . '_desenho';
        }
        // Devolve a lista
        return $chaves;
    }

    /**
     * Acrescenta um achado nos arquivos de dados e no índice de posições
     */
    private static function gravar_item( $fp_itens, $fp_idx, $item ) {
        // Posição atual antes de escrever
        $pos = ftell( $fp_itens );
        // Escreve o achado em uma linha
        fwrite( $fp_itens, wp_json_encode( $item ) . "\n" );
        // Registra a posição no índice (inteiro de 8 bytes)
        fwrite( $fp_idx, pack( 'J', $pos ) );
    }

    /**
     * Lê as posições de um intervalo de achados a partir do índice
     */
    private static function ler_offsets( $token, $inicio, $quantidade ) {
        // Caminho do índice
        $arq = self::caminho( $token, 'itens.idx' );
        // Lista de retorno
        $lista = array();
        // Índice ausente
        if ( '' === $arq || ! file_exists( $arq ) ) {
            return $lista;
        }
        // Abre o índice
        $fp = fopen( $arq, 'rb' );
        // Falha de abertura
        if ( ! $fp ) {
            return $lista;
        }
        // Posiciona no primeiro registro pedido
        fseek( $fp, $inicio * 8 );
        // Lê o bloco inteiro de uma vez
        $bloco = fread( $fp, $quantidade * 8 );
        // Fecha o índice
        fclose( $fp );
        // Converte cada grupo de 8 bytes em inteiro
        if ( is_string( $bloco ) && strlen( $bloco ) >= 8 ) {
            $total = intdiv( strlen( $bloco ), 8 );
            for ( $i = 0; $i < $total; $i++ ) {
                $u = unpack( 'J', substr( $bloco, $i * 8, 8 ) );
                $lista[] = (int) $u[1];
            }
        }
        // Devolve as posições
        return $lista;
    }

    /**
     * Lê os achados correspondentes a uma lista de posições
     */
    private static function ler_por_offsets( $token, $offsets ) {
        // Caminho do arquivo de achados
        $arq = self::caminho( $token, 'itens.jsonl' );
        // Lista de retorno
        $lista = array();
        // Arquivo ausente ou nada a ler
        if ( '' === $arq || ! file_exists( $arq ) || empty( $offsets ) ) {
            return $lista;
        }
        // Abre o arquivo
        $fp = fopen( $arq, 'r' );
        // Falha de abertura
        if ( ! $fp ) {
            return $lista;
        }
        // Lê cada posição solicitada
        foreach ( $offsets as $pos ) {
            // Posiciona o ponteiro
            fseek( $fp, (int) $pos );
            // Lê a linha
            $linha = fgets( $fp );
            // Decodifica quando válida
            if ( false !== $linha ) {
                $item = json_decode( $linha, true );
                if ( is_array( $item ) ) {
                    $lista[] = $item;
                }
            }
        }
        // Fecha o arquivo
        fclose( $fp );
        // Devolve os achados
        return $lista;
    }

    /**
     * Lê uma página de achados aplicando o filtro por tipo de problema
     */
    private static function ler_pagina( $token, $pagina, $filtro = '' ) {
        // Índice do primeiro item da página
        $inicio = max( 0, ( (int) $pagina - 1 ) ) * self::POR_PAGINA;

        // Sem filtro basta usar o índice de posições
        if ( '' === $filtro ) {
            $offsets = self::ler_offsets( $token, $inicio, self::POR_PAGINA );
            return self::ler_por_offsets( $token, $offsets );
        }

        // Com filtro percorre o arquivo contando apenas os achados do tipo pedido
        $arq = self::caminho( $token, 'itens.jsonl' );
        // Lista de retorno
        $lista = array();
        // Arquivo ausente
        if ( '' === $arq || ! file_exists( $arq ) ) {
            return $lista;
        }
        // Abre o arquivo
        $fp = fopen( $arq, 'r' );
        // Falha de abertura
        if ( ! $fp ) {
            return $lista;
        }
        // Contador de itens do tipo já vistos
        $vistos = 0;
        // Percorre linha a linha
        while ( false !== ( $linha = fgets( $fp ) ) ) {
            // Decodifica
            $item = json_decode( $linha, true );
            // Ignora linhas inválidas ou de outro tipo
            if ( ! is_array( $item ) || $item['tipo'] !== $filtro ) {
                continue;
            }
            // Já passou da página pedida
            if ( $vistos >= $inicio + self::POR_PAGINA ) {
                break;
            }
            // Está dentro da janela da página
            if ( $vistos >= $inicio ) {
                $lista[] = $item;
            }
            // Avança o contador
            $vistos++;
        }
        // Fecha o arquivo
        fclose( $fp );
        // Devolve a página
        return $lista;
    }

    /**
     * AJAX: analisa um lote de colaboradores
     */
    public static function ajax_analisar_lote() {
        // Confere o nonce
        check_ajax_referer( self::NONCE, 'nonce' );

        // Confere a permissão
        if ( ! self::pode_operar() ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ) );
        }

        // Acesso direto ao banco
        global $wpdb;

        // Lê os parâmetros
        $token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
        $inicio  = isset( $_POST['inicio'] ) ? max( 0, (int) $_POST['inicio'] ) : 0;

        // Carrega o cabeçalho da análise
        $cab = self::ler_cabecalho( $token );

        // Análise inexistente ou expirada
        if ( ! $cab ) {
            wp_send_json_error( array( 'message' => 'Análise não encontrada ou expirada. Inicie uma nova varredura.' ) );
        }

        // Mapa de termos da taxonomia
        $termos = self::termos();
        // Opção de deduzir a categoria pela idade
        $deduzir = ! empty( $cab['opcoes']['deduzir_idade'] );
        // Opção de reportar divergência entre idade e categoria
        $checar_idade = ! empty( $cab['opcoes']['checar_idade'] );

        // Busca os IDs dos colaboradores deste lote, em ordem estável
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} ORDER BY ID ASC LIMIT %d OFFSET %d",
            self::LOTE_ANALISE,
            $inicio
        ) );

        // Abre os arquivos de saída em modo de acréscimo
        $fp_itens = fopen( self::caminho( $token, 'itens.jsonl' ), 'a' );
        $fp_idx   = fopen( self::caminho( $token, 'itens.idx' ), 'ab' );

        // Contadores deste lote
        $novos = 0;

        // Só consulta os metadados se houver usuários no lote
        if ( ! empty( $ids ) ) {

            // Monta a consulta única de metadados do lote
            $ids_int    = array_map( 'intval', $ids );
            $lista_ids  = implode( ',', $ids_int );
            $chaves     = self::chaves_meta();
            $marcadores = implode( ',', array_fill( 0, count( $chaves ), '%s' ) );

            // Busca todos os metadados relevantes de uma vez
            $linhas = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta}
                     WHERE user_id IN ({$lista_ids}) AND meta_key IN ({$marcadores})",
                    $chaves
                ),
                ARRAY_A
            );

            // Indexa os metadados por usuário
            $meta = array();
            foreach ( (array) $linhas as $l ) {
                $meta[ (int) $l['user_id'] ][ $l['meta_key'] ] = $l['meta_value'];
            }

            // Dados básicos dos usuários do lote
            $dados_users = $wpdb->get_results(
                "SELECT ID, user_login, display_name FROM {$wpdb->users} WHERE ID IN ({$lista_ids})",
                ARRAY_A
            );
            // Indexa por ID
            $users = array();
            foreach ( (array) $dados_users as $u ) {
                $users[ (int) $u['ID'] ] = $u;
            }

            // Avalia cada colaborador do lote
            foreach ( $ids_int as $uid ) {
                // Metadados do usuário
                $m = isset( $meta[ $uid ] ) ? $meta[ $uid ] : array();

                // Descobre se o usuário tem algum dependente ou resíduo a avaliar
                $tem_conteudo = false;
                for ( $s = 1; $s <= self::max_dep(); $s++ ) {
                    if ( '' !== trim( (string) ( isset( $m[ 'user_field_dependente_' . $s . '_nome' ] ) ? $m[ 'user_field_dependente_' . $s . '_nome' ] : '' ) )
                      || '' !== trim( (string) ( isset( $m[ 'user_field_dependente_' . $s . '_cat' ] ) ? $m[ 'user_field_dependente_' . $s . '_cat' ] : '' ) ) ) {
                        $tem_conteudo = true;
                        break;
                    }
                }
                // Usuário sem dependentes não interessa à varredura
                if ( ! $tem_conteudo ) {
                    continue;
                }

                // Resolve os grupos do colaborador
                $meta_grupo = isset( $m['user_field_funcionario_grupo'] ) ? $m['user_field_funcionario_grupo'] : '';
                $grupos     = self::grupos_do_usuario( $meta_grupo, $termos );

                // Avalia cada slot
                for ( $s = 1; $s <= self::max_dep(); $s++ ) {
                    // Executa o diagnóstico do slot
                    $diag = self::avaliar_slot(
                        $s,
                        isset( $m[ 'user_field_dependente_' . $s . '_nome' ] ) ? $m[ 'user_field_dependente_' . $s . '_nome' ] : '',
                        isset( $m[ 'user_field_dependente_' . $s . '_idade' ] ) ? $m[ 'user_field_dependente_' . $s . '_idade' ] : '',
                        isset( $m[ 'user_field_dependente_' . $s . '_cat' ] ) ? $m[ 'user_field_dependente_' . $s . '_cat' ] : '',
                        isset( $m[ 'user_field_dependente_' . $s . '_desenho' ] ) ? $m[ 'user_field_dependente_' . $s . '_desenho' ] : '',
                        $grupos['ids'],
                        $termos,
                        $deduzir
                    );

                    // Slot correto
                    if ( null === $diag ) {
                        $cab['contadores']['ok']++;
                        continue;
                    }

                    // Aviso de idade só é registrado quando o administrador pediu
                    if ( 'aviso_idade' === $diag['tipo'] && ! $checar_idade ) {
                        $cab['contadores']['ok']++;
                        continue;
                    }

                    // Completa o achado com a identificação do colaborador
                    $diag['user_id']    = $uid;
                    $diag['login']      = isset( $users[ $uid ]['user_login'] ) ? $users[ $uid ]['user_login'] : (string) $uid;
                    $diag['colab']      = isset( $users[ $uid ]['display_name'] ) ? $users[ $uid ]['display_name'] : '';
                    $diag['grupo_meta'] = trim( (string) $meta_grupo );
                    $diag['grupo_nome'] = ! empty( $grupos['nomes'] ) ? implode( ', ', $grupos['nomes'] ) : '';
                    $diag['grupo_id']   = ! empty( $grupos['ids'] ) ? (int) $grupos['ids'][0] : 0;

                    // Grava o achado
                    self::gravar_item( $fp_itens, $fp_idx, $diag );

                    // Atualiza os contadores
                    $cab['contadores']['total']++;
                    if ( ! isset( $cab['contadores']['tipos'][ $diag['tipo'] ] ) ) {
                        $cab['contadores']['tipos'][ $diag['tipo'] ] = 0;
                    }
                    $cab['contadores']['tipos'][ $diag['tipo'] ]++;
                    // Conta separadamente os que já enviaram desenho
                    if ( ! empty( $diag['desenho'] ) ) {
                        $cab['contadores']['com_desenho']++;
                    }
                    // Conta os que podem ser corrigidos automaticamente
                    if ( '' !== $diag['novo'] || 'residuo_slot_vazio' === $diag['tipo'] ) {
                        $cab['contadores']['corrigiveis']++;
                    }
                    // Avança o contador do lote
                    $novos++;
                }

                // Contabiliza o colaborador analisado
                $cab['contadores']['usuarios']++;
            }
        }

        // Fecha os arquivos
        fclose( $fp_itens );
        fclose( $fp_idx );

        // Atualiza a posição da varredura
        $cab['processados'] = $inicio + count( $ids );
        // Marca a conclusão quando não há mais usuários
        $cab['concluido']   = ( count( $ids ) < self::LOTE_ANALISE ) ? 1 : 0;
        // Persiste o cabeçalho
        self::salvar_cabecalho( $cab );

        // Devolve o andamento
        wp_send_json_success( array(
            'processados' => $cab['processados'],
            'total_users' => (int) $cab['total_users'],
            'achados'     => (int) $cab['contadores']['total'],
            'novos'       => $novos,
            'fim'         => (int) $cab['concluido'],
        ) );
    }

    /* =====================================================================
     * APLICACAO DAS CORRECOES E DESFAZER
     * ===================================================================== */

    /**
     * AJAX: aplica (ou desfaz) um lote de correções
     */
    public static function ajax_corrigir_lote() {
        // Confere o nonce
        check_ajax_referer( self::NONCE, 'nonce' );

        // Confere a permissão
        if ( ! self::pode_operar() ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ) );
        }

        // Lê os parâmetros
        $token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
        $inicio = isset( $_POST['inicio'] ) ? max( 0, (int) $_POST['inicio'] ) : 0;
        $modo   = isset( $_POST['modo'] ) && 'desfazer' === $_POST['modo'] ? 'desfazer' : 'aplicar';

        // Tipos de problema autorizados pelo administrador
        $tipos = array();
        if ( isset( $_POST['tipos'] ) && is_array( $_POST['tipos'] ) ) {
            $tipos = array_map( 'sanitize_key', wp_unslash( $_POST['tipos'] ) );
        }

        // Preservar quem já enviou desenho
        $preservar_desenho = isset( $_POST['preservar_desenho'] ) && 'true' === $_POST['preservar_desenho'];

        // Carrega o cabeçalho
        $cab = self::ler_cabecalho( $token );

        // Análise inexistente
        if ( ! $cab ) {
            wp_send_json_error( array( 'message' => 'Análise não encontrada ou expirada. Faça a varredura novamente.' ) );
        }

        // Encaminha para o desfazer quando for o caso
        if ( 'desfazer' === $modo ) {
            self::processar_desfazer( $cab, $inicio );
        }

        // Nenhum tipo autorizado
        if ( empty( $tipos ) ) {
            wp_send_json_error( array( 'message' => 'Selecione ao menos um tipo de problema para corrigir.' ) );
        }

        // Lê a fatia de achados
        $offsets = self::ler_offsets( $token, $inicio, self::LOTE_CORRECAO );
        $itens   = self::ler_por_offsets( $token, $offsets );

        // Abre os arquivos de desfazer
        $fp_und = fopen( self::caminho( $token, 'desfazer.jsonl' ), 'a' );
        $fp_uidx = fopen( self::caminho( $token, 'desfazer.idx' ), 'ab' );

        // Resultado do lote
        $resumo = array( 'aplicados' => 0, 'ignorados' => 0, 'preservados' => 0 );
        // Linhas de log
        $log = array();

        // Percorre os achados do lote
        foreach ( $itens as $item ) {
            // Tipo não autorizado
            if ( ! in_array( $item['tipo'], $tipos, true ) ) {
                $resumo['ignorados']++;
                continue;
            }

            // Achado sem valor novo e que não é limpeza de resíduo
            if ( '' === $item['novo'] && 'residuo_slot_vazio' !== $item['tipo'] ) {
                $resumo['ignorados']++;
                continue;
            }

            // Salvaguarda: não mexer em dependente que já enviou desenho
            if ( $preservar_desenho && ! empty( $item['desenho'] ) ) {
                $resumo['preservados']++;
                $log[] = array(
                    'tipo'  => 'warning',
                    'texto' => $item['login'] . ' — slot ' . $item['slot'] . ' (' . $item['nome'] . ') preservado: o dependente já enviou desenho.',
                );
                continue;
            }

            // Monta a chave do meta a alterar
            $chave = 'user_field_dependente_' . (int) $item['slot'] . '_cat';
            // Lê o valor que está gravado agora (pode ter mudado desde a varredura)
            $atual_banco = get_user_meta( (int) $item['user_id'], $chave, true );

            // Registra o valor anterior para permitir o desfazer
            $registro_undo = array(
                'user_id'  => (int) $item['user_id'],
                'chave'    => $chave,
                'anterior' => (string) $atual_banco,
                'aplicado' => (string) $item['novo'],
            );
            // Grava no arquivo de desfazer
            $pos = ftell( $fp_und );
            fwrite( $fp_und, wp_json_encode( $registro_undo ) . "\n" );
            fwrite( $fp_uidx, pack( 'J', $pos ) );

            // Aplica a alteração
            if ( 'residuo_slot_vazio' === $item['tipo'] ) {
                // Slot sem dependente: o campo é apagado
                delete_user_meta( (int) $item['user_id'], $chave );
            } else {
                // Grava o valor corrigido
                update_user_meta( (int) $item['user_id'], $chave, $item['novo'] );
            }

            // Contabiliza
            $resumo['aplicados']++;

            // Registra no log da tela
            $log[] = array(
                'tipo'  => 'success',
                'texto' => $item['login'] . ' — slot ' . $item['slot'] . ' (' . $item['nome'] . '): '
                            . ( '' !== (string) $atual_banco ? $atual_banco : 'vazio' )
                            . ' → ' . ( '' !== $item['novo'] ? $item['novo'] : 'limpo' ),
            );
        }

        // Fecha os arquivos de desfazer
        fclose( $fp_und );
        fclose( $fp_uidx );

        // Atualiza o cabeçalho
        $cab['aplicados']      = (int) ( isset( $cab['aplicados'] ) ? $cab['aplicados'] : 0 ) + $resumo['aplicados'];
        $cab['tem_desfazer']   = $cab['aplicados'] > 0 ? 1 : 0;
        $cab['corrigido_ate']  = $inicio + count( $itens );
        // Persiste
        self::salvar_cabecalho( $cab );

        // Devolve o andamento
        wp_send_json_success( array(
            'processados' => $inicio + count( $itens ),
            'total'       => (int) $cab['contadores']['total'],
            'resumo'      => $resumo,
            'log'         => $log,
            'fim'         => ( $inicio + count( $itens ) ) >= (int) $cab['contadores']['total'] || empty( $itens ),
        ) );
    }

    /**
     * Reverte um lote de alterações usando o arquivo de desfazer
     */
    private static function processar_desfazer( $cab, $inicio ) {
        // Token da análise
        $token = $cab['token'];
        // Caminho do índice de desfazer
        $arq_idx = self::caminho( $token, 'desfazer.idx' );

        // Nada gravado para reverter
        if ( ! file_exists( $arq_idx ) ) {
            wp_send_json_error( array( 'message' => 'Não há nenhuma correção desta análise para desfazer.' ) );
        }

        // Total de registros de desfazer
        $total = intdiv( (int) filesize( $arq_idx ), 8 );

        // Lê as posições do lote
        $fp = fopen( $arq_idx, 'rb' );
        fseek( $fp, $inicio * 8 );
        $bloco = fread( $fp, self::LOTE_CORRECAO * 8 );
        fclose( $fp );

        // Converte as posições
        $offsets = array();
        if ( is_string( $bloco ) && strlen( $bloco ) >= 8 ) {
            $qtd = intdiv( strlen( $bloco ), 8 );
            for ( $i = 0; $i < $qtd; $i++ ) {
                $u = unpack( 'J', substr( $bloco, $i * 8, 8 ) );
                $offsets[] = (int) $u[1];
            }
        }

        // Abre o arquivo de registros
        $fp_und = fopen( self::caminho( $token, 'desfazer.jsonl' ), 'r' );
        // Resultado do lote
        $revertidos = 0;
        // Linhas de log
        $log = array();

        // Percorre cada registro
        foreach ( $offsets as $pos ) {
            // Posiciona e lê
            fseek( $fp_und, $pos );
            $linha = fgets( $fp_und );
            // Linha inválida
            if ( false === $linha ) {
                continue;
            }
            // Decodifica
            $reg = json_decode( $linha, true );
            // Estrutura inválida
            if ( ! is_array( $reg ) || ! isset( $reg['user_id'], $reg['chave'] ) ) {
                continue;
            }
            // Restaura o valor anterior
            if ( '' === $reg['anterior'] ) {
                delete_user_meta( (int) $reg['user_id'], $reg['chave'] );
            } else {
                update_user_meta( (int) $reg['user_id'], $reg['chave'], $reg['anterior'] );
            }
            // Contabiliza
            $revertidos++;
            // Registra no log
            $log[] = array(
                'tipo'  => 'warning',
                'texto' => 'Usuário ' . $reg['user_id'] . ' / ' . $reg['chave'] . ': restaurado para '
                            . ( '' !== $reg['anterior'] ? $reg['anterior'] : 'vazio' ),
            );
        }

        // Fecha o arquivo
        fclose( $fp_und );

        // Devolve o andamento
        wp_send_json_success( array(
            'processados' => $inicio + count( $offsets ),
            'total'       => $total,
            'resumo'      => array( 'aplicados' => $revertidos, 'ignorados' => 0, 'preservados' => 0 ),
            'log'         => $log,
            'fim'         => ( $inicio + count( $offsets ) ) >= $total || empty( $offsets ),
        ) );
    }

    /* =====================================================================
     * REQUISICOES (INICIAR VARREDURA E RELATORIO)
     * ===================================================================== */

    /**
     * Trata as requisições da página
     */
    public static function handle_requests() {
        // Só age dentro da página desta ferramenta
        $pagina = isset( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';
        // Fora da página não faz nada
        if ( self::MENU_SLUG !== $pagina ) {
            return;
        }

        // Download do relatório
        if ( isset( $_GET['acao'] ) && 'relatorio' === $_GET['acao'] ) {
            // Permissão
            if ( ! self::pode_operar() ) {
                wp_die( 'Você não tem permissão para acessar este recurso.' );
            }
            // Nonce
            check_admin_referer( self::NONCE );
            // Envia o arquivo
            self::baixar_relatorio( isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '' );
        }

        // Início de uma nova varredura
        if ( isset( $_POST['explode_corretor_iniciar'] ) ) {
            // Permissão
            if ( ! self::pode_operar() ) {
                wp_die( 'Você não tem permissão para executar esta ferramenta.' );
            }
            // Nonce
            check_admin_referer( self::NONCE );
            // Cria a análise
            self::iniciar_analise();
        }
    }

    /**
     * Cria uma nova análise e redireciona para a tela de varredura
     */
    private static function iniciar_analise() {
        // Acesso direto ao banco
        global $wpdb;

        // Remove análises vencidas
        self::limpar_antigos();

        // Gera o token
        $token = wp_generate_password( 32, false, false );

        // Monta o cabeçalho inicial
        $cab = array(
            'token'       => $token,
            'autor'       => get_current_user_id(),
            'criado_em'   => time(),
            'opcoes'      => array(
                'deduzir_idade' => ! empty( $_POST['deduzir_idade'] ),
                'checar_idade'  => ! empty( $_POST['checar_idade'] ),
            ),
            'total_users' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" ),
            'processados' => 0,
            'concluido'   => 0,
            'aplicados'   => 0,
            'tem_desfazer'=> 0,
            'contadores'  => array(
                'total'       => 0,
                'ok'          => 0,
                'usuarios'    => 0,
                'com_desenho' => 0,
                'corrigiveis' => 0,
                'tipos'       => array(),
            ),
        );

        // Cria os arquivos vazios da análise
        @file_put_contents( self::caminho( $token, 'itens.jsonl' ), '' );
        @file_put_contents( self::caminho( $token, 'itens.idx' ), '' );

        // Grava o cabeçalho
        if ( ! self::salvar_cabecalho( $cab ) ) {
            wp_safe_redirect( add_query_arg( 'erro_corretor', rawurlencode( 'Não foi possível gravar os arquivos da análise na pasta de uploads.' ), admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) );
            exit;
        }

        // Redireciona para a tela de varredura
        wp_safe_redirect( add_query_arg( array( 'etapa' => 'analisar', 'token' => $token ), admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) );
        exit;
    }

    /**
     * Gera e envia o relatório completo da análise em CSV
     */
    private static function baixar_relatorio( $token ) {
        // Carrega o cabeçalho para validar o acesso
        $cab = self::ler_cabecalho( $token );
        // Análise inválida
        if ( ! $cab ) {
            wp_die( 'Análise não encontrada ou expirada.' );
        }

        // Caminho do arquivo de achados
        $arq = self::caminho( $token, 'itens.jsonl' );
        // Arquivo ausente
        if ( ! file_exists( $arq ) ) {
            wp_die( 'Nenhum achado registrado nesta análise.' );
        }

        // Catálogo para traduzir os tipos
        $catalogo = self::catalogo_problemas();

        // Cabeçalhos de download
        nocache_headers();
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="categorias-dependentes-' . gmdate( 'Y-m-d-His' ) . '.csv"' );

        // Abre a saída
        $saida = fopen( 'php://output', 'w' );
        // BOM para o Excel
        fwrite( $saida, "\xEF\xBB\xBF" );
        // Cabeçalho do CSV
        fputcsv( $saida, array(
            'user_id', 'matricula', 'colaborador', 'grupo_cadastrado', 'grupo_resolvido', 'grupo_id',
            'slot', 'dependente', 'idade', 'valor_atual', 'valor_corrigido', 'problema', 'detalhe', 'ja_enviou_desenho',
        ), ';' );

        // Percorre os achados
        $fp = fopen( $arq, 'r' );
        while ( false !== ( $linha = fgets( $fp ) ) ) {
            // Decodifica
            $i = json_decode( $linha, true );
            // Ignora inválidos
            if ( ! is_array( $i ) ) {
                continue;
            }
            // Rótulo do problema
            $rotulo = isset( $catalogo[ $i['tipo'] ]['rotulo'] )
                ? $catalogo[ $i['tipo'] ]['rotulo']
                : ( 'aviso_idade' === $i['tipo'] ? 'Categoria não bate com a idade' : $i['tipo'] );
            // Escreve a linha
            fputcsv( $saida, array(
                $i['user_id'], $i['login'], $i['colab'], $i['grupo_meta'], $i['grupo_nome'], $i['grupo_id'],
                $i['slot'], $i['nome'], $i['idade'], $i['atual'], $i['novo'], $rotulo, $i['motivo'],
                ! empty( $i['desenho'] ) ? 'Sim' : 'Não',
            ), ';' );
        }
        // Fecha os arquivos
        fclose( $fp );
        fclose( $saida );
        exit;
    }

    /* =====================================================================
     * INTERFACE ADMINISTRATIVA
     * ===================================================================== */

    /**
     * Renderiza a página da ferramenta
     */
    public static function render_admin_page() {
        // Bloqueia quem não tem permissão
        if ( ! self::pode_operar() ) {
            wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'explode' ) );
        }

        // Parâmetros da tela
        $etapa = isset( $_GET['etapa'] ) ? sanitize_key( $_GET['etapa'] ) : 'inicio';
        $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        $erro  = isset( $_GET['erro_corretor'] ) ? sanitize_text_field( wp_unslash( $_GET['erro_corretor'] ) ) : '';
        // URL base e nonce
        $base  = admin_url( 'admin.php?page=' . self::MENU_SLUG );
        $nonce = wp_create_nonce( self::NONCE );
        // Cabeçalho da análise, quando houver
        $cab = ( '' !== $token ) ? self::ler_cabecalho( $token ) : null;

        // Abre o container e imprime o estilo
        echo '<div class="wrap explode-cf-wrap">';
        self::imprimir_estilo();
        ?>
        <div class="ecf-hero">
            <div class="ecf-hero-left">
                <div class="ecf-hero-badge"><span class="dashicons dashicons-image-filter"></span> Módulo Administrativo Oficial</div>
                <h1 class="ecf-hero-title">Corretor de Categorias dos Dependentes</h1>
                <p class="ecf-hero-desc">
                    A categoria de cada dependente é gravada como
                    <strong>ID da categoria de idade</strong> + <strong>.</strong> + <strong>ID do grupo do colaborador</strong>.
                    A categoria muda de filho para filho, mas o grupo é sempre o mesmo do pai/mãe.
                    Esta ferramenta encontra quem está fora dessa regra e corrige em lote.
                </p>
            </div>
            <div class="ecf-hero-right">
                <?php if ( $cab ) : ?>
                    <a class="ecf-btn ecf-btn-ghost" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'acao' => 'relatorio', 'token' => $token ), $base ), self::NONCE ) ); ?>">
                        <span class="dashicons dashicons-media-spreadsheet"></span> Baixar relatório
                    </a>
                <?php endif; ?>
                <a class="ecf-btn ecf-btn-secondary" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
                    <span class="dashicons dashicons-admin-users"></span> Usuários do WordPress
                </a>
            </div>
        </div>
        <?php

        // Mensagem de erro vinda do redirecionamento
        if ( '' !== $erro ) {
            echo '<div class="ecf-alert ecf-alert-danger"><span class="dashicons dashicons-warning"></span><div>' . esc_html( $erro ) . '</div></div>';
        }

        // Análise informada mas inválida
        if ( '' !== $token && ! $cab ) {
            echo '<div class="ecf-alert ecf-alert-warning"><span class="dashicons dashicons-clock"></span><div><strong>Análise expirada.</strong> Faça uma nova varredura.</div></div>';
            $etapa = 'inicio';
        }

        // Roteia para a etapa correspondente
        if ( 'analisar' === $etapa && $cab ) {
            self::render_varredura( $cab, $base, $nonce );
        } elseif ( 'resultado' === $etapa && $cab ) {
            self::render_resultado( $cab, $base, $nonce );
        } else {
            self::render_inicio( $base, $nonce );
        }

        // Fecha o container
        echo '</div>';
    }

    /**
     * Tela 1: explicação da regra e início da varredura
     */
    private static function render_inicio( $base, $nonce ) {
        // Mapa de termos para a tabela de referência
        $termos = self::termos();
        // Catálogo de problemas
        $catalogo = self::catalogo_problemas();
        ?>
        <div class="ecf-grid-2">

            <!-- CARTAO DA REGRA -->
            <div class="ecf-card">
                <div class="ecf-card-head">
                    <div class="ecf-card-icon icon-navy"><span class="dashicons dashicons-book"></span></div>
                    <div>
                        <h2 class="ecf-card-title">A regra que será verificada</h2>
                        <p class="ecf-card-sub">Como o campo <code>user_field_dependente_N_cat</code> deve estar preenchido.</p>
                    </div>
                </div>
                <div class="ecf-card-body">
                    <div class="ecf-formula">
                        <span class="ecf-formula-parte ecf-formula-cat">ID da categoria</span>
                        <span class="ecf-formula-ponto">.</span>
                        <span class="ecf-formula-parte ecf-formula-grp">ID do grupo do colaborador</span>
                    </div>

                    <p class="ecf-help">
                        Um colaborador do <strong>Grupo 1</strong> (termo
                        <code><?php echo esc_html( isset( $termos['grupos']['grupo_1'] ) ? $termos['grupos']['grupo_1']['id'] : '32' ); ?></code>)
                        com três filhos em categorias diferentes fica assim:
                    </p>

                    <table class="ecf-table ecf-table-mini">
                        <thead><tr><th>Dependente</th><th>Categoria</th><th>Valor correto</th></tr></thead>
                        <tbody>
                        <?php
                        // ID do Grupo 1 para o exemplo
                        $ex_g = isset( $termos['grupos']['grupo_1'] ) ? $termos['grupos']['grupo_1']['id'] : 32;
                        // Percorre as categorias existentes montando o exemplo
                        $ex_i = 1;
                        foreach ( $termos['lista_cat'] as $cat ) :
                            if ( $ex_i > self::max_dep() ) { break; }
                            ?>
                            <tr>
                                <td>Dependente <?php echo esc_html( $ex_i ); ?></td>
                                <td><?php echo esc_html( $cat['nome'] ); ?> <code><?php echo esc_html( $cat['id'] ); ?></code></td>
                                <td><code class="ecf-code-ok"><?php echo esc_html( $cat['id'] . '.' . $ex_g ); ?></code></td>
                            </tr>
                            <?php $ex_i++;
                        endforeach; ?>
                        </tbody>
                    </table>

                    <div class="ecf-alert ecf-alert-info" style="margin-top:16px;">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            A <strong>parte da categoria muda livremente</strong> entre os filhos do mesmo colaborador —
                            isso não é erro. O que a ferramenta cobra é a <strong>parte do grupo</strong>,
                            que precisa ser sempre a do cadastro do colaborador.
                        </div>
                    </div>

                    <h3 class="ecf-sub-title">Por que isso quebra o site</h3>
                    <p class="ecf-help">
                        Em <code>template-filtro.php</code> o desenho só aparece se um dos termos dele bater com
                        <code>sanitize_title</code> do grupo do colaborador. Com o grupo errado na categoria, o desenho
                        é enviado para o grupo errado e some da votação do colaborador.
                    </p>
                </div>
            </div>

            <!-- CARTAO DE EXECUCAO -->
            <div class="ecf-card">
                <div class="ecf-card-head">
                    <div class="ecf-card-icon icon-teal"><span class="dashicons dashicons-search"></span></div>
                    <div>
                        <h2 class="ecf-card-title">Iniciar a verificação</h2>
                        <p class="ecf-card-sub">A varredura apenas lê o banco. Nada é alterado sem a sua confirmação.</p>
                    </div>
                </div>
                <form method="post" action="<?php echo esc_url( $base ); ?>">
                    <?php wp_nonce_field( self::NONCE ); ?>
                    <input type="hidden" name="explode_corretor_iniciar" value="1">
                    <div class="ecf-card-body">
                        <div class="ecf-checks">
                            <label class="ecf-check">
                                <input type="checkbox" name="checar_idade" checked>
                                <span><strong>Apontar quando a categoria não bater com a idade</strong> — informativo, usando as faixas do concurso (A: 4 a 6, B: 7 a 9, C: 10 a 11 anos).</span>
                            </label>
                            <label class="ecf-check">
                                <input type="checkbox" name="deduzir_idade" checked>
                                <span><strong>Sugerir a categoria pela idade quando ela estiver faltando</strong> — permite corrigir também os dependentes que hoje não têm categoria nenhuma.</span>
                            </label>
                        </div>

                        <h3 class="ecf-sub-title">O que será procurado</h3>
                        <ul class="ecf-lista-problemas">
                            <?php foreach ( $catalogo as $slug => $p ) : ?>
                                <li>
                                    <span class="ecf-tag ecf-tag-<?php echo esc_attr( $p['cor'] ); ?>"><?php echo esc_html( $p['rotulo'] ); ?></span>
                                    <span><?php echo esc_html( $p['descricao'] ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="ecf-card-foot">
                        <button type="submit" class="ecf-btn ecf-btn-primary">
                            <span class="dashicons dashicons-search"></span> Analisar todos os colaboradores
                        </button>
                        <span class="ecf-foot-note">Somente leitura nesta etapa.</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- REFERENCIA DE TERMOS -->
        <div class="ecf-card">
            <div class="ecf-card-head">
                <div class="ecf-card-icon icon-cyan"><span class="dashicons dashicons-category"></span></div>
                <div>
                    <h2 class="ecf-card-title">IDs cadastrados hoje na taxonomia</h2>
                    <p class="ecf-card-sub">A ferramenta usa exatamente estes termos. Se uma Categoria D for criada, ela passa a valer automaticamente.</p>
                </div>
            </div>
            <div class="ecf-card-body ecf-ref-grid">
                <div>
                    <h4 class="ecf-ref-title">Categorias de idade</h4>
                    <table class="ecf-table ecf-table-mini">
                        <thead><tr><th>Nome</th><th>ID</th><th>Slug</th></tr></thead>
                        <tbody>
                        <?php foreach ( $termos['lista_cat'] as $t ) : ?>
                            <tr><td><?php echo esc_html( $t['nome'] ); ?></td><td><code><?php echo esc_html( $t['id'] ); ?></code></td><td><?php echo esc_html( $t['slug'] ); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h4 class="ecf-ref-title">Grupos</h4>
                    <table class="ecf-table ecf-table-mini">
                        <thead><tr><th>Nome</th><th>ID</th><th>Slug</th></tr></thead>
                        <tbody>
                        <?php foreach ( $termos['lista_grp'] as $t ) : ?>
                            <tr><td><?php echo esc_html( $t['nome'] ); ?></td><td><code><?php echo esc_html( $t['id'] ); ?></code></td><td><?php echo esc_html( $t['slug'] ); ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tela 2: varredura em andamento
     */
    private static function render_varredura( $cab, $base, $nonce ) {
        // URL da tela de resultado
        $url_resultado = add_query_arg( array( 'etapa' => 'resultado', 'token' => $cab['token'] ), $base );
        ?>
        <div class="ecf-card">
            <div class="ecf-card-head">
                <div class="ecf-card-icon icon-teal"><span class="dashicons dashicons-update"></span></div>
                <div>
                    <h2 class="ecf-card-title">Analisando os colaboradores</h2>
                    <p class="ecf-card-sub">Somente leitura. Não feche esta página até a barra chegar em 100%.</p>
                </div>
            </div>
            <div class="ecf-card-body">
                <div class="ecf-bar"><div class="ecf-bar-fill" id="ecf-bar"></div></div>
                <div class="ecf-bar-info">
                    <span id="ecf-txt">Iniciando a varredura...</span>
                    <span id="ecf-pct">0%</span>
                </div>
                <div class="ecf-contadores">
                    <span class="ecf-cont"><em>Colaboradores lidos</em><strong id="ecf-lidos">0</strong></span>
                    <span class="ecf-cont"><em>De um total de</em><strong id="ecf-total"><?php echo esc_html( number_format_i18n( $cab['total_users'] ) ); ?></strong></span>
                    <span class="ecf-cont ecf-cont-alerta"><em>Problemas encontrados</em><strong id="ecf-achados">0</strong></span>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        (function($){
            // Dados da análise
            var token = <?php echo wp_json_encode( $cab['token'] ); ?>;
            var nonce = <?php echo wp_json_encode( $nonce ); ?>;
            var total = <?php echo (int) $cab['total_users']; ?>;
            var destino = <?php echo wp_json_encode( $url_resultado ); ?>;

            // Processa um lote e encadeia o próximo
            function lote(inicio){
                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: { action: 'explode_cat_analisar', nonce: nonce, token: token, inicio: inicio },
                    success: function(r){
                        // Erro devolvido pelo servidor
                        if (!r || !r.success) {
                            var m = (r && r.data && r.data.message) ? r.data.message : 'Erro desconhecido.';
                            $('#ecf-txt').text('Falha: ' + m);
                            return;
                        }
                        // Atualiza a interface
                        var pct = total > 0 ? Math.min(100, Math.round((r.data.processados / total) * 100)) : 100;
                        $('#ecf-bar').css('width', pct + '%');
                        $('#ecf-pct').text(pct + '%');
                        $('#ecf-lidos').text(r.data.processados);
                        $('#ecf-achados').text(r.data.achados);
                        $('#ecf-txt').text('Lendo colaboradores...');

                        // Continua ou encerra
                        if (r.data.fim) {
                            $('#ecf-bar').css('width', '100%');
                            $('#ecf-pct').text('100%');
                            $('#ecf-txt').text('Varredura concluída. Abrindo o resultado...');
                            window.location.href = destino;
                        } else {
                            lote(r.data.processados);
                        }
                    },
                    error: function(){
                        $('#ecf-txt').text('Falha de conexão. Tentando novamente em 3 segundos...');
                        setTimeout(function(){ lote(inicio); }, 3000);
                    }
                });
            }

            // Dispara a primeira requisição
            lote(<?php echo (int) $cab['processados']; ?>);
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Tela 3: resultado da análise, lista dos problemas e execução da correção
     */
    private static function render_resultado( $cab, $base, $nonce ) {
        // Catálogo de problemas
        $catalogo = self::catalogo_problemas();
        // Contadores
        $c = $cab['contadores'];
        // Filtro e página atuais
        $filtro = isset( $_GET['filtro'] ) ? sanitize_key( $_GET['filtro'] ) : '';
        $pagina = isset( $_GET['pg'] ) ? max( 1, (int) $_GET['pg'] ) : 1;
        // Valida o filtro contra o catálogo
        if ( '' !== $filtro && ! isset( $catalogo[ $filtro ] ) && 'aviso_idade' !== $filtro ) {
            $filtro = '';
        }
        // Quantidade de itens considerando o filtro
        $total_filtrado = ( '' === $filtro )
            ? (int) $c['total']
            : (int) ( isset( $c['tipos'][ $filtro ] ) ? $c['tipos'][ $filtro ] : 0 );
        // Total de páginas
        $total_paginas = max( 1, (int) ceil( $total_filtrado / self::POR_PAGINA ) );
        // Corrige a página quando passa do fim
        $pagina = min( $pagina, $total_paginas );
        // Itens da página
        $itens = self::ler_pagina( $cab['token'], $pagina, $filtro );
        // URL base preservando o token
        $url_tela = add_query_arg( array( 'etapa' => 'resultado', 'token' => $cab['token'] ), $base );
        ?>

        <!-- INDICADORES -->
        <div class="ecf-kpi-grid">
            <div class="ecf-kpi">
                <span class="ecf-kpi-label">Dependentes analisados</span>
                <strong class="ecf-kpi-val"><?php echo esc_html( number_format_i18n( (int) $c['ok'] + (int) $c['total'] ) ); ?></strong>
                <span class="ecf-kpi-sub">em <?php echo esc_html( number_format_i18n( $c['usuarios'] ) ); ?> colaboradores</span>
            </div>
            <div class="ecf-kpi">
                <span class="ecf-kpi-label">Dentro da regra</span>
                <strong class="ecf-kpi-val text-green"><?php echo esc_html( number_format_i18n( $c['ok'] ) ); ?></strong>
                <span class="ecf-kpi-sub">nada a fazer</span>
            </div>
            <div class="ecf-kpi">
                <span class="ecf-kpi-label">Fora da regra</span>
                <strong class="ecf-kpi-val <?php echo $c['total'] > 0 ? 'text-red' : 'text-green'; ?>"><?php echo esc_html( number_format_i18n( $c['total'] ) ); ?></strong>
                <span class="ecf-kpi-sub">precisam de atenção</span>
            </div>
            <div class="ecf-kpi">
                <span class="ecf-kpi-label">Corrigíveis automaticamente</span>
                <strong class="ecf-kpi-val text-blue"><?php echo esc_html( number_format_i18n( $c['corrigiveis'] ) ); ?></strong>
                <span class="ecf-kpi-sub">com valor novo já calculado</span>
            </div>
            <div class="ecf-kpi">
                <span class="ecf-kpi-label">Já enviaram desenho</span>
                <strong class="ecf-kpi-val <?php echo $c['com_desenho'] > 0 ? 'text-amber' : ''; ?>"><?php echo esc_html( number_format_i18n( $c['com_desenho'] ) ); ?></strong>
                <span class="ecf-kpi-sub">entre os problemas encontrados</span>
            </div>
        </div>

        <?php if ( 0 === (int) $c['total'] ) : ?>
            <div class="ecf-alert ecf-alert-sucesso">
                <span class="dashicons dashicons-yes-alt"></span>
                <div><strong>Nenhum problema encontrado.</strong> Todas as categorias de dependentes estão de acordo com o grupo dos colaboradores.</div>
            </div>
            <p><a class="ecf-btn ecf-btn-secondary" href="<?php echo esc_url( $base ); ?>"><span class="dashicons dashicons-update"></span> Nova varredura</a></p>
            <?php return; ?>
        <?php endif; ?>

        <!-- SELECAO DO QUE CORRIGIR -->
        <div class="ecf-card">
            <div class="ecf-card-head">
                <div class="ecf-card-icon icon-teal"><span class="dashicons dashicons-admin-tools"></span></div>
                <div>
                    <h2 class="ecf-card-title">O que corrigir</h2>
                    <p class="ecf-card-sub">Marque os tipos de problema que devem ser gravados. Clique no nome de um tipo para filtrar a lista abaixo.</p>
                </div>
            </div>
            <div class="ecf-card-body">
                <div class="ecf-tipos">
                    <?php foreach ( $catalogo as $slug => $p ) :
                        // Quantidade encontrada deste tipo
                        $qtd = isset( $c['tipos'][ $slug ] ) ? (int) $c['tipos'][ $slug ] : 0;
                        // Não exibe tipos sem ocorrência
                        if ( $qtd < 1 ) { continue; }
                        // Marca por padrão apenas os seguros
                        $marcado = ! empty( $p['corrigivel'] );
                        ?>
                        <div class="ecf-tipo <?php echo $filtro === $slug ? 'ativo' : ''; ?>">
                            <label class="ecf-tipo-check">
                                <input type="checkbox" class="ecf-tipo-input" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $marcado ); ?> <?php disabled( empty( $p['corrigivel'] ) && 'usuario_sem_grupo' === $slug ); ?>>
                                <span class="ecf-tipo-info">
                                    <span class="ecf-tipo-topo">
                                        <span class="ecf-tag ecf-tag-<?php echo esc_attr( $p['cor'] ); ?>"><?php echo esc_html( $p['rotulo'] ); ?></span>
                                        <strong class="ecf-tipo-qtd"><?php echo esc_html( number_format_i18n( $qtd ) ); ?></strong>
                                    </span>
                                    <span class="ecf-tipo-desc"><?php echo esc_html( $p['descricao'] ); ?></span>
                                </span>
                            </label>
                            <a class="ecf-tipo-filtro" href="<?php echo esc_url( add_query_arg( array( 'filtro' => $slug, 'pg' => 1 ), $url_tela ) ); ?>">ver os <?php echo esc_html( number_format_i18n( $qtd ) ); ?></a>
                        </div>
                    <?php endforeach; ?>
                    <?php
                    // Tipo informativo de divergência de idade
                    $qtd_aviso = isset( $c['tipos']['aviso_idade'] ) ? (int) $c['tipos']['aviso_idade'] : 0;
                    if ( $qtd_aviso > 0 ) : ?>
                        <div class="ecf-tipo <?php echo 'aviso_idade' === $filtro ? 'ativo' : ''; ?>">
                            <label class="ecf-tipo-check">
                                <input type="checkbox" class="ecf-tipo-input" value="aviso_idade">
                                <span class="ecf-tipo-info">
                                    <span class="ecf-tipo-topo">
                                        <span class="ecf-tag ecf-tag-amber">Categoria não bate com a idade</span>
                                        <strong class="ecf-tipo-qtd"><?php echo esc_html( number_format_i18n( $qtd_aviso ) ); ?></strong>
                                    </span>
                                    <span class="ecf-tipo-desc">O grupo está certo, mas a faixa etária indica outra categoria. Marque só se quiser reclassificar pela idade.</span>
                                </span>
                            </label>
                            <a class="ecf-tipo-filtro" href="<?php echo esc_url( add_query_arg( array( 'filtro' => 'aviso_idade', 'pg' => 1 ), $url_tela ) ); ?>">ver os <?php echo esc_html( number_format_i18n( $qtd_aviso ) ); ?></a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ecf-checks" style="margin-top:18px;">
                    <label class="ecf-check">
                        <input type="checkbox" id="ecf-preservar" checked>
                        <span><strong>Não alterar dependentes que já enviaram desenho</strong> — recomendado durante o concurso, para não mexer em quem já está com o desenho publicado (<?php echo esc_html( number_format_i18n( $c['com_desenho'] ) ); ?> caso(s)).</span>
                    </label>
                </div>

                <div class="ecf-exec-acoes">
                    <button type="button" id="ecf-corrigir" class="ecf-btn ecf-btn-primary">
                        <span class="dashicons dashicons-yes"></span> Corrigir os tipos marcados
                    </button>
                    <a class="ecf-btn ecf-btn-ghost-dark" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'acao' => 'relatorio', 'token' => $cab['token'] ), $base ), self::NONCE ) ); ?>">
                        <span class="dashicons dashicons-media-spreadsheet"></span> Baixar lista completa
                    </a>
                    <a class="ecf-btn ecf-btn-ghost-dark" href="<?php echo esc_url( $base ); ?>">
                        <span class="dashicons dashicons-update"></span> Nova varredura
                    </a>
                    <button type="button" id="ecf-desfazer" class="ecf-btn ecf-btn-perigo" <?php echo empty( $cab['tem_desfazer'] ) ? 'style="display:none;"' : ''; ?>>
                        <span class="dashicons dashicons-undo"></span> Desfazer as correções desta análise
                    </button>
                </div>

                <div id="ecf-progresso" style="display:none;">
                    <div class="ecf-bar"><div class="ecf-bar-fill" id="ecf-bar2"></div></div>
                    <div class="ecf-bar-info">
                        <span id="ecf-txt2">Iniciando...</span>
                        <span id="ecf-pct2">0%</span>
                    </div>
                    <div class="ecf-contadores">
                        <span class="ecf-cont"><em>Gravados</em><strong id="ecf-aplicados">0</strong></span>
                        <span class="ecf-cont"><em>Ignorados</em><strong id="ecf-ignorados">0</strong></span>
                        <span class="ecf-cont"><em>Preservados</em><strong id="ecf-preservados">0</strong></span>
                    </div>
                    <div class="ecf-log" id="ecf-log"></div>
                </div>

                <div id="ecf-final" class="ecf-alert ecf-alert-sucesso" style="display:none; margin-top:18px;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div>
                        <strong>Correção concluída.</strong> <span id="ecf-final-txt"></span>
                        <div style="margin-top:8px;">Faça uma nova varredura para confirmar que não sobrou nada fora da regra.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LISTA DOS PROBLEMAS -->
        <div class="ecf-card">
            <div class="ecf-card-head">
                <div class="ecf-card-icon icon-cyan"><span class="dashicons dashicons-list-view"></span></div>
                <div>
                    <h2 class="ecf-card-title">
                        <?php if ( '' !== $filtro ) : ?>
                            <?php echo esc_html( isset( $catalogo[ $filtro ]['rotulo'] ) ? $catalogo[ $filtro ]['rotulo'] : 'Categoria não bate com a idade' ); ?>
                        <?php else : ?>
                            Todos os dependentes fora da regra
                        <?php endif; ?>
                    </h2>
                    <p class="ecf-card-sub">
                        <?php echo esc_html( number_format_i18n( $total_filtrado ) ); ?> registro(s)
                        &middot; página <?php echo esc_html( $pagina ); ?> de <?php echo esc_html( $total_paginas ); ?>
                    </p>
                </div>
                <?php if ( '' !== $filtro ) : ?>
                    <a class="ecf-btn ecf-btn-ghost-dark" style="margin-left:auto;" href="<?php echo esc_url( $url_tela ); ?>">
                        <span class="dashicons dashicons-no-alt"></span> Limpar filtro
                    </a>
                <?php endif; ?>
            </div>
            <div class="ecf-card-body">
                <div class="ecf-table-scroll">
                    <table class="ecf-table ecf-table-lista">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Grupo do cadastro</th>
                                <th>Slot</th>
                                <th>Dependente</th>
                                <th>Idade</th>
                                <th>Está gravado</th>
                                <th>Fica assim</th>
                                <th>Problema</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $itens as $i ) :
                            // Dados do tipo
                            $p = isset( $catalogo[ $i['tipo'] ] ) ? $catalogo[ $i['tipo'] ] : array( 'rotulo' => 'Categoria não bate com a idade', 'cor' => 'amber' );
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (int) $i['user_id'] ) ); ?>" target="_blank"><strong><?php echo esc_html( $i['login'] ); ?></strong></a>
                                    <span class="ecf-sub"><?php echo esc_html( $i['colab'] ); ?></span>
                                </td>
                                <td>
                                    <?php if ( '' !== $i['grupo_nome'] ) : ?>
                                        <?php echo esc_html( $i['grupo_nome'] ); ?> <code><?php echo esc_html( $i['grupo_id'] ); ?></code>
                                    <?php else : ?>
                                        <span class="ecf-tag ecf-tag-red"><?php echo '' !== $i['grupo_meta'] ? esc_html( $i['grupo_meta'] ) : 'sem grupo'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="ecf-center"><strong><?php echo esc_html( $i['slot'] ); ?></strong></td>
                                <td>
                                    <?php echo '' !== $i['nome'] ? esc_html( $i['nome'] ) : '<em>slot vazio</em>'; ?>
                                    <?php if ( ! empty( $i['desenho'] ) ) : ?>
                                        <span class="ecf-tag ecf-tag-amber" title="Este dependente já enviou desenho">desenho enviado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ecf-center"><?php echo ( (int) $i['idade'] > 0 ) ? esc_html( $i['idade'] ) : '—'; ?></td>
                                <td><code class="ecf-code-erro"><?php echo '' !== $i['atual'] ? esc_html( $i['atual'] ) : '(vazio)'; ?></code></td>
                                <td>
                                    <?php if ( '' !== $i['novo'] ) : ?>
                                        <code class="ecf-code-ok"><?php echo esc_html( $i['novo'] ); ?></code>
                                        <?php if ( ! empty( $i['deduzida'] ) ) : ?>
                                            <span class="ecf-tag ecf-tag-cyan">pela idade</span>
                                        <?php endif; ?>
                                    <?php elseif ( 'residuo_slot_vazio' === $i['tipo'] ) : ?>
                                        <span class="ecf-tag ecf-tag-cyan">apagar o campo</span>
                                    <?php else : ?>
                                        <span class="ecf-tag ecf-tag-red">precisa de ajuste manual</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ecf-tag ecf-tag-<?php echo esc_attr( $p['cor'] ); ?>"><?php echo esc_html( $p['rotulo'] ); ?></span>
                                    <div class="ecf-motivo"><?php echo esc_html( $i['motivo'] ); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ( empty( $itens ) ) : ?>
                            <tr><td colspan="8">Nenhum registro nesta página.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ( $total_paginas > 1 ) : ?>
                    <div class="ecf-paginacao">
                        <?php
                        // Janela de páginas exibidas
                        $ini_pg = max( 1, $pagina - 3 );
                        $fim_pg = min( $total_paginas, $pagina + 3 );
                        // Link para a primeira página
                        if ( $ini_pg > 1 ) {
                            echo '<a href="' . esc_url( add_query_arg( array( 'filtro' => $filtro, 'pg' => 1 ), $url_tela ) ) . '">1</a><span class="ecf-pag-sep">…</span>';
                        }
                        // Páginas da janela
                        for ( $p_i = $ini_pg; $p_i <= $fim_pg; $p_i++ ) {
                            $classe = ( $p_i === $pagina ) ? ' class="ativo"' : '';
                            echo '<a' . $classe . ' href="' . esc_url( add_query_arg( array( 'filtro' => $filtro, 'pg' => $p_i ), $url_tela ) ) . '">' . esc_html( $p_i ) . '</a>';
                        }
                        // Link para a última página
                        if ( $fim_pg < $total_paginas ) {
                            echo '<span class="ecf-pag-sep">…</span><a href="' . esc_url( add_query_arg( array( 'filtro' => $filtro, 'pg' => $total_paginas ), $url_tela ) ) . '">' . esc_html( $total_paginas ) . '</a>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script type="text/javascript">
        (function($){
            // Dados da análise
            var token = <?php echo wp_json_encode( $cab['token'] ); ?>;
            var nonce = <?php echo wp_json_encode( $nonce ); ?>;
            var total = <?php echo (int) $c['total']; ?>;

            // Acumuladores da execução
            var acc = { aplicados:0, ignorados:0, preservados:0 };
            var modoAtual = 'aplicar';
            var totalModo = total;

            // Acrescenta uma linha ao log
            function logar(tipo, texto){
                var $l = $('#ecf-log');
                $l.append('<div class="ecf-log-item log-' + tipo + '">' + texto + '</div>');
                $l.scrollTop($l[0].scrollHeight);
            }

            // Atualiza a barra de progresso
            function progresso(feitos){
                var pct = totalModo > 0 ? Math.min(100, Math.round((feitos / totalModo) * 100)) : 100;
                $('#ecf-bar2').css('width', pct + '%');
                $('#ecf-pct2').text(pct + '%');
                $('#ecf-txt2').text((modoAtual === 'desfazer' ? 'Revertendo ' : 'Verificando ') + feitos + ' de ' + totalModo + '...');
                $('#ecf-aplicados').text(acc.aplicados);
                $('#ecf-ignorados').text(acc.ignorados);
                $('#ecf-preservados').text(acc.preservados);
            }

            // Processa um lote e encadeia o próximo
            function lote(inicio, tipos){
                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: {
                        action: 'explode_cat_corrigir',
                        nonce: nonce, token: token, inicio: inicio,
                        modo: modoAtual,
                        tipos: tipos,
                        preservar_desenho: $('#ecf-preservar').is(':checked') ? 'true' : 'false'
                    },
                    success: function(r){
                        // Erro devolvido pelo servidor
                        if (!r || !r.success) {
                            var m = (r && r.data && r.data.message) ? r.data.message : 'Erro desconhecido.';
                            logar('danger', 'Falha: ' + m);
                            $('#ecf-txt2').text('Processo interrompido.');
                            $('#ecf-corrigir, #ecf-desfazer').prop('disabled', false);
                            return;
                        }

                        // Soma os contadores
                        var d = r.data;
                        acc.aplicados   += d.resumo.aplicados;
                        acc.ignorados   += d.resumo.ignorados;
                        acc.preservados += d.resumo.preservados;
                        totalModo = d.total || totalModo;

                        // Escreve o log do lote
                        $.each(d.log, function(i, item){ logar(item.tipo, item.texto); });

                        // Atualiza a interface
                        progresso(d.processados);

                        // Continua ou encerra
                        if (d.fim) {
                            $('#ecf-bar2').css('width', '100%');
                            $('#ecf-pct2').text('100%');
                            $('#ecf-txt2').text('Concluído.');
                            $('#ecf-final-txt').text(
                                acc.aplicados + (modoAtual === 'desfazer' ? ' valor(es) restaurado(s).' : ' campo(s) corrigido(s), ')
                                + (modoAtual === 'desfazer' ? '' : acc.preservados + ' preservado(s) por já terem desenho enviado.')
                            );
                            $('#ecf-final').show();
                            if (modoAtual === 'aplicar' && acc.aplicados > 0) { $('#ecf-desfazer').show().prop('disabled', false); }
                        } else {
                            lote(d.processados, tipos);
                        }
                    },
                    error: function(){
                        logar('danger', 'Falha de conexão no lote ' + inicio + '. Tentando novamente em 3 segundos...');
                        setTimeout(function(){ lote(inicio, tipos); }, 3000);
                    }
                });
            }

            // Botão de correção
            $('#ecf-corrigir').on('click', function(){
                // Reúne os tipos marcados
                var tipos = [];
                $('.ecf-tipo-input:checked').each(function(){ tipos.push($(this).val()); });
                // Nenhum tipo marcado
                if (!tipos.length) { window.alert('Marque ao menos um tipo de problema para corrigir.'); return; }
                // Confirmação
                if (!window.confirm('Confirmar a correção dos tipos marcados? Um arquivo de desfazer será gravado antes de qualquer alteração.')) { return; }
                // Prepara a execução
                modoAtual = 'aplicar';
                totalModo = total;
                acc = { aplicados:0, ignorados:0, preservados:0 };
                $('#ecf-log').empty();
                $('#ecf-final').hide();
                $(this).prop('disabled', true);
                $('#ecf-progresso').show();
                progresso(0);
                lote(0, tipos);
            });

            // Botão de desfazer
            $('#ecf-desfazer').on('click', function(){
                // Confirmação
                if (!window.confirm('Restaurar todos os valores anteriores gravados por esta análise?')) { return; }
                // Prepara a execução
                modoAtual = 'desfazer';
                totalModo = 0;
                acc = { aplicados:0, ignorados:0, preservados:0 };
                $('#ecf-log').empty();
                $('#ecf-final').hide();
                $(this).prop('disabled', true);
                $('#ecf-progresso').show();
                progresso(0);
                lote(0, []);
            });

        })(jQuery);
        </script>
        <?php
    }

    /**
     * Imprime o estilo da página, alinhado à identidade visual do painel Explode
     */
    private static function imprimir_estilo() {
        ?>
        <style>
            .explode-cf-wrap {
                --exp-navy: #143240;
                --exp-teal: #5b9b99;
                --exp-cyan: #54c5cf;
                --exp-card: #ffffff;
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
            .explode-cf-wrap * { box-sizing: border-box; }
            .explode-cf-wrap code { background: #eef2f5; color: #0b3040; padding: 1px 6px; border-radius: 4px; font-size: 12px; font-weight: 600; }
            .explode-cf-wrap code.ecf-code-ok { background: #d1fae5; color: #047857; }
            .explode-cf-wrap code.ecf-code-erro { background: #ffe4e6; color: #be123c; }

            /* HERO */
            .ecf-hero {
                background: linear-gradient(135deg, var(--exp-navy) 0%, #1c4b61 50%, var(--exp-teal) 100%);
                border-radius: var(--exp-radius); padding: 30px 34px; color: #fff;
                display: flex; align-items: center; justify-content: space-between; gap: 24px;
                box-shadow: var(--exp-shadow); margin-bottom: 22px; position: relative; overflow: hidden;
            }
            .ecf-hero::after {
                content: ""; position: absolute; right: -60px; top: -60px; width: 260px; height: 260px;
                background: radial-gradient(circle, rgba(84,197,207,.2) 0%, rgba(255,255,255,0) 70%);
                border-radius: 50%; pointer-events: none;
            }
            .ecf-hero-left { max-width: 780px; }
            .ecf-hero-badge {
                display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.15);
                border: 1px solid rgba(255,255,255,.25); padding: 4px 12px; border-radius: 20px;
                font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;
            }
            .ecf-hero-title { color: #fff; font-size: 26px; line-height: 1.2; margin: 0 0 10px; font-weight: 700; }
            .ecf-hero-desc { color: rgba(255,255,255,.9); font-size: 14px; line-height: 1.65; margin: 0; }
            .ecf-hero-right { display: flex; flex-direction: column; gap: 10px; flex-shrink: 0; }

            /* BOTOES */
            .ecf-btn {
                display: inline-flex; align-items: center; justify-content: center; gap: 7px;
                padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600;
                text-decoration: none; border: 1px solid transparent; cursor: pointer;
                transition: all .18s ease; line-height: 1.2;
            }
            .ecf-btn .dashicons { font-size: 17px; width: 17px; height: 17px; }
            .ecf-btn-primary { background: linear-gradient(90deg, var(--exp-teal), var(--exp-cyan)); color: #fff !important; }
            .ecf-btn-primary:hover { filter: brightness(1.07); }
            .ecf-btn-primary:disabled { opacity: .55; cursor: not-allowed; }
            .ecf-btn-secondary { background: #fff; color: var(--exp-navy) !important; border-color: var(--exp-border); }
            .ecf-btn-secondary:hover { border-color: var(--exp-cyan); }
            .ecf-btn-ghost { background: rgba(255,255,255,.14); color: #fff !important; border-color: rgba(255,255,255,.3); }
            .ecf-btn-ghost:hover { background: rgba(255,255,255,.24); }
            .ecf-btn-ghost-dark { background: #fff; color: var(--exp-muted) !important; border-color: var(--exp-border); }
            .ecf-btn-ghost-dark:hover { color: var(--exp-navy) !important; }
            .ecf-btn-perigo { background: #fff; color: var(--exp-danger) !important; border-color: #fecdd3; }
            .ecf-btn-perigo:hover { background: #fff1f2; }

            /* ALERTAS */
            .ecf-alert {
                display: flex; gap: 12px; align-items: flex-start; padding: 14px 18px;
                border-radius: var(--exp-radius); margin-bottom: 18px; font-size: 13.5px; line-height: 1.6;
                border-left: 4px solid var(--exp-cyan); background: #eef8fa;
            }
            .ecf-alert .dashicons { flex-shrink: 0; margin-top: 1px; }
            .ecf-alert-info { background: #eef8fa; border-left-color: var(--exp-cyan); }
            .ecf-alert-warning { background: #fef3c7; border-left-color: var(--exp-warning); }
            .ecf-alert-danger { background: #ffe4e6; border-left-color: var(--exp-danger); }
            .ecf-alert-sucesso { background: #d1fae5; border-left-color: var(--exp-success); }

            /* CARTOES */
            .ecf-grid-2 { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 20px; margin-bottom: 20px; }
            @media (max-width: 1400px) { .ecf-grid-2 { grid-template-columns: 1fr; } }
            .ecf-card {
                background: var(--exp-card); border: 1px solid var(--exp-border); border-radius: var(--exp-radius);
                box-shadow: var(--exp-shadow); margin-bottom: 20px; overflow: hidden;
            }
            .ecf-card-head {
                display: flex; align-items: center; gap: 14px; padding: 18px 22px;
                border-bottom: 1px solid var(--exp-border); background: #fbfcfd; flex-wrap: wrap;
            }
            .ecf-card-icon {
                width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
                justify-content: center; color: #fff; flex-shrink: 0;
            }
            .ecf-card-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }
            .icon-teal { background: linear-gradient(135deg, var(--exp-teal), var(--exp-cyan)); }
            .icon-navy { background: linear-gradient(135deg, var(--exp-navy), #1c4b61); }
            .icon-cyan { background: linear-gradient(135deg, var(--exp-cyan), #7fd9e2); }
            .ecf-card-title { margin: 0; font-size: 16px; font-weight: 700; color: var(--exp-navy); }
            .ecf-card-sub { margin: 3px 0 0; font-size: 12.5px; color: var(--exp-muted); }
            .ecf-card-body { padding: 22px; }
            .ecf-card-foot {
                padding: 16px 22px; border-top: 1px solid var(--exp-border); background: #fbfcfd;
                display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
            }
            .ecf-foot-note { font-size: 12px; color: var(--exp-muted); }
            .ecf-sub-title { font-size: 14px; color: var(--exp-navy); margin: 22px 0 6px; }
            .ecf-help { font-size: 12.5px; color: var(--exp-muted); margin: 6px 0 0; line-height: 1.65; }

            /* FORMULA VISUAL */
            .ecf-formula {
                display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap;
                background: #f4f7f9; border: 1px dashed var(--exp-border); border-radius: 10px; padding: 18px; margin-bottom: 16px;
            }
            .ecf-formula-parte { padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; color: #fff; }
            .ecf-formula-cat { background: linear-gradient(90deg, #10b981, #34d399); }
            .ecf-formula-grp { background: linear-gradient(90deg, var(--exp-navy), var(--exp-teal)); }
            .ecf-formula-ponto { font-size: 26px; font-weight: 700; color: var(--exp-navy); line-height: 1; }

            /* CHECKS */
            .ecf-checks { border-top: 1px dashed var(--exp-border); padding-top: 16px; }
            .ecf-check {
                display: flex; gap: 9px; align-items: flex-start; font-size: 12.5px; line-height: 1.6;
                margin-bottom: 11px; cursor: pointer;
            }
            .ecf-check input { margin: 2px 0 0; flex-shrink: 0; }

            /* LISTA DE PROBLEMAS NA TELA INICIAL */
            .ecf-lista-problemas { margin: 0; padding: 0; list-style: none; }
            .ecf-lista-problemas li {
                display: flex; gap: 10px; align-items: flex-start; padding: 8px 0;
                border-bottom: 1px solid #f0f2f4; font-size: 12.5px; line-height: 1.55; color: var(--exp-muted);
            }
            .ecf-lista-problemas li .ecf-tag { flex-shrink: 0; }

            /* KPIS */
            .ecf-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
            .ecf-kpi {
                background: #fff; border: 1px solid var(--exp-border); border-radius: var(--exp-radius);
                padding: 16px 18px; box-shadow: var(--exp-shadow); display: flex; flex-direction: column; gap: 3px;
            }
            .ecf-kpi-label { font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--exp-muted); font-weight: 600; }
            .ecf-kpi-val { font-size: 26px; font-weight: 700; color: var(--exp-navy); line-height: 1.15; }
            .ecf-kpi-sub { font-size: 11.5px; color: var(--exp-muted); }
            .text-green { color: var(--exp-success) !important; }
            .text-blue { color: #2563eb !important; }
            .text-red { color: var(--exp-danger) !important; }
            .text-amber { color: #b45309 !important; }

            /* SELECAO DE TIPOS */
            .ecf-tipos { display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 12px; }
            .ecf-tipo {
                border: 1px solid var(--exp-border); border-radius: 10px; padding: 12px 14px; background: #fbfcfd;
                display: flex; flex-direction: column; gap: 8px; transition: all .18s ease;
            }
            .ecf-tipo.ativo { border-color: var(--exp-cyan); background: #eef8fa; }
            .ecf-tipo-check { display: flex; gap: 10px; align-items: flex-start; cursor: pointer; }
            .ecf-tipo-check input { margin: 3px 0 0; flex-shrink: 0; }
            .ecf-tipo-info { display: flex; flex-direction: column; gap: 4px; }
            .ecf-tipo-topo { display: flex; align-items: center; gap: 8px; }
            .ecf-tipo-qtd { font-size: 17px; color: var(--exp-navy); }
            .ecf-tipo-desc { font-size: 11.5px; color: var(--exp-muted); line-height: 1.5; }
            .ecf-tipo-filtro { font-size: 11.5px; font-weight: 600; align-self: flex-start; }

            /* TABELAS */
            .ecf-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
            .ecf-table th {
                text-align: left; padding: 9px 10px; background: #f4f7f9; color: var(--exp-navy);
                font-weight: 700; border-bottom: 1px solid var(--exp-border); font-size: 11.5px;
                text-transform: uppercase; letter-spacing: .3px;
            }
            .ecf-table td { padding: 9px 10px; border-bottom: 1px solid #f0f2f4; vertical-align: top; line-height: 1.5; }
            .ecf-table tbody tr:hover { background: #fafcfd; }
            .ecf-table-mini th, .ecf-table-mini td { padding: 6px 8px; font-size: 12px; }
            .ecf-table-scroll { overflow-x: auto; border: 1px solid var(--exp-border); border-radius: 8px; }
            .ecf-center { text-align: center; }
            .ecf-sub { display: block; font-size: 11.5px; color: var(--exp-muted); }
            .ecf-motivo { font-size: 11px; color: var(--exp-muted); margin-top: 4px; line-height: 1.45; max-width: 340px; }
            .ecf-ref-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
            @media (max-width: 782px) { .ecf-ref-grid { grid-template-columns: 1fr; } }
            .ecf-ref-title { font-size: 12px; text-transform: uppercase; letter-spacing: .4px; color: var(--exp-muted); margin: 0 0 8px; }

            /* ETIQUETAS */
            .ecf-tag {
                display: inline-block; padding: 1px 8px; border-radius: 20px; font-size: 10.5px;
                font-weight: 700; background: #eef2f5; color: var(--exp-muted);
                text-transform: uppercase; letter-spacing: .3px;
            }
            .ecf-tag-red { background: #ffe4e6; color: #be123c; }
            .ecf-tag-green { background: #d1fae5; color: #047857; }
            .ecf-tag-cyan { background: #cffafe; color: #0e7490; }
            .ecf-tag-amber { background: #fef3c7; color: #b45309; }

            /* PAGINACAO */
            .ecf-paginacao { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 16px; align-items: center; }
            .ecf-paginacao a {
                display: inline-block; min-width: 32px; text-align: center; padding: 6px 9px;
                border: 1px solid var(--exp-border); border-radius: 7px; background: #fff;
                font-size: 12px; font-weight: 600; color: var(--exp-muted); text-decoration: none;
            }
            .ecf-paginacao a:hover { border-color: var(--exp-cyan); color: var(--exp-navy); }
            .ecf-paginacao a.ativo { background: var(--exp-navy); border-color: var(--exp-navy); color: #fff; }
            .ecf-pag-sep { color: var(--exp-muted); padding: 0 2px; }

            /* PROGRESSO */
            .ecf-exec-acoes { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
            .ecf-bar { height: 12px; background: #eef2f5; border-radius: 20px; overflow: hidden; margin: 20px 0 8px; }
            .ecf-bar-fill {
                height: 100%; width: 0; border-radius: 20px; transition: width .3s ease;
                background: linear-gradient(90deg, var(--exp-teal), var(--exp-cyan));
            }
            .ecf-bar-info { display: flex; justify-content: space-between; font-size: 12.5px; color: var(--exp-muted); font-weight: 600; }
            .ecf-contadores { display: flex; gap: 10px; flex-wrap: wrap; margin: 16px 0; }
            .ecf-cont {
                display: flex; flex-direction: column; gap: 2px; padding: 9px 16px; border-radius: 8px;
                background: #f4f7f9; border: 1px solid var(--exp-border); min-width: 120px;
            }
            .ecf-cont-alerta { background: #fff7ed; border-color: #fed7aa; }
            .ecf-cont em { font-style: normal; font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--exp-muted); font-weight: 600; }
            .ecf-cont strong { font-size: 19px; color: var(--exp-navy); }
            .ecf-log {
                max-height: 320px; overflow-y: auto; background: #0b1d26; border-radius: 8px; padding: 14px;
                font-family: Consolas, Monaco, "Courier New", monospace; font-size: 11.5px; line-height: 1.7; color: #cbd5e1;
            }
            .ecf-log-item { padding: 1px 0; border-bottom: 1px solid rgba(255,255,255,.05); }
            .ecf-log-item.log-success { color: #6ee7b7; }
            .ecf-log-item.log-warning { color: #fcd34d; }
            .ecf-log-item.log-danger { color: #fda4af; }
        </style>
        <?php
    }
}

// Inicializa a classe
Explode_Admin_Cat_Fixer::init();
