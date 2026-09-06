<?php
/**
 * Módulo de Importação Inteligente de Usuários via CSV
 * Tema: Explode Criação
 *
 * Funcionalidades:
 * - Download de um modelo CSV de exemplo já preenchido para orientar o administrador
 * - Leitura tolerante de CSV (delimitador , ; ou TAB, acentuação UTF-8 / ISO-8859-1, BOM)
 * - Mapeamento flexível de colunas (aceita variações de nome, acento e caixa)
 * - CONSOLIDAÇÃO INTELIGENTE: quando a mesma matrícula aparece em várias linhas,
 *   o 1º dependente encontrado vai para o slot 1, o 2º para o slot 2 e o 3º para o slot 3,
 *   levando junto o nome, a idade e a categoria correspondentes, sem trocar colunas
 * - Categoria gravada como concatenação "<ID da categoria>.<ID do grupo>" (taxonomia desenhos_cat)
 * - Tela de conferência (simulação) antes de qualquer gravação no banco
 * - Execução em lote via AJAX com barra de progresso e log em tempo real
 * - Relatório final em CSV com o resultado de cada matrícula
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança
}

class Explode_Admin_User_Importer {

    /** Slug da página administrativa */
    const MENU_SLUG = 'explode-importar-usuarios';

    /** Nome do nonce utilizado nos formulários e chamadas AJAX */
    const NONCE = 'explode_importador_nonce';

    /**
     * Quantidade máxima de dependentes suportada pelo tema
     *
     * O valor vem de explode_max_dependentes(), em functions.php, que é o único
     * lugar onde esse limite é definido. Antes era uma constante fixa em 4 aqui
     * dentro, o que fazia o importador ignorar dependentes que o resto do tema
     * já aceitava.
     */
    public static function max_dep() {
        // Delega ao valor central do tema, com uma reserva segura
        return function_exists( 'explode_max_dependentes' ) ? explode_max_dependentes() : 6;
    }

    /** Domínio padrão usado para gerar e-mails fictícios dos colaboradores */
    const DOMINIO_PADRAO = 'explodecriacao.com.br';

    /** Tamanho máximo aceito para o arquivo CSV (20 MB) */
    const MAX_UPLOAD = 20971520;

    /** Cache interno dos termos da taxonomia desenhos_cat */
    private static $cache_termos = null;

    /**
     * Inicialização dos hooks
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_requests' ) );
        add_action( 'wp_ajax_explode_importar_lote', array( __CLASS__, 'ajax_importar_lote' ) );
    }

    /**
     * Registra o menu no WP-Admin
     */
    public static function register_admin_menu() {
        // Menu de nível superior
        add_menu_page(
            'Importação Inteligente de Usuários',
            'Importar Usuários',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_admin_page' ),
            'dashicons-upload',
            70
        );

        // Também acessível dentro do menu Usuários
        add_submenu_page(
            'users.php',
            'Importação Inteligente de Usuários',
            'Importar via CSV',
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_admin_page' )
        );
    }

    /**
     * Verifica se o usuário atual pode operar o importador
     */
    private static function pode_operar() {
        return current_user_can( 'manage_options' ) && current_user_can( 'create_users' );
    }

    /* =====================================================================
     * HELPERS DE NORMALIZACAO
     * ===================================================================== */

    /**
     * Converte qualquer texto em uma chave comparável: minúsculo, sem acento e sem pontuação
     */
    public static function chave( $texto ) {
        // Remove acentuação utilizando a função nativa do WordPress
        $t = remove_accents( (string) $texto );
        // Converte para minúsculas
        $t = strtolower( $t );
        // Troca qualquer caractere que não seja letra ou número por underline
        $t = preg_replace( '/[^a-z0-9]+/', '_', $t );
        // Remove underlines das pontas
        return trim( (string) $t, '_' );
    }

    /**
     * Limpa um valor de célula do CSV (espaços, espaços duplos e caracteres invisíveis)
     */
    public static function limpar( $valor ) {
        // Garante string
        $v = (string) $valor;
        // Remove BOM residual e espaços não separáveis
        $v = str_replace( array( "\xEF\xBB\xBF", "\xC2\xA0" ), array( '', ' ' ), $v );
        // Colapsa espaços em branco repetidos
        $v = preg_replace( '/\s+/u', ' ', $v );
        // Remove espaços das pontas
        return trim( (string) $v );
    }

    /**
     * Normaliza a matrícula para servir de chave de agrupamento
     */
    public static function chave_matricula( $valor ) {
        // Limpa o valor
        $v = self::limpar( $valor );
        // Remove espaços internos, pontos e traços usados por algumas planilhas
        $v = str_replace( array( ' ', '.', '-' ), '', $v );
        // Compara sempre em maiúsculas para matrículas alfanuméricas
        return strtoupper( $v );
    }

    /**
     * Normaliza o nome de um dependente para comparação (evita duplicar o mesmo dependente)
     */
    public static function chave_nome( $valor ) {
        // Reaproveita a normalização genérica de chaves
        return self::chave( self::limpar( $valor ) );
    }

    /**
     * Formata um nome próprio para gravação (Primeira Letra Maiúscula)
     */
    public static function formatar_nome( $valor ) {
        // Limpa o valor recebido
        $v = self::limpar( $valor );
        // Se estiver vazio devolve vazio
        if ( '' === $v ) {
            return '';
        }
        // Aplica capitalização respeitando acentuação multibyte
        if ( function_exists( 'mb_convert_case' ) ) {
            return mb_convert_case( mb_strtolower( $v, 'UTF-8' ), MB_CASE_TITLE, 'UTF-8' );
        }
        // Alternativa sem mbstring
        return ucwords( strtolower( $v ) );
    }

    /* =====================================================================
     * DIRETORIO PROTEGIDO DE TRABALHO
     * ===================================================================== */

    /**
     * Retorna (criando se necessário) o diretório protegido onde ficam os arquivos temporários
     */
    private static function dir_trabalho() {
        // Obtém a pasta de uploads do WordPress
        $up = wp_upload_dir();
        // Define o caminho da pasta do importador
        $dir = trailingslashit( $up['basedir'] ) . 'explode-importador';

        // Cria a pasta se ainda não existir
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        // Bloqueia acesso direto via navegador
        if ( ! file_exists( $dir . '/.htaccess' ) ) {
            $regras  = "Order Deny,Allow" . PHP_EOL . "Deny from all" . PHP_EOL;
            $regras .= "<IfModule mod_authz_core.c>" . PHP_EOL . "Require all denied" . PHP_EOL . "</IfModule>" . PHP_EOL;
            @file_put_contents( $dir . '/.htaccess', $regras );
        }

        // Impede a listagem de diretório
        if ( ! file_exists( $dir . '/index.php' ) ) {
            @file_put_contents( $dir . '/index.php', '<?php // Silence is golden.' );
        }

        // Devolve o caminho pronto
        return $dir;
    }

    /**
     * Monta o caminho completo de um arquivo do plano a partir do seu token
     */
    private static function caminho_plano( $token, $sufixo ) {
        // Valida o token para impedir travessia de diretório
        if ( ! preg_match( '/^[a-zA-Z0-9]{16,64}$/', (string) $token ) ) {
            return '';
        }
        // Devolve o caminho concatenado
        return trailingslashit( self::dir_trabalho() ) . 'plano-' . $token . '-' . $sufixo;
    }

    /**
     * Remove arquivos de planos antigos (mais de 24 horas) para não acumular lixo
     */
    private static function limpar_planos_antigos() {
        // Localiza todos os arquivos de plano
        $arquivos = glob( trailingslashit( self::dir_trabalho() ) . 'plano-*' );
        // Se não houver nada encerra
        if ( ! is_array( $arquivos ) ) {
            return;
        }
        // Limite de idade em segundos
        $limite = time() - DAY_IN_SECONDS;
        // Percorre removendo os vencidos
        foreach ( $arquivos as $arq ) {
            if ( is_file( $arq ) && filemtime( $arq ) < $limite ) {
                @unlink( $arq );
            }
        }
    }

    /* =====================================================================
     * TERMOS: CATEGORIAS E GRUPOS (taxonomia desenhos_cat)
     * ===================================================================== */

    /**
     * Carrega e organiza os termos da taxonomia desenhos_cat separando categorias e grupos
     */
    public static function carregar_termos( $forcar = false ) {
        // Reaproveita o cache quando disponível
        if ( null !== self::$cache_termos && ! $forcar ) {
            return self::$cache_termos;
        }

        // Estrutura de retorno
        $mapa = array(
            'categorias' => array(), // chave => array( id, nome, slug, tipo )
            'grupos'     => array(), // chave => array( id, nome, slug, tipo )
            'por_id'     => array(), // id    => array( id, nome, slug, tipo )
            'lista_cat'  => array(), // lista limpa para exibição
            'lista_grp'  => array(), // lista limpa para exibição
            'lista_pcd'  => array(), // termo PCD (marcação, não é faixa etária)
        );

        // Busca todos os termos da taxonomia, inclusive os sem posts
        $termos = get_terms( array(
            'taxonomy'   => 'desenhos_cat',
            'hide_empty' => false,
        ) );

        // Se houve erro devolve o mapa vazio
        if ( is_wp_error( $termos ) ) {
            self::$cache_termos = $mapa;
            return $mapa;
        }

        // Percorre cada termo classificando entre categoria e grupo
        foreach ( $termos as $termo ) {
            // Dados básicos do termo
            $item = array(
                'id'   => (int) $termo->term_id,
                'nome' => $termo->name,
                'slug' => $termo->slug,
            );

            // Chave normalizada do nome e do slug
            $k_nome = self::chave( $termo->name );
            $k_slug = self::chave( $termo->slug );

            // Termos cujo nome comeca com "grupo" sao tratados como grupos
            if ( 0 === strpos( $k_nome, 'grupo' ) || 0 === strpos( $k_slug, 'grupo' ) ) {
                // Marca o tipo
                $item['tipo'] = 'grupo';
                // Registra pelas duas chaves
                $mapa['grupos'][ $k_nome ] = $item;
                $mapa['grupos'][ $k_slug ] = $item;
                // Aceita informar apenas o numero quando o nome e "Grupo N"
                if ( preg_match( '/^grupo_?(\d+)$/', $k_nome, $m ) ) {
                    $mapa['grupos'][ $m[1] ] = $item;
                }
                // Guarda na lista de exibicao
                $mapa['lista_grp'][ $item['id'] ] = $item;
            } elseif ( 'pcd' === $k_nome || 'pcd' === $k_slug ) {
                // O termo PCD e uma marcacao, nao uma categoria de faixa etaria
                $item['tipo'] = 'pcd';
                // Guarda na lista propria
                $mapa['lista_pcd'][ $item['id'] ] = $item;
            } else {
                // Marca o tipo
                $item['tipo'] = 'categoria';
                // Registra pelas duas chaves
                $mapa['categorias'][ $k_nome ] = $item;
                $mapa['categorias'][ $k_slug ] = $item;
                // Aceita a forma curta "a", "b", "c" quando o nome e "Categoria X"
                if ( preg_match( '/^categoria_?([a-z0-9]+)$/', $k_nome, $m ) ) {
                    $mapa['categorias'][ $m[1] ] = $item;
                }
                // Guarda na lista de exibicao
                $mapa['lista_cat'][ $item['id'] ] = $item;
            }

            // Índice por ID para consulta direta
            $mapa['por_id'][ $item['id'] ] = $item;
        }

        // Guarda no cache e devolve
        self::$cache_termos = $mapa;
        return $mapa;
    }

    /**
     * Resolve o termo do GRUPO a partir do valor bruto vindo do CSV
     */
    public static function resolver_grupo( $bruto, $criar_faltantes = false ) {
        // Limpa o valor
        $valor = self::limpar( $bruto );
        // Sem valor não há o que resolver
        if ( '' === $valor ) {
            return null;
        }

        // Carrega o mapa de termos
        $mapa = self::carregar_termos();
        // Gera a chave de busca
        $k = self::chave( $valor );

        // Busca direta pela chave
        if ( isset( $mapa['grupos'][ $k ] ) ) {
            return $mapa['grupos'][ $k ];
        }

        // Aceita o ID numérico do termo informado diretamente
        if ( ctype_digit( $valor ) && isset( $mapa['por_id'][ (int) $valor ] ) && 'grupo' === $mapa['por_id'][ (int) $valor ]['tipo'] ) {
            return $mapa['por_id'][ (int) $valor ];
        }

        // Tenta a forma "grupo N" quando o CSV traz apenas o número
        if ( preg_match( '/^(\d+)$/', $k, $m ) && isset( $mapa['grupos'][ 'grupo_' . $m[1] ] ) ) {
            return $mapa['grupos'][ 'grupo_' . $m[1] ];
        }

        // Cria o termo quando o administrador autorizou
        if ( $criar_faltantes ) {
            // Padroniza o nome como "Grupo N" quando aplicável
            $nome_novo = self::formatar_nome( $valor );
            // Ajusta a caixa da palavra Grupo
            if ( preg_match( '/^grupo_?(.+)$/', $k, $mm ) ) {
                $nome_novo = 'Grupo ' . strtoupper( str_replace( '_', ' ', $mm[1] ) );
            }
            // Insere o termo na taxonomia
            $novo = wp_insert_term( $nome_novo, 'desenhos_cat' );
            // Se criou com sucesso atualiza o cache e devolve
            if ( ! is_wp_error( $novo ) ) {
                self::carregar_termos( true );
                return array(
                    'id'   => (int) $novo['term_id'],
                    'nome' => $nome_novo,
                    'slug' => sanitize_title( $nome_novo ),
                    'tipo' => 'grupo',
                );
            }
        }

        // Não foi possível resolver
        return null;
    }

    /**
     * Resolve o termo da CATEGORIA a partir do valor bruto vindo do CSV
     */
    public static function resolver_categoria( $bruto, $criar_faltantes = false ) {
        // Limpa o valor
        $valor = self::limpar( $bruto );
        // Sem valor não há o que resolver
        if ( '' === $valor ) {
            return null;
        }

        // Carrega o mapa de termos
        $mapa = self::carregar_termos();
        // Gera a chave de busca
        $k = self::chave( $valor );

        // Busca direta pela chave (aceita "A", "Categoria A", "categoria-a")
        if ( isset( $mapa['categorias'][ $k ] ) ) {
            return $mapa['categorias'][ $k ];
        }

        // Aceita o ID numérico do termo informado diretamente
        if ( ctype_digit( $valor ) && isset( $mapa['por_id'][ (int) $valor ] ) && 'categoria' === $mapa['por_id'][ (int) $valor ]['tipo'] ) {
            return $mapa['por_id'][ (int) $valor ];
        }

        // Tenta a forma "categoria X" quando o CSV traz só a letra
        if ( isset( $mapa['categorias'][ 'categoria_' . $k ] ) ) {
            return $mapa['categorias'][ 'categoria_' . $k ];
        }

        // Cria o termo quando o administrador autorizou
        if ( $criar_faltantes ) {
            // Padroniza o nome como "Categoria X" quando o CSV traz só a letra
            $nome_novo = ( 1 === strlen( $k ) ) ? 'Categoria ' . strtoupper( $k ) : self::formatar_nome( $valor );
            // Insere o termo na taxonomia
            $novo = wp_insert_term( $nome_novo, 'desenhos_cat' );
            // Se criou com sucesso atualiza o cache e devolve
            if ( ! is_wp_error( $novo ) ) {
                self::carregar_termos( true );
                return array(
                    'id'   => (int) $novo['term_id'],
                    'nome' => $nome_novo,
                    'slug' => sanitize_title( $nome_novo ),
                    'tipo' => 'categoria',
                );
            }
        }

        // Não foi possível resolver
        return null;
    }

    /**
     * Deduz a categoria a partir da idade. 
     * Regra MAO: A=0-3, B=4-6, C=7-9, D=10-11
     * Regra Padrão: A=4-6, B=7-9, C=10-11
     */
    public static function categoria_por_idade( $idade, $grupo_nome = '' ) {
        // Converte para inteiro
        $i = (int) $idade;
        
        // Verifica se o grupo contém "MAO"
        $is_mao = false;
        if ( stripos( $grupo_nome, 'MAO' ) !== false ) {
            $is_mao = true;
        }

        if ( $is_mao ) {
            // Regra Grupos MAO
            if ( $i >= 0 && $i <= 3 ) {
                return 'Categoria A';
            }
            if ( $i >= 4 && $i <= 6 ) {
                return 'Categoria B';
            }
            if ( $i >= 7 && $i <= 9 ) {
                return 'Categoria C';
            }
            if ( $i >= 10 && $i <= 11 ) {
                return 'Categoria D';
            }
        } else {
            // Regra Padrão (Sem MAO)
            if ( $i >= 4 && $i <= 6 ) {
                return 'Categoria A';
            }
            if ( $i >= 7 && $i <= 9 ) {
                return 'Categoria B';
            }
            if ( $i >= 10 && $i <= 11 ) {
                return 'Categoria C';
            }
        }

        // Fora das faixas previstas
        return '';
    }

    /**
     * Resolve o termo da UNIDADE na taxonomia user_unidade devolvendo o ID
     */
    public static function resolver_unidade( $bruto, $criar_faltantes = false ) {
        // Limpa o valor
        $valor = self::limpar( $bruto );
        // Sem valor não há o que resolver
        if ( '' === $valor ) {
            return null;
        }

        // Aceita o ID numérico direto
        if ( ctype_digit( $valor ) ) {
            $t = get_term( (int) $valor, 'user_unidade' );
            if ( $t && ! is_wp_error( $t ) ) {
                return array( 'id' => (int) $t->term_id, 'nome' => $t->name );
            }
        }

        // Busca pelo nome exato
        $t = get_term_by( 'name', $valor, 'user_unidade' );
        // Busca pelo slug quando o nome falhar
        if ( ! $t ) {
            $t = get_term_by( 'slug', sanitize_title( $valor ), 'user_unidade' );
        }

        // Devolve o termo encontrado
        if ( $t && ! is_wp_error( $t ) ) {
            return array( 'id' => (int) $t->term_id, 'nome' => $t->name );
        }

        // Cria a unidade quando autorizado
        if ( $criar_faltantes ) {
            $novo = wp_insert_term( $valor, 'user_unidade' );
            if ( ! is_wp_error( $novo ) ) {
                return array( 'id' => (int) $novo['term_id'], 'nome' => $valor );
            }
        }

        // Não foi possível resolver
        return null;
    }

    /* =====================================================================
     * LEITURA E MAPEAMENTO DO CSV
     * ===================================================================== */

    /**
     * Tabela de sinônimos aceitos para as colunas do colaborador
     */
    private static function sinonimos_colunas() {
        return array(
            // Identificador do colaborador (obrigatório)
            'matricula' => array(
                'matricula', 'matriculas', 'matricula_colaborador', 'matricula_funcionario',
                'num_matricula', 'numero_matricula', 'registro', 'chapa', 'cracha',
                'codigo', 'cod', 'cod_colaborador', 'codigo_colaborador', 'id_colaborador',
                'login', 'usuario', 'user_login', 'user',
            ),
            // Nome do colaborador (obrigatório)
            'nome' => array(
                'nome', 'nome_colaborador', 'nome_do_colaborador', 'colaborador',
                'nome_completo', 'nome_funcionario', 'nome_do_funcionario', 'funcionario',
                'empregado', 'nome_empregado', 'nome_do_empregado', 'display_name',
            ),
            // E-mail (opcional)
            'email' => array( 'email', 'e_mail', 'email_colaborador', 'email_funcionario', 'user_email' ),
            // Senha (opcional)
            'senha' => array( 'senha', 'password', 'pass', 'user_pass' ),
            // Grupo do funcionário (obrigatório para montar a categoria)
            'grupo' => array(
                'grupo', 'grupos', 'grupo_funcionario', 'grupo_do_funcionario',
                'grupo_de_funcionario', 'grupo_colaborador', 'grupo_do_colaborador', 'group',
            ),
            // Unidade (opcional)
            'unidade' => array( 'unidade', 'unidades', 'unidade_colaborador', 'planta', 'filial', 'local', 'localidade' ),
            // Home office (opcional)
            'homeoffice' => array( 'homeoffice', 'home_office' ),
            // Comissão (opcional)
            'comissao' => array( 'comissao', 'comite' ),
        );
    }

    /**
     * Termos usados no reconhecimento APROXIMADO das colunas do colaborador
     *
     * A lista de sinônimos acima exige o nome exato. Isso fazia uma planilha com
     * "Senha inicial", "Senha de acesso" ou "Nome do empregado 2026" perder a coluna
     * em silêncio: o campo ficava vazio e a senha caía na matrícula sem nenhum aviso.
     *
     * Aqui basta que a chave normalizada CONTENHA um dos termos. A ordem importa:
     * o primeiro campo que casar leva a coluna. As colunas de dependente não passam
     * por aqui, porque são reconhecidas antes, por detectar_coluna_dependente().
     */
    private static function termos_colunas() {
        return array(
            // Identificador: precisa vir primeiro para não ser roubado por "nome"
            'matricula'  => array( 'matricula', 'chapa', 'cracha', 'registro_funcionario', 'id_colaborador', 'user_login' ),
            // E-mail antes de nome, porque "email_do_colaborador" contém as duas coisas
            'email'      => array( 'email', 'e_mail' ),
            // Senha: cobre "senha inicial", "senha de acesso", "senha provisória"...
            'senha'      => array( 'senha', 'password', 'user_pass' ),
            // Grupo do funcionário
            'grupo'      => array( 'grupo' ),
            // Unidade / planta
            'unidade'    => array( 'unidade', 'planta', 'filial', 'localidade' ),
            // Home office
            'homeoffice' => array( 'homeoffice', 'home_office' ),
            // Comissão
            'comissao'   => array( 'comissao', 'comite' ),
            // Nome fica por último para não capturar "nome do dependente" nem "sobrenome"
            'nome'       => array( 'nome', 'colaborador', 'funcionario', 'empregado' ),
        );
    }

    /**
     * Chaves que NUNCA devem ser confundidas com uma coluna do colaborador
     * São campos de controle interno do tema que por acaso contêm as mesmas palavras
     */
    private static function chaves_bloqueadas() {
        return array(
            'senha_alterada', 'user_field_senha_alterada',
            'leitura_reg', 'votacao', 'desenho_enviado',
        );
    }

    /**
     * Descobre se um cabeçalho pertence a um dependente e a qual campo/slot
     * Devolve array( indice, campo ) ou false
     */
    public static function detectar_coluna_dependente( $chave ) {
        // Só considera colunas que mencionem dependente / filho / crianca
        if ( false === strpos( $chave, 'depend' ) && false === strpos( $chave, 'filho' ) && false === strpos( $chave, 'crianca' ) ) {
            return false;
        }

        // Índice do dependente (padrão 1)
        $indice = 0;

        // Procura um número explícito no cabeçalho
        if ( preg_match( '/(\d+)/', $chave, $m ) ) {
            $indice = (int) $m[1];
        } else {
            // Procura ordinais escritos por extenso
            $ordinais = array(
                'primeiro' => 1, 'primeira' => 1, 'um' => 1, 'uma' => 1,
                'segundo'  => 2, 'segunda'  => 2, 'dois' => 2, 'duas' => 2,
                'terceiro' => 3, 'terceira' => 3, 'tres' => 3,
                'quarto'   => 4, 'quarta'   => 4, 'quatro' => 4,
                'quinto'   => 5, 'quinta'   => 5, 'cinco'  => 5,
                'sexto'    => 6, 'sexta'    => 6, 'seis'   => 6,
            );
            // Testa cada ordinal
            foreach ( $ordinais as $palavra => $num ) {
                if ( false !== strpos( $chave, $palavra ) ) {
                    $indice = $num;
                    break;
                }
            }
        }

        // Sem número identificado assume o primeiro dependente
        if ( $indice < 1 ) {
            $indice = 1;
        }

        // Ignora slots acima do suportado pelo tema
        if ( $indice > self::max_dep() ) {
            return false;
        }

        // Identifica o campo pelo vocabulário do cabeçalho
        if ( false !== strpos( $chave, 'categ' ) || false !== strpos( $chave, 'cat' ) ) {
            $campo = 'cat';
        } elseif ( false !== strpos( $chave, 'idade' ) || false !== strpos( $chave, 'anos' ) ) {
            $campo = 'idade';
        } else {
            // Qualquer outra variação (inclusive "dependente_1" puro) é tratada como nome
            $campo = 'nome';
        }

        // Devolve o par identificado
        return array( $indice, $campo );
    }

    /**
     * Interpreta a linha de cabeçalho e devolve o mapeamento de colunas
     */
    public static function mapear_colunas( $cabecalho ) {
        // Estrutura de retorno
        $mapa = array(
            'colaborador' => array(), // campo => índice da coluna
            'dependentes' => array(), // indice => array( campo => índice da coluna )
            'ignoradas'   => array(), // cabeçalhos que não foram reconhecidos
            'rotulos'     => array(), // índice => rótulo original (para exibição)
            'aproximadas' => array(), // campo => rótulo casado por aproximação
        );

        // Carrega a tabela de sinônimos
        $sinonimos = self::sinonimos_colunas();

        // Colunas que o nome exato não resolveu, avaliadas na segunda passada
        $pendentes = array();

        // Percorre cada coluna do cabeçalho
        foreach ( $cabecalho as $indice_col => $rotulo ) {
            // Guarda o rótulo original
            $mapa['rotulos'][ $indice_col ] = self::limpar( $rotulo );
            // Normaliza o cabeçalho
            $chave = self::chave( $rotulo );

            // Cabeçalho vazio é descartado silenciosamente
            if ( '' === $chave ) {
                continue;
            }

            // Primeiro tenta reconhecer uma coluna de dependente
            $dep = self::detectar_coluna_dependente( $chave );
            if ( false !== $dep ) {
                // Extrai índice e campo
                list( $idx, $campo ) = $dep;
                // Registra apenas a primeira ocorrência de cada par
                if ( ! isset( $mapa['dependentes'][ $idx ][ $campo ] ) ) {
                    $mapa['dependentes'][ $idx ][ $campo ] = $indice_col;
                } else {
                    $mapa['ignoradas'][] = self::limpar( $rotulo ) . ' (duplicada)';
                }
                continue;
            }

            // Depois tenta casar exatamente com um sinônimo de coluna do colaborador
            $achou = false;
            foreach ( $sinonimos as $campo => $lista ) {
                if ( in_array( $chave, $lista, true ) ) {
                    // Registra apenas a primeira ocorrência
                    if ( ! isset( $mapa['colaborador'][ $campo ] ) ) {
                        $mapa['colaborador'][ $campo ] = $indice_col;
                    } else {
                        $mapa['ignoradas'][] = self::limpar( $rotulo ) . ' (duplicada)';
                    }
                    $achou = true;
                    break;
                }
            }

            // Coluna não reconhecida pelo nome exato: guarda para a segunda passada
            if ( ! $achou ) {
                $pendentes[ $indice_col ] = $chave;
            }
        }

        // SEGUNDA PASSADA: reconhecimento aproximado das colunas que sobraram.
        // Só entra aqui o que o nome exato não resolveu, e só preenche campo vazio,
        // então uma planilha bem preenchida nunca muda de comportamento.
        $termos     = self::termos_colunas();
        $bloqueadas = self::chaves_bloqueadas();

        // Percorre os campos na ordem de prioridade definida em termos_colunas()
        foreach ( $termos as $campo => $lista ) {
            // Campo já resolvido pelo nome exato
            if ( isset( $mapa['colaborador'][ $campo ] ) ) {
                continue;
            }
            // Procura entre as colunas ainda sem dono
            foreach ( $pendentes as $indice_col => $chave ) {
                // Ignora chaves que são campos internos do tema
                if ( in_array( $chave, $bloqueadas, true ) ) {
                    continue;
                }
                // Testa cada termo do campo
                foreach ( $lista as $termo ) {
                    // Basta a chave conter o termo
                    if ( false === strpos( $chave, $termo ) ) {
                        continue;
                    }
                    // Assume a coluna para este campo
                    $mapa['colaborador'][ $campo ] = $indice_col;
                    // Registra que foi por aproximação, para avisar o administrador
                    $mapa['aproximadas'][ $campo ] = $mapa['rotulos'][ $indice_col ];
                    // A coluna deixa de estar disponível
                    unset( $pendentes[ $indice_col ] );
                    // Passa para o próximo campo
                    break 2;
                }
            }
        }

        // O que sobrou continua sendo ignorado
        foreach ( $pendentes as $indice_col => $chave ) {
            $mapa['ignoradas'][] = $mapa['rotulos'][ $indice_col ];
        }

        // Ordena os slots de dependentes
        ksort( $mapa['dependentes'] );

        // Devolve o mapeamento
        return $mapa;
    }

    /**
     * Descobre o delimitador mais provável analisando a primeira linha
     */
    public static function detectar_delimitador( $linha ) {
        // Candidatos suportados
        $candidatos = array( ';' => 0, ',' => 0, "\t" => 0, '|' => 0 );
        // Conta as ocorrências de cada um
        foreach ( $candidatos as $car => $qtd ) {
            $candidatos[ $car ] = substr_count( $linha, $car );
        }
        // Ordena do mais frequente para o menos frequente
        arsort( $candidatos );
        // Pega o primeiro
        $escolhido = key( $candidatos );
        // Se nenhum apareceu assume ponto e vírgula (padrão do Excel pt-BR)
        return ( $candidatos[ $escolhido ] > 0 ) ? $escolhido : ';';
    }

    /**
     * Normaliza a codificação e as quebras de linha do arquivo enviado
     * Devolve o caminho de um novo arquivo em UTF-8 pronto para o fgetcsv
     */
    public static function normalizar_arquivo( $caminho_origem, $token ) {
        // Lê o conteúdo completo do arquivo
        $conteudo = file_get_contents( $caminho_origem );

        // Falha de leitura
        if ( false === $conteudo ) {
            return new WP_Error( 'leitura', 'Não foi possível ler o arquivo enviado.' );
        }

        // Remove o BOM UTF-8 quando presente
        if ( 0 === strncmp( $conteudo, "\xEF\xBB\xBF", 3 ) ) {
            $conteudo = substr( $conteudo, 3 );
        }

        // Converte para UTF-8 quando o arquivo não estiver em UTF-8 válido
        if ( ! preg_match( '//u', $conteudo ) ) {
            // Tenta detectar entre as codificações usuais no Brasil
            if ( function_exists( 'mb_convert_encoding' ) ) {
                $conteudo = mb_convert_encoding( $conteudo, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8' );
            } else {
                $conteudo = utf8_encode( $conteudo );
            }
        }

        // Padroniza as quebras de linha para \n
        $conteudo = str_replace( array( "\r\n", "\r" ), "\n", $conteudo );

        // Define o destino normalizado
        $destino = self::caminho_plano( $token, 'origem.csv' );

        // Token inválido
        if ( '' === $destino ) {
            return new WP_Error( 'token', 'Token de importação inválido.' );
        }

        // Grava o arquivo normalizado
        if ( false === file_put_contents( $destino, $conteudo ) ) {
            return new WP_Error( 'gravacao', 'Não foi possível gravar o arquivo temporário de importação.' );
        }

        // Devolve o caminho gerado
        return $destino;
    }

    /* =====================================================================
     * CONSTRUCAO DO PLANO (CONSOLIDACAO INTELIGENTE)
     * ===================================================================== */

    /**
     * Lê o CSV normalizado e monta o plano de importação consolidado por matrícula
     *
     * Regra central pedida pelo cliente: cada vez que a MESMA matrícula reaparece,
     * o dependente daquela linha é encaixado no PRÓXIMO slot livre (1 -> 2 -> 3 -> 4),
     * levando junto nome, idade e categoria daquela mesma linha.
     */
    public static function construir_plano( $arquivo, $opcoes, $token, $nome_original ) {
        // Abre o arquivo normalizado
        $fp = fopen( $arquivo, 'r' );

        // Falha na abertura
        if ( ! $fp ) {
            return new WP_Error( 'abertura', 'Não foi possível abrir o arquivo de importação.' );
        }

        // Lê a primeira linha crua para descobrir o delimitador
        $primeira = fgets( $fp );
        // Arquivo vazio
        if ( false === $primeira ) {
            fclose( $fp );
            return new WP_Error( 'vazio', 'O arquivo enviado está vazio.' );
        }
        // Descobre o separador
        $delimitador = self::detectar_delimitador( $primeira );
        // Volta ao início do arquivo
        rewind( $fp );

        // Lê o cabeçalho já como CSV
        $cabecalho = fgetcsv( $fp, 0, $delimitador );
        // Cabeçalho inválido
        if ( ! is_array( $cabecalho ) ) {
            fclose( $fp );
            return new WP_Error( 'cabecalho', 'Não foi possível interpretar o cabeçalho do arquivo.' );
        }

        // Monta o mapeamento das colunas
        $mapa_col = self::mapear_colunas( $cabecalho );

        // Sem coluna de matrícula não há como continuar
        if ( ! isset( $mapa_col['colaborador']['matricula'] ) ) {
            fclose( $fp );
            return new WP_Error( 'sem_matricula', 'A planilha precisa ter uma coluna de MATRÍCULA. Baixe o modelo CSV e confira os nomes das colunas.' );
        }

        // Sem nenhuma coluna de dependente também não faz sentido importar
        if ( empty( $mapa_col['dependentes'] ) ) {
            fclose( $fp );
            return new WP_Error( 'sem_dependente', 'A planilha precisa ter ao menos a coluna de NOME DO DEPENDENTE. Baixe o modelo CSV e confira os nomes das colunas.' );
        }

        // Acumuladores da consolidação
        $registros    = array();  // chave da matrícula => registro consolidado
        $ordem        = array();  // preserva a ordem de aparição das matrículas
        $avisos       = array();  // avisos gerais do arquivo
        $total_linhas = 0;        // linhas de dados lidas
        $linhas_vazias = 0;       // linhas descartadas por estarem em branco
        $dep_excedentes = 0;      // dependentes que passaram do limite de slots
        $num_linha    = 1;        // número da linha no arquivo (cabeçalho é a 1)

        // Percorre todas as linhas de dados
        while ( false !== ( $linha = fgetcsv( $fp, 0, $delimitador ) ) ) {
            // Avança o contador de linha física
            $num_linha++;

            // Ignora linhas completamente vazias
            if ( ! is_array( $linha ) || ( 1 === count( $linha ) && ( null === $linha[0] || '' === trim( (string) $linha[0] ) ) ) ) {
                $linhas_vazias++;
                continue;
            }

            // Função auxiliar interna para ler uma célula com segurança
            $celula = function( $indice ) use ( $linha ) {
                // Índice inexistente devolve vazio
                if ( null === $indice || ! isset( $linha[ $indice ] ) ) {
                    return '';
                }
                // Devolve o valor limpo
                return Explode_Admin_User_Importer::limpar( $linha[ $indice ] );
            };

            // Lê a matrícula da linha
            $matricula_bruta = $celula( $mapa_col['colaborador']['matricula'] );
            // Gera a chave de agrupamento
            $chave_mat = self::chave_matricula( $matricula_bruta );

            // Linha sem matrícula é descartada com aviso
            if ( '' === $chave_mat ) {
                $avisos[] = 'Linha ' . $num_linha . ': ignorada por não ter matrícula.';
                continue;
            }

            // Conta como linha de dados válida
            $total_linhas++;

            // Cria o registro na primeira aparição da matrícula
            if ( ! isset( $registros[ $chave_mat ] ) ) {
                $registros[ $chave_mat ] = array(
                    'matricula'   => $matricula_bruta,
                    'nome'        => '',
                    'email'       => '',
                    'senha'       => '',
                    'grupo_bruto' => '',
                    'unidade'     => '',
                    'homeoffice'  => '',
                    'comissao'    => '',
                    'linhas'      => array(),
                    'dependentes' => array(),
                    'avisos'      => array(),
                    'erros'       => array(),
                );
                // Registra a ordem de aparição
                $ordem[] = $chave_mat;
            }

            // Atalho para o registro atual
            $reg =& $registros[ $chave_mat ];

            // Guarda o número da linha de origem
            $reg['linhas'][] = $num_linha;

            // Preenche os campos do colaborador: o primeiro valor não vazio prevalece
            foreach ( array( 'nome', 'email', 'senha', 'unidade', 'homeoffice', 'comissao' ) as $campo ) {
                // Coluna não mapeada
                if ( ! isset( $mapa_col['colaborador'][ $campo ] ) ) {
                    continue;
                }
                // Lê o valor da célula
                $valor = $celula( $mapa_col['colaborador'][ $campo ] );
                // Nada a fazer com valor vazio
                if ( '' === $valor ) {
                    continue;
                }
                // Grava quando ainda estiver vazio
                if ( '' === $reg[ $campo ] ) {
                    $reg[ $campo ] = $valor;
                } elseif ( self::chave( $reg[ $campo ] ) !== self::chave( $valor ) ) {
                    // Divergência entre linhas da mesma matrícula: mantém o primeiro e avisa
                    $reg['avisos'][] = 'Linha ' . $num_linha . ': o campo "' . $campo . '" veio diferente ("' . $valor . '"). Foi mantido o primeiro valor ("' . $reg[ $campo ] . '").';
                }
            }

            // Campo grupo é tratado à parte por ter nome interno diferente
            if ( isset( $mapa_col['colaborador']['grupo'] ) ) {
                // Lê o grupo da linha
                $grupo_linha = $celula( $mapa_col['colaborador']['grupo'] );
                // Só processa se veio preenchido
                if ( '' !== $grupo_linha ) {
                    // Grava na primeira vez
                    if ( '' === $reg['grupo_bruto'] ) {
                        $reg['grupo_bruto'] = $grupo_linha;
                    } elseif ( self::chave( $reg['grupo_bruto'] ) !== self::chave( $grupo_linha ) ) {
                        // Divergência de grupo é crítica porque afeta a categoria
                        $reg['avisos'][] = 'Linha ' . $num_linha . ': grupo divergente ("' . $grupo_linha . '"). Foi mantido "' . $reg['grupo_bruto'] . '".';
                    }
                }
            }

            // Percorre os slots de dependentes existentes NESTA linha, na ordem das colunas
            foreach ( $mapa_col['dependentes'] as $slot_csv => $campos ) {
                // Lê nome, idade e categoria do slot desta linha
                $dep_nome  = isset( $campos['nome'] )  ? $celula( $campos['nome'] )  : '';
                $dep_idade = isset( $campos['idade'] ) ? $celula( $campos['idade'] ) : '';
                $dep_cat   = isset( $campos['cat'] )   ? $celula( $campos['cat'] )   : '';

                // Sem nome o dependente não existe: nada é criado
                if ( '' === $dep_nome ) {
                    // Avisa quando idade/categoria vieram sem nome (dado órfão)
                    if ( '' !== $dep_idade || '' !== $dep_cat ) {
                        $reg['avisos'][] = 'Linha ' . $num_linha . ': dependente ' . $slot_csv . ' tem idade/categoria mas está sem NOME. Os dados foram descartados.';
                    }
                    continue;
                }

                // Chave normalizada do nome para detectar repetição do mesmo dependente
                $chave_dep = self::chave_nome( $dep_nome );

                // Procura se esse dependente já foi registrado antes para a mesma matrícula
                $posicao_existente = null;
                foreach ( $reg['dependentes'] as $pos => $ja ) {
                    if ( $ja['chave'] === $chave_dep ) {
                        $posicao_existente = $pos;
                        break;
                    }
                }

                // Dependente repetido: completa lacunas ao invés de ocupar um novo slot
                if ( null !== $posicao_existente ) {
                    // Completa a idade se estava faltando
                    if ( '' === $reg['dependentes'][ $posicao_existente ]['idade'] && '' !== $dep_idade ) {
                        $reg['dependentes'][ $posicao_existente ]['idade'] = $dep_idade;
                    }
                    // Completa a categoria se estava faltando
                    if ( '' === $reg['dependentes'][ $posicao_existente ]['cat_bruta'] && '' !== $dep_cat ) {
                        $reg['dependentes'][ $posicao_existente ]['cat_bruta'] = $dep_cat;
                    }
                    // Registra o aviso de repetição
                    $reg['avisos'][] = 'Linha ' . $num_linha . ': o dependente "' . $dep_nome . '" já havia sido informado e não gerou um novo slot.';
                    continue;
                }

                // Verifica se ainda há slot livre no tema
                if ( count( $reg['dependentes'] ) >= self::max_dep() ) {
                    // Contabiliza e avisa o descarte
                    $dep_excedentes++;
                    $reg['erros'][] = 'Linha ' . $num_linha . ': o dependente "' . $dep_nome . '" excede o limite de ' . self::max_dep() . ' dependentes e NÃO será importado.';
                    continue;
                }

                // Encaixa o dependente no próximo slot livre (1 -> 2 -> 3 -> 4)
                $reg['dependentes'][] = array(
                    'nome'      => $dep_nome,
                    'chave'     => $chave_dep,
                    'idade'     => $dep_idade,
                    'cat_bruta' => $dep_cat,
                    'linha'     => $num_linha,
                );
            }

            // Libera a referência para a próxima iteração
            unset( $reg );
        }

        // Fecha o arquivo lido
        fclose( $fp );

        // Nenhuma linha aproveitável
        if ( empty( $registros ) ) {
            return new WP_Error( 'sem_dados', 'Nenhuma linha válida foi encontrada no arquivo.' );
        }

        // ############### DIAGNOSTICO DAS SENHAS ###############
        // A senha era o ponto cego do importador: se a coluna não fosse reconhecida,
        // ou se viesse vazia, o colaborador recebia a matrícula como senha e nada
        // era dito. Aqui as três situações são contadas para aparecerem na tela.
        $senha_diag = array(
            // Regra escolhida no formulário
            'tipo'        => $opcoes['senha_tipo'],
            // A planilha tem uma coluna de senha reconhecida?
            'coluna_ok'   => isset( $mapa_col['colaborador']['senha'] ),
            // Rótulo original dessa coluna, para o administrador conferir
            'coluna_nome' => isset( $mapa_col['colaborador']['senha'] ) ? $mapa_col['rotulos'][ $mapa_col['colaborador']['senha'] ] : '',
            // A coluna foi reconhecida por aproximação?
            'aproximada'  => isset( $mapa_col['aproximadas']['senha'] ),
            // Matrículas com senha preenchida
            'preenchidas' => 0,
            // Matrículas sem senha na planilha
            'vazias'      => 0,
            // Senha fixa informada quando a regra é "fixa"
            'fixa_ok'     => ( 'fixa' === $opcoes['senha_tipo'] && '' !== trim( (string) $opcoes['senha_fixa'] ) ),
        );

        // Conta quantas matrículas trouxeram senha de fato
        foreach ( $registros as $reg_diag ) {
            if ( '' !== self::limpar( $reg_diag['senha'] ) ) {
                $senha_diag['preenchidas']++;
            } else {
                $senha_diag['vazias']++;
            }
        }
        // ############### FIM DO DIAGNOSTICO ###############

        // Resolve grupos, categorias e situação de cada matrícula
        $resumo = self::resolver_registros( $registros, $ordem, $opcoes, $token );

        // Persiste os registros em disco e devolve o cabeçalho do plano
        $plano = array(
            'token'          => $token,
            'criado_em'      => time(),
            'autor'          => get_current_user_id(),
            'arquivo'        => $nome_original,
            'delimitador'    => ( "\t" === $delimitador ) ? 'TAB' : $delimitador,
            'opcoes'         => $opcoes,
            'mapa_colunas'   => $mapa_col,
            'senha_diag'     => $senha_diag,
            'total_linhas'   => $total_linhas,
            'linhas_vazias'  => $linhas_vazias,
            'dep_excedentes' => $dep_excedentes,
            'avisos'         => $avisos,
            'contadores'     => $resumo['contadores'],
            'offsets'        => $resumo['offsets'],
            'total_reg'      => count( $ordem ),
            'preview'        => $resumo['preview'],
            'preview_dup'    => $resumo['preview_dup'],
        );

        // Devolve o plano pronto
        return $plano;
    }

    /**
     * Consulta em lote quais matrículas já existem como usuários (evita milhares de queries)
     */
    private static function localizar_usuarios_existentes( $matriculas ) {
        // Acesso direto ao banco
        global $wpdb;

        // Mapa de retorno: chave da matrícula => ID do usuário
        $encontrados = array();

        // Nada a consultar
        if ( empty( $matriculas ) ) {
            return $encontrados;
        }

        // Consulta em blocos para não estourar o limite do MySQL
        $blocos = array_chunk( array_values( $matriculas ), 500 );

        // Percorre cada bloco
        foreach ( $blocos as $bloco ) {
            // Monta os marcadores da cláusula IN
            $marcadores = implode( ',', array_fill( 0, count( $bloco ), '%s' ) );
            // Executa a consulta preparada
            $linhas = $wpdb->get_results(
                $wpdb->prepare( "SELECT ID, user_login FROM {$wpdb->users} WHERE user_login IN ({$marcadores})", $bloco ),
                ARRAY_A
            );
            // Indexa o resultado pela chave normalizada
            if ( is_array( $linhas ) ) {
                foreach ( $linhas as $l ) {
                    $encontrados[ self::chave_matricula( $l['user_login'] ) ] = (int) $l['ID'];
                }
            }
        }

        // Devolve o mapa
        return $encontrados;
    }

    /**
     * Resolve grupos/categorias de cada registro, grava o arquivo de registros e monta o resumo
     */
    private static function resolver_registros( &$registros, $ordem, $opcoes, $token ) {
        // Caminho do arquivo de registros (um JSON por linha)
        $arquivo_reg = self::caminho_plano( $token, 'registros.jsonl' );
        // Abre para escrita
        $fp = fopen( $arquivo_reg, 'w' );

        // Contadores do resumo
        $cont = array(
            'total'          => 0,
            'novos'          => 0,
            'atualizar'      => 0,
            'com_erro'       => 0,
            'com_aviso'      => 0,
            'duplicadas'     => 0,
            'dependentes'    => 0,
            'dep_slot'       => array_fill( 1, self::max_dep(), 0 ),
            'sem_categoria'  => 0,
            'sem_grupo'      => 0,
            'cat_por_idade'  => 0,
        );

        // Posições de cada registro dentro do arquivo, para leitura direta no lote
        $offsets = array();
        // Amostras exibidas na tela de conferência
        $preview = array();
        $preview_dup = array();

        // Descobre de uma vez quais matrículas já existem
        $matriculas_brutas = array();
        foreach ( $ordem as $chave_mat ) {
            $matriculas_brutas[] = $registros[ $chave_mat ]['matricula'];
        }
        $existentes = self::localizar_usuarios_existentes( $matriculas_brutas );

        // Flags das opções escolhidas pelo administrador
        $criar_termos   = ! empty( $opcoes['criar_termos'] );
        $cat_por_idade  = ! empty( $opcoes['cat_por_idade'] );
        $padronizar_grp = ! empty( $opcoes['padronizar_grupo'] );

        // Percorre os registros na ordem de aparição no CSV
        foreach ( $ordem as $chave_mat ) {
            // Atalho para o registro
            $reg = $registros[ $chave_mat ];

            // Resolve o termo do grupo
            $grupo = self::resolver_grupo( $reg['grupo_bruto'], $criar_termos );

            // Guarda o resultado da resolução do grupo
            $reg['grupo_id']   = $grupo ? (int) $grupo['id'] : 0;
            $reg['grupo_nome'] = $grupo ? $grupo['nome'] : '';

            // Valor que será gravado no meta user_field_funcionario_grupo
            if ( $grupo && $padronizar_grp ) {
                $reg['grupo_meta'] = $grupo['nome'];
            } else {
                $reg['grupo_meta'] = self::limpar( $reg['grupo_bruto'] );
            }

            // Sem grupo válido a categoria não pode ser concatenada
            if ( ! $grupo ) {
                $cont['sem_grupo']++;
                if ( '' === self::limpar( $reg['grupo_bruto'] ) ) {
                    $reg['erros'][] = 'Grupo não informado: a categoria dos dependentes não pode ser montada.';
                } else {
                    $reg['erros'][] = 'Grupo "' . $reg['grupo_bruto'] . '" não existe na taxonomia de categorias. Cadastre o grupo ou marque a opção de criar termos faltantes.';
                }
            }

            // Resolve a unidade quando informada
            $reg['unidade_id'] = 0;
            if ( '' !== $reg['unidade'] ) {
                // Busca (e cria quando autorizado) o termo da unidade
                $uni = self::resolver_unidade( $reg['unidade'], $criar_termos );
                // Guarda o ID resolvido
                if ( $uni ) {
                    $reg['unidade_id'] = (int) $uni['id'];
                } else {
                    $reg['avisos'][] = 'Unidade "' . $reg['unidade'] . '" não cadastrada. O campo Unidade ficará vazio.';
                }
            }

            // Resolve a categoria de cada dependente já na ordem final dos slots
            foreach ( $reg['dependentes'] as $pos => $dep ) {
                // Número do slot final (1 a 4)
                $slot = $pos + 1;

                // Normaliza a idade extraindo apenas os dígitos
                $idade_num = '';
                if ( '' !== $dep['idade'] ) {
                    // Extrai o primeiro número encontrado
                    if ( preg_match( '/(\d+)/', $dep['idade'], $mi ) ) {
                        $idade_num = (string) (int) $mi[1];
                    } else {
                        $reg['avisos'][] = 'Dependente ' . $slot . ' ("' . $dep['nome'] . '"): idade "' . $dep['idade'] . '" não é um número e foi ignorada.';
                    }
                }

                // Valor bruto da categoria informado no CSV
                $cat_bruta = $dep['cat_bruta'];
                // Marca se a categoria foi deduzida pela idade
                $deduzida = false;

                // Deduz a categoria pela idade quando permitido e a coluna veio vazia
                if ( '' === $cat_bruta && $cat_por_idade && '' !== $idade_num ) {
                    // Aplica as faixas oficiais
                    $cat_bruta = self::categoria_por_idade( $idade_num, $reg['grupo_nome'] );
                    // Marca a dedução
                    if ( '' !== $cat_bruta ) {
                        $deduzida = true;
                        $cont['cat_por_idade']++;
                    }
                }

                // Resolve o termo da categoria
                $cat = ( '' !== $cat_bruta ) ? self::resolver_categoria( $cat_bruta, $criar_termos ) : null;

                // Monta a concatenação "<ID da categoria>.<ID do grupo>"
                $cat_meta = '';
                if ( $cat && $reg['grupo_id'] > 0 ) {
                    $cat_meta = $cat['id'] . '.' . $reg['grupo_id'];
                }

                // Registra os problemas encontrados
                if ( ! $cat ) {
                    // Contabiliza dependente sem categoria
                    $cont['sem_categoria']++;
                    // Mensagem conforme o motivo
                    if ( '' === $cat_bruta ) {
                        $reg['erros'][] = 'Dependente ' . $slot . ' ("' . $dep['nome'] . '"): categoria não informada.';
                    } else {
                        $reg['erros'][] = 'Dependente ' . $slot . ' ("' . $dep['nome'] . '"): categoria "' . $cat_bruta . '" não existe na taxonomia.';
                    }
                } elseif ( $reg['grupo_id'] < 1 ) {
                    // Categoria existe mas falta o grupo para concatenar
                    $reg['erros'][] = 'Dependente ' . $slot . ' ("' . $dep['nome'] . '"): sem o grupo do colaborador não é possível montar a categoria.';
                }

                // Atualiza o dependente com os dados resolvidos
                $reg['dependentes'][ $pos ]['slot']     = $slot;
                $reg['dependentes'][ $pos ]['idade']    = $idade_num;
                $reg['dependentes'][ $pos ]['cat_nome'] = $cat ? $cat['nome'] : '';
                $reg['dependentes'][ $pos ]['cat_id']   = $cat ? (int) $cat['id'] : 0;
                $reg['dependentes'][ $pos ]['cat_meta'] = $cat_meta;
                $reg['dependentes'][ $pos ]['deduzida'] = $deduzida;

                // Contabiliza o dependente
                $cont['dependentes']++;
                $cont['dep_slot'][ $slot ]++;
            }

            // Verifica se a matrícula já existe no WordPress
            $reg['user_id'] = isset( $existentes[ $chave_mat ] ) ? (int) $existentes[ $chave_mat ] : 0;
            $reg['acao']    = $reg['user_id'] > 0 ? 'atualizar' : 'criar';

            // Nome do colaborador é obrigatório apenas na criação
            if ( 'criar' === $reg['acao'] && '' === $reg['nome'] ) {
                $reg['erros'][] = 'Nome do colaborador não informado (obrigatório para criar o usuário).';
            }

            // Sem nenhum dependente o registro não tem utilidade prática
            if ( empty( $reg['dependentes'] ) ) {
                $reg['avisos'][] = 'Nenhum dependente informado para esta matrícula.';
            }

            // Marca a quantidade de linhas de origem
            $reg['qtd_linhas'] = count( $reg['linhas'] );

            // Atualiza contadores gerais
            $cont['total']++;
            if ( 'criar' === $reg['acao'] ) {
                $cont['novos']++;
            } else {
                $cont['atualizar']++;
            }
            if ( ! empty( $reg['erros'] ) ) {
                $cont['com_erro']++;
            }
            if ( ! empty( $reg['avisos'] ) ) {
                $cont['com_aviso']++;
            }
            if ( $reg['qtd_linhas'] > 1 ) {
                $cont['duplicadas']++;
            }

            // Serializa o registro em uma única linha
            $json = wp_json_encode( $reg );
            // Guarda a posição atual do ponteiro antes de escrever
            $offsets[] = ftell( $fp );
            // Escreve o registro
            fwrite( $fp, $json . "\n" );

            // Alimenta as amostras de conferência
            if ( count( $preview ) < 60 ) {
                $preview[] = $reg;
            }
            if ( $reg['qtd_linhas'] > 1 && count( $preview_dup ) < 60 ) {
                $preview_dup[] = $reg;
            }
        }

        // Fecha o arquivo de registros
        fclose( $fp );

        // Devolve o resumo consolidado
        return array(
            'contadores'  => $cont,
            'offsets'     => $offsets,
            'preview'     => $preview,
            'preview_dup' => $preview_dup,
        );
    }

    /* =====================================================================
     * PERSISTENCIA DO PLANO
     * ===================================================================== */

    /**
     * Grava o cabeçalho do plano em disco
     */
    private static function salvar_plano( $plano ) {
        // Caminho do arquivo
        $arq = self::caminho_plano( $plano['token'], 'plano.json' );
        // Token inválido
        if ( '' === $arq ) {
            return false;
        }
        // Grava o JSON
        return false !== file_put_contents( $arq, wp_json_encode( $plano ) );
    }

    /**
     * Lê o cabeçalho do plano a partir do token
     */
    private static function ler_plano( $token ) {
        // Caminho do arquivo
        $arq = self::caminho_plano( $token, 'plano.json' );
        // Verifica a existência
        if ( '' === $arq || ! file_exists( $arq ) ) {
            return null;
        }
        // Decodifica o conteúdo
        $dados = json_decode( file_get_contents( $arq ), true );
        // Valida a estrutura mínima
        if ( ! is_array( $dados ) || ! isset( $dados['offsets'] ) ) {
            return null;
        }
        // Só o autor do plano pode utilizá-lo
        if ( (int) $dados['autor'] !== get_current_user_id() ) {
            return null;
        }
        // Devolve o plano
        return $dados;
    }

    /**
     * Lê um intervalo de registros do arquivo JSONL usando os offsets do plano
     */
    private static function ler_registros( $token, $offsets, $inicio, $quantidade ) {
        // Caminho do arquivo de registros
        $arq = self::caminho_plano( $token, 'registros.jsonl' );
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
        // Percorre a fatia solicitada
        for ( $i = $inicio; $i < $inicio + $quantidade; $i++ ) {
            // Fim da lista
            if ( ! isset( $offsets[ $i ] ) ) {
                break;
            }
            // Posiciona o ponteiro no início do registro
            fseek( $fp, (int) $offsets[ $i ] );
            // Lê a linha completa
            $linha = fgets( $fp );
            // Linha inválida
            if ( false === $linha ) {
                break;
            }
            // Decodifica
            $item = json_decode( $linha, true );
            // Adiciona quando válido
            if ( is_array( $item ) ) {
                $item['_indice'] = $i;
                $lista[] = $item;
            }
        }
        // Fecha o arquivo
        fclose( $fp );
        // Devolve a fatia
        return $lista;
    }

    /* =====================================================================
     * MODELO CSV DE EXEMPLO
     * ===================================================================== */

    /**
     * Devolve o cabeçalho oficial do modelo CSV
     */
    public static function modelo_cabecalho() {
        // Colunas fixas do colaborador
        $colunas = array(
            'matricula',
            'nome_colaborador',
            'email',
            'senha',
            'grupo',
            'unidade',
        );

        // Um trio de colunas por dependente aceito pelo tema, para que o modelo
        // nunca fique menor do que o número de slots realmente disponíveis
        for ( $s = 1; $s <= self::max_dep(); $s++ ) {
            $colunas[] = 'dependente_' . $s . '_nome';
            $colunas[] = 'dependente_' . $s . '_idade';
            $colunas[] = 'dependente_' . $s . '_categoria';
        }

        // Devolve o cabeçalho completo
        return $colunas;
    }

    /**
     * Devolve as linhas de exemplo do modelo CSV
     * O modelo demonstra propositalmente as DUAS formas aceitas de preenchimento
     */
    public static function modelo_linhas() {
        return array(
            // FORMA 1: colaborador com um único dependente - GRUPO PADRÃO (Sem MAO)
            array( '32658', 'ANDERSON CUSTODIO', '', '', 'Grupo 4', 'SAO-HDA-INDAIATUBA', 'ALANA CUSTODIO', '8', 'B', '', '', '', '', '', '' ),

            // FORMA 2: os tres dependentes na MESMA linha - GRUPO PADRÃO (Sem MAO)
            array( '58932', 'RENATA DIAS', 'renata.dias@empresa.com.br', '', 'Grupo 1', 'SUM-HAB', 'BEATRIZ DIAS', '6', 'A', 'THIAGO DIAS', '7', 'B', 'CAIO DIAS', '10', 'C' ),

            // FORMA 3: a MESMA matricula repetida, um dependente por linha - GRUPO MAO (Tem MAO)
            // O importador move sozinho o 2o para as colunas do dependente 2 e o 3o para as do dependente 3
            array( '41207', 'MARIANA ALVES', '', '', 'Grupo 8 MAO', 'SUM-HAB', 'PEDRO ALVES', '2', 'A', '', '', '', '', '', '' ),
            array( '41207', 'MARIANA ALVES', '', '', 'Grupo 8 MAO', 'SUM-HAB', 'LUCAS ALVES', '5', 'B', '', '', '', '', '', '' ),
            array( '41207', 'MARIANA ALVES', '', '', 'Grupo 8 MAO', 'SUM-HAB', 'JULIA ALVES', '8', 'C', '', '', '', '', '', '' ),
            
            // FORMA 4: mistura das duas formas para a mesma matricula - GRUPO MAO (Tem MAO)
            array( '77410', 'CARLOS EDUARDO LIMA', '', '', 'Grupo 10 MAO', 'SAO-HDA-SP2', 'HELENA LIMA', '3', 'A', 'MIGUEL LIMA', '6', 'B', '', '', '' ),
            array( '77410', 'CARLOS EDUARDO LIMA', '', '', 'Grupo 10 MAO', 'SAO-HDA-SP2', 'ARTHUR LIMA', '11', 'D', '', '', '', '', '', '' ),
        );
    }

    /**
     * Gera e envia o arquivo CSV modelo para download
     */
    private static function baixar_modelo() {
        // Nome do arquivo entregue ao administrador
        $nome = 'modelo-importacao-usuarios-explode.csv';

        // Cabeçalhos de download
        nocache_headers();
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $nome . '"' );

        // Abre a saída padrão como arquivo
        $saida = fopen( 'php://output', 'w' );

        // BOM UTF-8 para o Excel abrir a acentuação corretamente
        fwrite( $saida, "\xEF\xBB\xBF" );

        // Escreve o cabeçalho usando ponto e vírgula (padrão do Excel em português)
        fputcsv( $saida, self::modelo_cabecalho(), ';' );

        // Quantidade de colunas do cabeçalho, usada para alinhar os exemplos
        $total_colunas = count( self::modelo_cabecalho() );

        // Escreve as linhas de exemplo
        foreach ( self::modelo_linhas() as $linha ) {
            // Completa com colunas vazias quando o exemplo tiver menos campos
            // que o cabeçalho, mantendo o arquivo alinhado no Excel
            $linha = array_pad( $linha, $total_colunas, '' );
            // Grava a linha
            fputcsv( $saida, $linha, ';' );
        }

        // Fecha a saída e encerra a execução
        fclose( $saida );
        exit;
    }

    /* =====================================================================
     * REQUISICOES (DOWNLOAD DO MODELO, UPLOAD E RELATORIO)
     * ===================================================================== */

    /**
     * Trata as requisições feitas na página do importador
     */
    public static function handle_requests() {
        // Só age dentro da página do importador
        $pagina = isset( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';
        // Fora da página não faz nada
        if ( self::MENU_SLUG !== $pagina ) {
            return;
        }

        // Download do modelo CSV
        if ( isset( $_GET['acao'] ) && 'modelo' === $_GET['acao'] ) {
            // Verifica permissão
            if ( ! self::pode_operar() ) {
                wp_die( 'Você não tem permissão para acessar este recurso.' );
            }
            // Verifica o nonce
            check_admin_referer( self::NONCE );
            // Envia o arquivo
            self::baixar_modelo();
        }

        // Download do relatório de uma importação já executada
        if ( isset( $_GET['acao'] ) && 'relatorio' === $_GET['acao'] ) {
            // Verifica permissão
            if ( ! self::pode_operar() ) {
                wp_die( 'Você não tem permissão para acessar este recurso.' );
            }
            // Verifica o nonce
            check_admin_referer( self::NONCE );
            // Envia o relatório
            self::baixar_relatorio( isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '' );
        }

        // Processamento do upload
        if ( isset( $_POST['explode_importador_enviar'] ) ) {
            // Verifica permissão
            if ( ! self::pode_operar() ) {
                wp_die( 'Você não tem permissão para importar usuários.' );
            }
            // Verifica o nonce
            check_admin_referer( self::NONCE );
            // Executa a análise
            self::processar_upload();
        }
    }

    /**
     * Recebe o arquivo, monta o plano e redireciona para a tela de conferência
     */
    private static function processar_upload() {
        // Remove planos vencidos antes de criar um novo
        self::limpar_planos_antigos();

        // URL base da página
        $base = admin_url( 'admin.php?page=' . self::MENU_SLUG );

        // Valida a presença do arquivo
        if ( empty( $_FILES['arquivo_csv'] ) || ! isset( $_FILES['arquivo_csv']['tmp_name'] ) || '' === $_FILES['arquivo_csv']['tmp_name'] ) {
            self::redirecionar_com_erro( $base, 'Selecione um arquivo CSV para enviar.' );
        }

        // Atalho para os dados do arquivo
        $arquivo = $_FILES['arquivo_csv'];

        // Verifica erros de upload do PHP
        if ( ! empty( $arquivo['error'] ) ) {
            self::redirecionar_com_erro( $base, 'Falha no envio do arquivo (código ' . (int) $arquivo['error'] . '). Verifique o tamanho máximo permitido pelo servidor.' );
        }

        // Valida o tamanho
        if ( (int) $arquivo['size'] > self::MAX_UPLOAD ) {
            self::redirecionar_com_erro( $base, 'O arquivo excede o limite de ' . size_format( self::MAX_UPLOAD ) . '.' );
        }

        // Valida a extensão
        $ext = strtolower( pathinfo( $arquivo['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, array( 'csv', 'txt' ), true ) ) {
            self::redirecionar_com_erro( $base, 'Envie um arquivo com extensão .csv (ou .txt separado por ponto e vírgula).' );
        }

        // Confirma que o arquivo veio realmente de um upload HTTP
        if ( ! is_uploaded_file( $arquivo['tmp_name'] ) ) {
            self::redirecionar_com_erro( $base, 'Arquivo temporário inválido.' );
        }

        // Gera o token único deste plano
        $token = wp_generate_password( 32, false, false );

        // Lê as opções escolhidas no formulário
        $opcoes = array(
            'modo'             => isset( $_POST['modo'] ) && in_array( $_POST['modo'], array( 'ambos', 'criar', 'atualizar' ), true ) ? sanitize_key( $_POST['modo'] ) : 'ambos',
            'senha_tipo'       => isset( $_POST['senha_tipo'] ) && in_array( $_POST['senha_tipo'], array( 'matricula', 'coluna', 'fixa' ), true ) ? sanitize_key( $_POST['senha_tipo'] ) : 'matricula',
            'senha_fixa'       => isset( $_POST['senha_fixa'] ) ? sanitize_text_field( wp_unslash( $_POST['senha_fixa'] ) ) : '',
            'dominio'          => isset( $_POST['dominio'] ) && '' !== trim( (string) $_POST['dominio'] ) ? sanitize_text_field( wp_unslash( $_POST['dominio'] ) ) : self::DOMINIO_PADRAO,
            'criar_termos'     => ! empty( $_POST['criar_termos'] ),
            'cat_por_idade'    => ! empty( $_POST['cat_por_idade'] ),
            'padronizar_grupo' => ! empty( $_POST['padronizar_grupo'] ),
            'limpar_slots'     => ! empty( $_POST['limpar_slots'] ),
            'atualizar_senha'  => ! empty( $_POST['atualizar_senha'] ),
            'lote'             => isset( $_POST['lote'] ) ? max( 5, min( 200, (int) $_POST['lote'] ) ) : 40,
        );

        // Normaliza a codificação do arquivo enviado
        $normalizado = self::normalizar_arquivo( $arquivo['tmp_name'], $token );

        // Erro na normalização
        if ( is_wp_error( $normalizado ) ) {
            self::redirecionar_com_erro( $base, $normalizado->get_error_message() );
        }

        // Monta o plano consolidado
        $plano = self::construir_plano( $normalizado, $opcoes, $token, sanitize_file_name( $arquivo['name'] ) );

        // Erro na montagem do plano
        if ( is_wp_error( $plano ) ) {
            // Remove o arquivo temporário
            @unlink( $normalizado );
            // Volta com a mensagem
            self::redirecionar_com_erro( $base, $plano->get_error_message() );
        }

        // Grava o plano em disco
        if ( ! self::salvar_plano( $plano ) ) {
            self::redirecionar_com_erro( $base, 'Não foi possível gravar o plano de importação na pasta de uploads.' );
        }

        // O CSV original não é mais necessário
        @unlink( $normalizado );

        // Redireciona para a tela de conferência
        wp_safe_redirect( add_query_arg( array( 'etapa' => 'conferir', 'token' => $token ), $base ) );
        exit;
    }

    /**
     * Redireciona de volta para a página exibindo uma mensagem de erro
     */
    private static function redirecionar_com_erro( $base, $mensagem ) {
        // Monta a URL com a mensagem
        wp_safe_redirect( add_query_arg( 'erro_importador', rawurlencode( $mensagem ), $base ) );
        // Encerra a execução
        exit;
    }

    /**
     * Registra uma linha no relatório da importação
     */
    private static function registrar_relatorio( $token, $linha ) {
        // Caminho do relatório
        $arq = self::caminho_plano( $token, 'relatorio.csv' );
        // Token inválido
        if ( '' === $arq ) {
            return;
        }
        // Cria o cabeçalho na primeira gravação
        $novo = ! file_exists( $arq );
        // Abre em modo de acréscimo
        $fp = fopen( $arq, 'a' );
        // Falha de abertura
        if ( ! $fp ) {
            return;
        }
        // Escreve o BOM e o cabeçalho quando o arquivo é novo
        if ( $novo ) {
            fwrite( $fp, "\xEF\xBB\xBF" );
            fputcsv( $fp, array( 'matricula', 'colaborador', 'acao', 'resultado', 'dependentes', 'observacoes' ), ';' );
        }
        // Escreve a linha
        fputcsv( $fp, $linha, ';' );
        // Fecha o arquivo
        fclose( $fp );
    }

    /**
     * Envia o relatório de uma importação para download
     */
    private static function baixar_relatorio( $token ) {
        // Caminho do relatório
        $arq = self::caminho_plano( $token, 'relatorio.csv' );

        // Relatório inexistente
        if ( '' === $arq || ! file_exists( $arq ) ) {
            wp_die( 'Relatório não encontrado ou já expirado.' );
        }

        // Cabeçalhos de download
        nocache_headers();
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="relatorio-importacao-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
        header( 'Content-Length: ' . filesize( $arq ) );

        // Envia o conteúdo
        readfile( $arq );
        exit;
    }

    /* =====================================================================
     * GRAVACAO NO BANCO
     * ===================================================================== */

    /**
     * Converte um valor livre de Sim/Não para o formato usado pelos campos do tema
     */
    private static function normalizar_sim_nao( $valor ) {
        // Gera a chave comparável
        $k = self::chave( $valor );
        // Valores considerados afirmativos
        if ( in_array( $k, array( 's', 'sim', 'y', 'yes', '1', 'true', 'verdadeiro' ), true ) ) {
            return 'Sim';
        }
        // Valores considerados negativos
        if ( in_array( $k, array( 'n', 'nao', 'no', '0', 'false', 'falso' ), true ) ) {
            return 'Não';
        }
        // Valor não reconhecido
        return '';
    }

    /**
     * Gera um e-mail único para o colaborador quando o CSV não trouxer um
     */
    private static function gerar_email( $matricula, $dominio, $id_atual ) {
        // Base do e-mail a partir da matrícula
        $base = strtolower( preg_replace( '/[^a-zA-Z0-9._-]/', '', $matricula ) );
        // Matrícula sem caracteres válidos
        if ( '' === $base ) {
            $base = 'colaborador' . wp_rand( 1000, 9999 );
        }
        // Primeira tentativa
        $email = $base . '@' . $dominio;
        // Contador de tentativas
        $tentativa = 1;
        // Procura um e-mail livre
        while ( ( $dono = email_exists( $email ) ) && (int) $dono !== (int) $id_atual ) {
            // Gera a próxima variação
            $email = $base . '.' . $tentativa . '@' . $dominio;
            // Avança o contador
            $tentativa++;
            // Trava de segurança
            if ( $tentativa > 50 ) {
                $email = $base . '.' . wp_rand( 10000, 99999 ) . '@' . $dominio;
                break;
            }
        }
        // Devolve o e-mail escolhido
        return $email;
    }

    /**
     * Define em qual slot (1 a 4) cada dependente do CSV será gravado
     * Para usuários já existentes, o dependente é ancorado pelo NOME para não
     * embaralhar quem já enviou desenho; os demais ocupam o primeiro slot livre
     */
    private static function distribuir_slots( $user_id, $dependentes ) {
        // Mapa final: posição no CSV => slot no banco
        $destino = array();
        // Slots já ocupados por esta distribuição
        $ocupados = array();
        // Nomes que já existem no perfil do usuário
        $existentes = array();

        // Usuário já cadastrado: lê os nomes gravados em cada slot
        if ( $user_id > 0 ) {
            // Percorre os slots do tema
            for ( $s = 1; $s <= self::max_dep(); $s++ ) {
                // Lê o nome gravado
                $nome_slot = get_user_meta( $user_id, 'user_field_dependente_' . $s . '_nome', true );
                // Indexa pela chave normalizada quando houver nome
                if ( '' !== self::limpar( $nome_slot ) ) {
                    $existentes[ self::chave_nome( $nome_slot ) ] = $s;
                }
            }
        }

        // Primeira passada: fixa os dependentes que já existem no perfil
        foreach ( $dependentes as $pos => $dep ) {
            // Procura o nome entre os slots já gravados
            if ( isset( $existentes[ $dep['chave'] ] ) && ! in_array( $existentes[ $dep['chave'] ], $ocupados, true ) ) {
                // Reaproveita o mesmo slot
                $destino[ $pos ] = $existentes[ $dep['chave'] ];
                // Marca como ocupado
                $ocupados[] = $existentes[ $dep['chave'] ];
            }
        }

        // Segunda passada: distribui os novos dependentes nos slots livres, em ordem
        foreach ( $dependentes as $pos => $dep ) {
            // Já resolvido na primeira passada
            if ( isset( $destino[ $pos ] ) ) {
                continue;
            }
            // Procura o menor slot livre
            for ( $s = 1; $s <= self::max_dep(); $s++ ) {
                // Slot disponível
                if ( ! in_array( $s, $ocupados, true ) ) {
                    // Reserva o slot
                    $destino[ $pos ] = $s;
                    $ocupados[] = $s;
                    break;
                }
            }
        }

        // Ordena pela posição original do CSV
        ksort( $destino );

        // Devolve o mapa de destino
        return $destino;
    }

    /**
     * Grava (cria ou atualiza) um registro consolidado no banco
     */
    public static function importar_registro( $reg, $opcoes ) {
        // Estrutura de retorno
        $saida = array(
            'matricula'   => isset( $reg['matricula'] ) ? $reg['matricula'] : '',
            'colaborador' => isset( $reg['nome'] ) ? $reg['nome'] : '',
            'acao'        => isset( $reg['acao'] ) ? $reg['acao'] : 'criar',
            'resultado'   => '',
            'dependentes' => 0,
            'mensagens'   => array(),
        );

        // Matrícula limpa que vira o login
        $login = self::limpar( $reg['matricula'] );

        // Sem login não há como prosseguir
        if ( '' === $login ) {
            $saida['resultado'] = 'erro';
            $saida['mensagens'][] = 'Matrícula vazia.';
            return $saida;
        }

        // Reconfere a existência do usuário no momento da gravação
        $usuario = get_user_by( 'login', $login );
        // ID atual (0 quando não existe)
        $user_id = $usuario ? (int) $usuario->ID : 0;
        // Ação realmente executada
        $acao = $user_id > 0 ? 'atualizar' : 'criar';
        // Atualiza a ação de saída
        $saida['acao'] = $acao;

        // Respeita o modo escolhido pelo administrador
        if ( 'criar' === $opcoes['modo'] && 'atualizar' === $acao ) {
            $saida['resultado'] = 'pulado';
            $saida['mensagens'][] = 'Já existia e o modo selecionado é "somente criar".';
            return $saida;
        }
        if ( 'atualizar' === $opcoes['modo'] && 'criar' === $acao ) {
            $saida['resultado'] = 'pulado';
            $saida['mensagens'][] = 'Não existe e o modo selecionado é "somente atualizar".';
            return $saida;
        }

        // Nome do colaborador formatado
        $nome_colab = '' !== self::limpar( $reg['nome'] ) ? self::limpar( $reg['nome'] ) : $login;

        // Define a senha conforme a opção escolhida.
        // A origem é registrada em $origem_senha para entrar no relatório: sem isso
        // o administrador não tinha como saber que a senha caiu na matrícula.
        if ( 'coluna' === $opcoes['senha_tipo'] && '' !== self::limpar( $reg['senha'] ) ) {
            $senha = self::limpar( $reg['senha'] );
            $origem_senha = 'coluna do CSV';
        } elseif ( 'fixa' === $opcoes['senha_tipo'] && '' !== $opcoes['senha_fixa'] ) {
            $senha = $opcoes['senha_fixa'];
            $origem_senha = 'senha fixa';
        } else {
            $senha = $login;
            // Explica por que caiu na matrícula, que é o caso que mais confundia
            if ( 'coluna' === $opcoes['senha_tipo'] ) {
                $origem_senha = 'matrícula (a coluna senha veio vazia nesta linha)';
            } elseif ( 'fixa' === $opcoes['senha_tipo'] ) {
                $origem_senha = 'matrícula (nenhuma senha fixa foi informada)';
            } else {
                $origem_senha = 'matrícula';
            }
        }

        // Define o e-mail: prioriza o que veio no CSV
        $email_csv = self::limpar( $reg['email'] );
        // Valida o e-mail informado
        if ( '' !== $email_csv && is_email( $email_csv ) ) {
            // Verifica se já pertence a outro usuário
            $dono = email_exists( $email_csv );
            if ( $dono && (int) $dono !== $user_id ) {
                // Gera um e-mail alternativo e avisa
                $email = self::gerar_email( $login, $opcoes['dominio'], $user_id );
                $saida['mensagens'][] = 'O e-mail "' . $email_csv . '" já pertence a outro usuário; foi usado "' . $email . '".';
            } else {
                $email = $email_csv;
            }
        } else {
            // Gera o e-mail padrão baseado na matrícula
            $email = self::gerar_email( $login, $opcoes['dominio'], $user_id );
            // Avisa quando o CSV trouxe um e-mail inválido
            if ( '' !== $email_csv ) {
                $saida['mensagens'][] = 'E-mail "' . $email_csv . '" é inválido; foi gerado "' . $email . '".';
            }
        }

        // Cria o usuário quando ainda não existe
        if ( 0 === $user_id ) {
            // Monta os dados de inserção
            $novo_id = wp_insert_user( array(
                'user_login'   => $login,
                'user_pass'    => $senha,
                'user_email'   => $email,
                'display_name' => $nome_colab,
                'first_name'   => $nome_colab,
                'nickname'     => $login,
                'role'         => 'subscriber',
            ) );

            // Falha na criação
            if ( is_wp_error( $novo_id ) ) {
                $saida['resultado'] = 'erro';
                $saida['mensagens'][] = 'Falha ao criar o usuário: ' . $novo_id->get_error_message();
                return $saida;
            }

            // Guarda o ID gerado
            $user_id = (int) $novo_id;

            // Informa a origem da senha gravada, para o relatório da importação
            $saida['mensagens'][] = 'Senha definida pela ' . $origem_senha . '.';

            // Valores iniciais dos campos de controle do tema
            update_user_meta( $user_id, 'user_field_senha_alterada', 'Não' );
            update_user_meta( $user_id, 'user_field_leitura_reg', 'Não' );
            update_user_meta( $user_id, 'user_field_votacao', 'Não' );
            update_user_meta( $user_id, 'user_field_comissao', 'Não' );

        } else {
            // Atualiza os dados básicos do usuário existente
            $dados_update = array(
                'ID'           => $user_id,
                'display_name' => $nome_colab,
                'user_email'   => $email,
            );
            // Redefine a senha somente quando autorizado
            if ( ! empty( $opcoes['atualizar_senha'] ) ) {
                $dados_update['user_pass'] = $senha;
            }
            // Executa a atualização
            $res = wp_update_user( $dados_update );
            // Falha na atualização
            if ( is_wp_error( $res ) ) {
                $saida['resultado'] = 'erro';
                $saida['mensagens'][] = 'Falha ao atualizar o usuário: ' . $res->get_error_message();
                return $saida;
            }
            // Atualiza o primeiro nome
            update_user_meta( $user_id, 'first_name', $nome_colab );
            // Ao trocar a senha o colaborador precisa alterá-la no primeiro acesso
            if ( ! empty( $opcoes['atualizar_senha'] ) ) {
                update_user_meta( $user_id, 'user_field_senha_alterada', 'Não' );
                // Registra a troca no relatório
                $saida['mensagens'][] = 'Senha redefinida pela ' . $origem_senha . '.';
            } else {
                // Deixa explícito que a senha antiga foi mantida de propósito
                $saida['mensagens'][] = 'Senha mantida (a opção "Redefinir a senha de quem já existe" está desmarcada).';
            }
        }

        // Grava o grupo do funcionário
        if ( '' !== self::limpar( $reg['grupo_meta'] ) ) {
            update_user_meta( $user_id, 'user_field_funcionario_grupo', self::limpar( $reg['grupo_meta'] ) );
        }

        // Grava a unidade quando resolvida
        if ( ! empty( $reg['unidade_id'] ) ) {
            update_user_meta( $user_id, 'user_field_unidade', (int) $reg['unidade_id'] );
        }

        // Grava home office quando informado
        $ho = self::normalizar_sim_nao( isset( $reg['homeoffice'] ) ? $reg['homeoffice'] : '' );
        if ( '' !== $ho ) {
            update_user_meta( $user_id, 'user_field_homeoffice', $ho );
        }

        // Grava comissão quando informada
        $com = self::normalizar_sim_nao( isset( $reg['comissao'] ) ? $reg['comissao'] : '' );
        if ( '' !== $com ) {
            update_user_meta( $user_id, 'user_field_comissao', $com );
        }

        // Descobre em qual slot cada dependente será gravado
        $dependentes = isset( $reg['dependentes'] ) ? $reg['dependentes'] : array();
        $destino = self::distribuir_slots( $user_id, $dependentes );

        // Slots efetivamente usados nesta importação
        $usados = array();

        // Grava cada dependente no slot correspondente
        foreach ( $dependentes as $pos => $dep ) {
            // Slot indisponível (não deveria ocorrer por causa do limite na análise)
            if ( ! isset( $destino[ $pos ] ) ) {
                $saida['mensagens'][] = 'Dependente "' . $dep['nome'] . '" não coube em nenhum slot livre.';
                continue;
            }

            // Número do slot escolhido
            $slot = (int) $destino[ $pos ];
            // Prefixo dos metadados deste slot
            $prefixo = 'user_field_dependente_' . $slot . '_';

            // Grava o nome do dependente
            update_user_meta( $user_id, $prefixo . 'nome', self::limpar( $dep['nome'] ) );

            // Grava a idade quando informada
            if ( '' !== $dep['idade'] ) {
                update_user_meta( $user_id, $prefixo . 'idade', (string) (int) $dep['idade'] );
            }

            // Grava a categoria concatenada "<ID da categoria>.<ID do grupo>"
            if ( '' !== $dep['cat_meta'] ) {
                update_user_meta( $user_id, $prefixo . 'cat', $dep['cat_meta'] );
            } else {
                // Registra o motivo de a categoria não ter sido gravada
                $saida['mensagens'][] = 'Dependente ' . $slot . ' ("' . $dep['nome'] . '") ficou SEM categoria.';
            }

            // Marca o slot como utilizado
            $usados[] = $slot;
            // Contabiliza o dependente gravado
            $saida['dependentes']++;
        }

        // Limpa os slots que não vieram no CSV, quando o administrador pediu
        if ( ! empty( $opcoes['limpar_slots'] ) ) {
            // Percorre todos os slots do tema
            for ( $s = 1; $s <= self::max_dep(); $s++ ) {
                // Ignora os que acabaram de ser preenchidos
                if ( in_array( $s, $usados, true ) ) {
                    continue;
                }
                // Prefixo do slot
                $prefixo = 'user_field_dependente_' . $s . '_';
                // Slot já vazio não precisa de limpeza
                if ( '' === self::limpar( get_user_meta( $user_id, $prefixo . 'nome', true ) ) ) {
                    continue;
                }
                // SALVAGUARDA: nunca apaga um dependente que já enviou desenho
                if ( 'Sim' === get_user_meta( $user_id, $prefixo . 'desenho', true ) ) {
                    $saida['mensagens'][] = 'Slot ' . $s . ' preservado: o dependente já enviou desenho.';
                    continue;
                }
                // Remove os dados residuais do slot
                delete_user_meta( $user_id, $prefixo . 'nome' );
                delete_user_meta( $user_id, $prefixo . 'idade' );
                delete_user_meta( $user_id, $prefixo . 'cat' );
                delete_user_meta( $user_id, $prefixo . 'desenho' );
                delete_user_meta( $user_id, $prefixo . 'pcd' );
                // Informa a limpeza
                $saida['mensagens'][] = 'Slot ' . $s . ' foi limpo por não constar no CSV.';
            }
        }

        // Repassa os problemas detectados na análise
        if ( ! empty( $reg['erros'] ) ) {
            $saida['mensagens'] = array_merge( $saida['mensagens'], $reg['erros'] );
        }

        // Define o resultado final
        $saida['resultado'] = ( 'criar' === $acao ) ? 'criado' : 'atualizado';
        // Guarda o ID gravado
        $saida['user_id'] = $user_id;

        // Devolve o resultado
        return $saida;
    }

    /* =====================================================================
     * AJAX: EXECUCAO EM LOTES
     * ===================================================================== */

    /**
     * Processa um lote de registros do plano
     */
    public static function ajax_importar_lote() {
        // Confere o nonce
        check_ajax_referer( self::NONCE, 'nonce' );

        // Confere a permissão
        if ( ! self::pode_operar() ) {
            wp_send_json_error( array( 'message' => 'Permissão negada.' ) );
        }

        // Lê os parâmetros
        $token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
        $inicio = isset( $_POST['inicio'] ) ? max( 0, (int) $_POST['inicio'] ) : 0;

        // Carrega o plano
        $plano = self::ler_plano( $token );

        // Plano inválido ou expirado
        if ( ! $plano ) {
            wp_send_json_error( array( 'message' => 'Plano de importação não encontrado ou expirado. Envie o arquivo novamente.' ) );
        }

        // Tamanho do lote definido no envio
        $tamanho = isset( $plano['opcoes']['lote'] ) ? (int) $plano['opcoes']['lote'] : 40;

        // Lê a fatia de registros a processar
        $registros = self::ler_registros( $token, $plano['offsets'], $inicio, $tamanho );

        // Contadores do lote
        $resumo = array(
            'criados'     => 0,
            'atualizados' => 0,
            'pulados'     => 0,
            'erros'       => 0,
            'dependentes' => 0,
        );

        // Linhas de log devolvidas para a tela
        $log = array();

        // Processa cada registro do lote
        foreach ( $registros as $reg ) {
            // Executa a gravação
            $res = self::importar_registro( $reg, $plano['opcoes'] );

            // Atualiza os contadores
            if ( 'criado' === $res['resultado'] ) {
                $resumo['criados']++;
            } elseif ( 'atualizado' === $res['resultado'] ) {
                $resumo['atualizados']++;
            } elseif ( 'pulado' === $res['resultado'] ) {
                $resumo['pulados']++;
            } else {
                $resumo['erros']++;
            }

            // Soma os dependentes gravados
            $resumo['dependentes'] += (int) $res['dependentes'];

            // Grava a linha no relatório em disco
            self::registrar_relatorio( $token, array(
                $res['matricula'],
                $res['colaborador'],
                $res['acao'],
                $res['resultado'],
                $res['dependentes'],
                implode( ' | ', $res['mensagens'] ),
            ) );

            // Monta a linha de log da tela
            $log[] = array(
                'tipo'      => ( 'erro' === $res['resultado'] ) ? 'danger' : ( ( 'pulado' === $res['resultado'] ) ? 'warning' : 'success' ),
                'matricula' => $res['matricula'],
                'nome'      => $res['colaborador'],
                'texto'     => strtoupper( $res['resultado'] ) . ' - ' . $res['dependentes'] . ' dependente(s)'
                                . ( ! empty( $res['mensagens'] ) ? ' - ' . implode( ' | ', array_slice( $res['mensagens'], 0, 3 ) ) : '' ),
            );
        }

        // Devolve o resultado do lote
        wp_send_json_success( array(
            'processados' => count( $registros ),
            'resumo'      => $resumo,
            'log'         => $log,
            'fim'         => ( $inicio + count( $registros ) ) >= (int) $plano['total_reg'] || empty( $registros ),
        ) );
    }

    /* =====================================================================
     * INTERFACE ADMINISTRATIVA
     * ===================================================================== */

    /**
     * Renderiza a página do importador
     */
    public static function render_admin_page() {
        // Bloqueia quem não tem permissão
        if ( ! self::pode_operar() ) {
            wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'explode' ) );
        }

        // Etapa atual do fluxo
        $etapa = isset( $_GET['etapa'] ) ? sanitize_key( $_GET['etapa'] ) : 'enviar';
        // Token do plano em conferência
        $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
        // Plano carregado quando estamos conferindo
        $plano = ( 'conferir' === $etapa && '' !== $token ) ? self::ler_plano( $token ) : null;
        // Mensagem de erro vinda do redirecionamento
        $erro = isset( $_GET['erro_importador'] ) ? sanitize_text_field( wp_unslash( $_GET['erro_importador'] ) ) : '';
        // URL base da página
        $base = admin_url( 'admin.php?page=' . self::MENU_SLUG );
        // Nonce usado nos formulários e no AJAX
        $nonce = wp_create_nonce( self::NONCE );
        // Termos disponíveis para exibição na tabela de referência
        $termos = self::carregar_termos( true );

        // Abre o container e imprime o estilo
        echo '<div class="wrap explode-imp-wrap">';
        self::imprimir_estilo();

        // Cabeçalho da página
        ?>
        <div class="expi-hero">
            <div class="expi-hero-left">
                <div class="expi-hero-badge"><span class="dashicons dashicons-database-import"></span> Módulo Administrativo Oficial</div>
                <h1 class="expi-hero-title">Importação Inteligente de Usuários</h1>
                <p class="expi-hero-desc">
                    Cadastre colaboradores e dependentes a partir de uma planilha CSV. Quando a mesma
                    <strong>matrícula</strong> aparece em mais de uma linha, o importador move automaticamente o
                    dependente para o <strong>slot 2</strong>, depois para o <strong>slot 3</strong>, levando junto
                    o <strong>nome</strong>, a <strong>idade</strong> e a <strong>categoria</strong> corretos.
                </p>
            </div>
            <div class="expi-hero-right">
                <a class="expi-btn expi-btn-ghost" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'acao', 'modelo', $base ), self::NONCE ) ); ?>">
                    <span class="dashicons dashicons-download"></span> Baixar modelo CSV
                </a>
                <a class="expi-btn expi-btn-secondary" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
                    <span class="dashicons dashicons-admin-users"></span> Usuários do WordPress
                </a>
            </div>
        </div>
        <?php

        // Exibe erro de processamento quando houver
        if ( '' !== $erro ) {
            echo '<div class="expi-alert expi-alert-danger"><span class="dashicons dashicons-warning"></span><div><strong>Não foi possível continuar:</strong> ' . esc_html( $erro ) . '</div></div>';
        }

        // Avisa quando o token da conferência não é mais válido
        if ( 'conferir' === $etapa && ! $plano ) {
            echo '<div class="expi-alert expi-alert-warning"><span class="dashicons dashicons-clock"></span><div><strong>Análise expirada.</strong> Envie o arquivo novamente para conferir os dados.</div></div>';
        }

        // Renderiza a etapa correspondente
        if ( $plano ) {
            self::render_conferencia( $plano, $base, $nonce );
        } else {
            self::render_formulario( $base, $nonce, $termos );
        }

        // Fecha o container
        echo '</div>';
    }

    /**
     * Tela 1: formulário de envio, documentação do modelo e tabela de referência
     */
    private static function render_formulario( $base, $nonce, $termos ) {
        ?>
        <div class="expi-grid-2">

            <!-- CARTAO DE ENVIO -->
            <div class="expi-card">
                <div class="expi-card-head">
                    <div class="expi-card-icon icon-teal"><span class="dashicons dashicons-upload"></span></div>
                    <div>
                        <h2 class="expi-card-title">1. Enviar a planilha</h2>
                        <p class="expi-card-sub">Nenhum dado é gravado agora: primeiro você confere o resultado da análise.</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( $base ); ?>">
                    <?php wp_nonce_field( self::NONCE ); ?>
                    <input type="hidden" name="explode_importador_enviar" value="1">

                    <div class="expi-card-body">

                        <div class="expi-field">
                            <label class="expi-label" for="arquivo_csv">Arquivo CSV</label>
                            <input class="expi-input" type="file" name="arquivo_csv" id="arquivo_csv" accept=".csv,.txt" required>
                            <p class="expi-help">Aceita separador <code>;</code>, <code>,</code> ou TAB e acentuação UTF-8 ou ISO-8859-1. Limite de <?php echo esc_html( size_format( self::MAX_UPLOAD ) ); ?>.</p>
                        </div>

                        <div class="expi-field-row">
                            <div class="expi-field">
                                <label class="expi-label" for="modo">O que fazer com as matrículas</label>
                                <select class="expi-input" name="modo" id="modo">
                                    <option value="ambos">Criar as novas e atualizar as existentes (recomendado)</option>
                                    <option value="criar">Somente criar as que ainda não existem</option>
                                    <option value="atualizar">Somente atualizar as que já existem</option>
                                </select>
                            </div>
                            <div class="expi-field">
                                <label class="expi-label" for="lote">Registros por lote</label>
                                <select class="expi-input" name="lote" id="lote">
                                    <option value="20">20 (servidores lentos)</option>
                                    <option value="40" selected>40 (padrão)</option>
                                    <option value="80">80</option>
                                    <option value="120">120 (servidores rápidos)</option>
                                </select>
                            </div>
                        </div>

                        <div class="expi-field-row">
                            <div class="expi-field">
                                <label class="expi-label" for="senha_tipo">Senha inicial do colaborador</label>
                                <select class="expi-input" name="senha_tipo" id="senha_tipo">
                                    <option value="matricula">A própria matrícula (padrão do concurso)</option>
                                    <option value="coluna">A coluna "senha" da planilha</option>
                                    <option value="fixa">Uma senha fixa para todos</option>
                                </select>
                            </div>
                            <div class="expi-field">
                                <label class="expi-label" for="senha_fixa">Senha fixa (se aplicável)</label>
                                <input class="expi-input" type="text" name="senha_fixa" id="senha_fixa" placeholder="Ex.: Honda2026">
                            </div>
                        </div>

                        <div class="expi-field">
                            <label class="expi-label" for="dominio">Domínio para gerar e-mails automáticos</label>
                            <input class="expi-input" type="text" name="dominio" id="dominio" value="<?php echo esc_attr( self::DOMINIO_PADRAO ); ?>">
                            <p class="expi-help">Usado quando a planilha não traz e-mail. O endereço fica <code>matricula@dominio</code>.</p>
                        </div>

                        <div class="expi-checks">
                            <label class="expi-check">
                                <input type="checkbox" name="padronizar_grupo" checked>
                                <span><strong>Padronizar o nome do grupo</strong> — grava "Grupo 4" mesmo que a planilha traga "GRUPO 4" ou "grupo-4".</span>
                            </label>
                            <label class="expi-check">
                                <input type="checkbox" name="cat_por_idade" checked>
                                <span><strong>Deduzir a categoria pela idade</strong> — Padrão: A: 4-6, B: 7-9, C: 10-11 | Grupos MAO: A: 0-3, B: 4-6, C: 7-9, D: 10-11</span>
                            </label>
                            <label class="expi-check">
                                <input type="checkbox" name="limpar_slots">
                                <span><strong>Limpar dependentes antigos que não vierem na planilha</strong> — quem já enviou desenho nunca é apagado.</span>
                            </label>
                            <label class="expi-check">
                                <input type="checkbox" name="atualizar_senha">
                                <span><strong>Redefinir a senha de quem já existe</strong> — marque apenas para reiniciar o ciclo de acessos.</span>
                            </label>
                            <label class="expi-check">
                                <input type="checkbox" name="criar_termos">
                                <span><strong>Criar grupos, categorias e unidades que não existirem</strong> — deixe desmarcado para que a planilha seja conferida contra o que já está cadastrado.</span>
                            </label>
                        </div>

                    </div>

                    <div class="expi-card-foot">
                        <button type="submit" class="expi-btn expi-btn-primary">
                            <span class="dashicons dashicons-search"></span> Analisar planilha
                        </button>
                        <span class="expi-foot-note">A gravação só acontece na próxima tela, após a sua conferência.</span>
                    </div>
                </form>
            </div>

            <!-- CARTAO DE DOCUMENTACAO -->
            <div class="expi-card">
                <div class="expi-card-head">
                    <div class="expi-card-icon icon-navy"><span class="dashicons dashicons-media-spreadsheet"></span></div>
                    <div>
                        <h2 class="expi-card-title">2. Como preencher o CSV</h2>
                        <p class="expi-card-sub">Baixe o modelo e mantenha os nomes das colunas.</p>
                    </div>
                </div>
                <div class="expi-card-body">
                    <table class="expi-table expi-table-doc">
                        <thead>
                            <tr><th>Coluna</th><th>Obrigatória</th><th>O que preencher</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>matricula</code></td><td><span class="expi-tag expi-tag-red">Sim</span></td><td>Vira o <strong>login</strong> do colaborador. É a chave que agrupa as linhas repetidas.</td></tr>
                            <tr><td><code>nome_colaborador</code></td><td><span class="expi-tag expi-tag-red">Sim</span></td><td>Nome do funcionário.</td></tr>
                            <tr><td><code>grupo</code></td><td><span class="expi-tag expi-tag-red">Sim</span></td><td>"Grupo 1" a "Grupo 10". Entra na concatenação da categoria.</td></tr>
                            <tr><td><code>email</code></td><td><span class="expi-tag">Não</span></td><td>Se vazio, é gerado como <code>matricula@dominio</code>.</td></tr>
                            <tr><td><code>senha</code></td><td><span class="expi-tag">Não</span></td><td>Só é usada se você escolher a opção "coluna senha".</td></tr>
                            <tr><td><code>unidade</code></td><td><span class="expi-tag">Não</span></td><td>Nome exato da unidade já cadastrada.</td></tr>
                            <tr><td><code>dependente_1_nome</code></td><td><span class="expi-tag expi-tag-red">Sim</span></td><td>Nome da criança. Sem nome, a linha não gera dependente.</td></tr>
                            <tr><td><code>dependente_1_idade</code></td><td><span class="expi-tag">Não</span></td><td>Apenas o número. Ex.: <code>8</code>.</td></tr>
                            <tr><td><code>dependente_1_categoria</code></td><td><span class="expi-tag expi-tag-amber">Quase</span></td><td>Aceita <code>A</code>, <code>B</code>, <code>C</code>, <code>D</code> ou "Categoria A". Se vazia, pode ser deduzida pela idade.</td></tr>
                            <tr><td><code>dependente_2_*</code> até <code>dependente_<?php echo esc_html( self::max_dep() ); ?>_*</code></td><td><span class="expi-tag">Não</span></td><td>Use quando os dependentes vierem na mesma linha. O tema aceita <?php echo esc_html( self::max_dep() ); ?> dependentes por colaborador.</td></tr>
                        </tbody>
                    </table>

                    <div class="expi-alert expi-alert-info" style="margin-top:16px;">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <div>
                            <strong>As duas formas funcionam.</strong> Você pode colocar os três dependentes na mesma linha
                            <em>ou</em> repetir a matrícula em três linhas com apenas as colunas do dependente 1 preenchidas.
                            No segundo caso o importador move sozinho o 2º nome/idade/categoria para as colunas do dependente 2
                            e o 3º para as do dependente 3.
                        </div>
                    </div>

                    <h3 class="expi-sub-title">Como a categoria é gravada</h3>
                    <p class="expi-help">
                        O campo <code>user_field_dependente_N_cat</code> recebe a concatenação
                        <code>ID&nbsp;da&nbsp;categoria</code> + <code>.</code> + <code>ID&nbsp;do&nbsp;grupo</code>.
                        Exemplo: categoria <strong>B</strong> (ID <?php echo esc_html( isset( $termos['categorias']['b'] ) ? $termos['categorias']['b']['id'] : '3' ); ?>)
                        com <strong>Grupo 4</strong> (ID <?php echo esc_html( isset( $termos['grupos']['grupo_4'] ) ? $termos['grupos']['grupo_4']['id'] : '37' ); ?>)
                        resulta em
                        <code><?php echo esc_html( ( isset( $termos['categorias']['b'] ) ? $termos['categorias']['b']['id'] : '3' ) . '.' . ( isset( $termos['grupos']['grupo_4'] ) ? $termos['grupos']['grupo_4']['id'] : '37' ) ); ?></code>.
                    </p>

                    <div class="expi-ref-grid">
                        <div>
                            <h4 class="expi-ref-title">Categorias cadastradas</h4>
                            <table class="expi-table expi-table-mini">
                                <thead><tr><th>Nome</th><th>ID</th></tr></thead>
                                <tbody>
                                <?php foreach ( $termos['lista_cat'] as $item ) : ?>
                                    <tr><td><?php echo esc_html( $item['nome'] ); ?></td><td><code><?php echo esc_html( $item['id'] ); ?></code></td></tr>
                                <?php endforeach; ?>
                                <?php if ( empty( $termos['lista_cat'] ) ) : ?>
                                    <tr><td colspan="2">Nenhuma categoria cadastrada.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 class="expi-ref-title">Grupos cadastrados</h4>
                            <table class="expi-table expi-table-mini">
                                <thead><tr><th>Nome</th><th>ID</th></tr></thead>
                                <tbody>
                                <?php foreach ( $termos['lista_grp'] as $item ) : ?>
                                    <tr><td><?php echo esc_html( $item['nome'] ); ?></td><td><code><?php echo esc_html( $item['id'] ); ?></code></td></tr>
                                <?php endforeach; ?>
                                <?php if ( empty( $termos['lista_grp'] ) ) : ?>
                                    <tr><td colspan="2">Nenhum grupo cadastrado.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- EXEMPLO VISUAL DA CONSOLIDACAO -->
        <div class="expi-card">
            <div class="expi-card-head">
                <div class="expi-card-icon icon-cyan"><span class="dashicons dashicons-randomize"></span></div>
                <div>
                    <h2 class="expi-card-title">O que o importador faz com matrículas repetidas</h2>
                    <p class="expi-card-sub">Exemplo real do modelo CSV que você pode baixar acima.</p>
                </div>
            </div>
            <div class="expi-card-body expi-exemplo">
                <div class="expi-exemplo-col">
                    <h4 class="expi-ref-title">Planilha enviada</h4>
                    <table class="expi-table expi-table-mini">
                        <thead><tr><th>matricula</th><th>dependente_1_nome</th><th>idade</th><th>cat</th></tr></thead>
                        <tbody>
                            <tr><td>41207</td><td>PEDRO ALVES</td><td>5</td><td>A</td></tr>
                            <tr><td>41207</td><td>LUCAS ALVES</td><td>9</td><td>B</td></tr>
                            <tr><td>41207</td><td>JULIA ALVES</td><td>11</td><td>C</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="expi-exemplo-seta"><span class="dashicons dashicons-arrow-right-alt"></span></div>
                <div class="expi-exemplo-col">
                    <h4 class="expi-ref-title">Gravado no perfil da matrícula 41207</h4>
                    <table class="expi-table expi-table-mini">
                        <thead><tr><th>Slot</th><th>Nome</th><th>Idade</th><th>Categoria gravada</th></tr></thead>
                        <tbody>
                            <tr><td><strong>1</strong></td><td>PEDRO ALVES</td><td>5</td><td><code>2.41</code> (A + Grupo 8)</td></tr>
                            <tr><td><strong>2</strong></td><td>LUCAS ALVES</td><td>9</td><td><code>3.41</code> (B + Grupo 8)</td></tr>
                            <tr><td><strong>3</strong></td><td>JULIA ALVES</td><td>11</td><td><code>4.41</code> (C + Grupo 8)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Monta a tabela de conferência com os registros consolidados
     */
    private static function render_tabela_preview( $lista, $vazio ) {
        // Nada a exibir
        if ( empty( $lista ) ) {
            echo '<p class="expi-help">' . esc_html( $vazio ) . '</p>';
            return;
        }
        ?>
        <div class="expi-table-scroll">
            <table class="expi-table expi-table-preview">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Colaborador</th>
                        <th>Grupo</th>
                        <th>Linhas<br><span class="expi-th-sub">no CSV</span></th>
                        <?php for ( $s_cab = 1; $s_cab <= self::max_dep(); $s_cab++ ) : ?>
                            <th>Dependente <?php echo esc_html( $s_cab ); ?></th>
                        <?php endfor; ?>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $lista as $reg ) : ?>
                    <tr class="<?php echo ! empty( $reg['erros'] ) ? 'linha-erro' : ''; ?>">
                        <td><strong><?php echo esc_html( $reg['matricula'] ); ?></strong></td>
                        <td><?php echo esc_html( $reg['nome'] ); ?></td>
                        <td>
                            <?php if ( ! empty( $reg['grupo_id'] ) ) : ?>
                                <?php echo esc_html( $reg['grupo_nome'] ); ?> <code><?php echo esc_html( $reg['grupo_id'] ); ?></code>
                            <?php else : ?>
                                <span class="expi-tag expi-tag-red">não resolvido</span>
                            <?php endif; ?>
                        </td>
                        <td class="expi-center">
                            <?php if ( (int) $reg['qtd_linhas'] > 1 ) : ?>
                                <span class="expi-tag expi-tag-cyan" title="Linhas <?php echo esc_attr( implode( ', ', $reg['linhas'] ) ); ?>"><?php echo esc_html( $reg['qtd_linhas'] ); ?>x</span>
                            <?php else : ?>
                                <?php echo esc_html( $reg['qtd_linhas'] ); ?>
                            <?php endif; ?>
                        </td>
                        <?php for ( $s = 1; $s <= self::max_dep(); $s++ ) :
                            // Localiza o dependente que ficou neste slot
                            $dep = null;
                            foreach ( $reg['dependentes'] as $d ) {
                                if ( (int) $d['slot'] === $s ) {
                                    $dep = $d;
                                    break;
                                }
                            }
                            ?>
                            <td class="expi-dep-cell">
                                <?php if ( $dep ) : ?>
                                    <span class="expi-dep-nome"><?php echo esc_html( $dep['nome'] ); ?></span>
                                    <span class="expi-dep-meta">
                                        <?php echo '' !== $dep['idade'] ? esc_html( $dep['idade'] . ' anos' ) : '<em>sem idade</em>'; ?>
                                    </span>
                                    <span class="expi-dep-meta">
                                        <?php if ( '' !== $dep['cat_meta'] ) : ?>
                                            <code><?php echo esc_html( $dep['cat_meta'] ); ?></code>
                                            <?php echo esc_html( $dep['cat_nome'] ); ?>
                                            <?php if ( ! empty( $dep['deduzida'] ) ) : ?>
                                                <span class="expi-tag expi-tag-amber" title="Deduzida pela idade">idade</span>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="expi-tag expi-tag-red">sem categoria</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else : ?>
                                    <span class="expi-dep-vazio">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td>
                            <?php if ( 'criar' === $reg['acao'] ) : ?>
                                <span class="expi-tag expi-tag-green">criar</span>
                            <?php else : ?>
                                <span class="expi-tag expi-tag-blue">atualizar</span>
                            <?php endif; ?>
                            <?php if ( ! empty( $reg['erros'] ) ) : ?>
                                <div class="expi-msg expi-msg-erro"><?php echo esc_html( implode( ' ', array_slice( $reg['erros'], 0, 2 ) ) ); ?></div>
                            <?php endif; ?>
                            <?php if ( ! empty( $reg['avisos'] ) ) : ?>
                                <div class="expi-msg expi-msg-aviso"><?php echo esc_html( implode( ' ', array_slice( $reg['avisos'], 0, 2 ) ) ); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Tela 2: conferência do plano e execução da importação
     */
    private static function render_conferencia( $plano, $base, $nonce ) {
        // Atalho para os contadores
        $c = $plano['contadores'];
        // Colunas reconhecidas para exibir ao administrador
        $mapa = $plano['mapa_colunas'];
        // URL para reiniciar o processo
        $url_reiniciar = $base;
        // URL do relatório
        $url_relatorio = wp_nonce_url( add_query_arg( array( 'acao' => 'relatorio', 'token' => $plano['token'] ), $base ), self::NONCE );
        ?>
        <div class="expi-alert expi-alert-info">
            <span class="dashicons dashicons-visibility"></span>
            <div>
                <strong>Nada foi gravado ainda.</strong> Esta é a simulação do arquivo
                <code><?php echo esc_html( $plano['arquivo'] ); ?></code>
                (separador <code><?php echo esc_html( $plano['delimitador'] ); ?></code>).
                Confira as colunas abaixo e só então execute a importação.
            </div>
        </div>

        <!-- INDICADORES DA ANALISE -->
        <div class="expi-kpi-grid">
            <div class="expi-kpi"><span class="expi-kpi-label">Linhas lidas</span><strong class="expi-kpi-val"><?php echo esc_html( number_format_i18n( $plano['total_linhas'] ) ); ?></strong><span class="expi-kpi-sub"><?php echo esc_html( number_format_i18n( $plano['linhas_vazias'] ) ); ?> em branco</span></div>
            <div class="expi-kpi"><span class="expi-kpi-label">Matrículas únicas</span><strong class="expi-kpi-val"><?php echo esc_html( number_format_i18n( $c['total'] ) ); ?></strong><span class="expi-kpi-sub"><?php echo esc_html( number_format_i18n( $c['duplicadas'] ) ); ?> vieram repetidas</span></div>
            <div class="expi-kpi"><span class="expi-kpi-label">Usuários a criar</span><strong class="expi-kpi-val text-green"><?php echo esc_html( number_format_i18n( $c['novos'] ) ); ?></strong><span class="expi-kpi-sub">ainda não existem</span></div>
            <div class="expi-kpi"><span class="expi-kpi-label">Usuários a atualizar</span><strong class="expi-kpi-val text-blue"><?php echo esc_html( number_format_i18n( $c['atualizar'] ) ); ?></strong><span class="expi-kpi-sub">matrícula já cadastrada</span></div>
            <div class="expi-kpi"><span class="expi-kpi-label">Dependentes</span><strong class="expi-kpi-val"><?php echo esc_html( number_format_i18n( $c['dependentes'] ) ); ?></strong><span class="expi-kpi-sub">slot 1: <?php echo esc_html( $c['dep_slot'][1] ); ?> &middot; 2: <?php echo esc_html( $c['dep_slot'][2] ); ?> &middot; 3: <?php echo esc_html( $c['dep_slot'][3] ); ?> &middot; 4: <?php echo esc_html( $c['dep_slot'][4] ); ?></span></div>
            <div class="expi-kpi"><span class="expi-kpi-label">Com pendência</span><strong class="expi-kpi-val <?php echo $c['com_erro'] > 0 ? 'text-red' : 'text-green'; ?>"><?php echo esc_html( number_format_i18n( $c['com_erro'] ) ); ?></strong><span class="expi-kpi-sub"><?php echo esc_html( number_format_i18n( $c['sem_categoria'] ) ); ?> sem categoria &middot; <?php echo esc_html( number_format_i18n( $c['sem_grupo'] ) ); ?> sem grupo</span></div>
        </div>

        <?php if ( $c['com_erro'] > 0 ) : ?>
        <div class="expi-alert expi-alert-warning">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php echo esc_html( number_format_i18n( $c['com_erro'] ) ); ?> matrícula(s) com pendência.</strong>
                Elas ainda podem ser importadas, mas os dependentes sem categoria resolvida ficarão com o campo
                <code>cat</code> vazio e não conseguirão enviar desenho. Corrija a planilha ou cadastre os
                grupos/categorias faltantes antes de executar.
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $plano['dep_excedentes'] ) ) : ?>
        <div class="expi-alert expi-alert-danger">
            <span class="dashicons dashicons-dismiss"></span>
            <div><strong><?php echo esc_html( $plano['dep_excedentes'] ); ?> dependente(s) além do limite de <?php echo esc_html( self::max_dep() ); ?>.</strong> Eles não serão importados.</div>
        </div>
        <?php endif; ?>

        <?php
        // Diagnóstico das senhas, calculado na montagem do plano
        $sd = isset( $plano['senha_diag'] ) ? $plano['senha_diag'] : null;
        // Quantidade de colaboradores que já existem e serão atualizados
        $qtd_atualizar = isset( $c['atualizar'] ) ? (int) $c['atualizar'] : 0;
        ?>
        <?php if ( $sd ) : ?>
        <!-- SENHAS: o que exatamente vai acontecer com a senha de cada colaborador -->
        <div class="expi-card">
            <div class="expi-card-head">
                <div class="expi-card-icon icon-navy"><span class="dashicons dashicons-privacy"></span></div>
                <div>
                    <h2 class="expi-card-title">Senhas: o que vai acontecer</h2>
                    <p class="expi-card-sub">Confira antes de importar. Esta era a parte que falhava em silêncio.</p>
                </div>
            </div>
            <div class="expi-card-body">

                <?php if ( 'coluna' === $sd['tipo'] ) : ?>

                    <?php if ( ! $sd['coluna_ok'] ) : ?>
                        <div class="expi-alert expi-alert-danger">
                            <span class="dashicons dashicons-dismiss"></span>
                            <div>
                                <strong>Você escolheu usar a coluna "senha" da planilha, mas nenhuma coluna de senha foi reconhecida.</strong><br>
                                Todos os colaboradores receberiam a <strong>matrícula</strong> como senha.
                                Volte, renomeie a coluna para <code>senha</code> e envie o arquivo de novo.
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="expi-alert expi-alert-sucesso">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <div>
                                A senha virá da coluna <code><?php echo esc_html( $sd['coluna_nome'] ); ?></code>.
                                <?php if ( $sd['aproximada'] ) : ?>
                                    <br><strong>Atenção:</strong> essa coluna foi reconhecida por aproximação, e não pelo nome exato.
                                    Confira se é mesmo a coluna certa.
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ( $sd['vazias'] > 0 ) : ?>
                            <div class="expi-alert expi-alert-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <div>
                                    <strong><?php echo esc_html( number_format_i18n( $sd['vazias'] ) ); ?> matrícula(s) estão com a coluna de senha vazia.</strong>
                                    Essas receberão a própria matrícula como senha.
                                    As outras <?php echo esc_html( number_format_i18n( $sd['preenchidas'] ) ); ?> usarão a senha da planilha.
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php elseif ( 'fixa' === $sd['tipo'] ) : ?>

                    <?php if ( ! $sd['fixa_ok'] ) : ?>
                        <div class="expi-alert expi-alert-danger">
                            <span class="dashicons dashicons-dismiss"></span>
                            <div>
                                <strong>Você escolheu uma senha fixa, mas deixou o campo em branco.</strong><br>
                                Todos receberiam a matrícula como senha. Volte e preencha a senha fixa.
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="expi-alert expi-alert-sucesso">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <div>Todos os colaboradores receberão a mesma senha fixa informada no formulário.</div>
                        </div>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="expi-alert expi-alert-sucesso">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <div>
                            A senha de cada colaborador será a <strong>própria matrícula</strong>.
                            <?php if ( $sd['coluna_ok'] ) : ?>
                                <br><strong>Observação:</strong> a sua planilha tem a coluna
                                <code><?php echo esc_html( $sd['coluna_nome'] ); ?></code> preenchida em
                                <?php echo esc_html( number_format_i18n( $sd['preenchidas'] ) ); ?> matrícula(s), mas ela
                                <u>não será usada</u> porque a opção escolhida foi "a própria matrícula".
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $qtd_atualizar > 0 ) : ?>
                    <?php if ( empty( $plano['opcoes']['atualizar_senha'] ) ) : ?>
                        <div class="expi-alert expi-alert-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <div>
                                <strong><?php echo esc_html( number_format_i18n( $qtd_atualizar ) ); ?> colaborador(es) já existem e terão a senha MANTIDA.</strong>
                                Os demais dados deles serão atualizados normalmente. Para trocar também a senha,
                                volte e marque <em>"Redefinir a senha de quem já existe"</em>.
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="expi-alert expi-alert-sucesso">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <div>
                                <strong><?php echo esc_html( number_format_i18n( $qtd_atualizar ) ); ?> colaborador(es) já existem e terão a senha REDEFINIDA</strong>,
                                porque a opção "Redefinir a senha de quem já existe" está marcada.
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>

        <!-- COLUNAS RECONHECIDAS -->
        <div class="expi-card">
            <div class="expi-card-head">
                <div class="expi-card-icon icon-navy"><span class="dashicons dashicons-editor-table"></span></div>
                <div>
                    <h2 class="expi-card-title">Colunas reconhecidas na sua planilha</h2>
                    <p class="expi-card-sub">Confira se cada informação foi lida da coluna certa.</p>
                </div>
            </div>
            <div class="expi-card-body">
                <div class="expi-chips">
                    <?php foreach ( $mapa['colaborador'] as $campo => $indice ) : ?>
                        <span class="expi-chip"><strong><?php echo esc_html( $campo ); ?></strong> &larr; <?php echo esc_html( $mapa['rotulos'][ $indice ] ); ?></span>
                    <?php endforeach; ?>
                    <?php foreach ( $mapa['dependentes'] as $slot => $campos ) : ?>
                        <?php foreach ( $campos as $campo => $indice ) : ?>
                            <span class="expi-chip expi-chip-dep"><strong>dep <?php echo esc_html( $slot ); ?> / <?php echo esc_html( $campo ); ?></strong> &larr; <?php echo esc_html( $mapa['rotulos'][ $indice ] ); ?></span>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ( ! empty( $mapa['aproximadas'] ) ) : ?>
                    <div class="expi-alert expi-alert-warning" style="margin-top:14px;">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong>Colunas reconhecidas por aproximação.</strong>
                            O nome não era exato, então o importador deduziu pelo conteúdo do título.
                            Confira se cada uma está certa:
                            <ul style="margin:8px 0 0 18px;list-style:disc;">
                                <?php foreach ( $mapa['aproximadas'] as $campo_ap => $rotulo_ap ) : ?>
                                    <li><strong><?php echo esc_html( $campo_ap ); ?></strong> &larr; <code><?php echo esc_html( $rotulo_ap ); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $mapa['ignoradas'] ) ) : ?>
                    <p class="expi-help" style="margin-top:12px;">
                        <strong>Colunas ignoradas:</strong> <?php echo esc_html( implode( ', ', array_slice( $mapa['ignoradas'], 0, 30 ) ) ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- AMOSTRA DA CONSOLIDACAO -->
        <div class="expi-card">
            <div class="expi-card-head">
                <div class="expi-card-icon icon-cyan"><span class="dashicons dashicons-list-view"></span></div>
                <div>
                    <h2 class="expi-card-title">Conferência dos dependentes por coluna</h2>
                    <p class="expi-card-sub">Amostra do resultado final. Cada categoria aparece como <code>ID da categoria . ID do grupo</code>.</p>
                </div>
                <div class="expi-tabs">
                    <button type="button" class="expi-tab ativo" data-alvo="expi-tab-todas">Amostra geral</button>
                    <button type="button" class="expi-tab" data-alvo="expi-tab-dup">Somente matrículas repetidas (<?php echo esc_html( number_format_i18n( $c['duplicadas'] ) ); ?>)</button>
                </div>
            </div>
            <div class="expi-card-body">
                <!-- CAMPO DE PESQUISA POR MATRICULA -->
                <div class="expi-filtro-matricula" style="margin-bottom:14px;">
                    <label for="expi-busca-matricula" class="expi-label">Pesquisar por matrícula:</label>
                    <div style="display:flex;gap:8px;align-items:center;margin-top:4px;">
                        <span class="dashicons dashicons-search" style="color:#6b7280;font-size:18px;line-height:36px;"></span>
                        <input type="text" id="expi-busca-matricula" class="expi-input" placeholder="Digite a matrícula para filtrar..." style="max-width:340px;">
                        <button type="button" id="expi-limpar-busca" class="expi-btn expi-btn-ghost-dark" style="font-size:12px;padding:6px 12px;">Limpar</button>
                    </div>
                </div>

                <div id="expi-tab-todas" class="expi-tab-painel">
                    <?php self::render_tabela_preview( isset( $plano['preview'] ) ? $plano['preview'] : array(), 'Nenhum registro para exibir.' ); ?>
                </div>
                <div id="expi-tab-dup" class="expi-tab-painel" style="display:none;">
                    <?php self::render_tabela_preview( isset( $plano['preview_dup'] ) ? $plano['preview_dup'] : array(), 'Nenhuma matrícula apareceu em mais de uma linha neste arquivo.' ); ?>
                </div>
                <?php if ( $c['total'] > count( $plano['preview'] ) ) : ?>
                    <p class="expi-help" style="margin-top:10px;">Exibindo <?php echo esc_html( count( $plano['preview'] ) ); ?> de <?php echo esc_html( number_format_i18n( $c['total'] ) ); ?> matrículas. O relatório completo fica disponível depois da execução.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( ! empty( $plano['avisos'] ) ) : ?>
        <div class="expi-card">
            <div class="expi-card-head">
                <div class="expi-card-icon icon-amber"><span class="dashicons dashicons-info"></span></div>
                <div><h2 class="expi-card-title">Avisos da leitura do arquivo</h2></div>
            </div>
            <div class="expi-card-body">
                <ul class="expi-lista-avisos">
                    <?php foreach ( array_slice( $plano['avisos'], 0, 100 ) as $a ) : ?>
                        <li><?php echo esc_html( $a ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- EXECUCAO -->
        <div class="expi-card expi-card-exec">
            <div class="expi-card-head">
                <div class="expi-card-icon icon-teal"><span class="dashicons dashicons-database-add"></span></div>
                <div>
                    <h2 class="expi-card-title">3. Executar a importação</h2>
                    <p class="expi-card-sub">
                        Serão processadas <strong><?php echo esc_html( number_format_i18n( $c['total'] ) ); ?></strong> matrículas
                        em lotes de <strong><?php echo esc_html( (int) $plano['opcoes']['lote'] ); ?></strong>.
                        Não feche esta página durante o processo.
                    </p>
                </div>
            </div>
            <div class="expi-card-body">
                <div class="expi-exec-acoes">
                    <button type="button" id="expi-executar" class="expi-btn expi-btn-primary">
                        <span class="dashicons dashicons-yes"></span> Gravar no banco de dados
                    </button>
                    <a href="<?php echo esc_url( $url_reiniciar ); ?>" class="expi-btn expi-btn-ghost-dark">
                        <span class="dashicons dashicons-undo"></span> Enviar outra planilha
                    </a>
                </div>

                <div id="expi-progresso" style="display:none;">
                    <div class="expi-bar"><div class="expi-bar-fill" id="expi-bar-fill"></div></div>
                    <div class="expi-progresso-info">
                        <span id="expi-progresso-txt">Iniciando...</span>
                        <span id="expi-progresso-pct">0%</span>
                    </div>
                    <div class="expi-contadores">
                        <span class="expi-cont"><em>Criados</em><strong id="expi-cont-criados">0</strong></span>
                        <span class="expi-cont"><em>Atualizados</em><strong id="expi-cont-atualizados">0</strong></span>
                        <span class="expi-cont"><em>Pulados</em><strong id="expi-cont-pulados">0</strong></span>
                        <span class="expi-cont"><em>Erros</em><strong id="expi-cont-erros">0</strong></span>
                        <span class="expi-cont"><em>Dependentes</em><strong id="expi-cont-dep">0</strong></span>
                    </div>
                    <div class="expi-log" id="expi-log"></div>
                </div>

                <div id="expi-final" class="expi-alert expi-alert-sucesso" style="display:none;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div>
                        <strong>Importação concluída.</strong>
                        <span id="expi-final-txt"></span>
                        <div style="margin-top:10px;">
                            <a href="<?php echo esc_url( $url_relatorio ); ?>" class="expi-btn expi-btn-secondary">
                                <span class="dashicons dashicons-media-spreadsheet"></span> Baixar relatório completo
                            </a>
                            <a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" class="expi-btn expi-btn-ghost-dark">
                                <span class="dashicons dashicons-admin-users"></span> Ver usuários
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        (function($){
            // Dados do plano em execução
            var token   = <?php echo wp_json_encode( $plano['token'] ); ?>;
            var nonce   = <?php echo wp_json_encode( $nonce ); ?>;
            var total   = <?php echo (int) $plano['total_reg']; ?>;
            var lote    = <?php echo (int) $plano['opcoes']['lote']; ?>;

            // Acumuladores da execução
            var acumulado = { criados:0, atualizados:0, pulados:0, erros:0, dependentes:0 };
            var processados = 0;

            // Alterna as abas de conferência
            $('.expi-tab').on('click', function(){
                var alvo = $(this).data('alvo');
                $('.expi-tab').removeClass('ativo');
                $(this).addClass('ativo');
                $('.expi-tab-painel').hide();
                $('#' + alvo).show();
            });

            // Pesquisa por matrícula na tabela de conferência
            $('#expi-busca-matricula').on('input', function(){
                var termo = $.trim($(this).val()).toUpperCase();
                // Filtra as linhas de todas as tabelas de preview
                $('.expi-table-preview tbody tr').each(function(){
                    // A matrícula está na primeira coluna da linha (td:first strong)
                    var matricula = $(this).find('td:first').text().toUpperCase().replace(/\s/g, '');
                    if (termo === '' || matricula.indexOf(termo) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Botão para limpar a busca por matrícula
            $('#expi-limpar-busca').on('click', function(){
                $('#expi-busca-matricula').val('').trigger('input');
            });

            // Acrescenta uma linha ao log da tela
            function logar(tipo, texto){
                var $log = $('#expi-log');
                $log.append('<div class="expi-log-item log-' + tipo + '">' + texto + '</div>');
                $log.scrollTop($log[0].scrollHeight);
            }

            // Atualiza a barra e os contadores
            function atualizarProgresso(){
                var pct = total > 0 ? Math.min(100, Math.round((processados / total) * 100)) : 100;
                $('#expi-bar-fill').css('width', pct + '%');
                $('#expi-progresso-pct').text(pct + '%');
                $('#expi-progresso-txt').text('Processando ' + processados + ' de ' + total + ' matrículas...');
                $('#expi-cont-criados').text(acumulado.criados);
                $('#expi-cont-atualizados').text(acumulado.atualizados);
                $('#expi-cont-pulados').text(acumulado.pulados);
                $('#expi-cont-erros').text(acumulado.erros);
                $('#expi-cont-dep').text(acumulado.dependentes);
            }

            // Processa um lote e encadeia o próximo
            function processarLote(inicio){
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'explode_importar_lote',
                        nonce: nonce,
                        token: token,
                        inicio: inicio
                    },
                    success: function(resposta){
                        // Erro devolvido pelo servidor
                        if (!resposta || !resposta.success) {
                            var msg = (resposta && resposta.data && resposta.data.message) ? resposta.data.message : 'Erro desconhecido.';
                            logar('danger', 'Falha: ' + msg);
                            $('#expi-progresso-txt').text('Importação interrompida.');
                            $('#expi-executar').prop('disabled', false);
                            return;
                        }

                        // Soma os contadores do lote
                        var d = resposta.data;
                        acumulado.criados     += d.resumo.criados;
                        acumulado.atualizados += d.resumo.atualizados;
                        acumulado.pulados     += d.resumo.pulados;
                        acumulado.erros       += d.resumo.erros;
                        acumulado.dependentes += d.resumo.dependentes;
                        processados += d.processados;

                        // Escreve as linhas do lote no log
                        $.each(d.log, function(i, item){
                            logar(item.tipo, '<strong>' + item.matricula + '</strong> ' + item.nome + ' — ' + item.texto);
                        });

                        // Atualiza a interface
                        atualizarProgresso();

                        // Continua ou encerra
                        if (d.fim || d.processados === 0) {
                            $('#expi-bar-fill').css('width', '100%');
                            $('#expi-progresso-pct').text('100%');
                            $('#expi-progresso-txt').text('Concluído.');
                            $('#expi-final-txt').text(
                                acumulado.criados + ' criados, ' +
                                acumulado.atualizados + ' atualizados, ' +
                                acumulado.pulados + ' pulados, ' +
                                acumulado.erros + ' com erro e ' +
                                acumulado.dependentes + ' dependentes gravados.'
                            );
                            $('#expi-final').show();
                        } else {
                            processarLote(inicio + d.processados);
                        }
                    },
                    error: function(){
                        logar('danger', 'Falha de conexão no lote iniciado em ' + inicio + '. Tentando novamente em 3 segundos...');
                        setTimeout(function(){ processarLote(inicio); }, 3000);
                    }
                });
            }

            // Dispara a execução
            $('#expi-executar').on('click', function(){
                if (!window.confirm('Confirmar a gravação de ' + total + ' matrículas no banco de dados?')) {
                    return;
                }
                $(this).prop('disabled', true);
                $('#expi-progresso').show();
                atualizarProgresso();
                processarLote(0);
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
            .explode-imp-wrap {
                --exp-navy: #143240;
                --exp-teal: #5b9b99;
                --exp-cyan: #54c5cf;
                --exp-mint: #dffdc2;
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
            .explode-imp-wrap * { box-sizing: border-box; }
            .explode-imp-wrap code { background: #eef2f5; color: #0b3040; padding: 1px 5px; border-radius: 4px; font-size: 12px; }

            /* HERO */
            .expi-hero {
                background: linear-gradient(135deg, var(--exp-navy) 0%, #1c4b61 50%, var(--exp-teal) 100%);
                border-radius: var(--exp-radius); padding: 30px 34px; color: #fff;
                display: flex; align-items: center; justify-content: space-between; gap: 24px;
                box-shadow: var(--exp-shadow); margin-bottom: 22px; position: relative; overflow: hidden;
            }
            .expi-hero::after {
                content: ""; position: absolute; right: -60px; top: -60px; width: 260px; height: 260px;
                background: radial-gradient(circle, rgba(84,197,207,.2) 0%, rgba(255,255,255,0) 70%);
                border-radius: 50%; pointer-events: none;
            }
            .expi-hero-left { max-width: 760px; }
            .expi-hero-badge {
                display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.15);
                border: 1px solid rgba(255,255,255,.25); padding: 4px 12px; border-radius: 20px;
                font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;
            }
            .expi-hero-title { color: #fff; font-size: 26px; line-height: 1.2; margin: 0 0 10px; font-weight: 700; }
            .expi-hero-desc { color: rgba(255,255,255,.9); font-size: 14px; line-height: 1.6; margin: 0; }
            .expi-hero-right { display: flex; flex-direction: column; gap: 10px; flex-shrink: 0; }

            /* BOTOES */
            .expi-btn {
                display: inline-flex; align-items: center; justify-content: center; gap: 7px;
                padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600;
                text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all .18s ease;
                line-height: 1.2;
            }
            .expi-btn .dashicons { font-size: 17px; width: 17px; height: 17px; }
            .expi-btn-primary { background: linear-gradient(90deg, var(--exp-teal), var(--exp-cyan)); color: #fff !important; }
            .expi-btn-primary:hover { filter: brightness(1.07); color: #fff !important; }
            .expi-btn-primary:disabled { opacity: .55; cursor: not-allowed; }
            .expi-btn-secondary { background: #fff; color: var(--exp-navy) !important; border-color: var(--exp-border); }
            .expi-btn-secondary:hover { border-color: var(--exp-cyan); color: var(--exp-navy) !important; }
            .expi-btn-ghost { background: rgba(255,255,255,.14); color: #fff !important; border-color: rgba(255,255,255,.3); }
            .expi-btn-ghost:hover { background: rgba(255,255,255,.24); color: #fff !important; }
            .expi-btn-ghost-dark { background: #fff; color: var(--exp-muted) !important; border-color: var(--exp-border); }
            .expi-btn-ghost-dark:hover { color: var(--exp-navy) !important; }

            /* ALERTAS */
            .expi-alert {
                display: flex; gap: 12px; align-items: flex-start; padding: 14px 18px;
                border-radius: var(--exp-radius); margin-bottom: 18px; font-size: 13.5px; line-height: 1.6;
                border-left: 4px solid var(--exp-cyan); background: #eef8fa;
            }
            .expi-alert .dashicons { flex-shrink: 0; margin-top: 1px; }
            .expi-alert-info { background: #eef8fa; border-left-color: var(--exp-cyan); }
            .expi-alert-warning { background: #fef3c7; border-left-color: var(--exp-warning); }
            .expi-alert-danger { background: #ffe4e6; border-left-color: var(--exp-danger); }
            .expi-alert-sucesso { background: #d1fae5; border-left-color: var(--exp-success); }

            /* CARTOES */
            .expi-grid-2 { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 20px; margin-bottom: 20px; }
            @media (max-width: 1400px) { .expi-grid-2 { grid-template-columns: 1fr; } }
            .expi-card {
                background: var(--exp-card); border: 1px solid var(--exp-border); border-radius: var(--exp-radius);
                box-shadow: var(--exp-shadow); margin-bottom: 20px; overflow: hidden;
            }
            .expi-card-head {
                display: flex; align-items: center; gap: 14px; padding: 18px 22px;
                border-bottom: 1px solid var(--exp-border); background: #fbfcfd; flex-wrap: wrap;
            }
            .expi-card-icon {
                width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
                justify-content: center; color: #fff; flex-shrink: 0;
            }
            .expi-card-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }
            .icon-teal { background: linear-gradient(135deg, var(--exp-teal), var(--exp-cyan)); }
            .icon-navy { background: linear-gradient(135deg, var(--exp-navy), #1c4b61); }
            .icon-cyan { background: linear-gradient(135deg, var(--exp-cyan), #7fd9e2); }
            .icon-amber { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
            .expi-card-title { margin: 0; font-size: 16px; font-weight: 700; color: var(--exp-navy); }
            .expi-card-sub { margin: 3px 0 0; font-size: 12.5px; color: var(--exp-muted); }
            .expi-card-body { padding: 22px; }
            .expi-card-foot {
                padding: 16px 22px; border-top: 1px solid var(--exp-border); background: #fbfcfd;
                display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
            }
            .expi-foot-note { font-size: 12px; color: var(--exp-muted); }
            .expi-sub-title { font-size: 14px; color: var(--exp-navy); margin: 22px 0 6px; }

            /* FORMULARIO */
            .expi-field { margin-bottom: 16px; }
            .expi-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
            @media (max-width: 782px) { .expi-field-row { grid-template-columns: 1fr; } }
            .expi-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--exp-navy); margin-bottom: 6px; }
            .expi-input {
                width: 100%; padding: 9px 12px; border: 1px solid var(--exp-border); border-radius: 8px;
                font-size: 13px; background: #fff; color: var(--exp-text); line-height: 1.4;
            }
            .expi-input:focus { border-color: var(--exp-cyan); outline: none; box-shadow: 0 0 0 3px rgba(84,197,207,.16); }
            .expi-help { font-size: 12px; color: var(--exp-muted); margin: 6px 0 0; line-height: 1.55; }
            .expi-checks { margin-top: 18px; border-top: 1px dashed var(--exp-border); padding-top: 16px; }
            .expi-check {
                display: flex; gap: 9px; align-items: flex-start; font-size: 12.5px; line-height: 1.55;
                color: var(--exp-text); margin-bottom: 11px; cursor: pointer;
            }
            .expi-check input { margin: 2px 0 0; flex-shrink: 0; }

            /* KPIS */
            .expi-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
            .expi-kpi {
                background: #fff; border: 1px solid var(--exp-border); border-radius: var(--exp-radius);
                padding: 16px 18px; box-shadow: var(--exp-shadow); display: flex; flex-direction: column; gap: 3px;
            }
            .expi-kpi-label { font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--exp-muted); font-weight: 600; }
            .expi-kpi-val { font-size: 26px; font-weight: 700; color: var(--exp-navy); line-height: 1.15; }
            .expi-kpi-sub { font-size: 11.5px; color: var(--exp-muted); }
            .text-green { color: var(--exp-success) !important; }
            .text-blue { color: #2563eb !important; }
            .text-red { color: var(--exp-danger) !important; }

            /* TABELAS */
            .expi-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
            .expi-table th {
                text-align: left; padding: 9px 10px; background: #f4f7f9; color: var(--exp-navy);
                font-weight: 700; border-bottom: 1px solid var(--exp-border); font-size: 11.5px;
                text-transform: uppercase; letter-spacing: .3px; vertical-align: bottom;
            }
            .expi-table td { padding: 9px 10px; border-bottom: 1px solid #f0f2f4; vertical-align: top; line-height: 1.5; }
            .expi-table tbody tr:hover { background: #fafcfd; }
            .expi-table-doc td:first-child { white-space: nowrap; }
            .expi-table-mini th, .expi-table-mini td { padding: 6px 8px; font-size: 12px; }
            .expi-th-sub { font-weight: 400; text-transform: none; letter-spacing: 0; font-size: 10px; color: var(--exp-muted); }
            .expi-table-scroll { overflow-x: auto; border: 1px solid var(--exp-border); border-radius: 8px; }
            .expi-table-preview tr.linha-erro { background: #fff5f6; }
            .expi-table-preview tr.linha-erro:hover { background: #ffecef; }
            .expi-center { text-align: center; }
            .expi-dep-cell { min-width: 165px; }
            .expi-dep-nome { display: block; font-weight: 600; color: var(--exp-navy); }
            .expi-dep-meta { display: block; font-size: 11.5px; color: var(--exp-muted); margin-top: 2px; }
            .expi-dep-vazio { color: #cbd5e1; }
            .expi-msg { font-size: 11px; line-height: 1.45; margin-top: 4px; }
            .expi-msg-erro { color: var(--exp-danger); }
            .expi-msg-aviso { color: #b45309; }
            .expi-lista-avisos { margin: 0; padding-left: 18px; font-size: 12.5px; color: var(--exp-muted); line-height: 1.7; max-height: 260px; overflow-y: auto; }

            /* ETIQUETAS E CHIPS */
            .expi-tag {
                display: inline-block; padding: 1px 7px; border-radius: 20px; font-size: 10.5px;
                font-weight: 700; background: #eef2f5; color: var(--exp-muted); text-transform: uppercase; letter-spacing: .3px;
            }
            .expi-tag-red { background: #ffe4e6; color: #be123c; }
            .expi-tag-green { background: #d1fae5; color: #047857; }
            .expi-tag-blue { background: #dbeafe; color: #1d4ed8; }
            .expi-tag-cyan { background: #cffafe; color: #0e7490; }
            .expi-tag-amber { background: #fef3c7; color: #b45309; }
            .expi-chips { display: flex; flex-wrap: wrap; gap: 8px; }
            .expi-chip {
                display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px; border-radius: 20px;
                background: #f4f7f9; border: 1px solid var(--exp-border); font-size: 11.5px; color: var(--exp-text);
            }
            .expi-chip strong { color: var(--exp-navy); }
            .expi-chip-dep { background: #eef8fa; border-color: #cdeef2; }

            /* REFERENCIA E EXEMPLO */
            .expi-ref-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 14px; }
            @media (max-width: 782px) { .expi-ref-grid { grid-template-columns: 1fr; } }
            .expi-ref-title { font-size: 12px; text-transform: uppercase; letter-spacing: .4px; color: var(--exp-muted); margin: 0 0 8px; }
            .expi-exemplo { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
            .expi-exemplo-col { flex: 1; min-width: 280px; }
            .expi-exemplo-seta { color: var(--exp-cyan); flex-shrink: 0; }
            .expi-exemplo-seta .dashicons { font-size: 30px; width: 30px; height: 30px; }

            /* ABAS */
            .expi-tabs { margin-left: auto; display: flex; gap: 8px; }
            .expi-tab {
                padding: 7px 14px; border-radius: 20px; border: 1px solid var(--exp-border); background: #fff;
                font-size: 12px; font-weight: 600; color: var(--exp-muted); cursor: pointer; transition: all .18s ease;
            }
            .expi-tab:hover { border-color: var(--exp-cyan); }
            .expi-tab.ativo { background: var(--exp-navy); border-color: var(--exp-navy); color: #fff; }

            /* EXECUCAO */
            .expi-card-exec { border-color: #cdeef2; }
            .expi-exec-acoes { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 4px; }
            .expi-bar { height: 12px; background: #eef2f5; border-radius: 20px; overflow: hidden; margin: 20px 0 8px; }
            .expi-bar-fill {
                height: 100%; width: 0; border-radius: 20px; transition: width .3s ease;
                background: linear-gradient(90deg, var(--exp-teal), var(--exp-cyan));
            }
            .expi-progresso-info { display: flex; justify-content: space-between; font-size: 12.5px; color: var(--exp-muted); font-weight: 600; }
            .expi-contadores { display: flex; gap: 10px; flex-wrap: wrap; margin: 16px 0; }
            .expi-cont {
                display: flex; flex-direction: column; gap: 2px; padding: 9px 16px; border-radius: 8px;
                background: #f4f7f9; border: 1px solid var(--exp-border); min-width: 104px;
            }
            .expi-cont em { font-style: normal; font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--exp-muted); font-weight: 600; }
            .expi-cont strong { font-size: 19px; color: var(--exp-navy); }
            .expi-log {
                max-height: 320px; overflow-y: auto; background: #0b1d26; border-radius: 8px; padding: 14px;
                font-family: Consolas, Monaco, "Courier New", monospace; font-size: 11.5px; line-height: 1.7; color: #cbd5e1;
            }
            .expi-log-item { padding: 1px 0; border-bottom: 1px solid rgba(255,255,255,.05); }
            .expi-log-item strong { color: #fff; }
            .expi-log-item.log-success { color: #6ee7b7; }
            .expi-log-item.log-warning { color: #fcd34d; }
            .expi-log-item.log-danger { color: #fda4af; }
        </style>
        <?php
    }
}

// Inicializa a classe
Explode_Admin_User_Importer::init();
