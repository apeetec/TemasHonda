<?php
/**
 * Regras de negócio específicas dos grupos MAO (Manaus)
 * Tema: Explode Criação
 *
 * Grupos alcançados: "Grupo 1 MAO" até "Grupo 9 MAO"
 *
 * REGRA 1 - LAUDO DE PCD
 * Quando o colaborador de um grupo MAO marca que o dependente é PCD, passa a ser
 * obrigatório anexar o laudo e aceitar o Termo de Uso de Imagem e Voz. O laudo é
 * gravado como anexo do próprio post do desenho (mesmo post_parent), de modo que
 * desenho e laudo ficam sempre juntos.
 *
 * REGRA 2 - CATEGORIA A SEM VOTAÇÃO
 * A Categoria A dos grupos MAO é decidida por sorteio presencial na fábrica.
 * Por isso os desenhos dessa categoria não entram na votação desses grupos e o
 * número de desenhos que o colaborador precisa escolher cai de 3 para 2.
 *
 * Estas regras NÃO afetam nenhum outro grupo do concurso.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Segurança
}

/* =====================================================================
 * OPCOES CONFIGURAVEIS NO PAINEL
 *
 * As tres regras abaixo eram fixas no codigo. Agora ficam em
 * "Opções gerais" > "Regras dos grupos MAO", no wp-admin, e podem ser
 * ligadas e desligadas pelo administrador quando a empresa mudar de ideia.
 *
 * O padrao de todas e "nao", que reproduz o comportamento pedido hoje:
 * sem cadastro de dependentes pelo colaborador, sem laudo obrigatorio e
 * sem aceite de termo obrigatorio.
 * ===================================================================== */

/** Nome da option onde a pagina de opcoes gerais grava os campos */
if ( ! defined( 'EXPLODE_OPCOES_GERAIS_OPTION' ) ) {
    define( 'EXPLODE_OPCOES_GERAIS_OPTION', 'opcoes_gerais_box' );
}

/**
 * Le um campo da pagina "Opções gerais" devolvendo o padrao quando nao houver valor
 */
if ( ! function_exists( 'explode_mao_opcao' ) ) {
    function explode_mao_opcao( $chave, $padrao = 'nao' ) {
        // Todos os campos da pagina ficam em uma unica option, em formato de array
        $opcoes = get_option( EXPLODE_OPCOES_GERAIS_OPTION );
        // Option ainda inexistente ou corrompida
        if ( ! is_array( $opcoes ) ) {
            return $padrao;
        }
        // Campo nunca salvo
        if ( ! isset( $opcoes[ $chave ] ) || '' === $opcoes[ $chave ] ) {
            return $padrao;
        }
        // Valor gravado pelo administrador
        return $opcoes[ $chave ];
    }
}

/**
 * Indica se o colaborador dos grupos MAO cadastra os proprios dependentes
 */
if ( ! function_exists( 'explode_mao_cadastro_dependentes_ativo' ) ) {
    function explode_mao_cadastro_dependentes_ativo() {
        // Le a escolha do administrador, desligada por padrao
        $ativo = ( 'sim' === explode_mao_opcao( 'opcoes_mao_cadastro_dependentes', 'nao' ) );
        // Permite forcar o valor por codigo, se algum dia for preciso
        return (bool) apply_filters( 'explode_mao_cadastro_dependentes_ativo', $ativo );
    }
}

/**
 * Indica se o laudo e obrigatorio quando o dependente MAO e marcado como PCD
 */
if ( ! function_exists( 'explode_mao_exige_laudo' ) ) {
    function explode_mao_exige_laudo() {
        // Le a escolha do administrador, desligada por padrao
        $exige = ( 'sim' === explode_mao_opcao( 'opcoes_mao_exigir_laudo', 'nao' ) );
        // Permite forcar o valor por codigo
        return (bool) apply_filters( 'explode_mao_exige_laudo', $exige );
    }
}

/**
 * Indica se o aceite do Termo de Uso de Imagem e Voz e obrigatorio para PCD
 */
if ( ! function_exists( 'explode_mao_exige_termo' ) ) {
    function explode_mao_exige_termo() {
        // Le a escolha do administrador, desligada por padrao
        $exige = ( 'sim' === explode_mao_opcao( 'opcoes_mao_exigir_termo', 'nao' ) );
        // Permite forcar o valor por codigo
        return (bool) apply_filters( 'explode_mao_exige_termo', $exige );
    }
}

/**
 * Indica se o bloco de PCD dos grupos MAO tem alguma exigencia a mostrar
 * Quando nenhuma das duas regras esta ligada, o bloco inteiro deixa de existir
 */
if ( ! function_exists( 'explode_mao_bloco_pcd_ativo' ) ) {
    function explode_mao_bloco_pcd_ativo() {
        // Basta uma das exigencias estar ligada
        return explode_mao_exige_laudo() || explode_mao_exige_termo();
    }
}

/**
 * Slug da categoria que fica fora da votação nos grupos MAO
 */
if ( ! function_exists( 'explode_mao_categoria_bloqueada' ) ) {
    function explode_mao_categoria_bloqueada() {
        // Categoria A é a sorteada presencialmente na fábrica
        return 'categoria-a';
    }
}

/**
 * Texto oficial exibido junto ao campo de upload do laudo
 */
if ( ! function_exists( 'explode_mao_texto_laudo' ) ) {
    function explode_mao_texto_laudo() {
        // Mensagem definida pelo serviço social
        return 'O laudo será validado pelo serviço social, caso haja descordância, o participante concorrerá automaticamente na categoria geral.';
    }
}

/**
 * Normaliza um texto para comparação (minúsculo, sem acento, separado por underline)
 */
if ( ! function_exists( 'explode_mao_chave' ) ) {
    function explode_mao_chave( $texto ) {
        // Remove a acentuação
        $k = remove_accents( (string) $texto );
        // Converte para minúsculas
        $k = strtolower( $k );
        // Troca o que não for letra ou número por underline
        $k = preg_replace( '/[^a-z0-9]+/', '_', $k );
        // Remove underlines das pontas
        return trim( (string) $k, '_' );
    }
}

/**
 * Verifica se um grupo (nome ou slug) pertence à regra MAO
 * Aceita "Grupo 1 MAO", "GRUPO 1 MAO", "grupo-1-mao" e variações
 */
if ( ! function_exists( 'explode_grupo_e_mao' ) ) {
    function explode_grupo_e_mao( $grupo ) {
        // Normaliza o valor recebido
        $k = explode_mao_chave( $grupo );
        // Valor vazio nunca é MAO
        if ( '' === $k ) {
            return false;
        }
        // Precisa ser um grupo e conter o marcador MAO como palavra isolada
        return ( 0 === strpos( $k, 'grupo' ) ) && (bool) preg_match( '/(^|_)mao(_|$)/', $k );
    }
}

/**
 * Devolve a lista de grupos MAO informados no cadastro do colaborador
 * O meta user_field_funcionario_grupo aceita mais de um grupo separado por vírgula
 */
if ( ! function_exists( 'explode_mao_grupos_do_usuario' ) ) {
    function explode_mao_grupos_do_usuario( $user_id = 0 ) {
        // Usa o usuário logado quando nenhum for informado
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        // Sem usuário não há grupo
        if ( $user_id < 1 ) {
            return array();
        }

        // Lê o grupo gravado no perfil
        $meta = get_user_meta( $user_id, 'user_field_funcionario_grupo', true );
        // Sem grupo definido
        if ( '' === trim( (string) $meta ) ) {
            return array();
        }

        // Separa os grupos informados
        $partes = preg_split( '/\s*[,;\|\r\n]+\s*/u', (string) $meta, -1, PREG_SPLIT_NO_EMPTY );
        // Guarda apenas os que são MAO
        $mao = array();
        // Percorre cada parte
        foreach ( (array) $partes as $parte ) {
            // Limpa o texto
            $texto = trim( $parte );
            // Guarda quando for um grupo MAO
            if ( explode_grupo_e_mao( $texto ) ) {
                $mao[] = $texto;
            }
        }

        // Devolve a lista encontrada
        return $mao;
    }
}

/**
 * Indica se o colaborador está sujeito às regras dos grupos MAO
 */
if ( ! function_exists( 'explode_usuario_e_mao' ) ) {
    function explode_usuario_e_mao( $user_id = 0 ) {
        // Basta ter ao menos um grupo MAO no cadastro
        return ! empty( explode_mao_grupos_do_usuario( $user_id ) );
    }
}

/**
 * Quantidade de desenhos que o colaborador precisa escolher na votação
 * Grupos MAO escolhem 2 porque a Categoria A sai por sorteio presencial
 */
if ( ! function_exists( 'explode_mao_max_votos' ) ) {
    function explode_mao_max_votos( $user_id = 0 ) {
        // Dois votos para MAO e três para os demais grupos
        return explode_usuario_e_mao( $user_id ) ? 2 : 3;
    }
}

/**
 * Indica se um desenho deve ficar fora da votação do colaborador
 *
 * @param array $slugs_categorias Slugs dos termos do desenho
 * @param int   $user_id          Colaborador que está votando
 */
if ( ! function_exists( 'explode_mao_desenho_sem_voto' ) ) {
    function explode_mao_desenho_sem_voto( $slugs_categorias, $user_id = 0 ) {
        // A regra só existe para colaboradores dos grupos MAO
        if ( ! explode_usuario_e_mao( $user_id ) ) {
            return false;
        }
        // Sem categorias não há como avaliar
        if ( empty( $slugs_categorias ) || ! is_array( $slugs_categorias ) ) {
            return false;
        }
        // Bloqueia quando o desenho estiver na categoria sorteada presencialmente
        return in_array( explode_mao_categoria_bloqueada(), $slugs_categorias, true );
    }
}

/**
 * Extensões aceitas para o arquivo do laudo
 */
if ( ! function_exists( 'explode_mao_extensoes_laudo' ) ) {
    function explode_mao_extensoes_laudo() {
        // Documento digitalizado em PDF ou foto do laudo
        return array( 'pdf', 'jpg', 'jpeg', 'png' );
    }
}

/**
 * Tamanho máximo aceito para o laudo (10 MB)
 */
if ( ! function_exists( 'explode_mao_tamanho_max_laudo' ) ) {
    function explode_mao_tamanho_max_laudo() {
        // Limite em bytes
        return 10485760;
    }
}

/* =====================================================================
 * CADASTRO DE DEPENDENTES PELO PROPRIO COLABORADOR (GRUPOS MAO)
 *
 * Nos grupos MAO o colaborador informa o nome e a idade de cada dependente na
 * propria tela de envio, podendo cadastrar mais de um. A categoria nao e digitada:
 * ela e deduzida da idade pelas faixas oficiais e concatenada com o grupo do
 * colaborador, no mesmo formato "<id da categoria>.<id do grupo>" usado pelo resto
 * do sistema. Nos demais grupos os dependentes continuam vindo da importacao.
 * ===================================================================== */

/**
 * Quantidade maxima de dependentes por colaborador, limite dos campos do tema
 */
if ( ! function_exists( 'explode_mao_max_dependentes' ) ) {
    function explode_mao_max_dependentes() {
        // O limite e o mesmo do resto do tema, definido em functions.php
        return function_exists( 'explode_max_dependentes' ) ? explode_max_dependentes() : 6;
    }
}

/**
 * Nome do primeiro grupo MAO do colaborador, usado para deduzir a categoria
 */
if ( ! function_exists( 'explode_mao_nome_grupo_do_usuario' ) ) {
    function explode_mao_nome_grupo_do_usuario( $user_id = 0 ) {
        // Reaproveita a leitura ja existente dos grupos MAO
        $grupos = explode_mao_grupos_do_usuario( $user_id );
        // Devolve o primeiro encontrado
        return ! empty( $grupos ) ? $grupos[0] : '';
    }
}

/**
 * ID do termo do grupo MAO do colaborador na taxonomia desenhos_cat
 */
if ( ! function_exists( 'explode_mao_id_grupo_do_usuario' ) ) {
    function explode_mao_id_grupo_do_usuario( $user_id = 0 ) {
        // Nome do grupo gravado no cadastro
        $nome = explode_mao_nome_grupo_do_usuario( $user_id );
        // Sem grupo nao ha termo
        if ( '' === $nome ) {
            return 0;
        }
        // Garante o mapa de termos
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }
        // Carrega os termos da taxonomia
        $termos = Explode_Admin_User_Importer::carregar_termos();
        // Chave normalizada do nome do grupo
        $chave  = Explode_Admin_User_Importer::chave( $nome );
        // Devolve o ID quando o termo existir
        return isset( $termos['grupos'][ $chave ] ) ? (int) $termos['grupos'][ $chave ]['id'] : 0;
    }
}

/**
 * Monta o valor do campo de categoria a partir da idade do dependente
 * Devolve "<id da categoria>.<id do grupo>" ou vazio quando nao for possivel
 */
if ( ! function_exists( 'explode_mao_cat_por_idade' ) ) {
    function explode_mao_cat_por_idade( $idade, $user_id = 0 ) {
        // Garante o importador carregado
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }

        // Nome do grupo, necessario porque as faixas dos grupos MAO sao proprias
        $nome_grupo = explode_mao_nome_grupo_do_usuario( $user_id );
        // Categoria deduzida pela idade
        $cat_nome   = Explode_Admin_User_Importer::categoria_por_idade( $idade, $nome_grupo );
        // Idade fora das faixas
        if ( '' === $cat_nome ) {
            return '';
        }

        // Termos da taxonomia
        $termos = Explode_Admin_User_Importer::carregar_termos();
        // Chave da categoria deduzida
        $chave  = Explode_Admin_User_Importer::chave( $cat_nome );
        // Categoria inexistente na taxonomia
        if ( ! isset( $termos['categorias'][ $chave ] ) ) {
            return '';
        }

        // ID do grupo do colaborador
        $grupo_id = explode_mao_id_grupo_do_usuario( $user_id );
        // Sem grupo nao ha como concatenar
        if ( $grupo_id < 1 ) {
            return '';
        }

        // Concatena no formato usado por todo o sistema
        return (int) $termos['categorias'][ $chave ]['id'] . '.' . $grupo_id;
    }
}

/**
 * Faixas etarias oferecidas ao colaborador, uma por categoria
 *
 * As faixas nao sao digitadas em lugar nenhum: elas sao descobertas percorrendo
 * as idades e perguntando a categoria_por_idade() qual categoria cada uma gera.
 * Assim, se as faixas mudarem naquela funcao, estas opcoes acompanham sozinhas.
 */
if ( ! function_exists( 'explode_mao_faixas_opcoes' ) ) {
    function explode_mao_faixas_opcoes( $user_id = 0 ) {
        // Garante o importador carregado
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }

        // Grupo do colaborador, porque os grupos MAO tem faixas proprias
        $nome_grupo = explode_mao_nome_grupo_do_usuario( $user_id );
        // Sem grupo usa um grupo MAO de referencia
        if ( '' === $nome_grupo ) {
            $nome_grupo = 'Grupo 1 MAO';
        }

        // Descobre o intervalo de cada categoria
        $intervalos = array();
        // Percorre as idades possiveis
        for ( $i = 0; $i <= 20; $i++ ) {
            // Categoria correspondente a esta idade
            $cat = Explode_Admin_User_Importer::categoria_por_idade( $i, $nome_grupo );
            // Idade fora das faixas
            if ( '' === $cat ) {
                continue;
            }
            // Abre o intervalo na primeira ocorrencia
            if ( ! isset( $intervalos[ $cat ] ) ) {
                $intervalos[ $cat ] = array( 'min' => $i, 'max' => $i );
            } else {
                // Estende o intervalo
                $intervalos[ $cat ]['max'] = $i;
            }
        }

        // Monta as opcoes exibidas no formulario
        $opcoes = array();
        // Percorre os intervalos encontrados
        foreach ( $intervalos as $cat => $faixa ) {
            // Identificador estavel da opcao
            $slug = sanitize_title( $cat );
            // Registro da opcao
            $opcoes[ $slug ] = array(
                'slug'      => $slug,
                'categoria' => $cat,
                'min'       => $faixa['min'],
                'max'       => $faixa['max'],
                'idade'     => $faixa['min'] . ' a ' . $faixa['max'],
                'descricao' => $faixa['min'] . ' a ' . $faixa['max'] . ' anos',
            );
        }

        // Devolve as opcoes prontas
        return $opcoes;
    }
}

/**
 * Monta o valor do campo de categoria a partir do NOME de uma categoria
 * Devolve "<id da categoria>.<id do grupo>" ou vazio quando nao for possivel
 */
if ( ! function_exists( 'explode_mao_montar_cat_meta' ) ) {
    function explode_mao_montar_cat_meta( $cat_nome, $user_id = 0 ) {
        // Categoria vazia nao gera valor
        if ( '' === trim( (string) $cat_nome ) ) {
            return '';
        }
        // Garante o importador carregado
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }

        // Termos da taxonomia
        $termos = Explode_Admin_User_Importer::carregar_termos();
        // Chave normalizada da categoria
        $chave  = Explode_Admin_User_Importer::chave( $cat_nome );
        // Categoria inexistente na taxonomia
        if ( ! isset( $termos['categorias'][ $chave ] ) ) {
            return '';
        }

        // ID do grupo do colaborador
        $grupo_id = explode_mao_id_grupo_do_usuario( $user_id );
        // Sem grupo nao ha como concatenar
        if ( $grupo_id < 1 ) {
            return '';
        }

        // Concatena no formato usado por todo o sistema
        return (int) $termos['categorias'][ $chave ]['id'] . '.' . $grupo_id;
    }
}

/**
 * Lista os dependentes ja cadastrados no perfil do colaborador
 */
if ( ! function_exists( 'explode_mao_listar_dependentes' ) ) {
    function explode_mao_listar_dependentes( $user_id = 0 ) {
        // Usa o usuario logado quando nenhum for informado
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        // Lista de retorno
        $lista = array();
        // Sem usuario nao ha dependentes
        if ( $user_id < 1 ) {
            return $lista;
        }

        // Percorre todos os slots do tema
        for ( $s = 1; $s <= explode_mao_max_dependentes(); $s++ ) {
            // Nome gravado neste slot
            $nome = trim( (string) get_user_meta( $user_id, 'user_field_dependente_' . $s . '_nome', true ) );
            // Slot vazio nao entra na lista
            if ( '' === $nome ) {
                continue;
            }
            // Monta o registro do dependente
            $lista[ $s ] = array(
                'slot'     => $s,
                'nome'     => $nome,
                'idade'    => trim( (string) get_user_meta( $user_id, 'user_field_dependente_' . $s . '_idade', true ) ),
                'cat'      => trim( (string) get_user_meta( $user_id, 'user_field_dependente_' . $s . '_cat', true ) ),
                'desenho'  => 'Sim' === get_user_meta( $user_id, 'user_field_dependente_' . $s . '_desenho', true ),
            );
        }

        // Devolve os dependentes encontrados
        return $lista;
    }
}

/**
 * Numero do proximo slot livre, ou zero quando o limite ja foi atingido
 */
if ( ! function_exists( 'explode_mao_proximo_slot_livre' ) ) {
    function explode_mao_proximo_slot_livre( $user_id = 0 ) {
        // Usa o usuario logado quando nenhum for informado
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        // Sem usuario nao ha slot
        if ( $user_id < 1 ) {
            return 0;
        }
        // Procura o primeiro slot sem nome
        for ( $s = 1; $s <= explode_mao_max_dependentes(); $s++ ) {
            // Nome gravado neste slot
            $nome = trim( (string) get_user_meta( $user_id, 'user_field_dependente_' . $s . '_nome', true ) );
            // Slot livre encontrado
            if ( '' === $nome ) {
                return $s;
            }
        }
        // Todos ocupados
        return 0;
    }
}

/**
 * Nome de exibicao da categoria a partir do valor gravado no campo
 */
if ( ! function_exists( 'explode_mao_rotulo_categoria' ) ) {
    function explode_mao_rotulo_categoria( $cat_meta ) {
        // Campo vazio
        if ( '' === trim( (string) $cat_meta ) ) {
            return '';
        }
        // Garante o importador carregado
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }
        // Termos da taxonomia
        $termos = Explode_Admin_User_Importer::carregar_termos();
        // Primeiro numero do campo e a categoria
        if ( ! preg_match( '/\d+/', (string) $cat_meta, $m ) ) {
            return '';
        }
        // ID encontrado
        $id = (int) $m[0];
        // Devolve o nome quando for um termo de categoria
        if ( isset( $termos['por_id'][ $id ] ) && 'categoria' === $termos['por_id'][ $id ]['tipo'] ) {
            return $termos['por_id'][ $id ]['nome'];
        }
        // Nao foi possivel identificar
        return '';
    }
}

/**
 * Cadastra um dependente informado pelo proprio colaborador
 * Devolve array com ok, mensagem e o slot utilizado
 */
if ( ! function_exists( 'explode_mao_adicionar_dependente' ) ) {
    function explode_mao_adicionar_dependente( $user_id, $nome, $faixa ) {
        // Normaliza o identificador do colaborador
        $user_id = (int) $user_id;

        // A regra vale apenas para os grupos MAO
        if ( ! explode_usuario_e_mao( $user_id ) ) {
            return array( 'ok' => false, 'msg' => 'Este cadastro está disponível apenas para os grupos de Manaus.', 'slot' => 0 );
        }

        // O administrador pode ter desligado o cadastro pelo proprio colaborador
        if ( ! explode_mao_cadastro_dependentes_ativo() ) {
            return array( 'ok' => false, 'msg' => 'O cadastro de dependentes pelo colaborador está desativado. Procure o RH.', 'slot' => 0 );
        }

        // Limpa o nome informado
        $nome = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $nome ) ) );

        // Nome muito curto
        if ( mb_strlen( $nome ) < 3 ) {
            return array( 'ok' => false, 'msg' => 'Informe o nome completo do dependente (mínimo de 3 letras).', 'slot' => 0 );
        }
        // Nome muito longo
        if ( mb_strlen( $nome ) > 120 ) {
            return array( 'ok' => false, 'msg' => 'O nome do dependente é muito longo.', 'slot' => 0 );
        }

        // Faixa etaria escolhida no formulario
        $faixa_slug = sanitize_title( (string) $faixa );
        // Opcoes validas para este colaborador
        $opcoes     = explode_mao_faixas_opcoes( $user_id );

        // Nenhuma faixa selecionada ou opcao inexistente
        if ( '' === $faixa_slug || ! isset( $opcoes[ $faixa_slug ] ) ) {
            return array( 'ok' => false, 'msg' => 'Selecione a faixa de idade do dependente.', 'slot' => 0 );
        }

        // Dados da faixa escolhida
        $faixa_dados = $opcoes[ $faixa_slug ];
        // Texto da idade que sera gravado no perfil
        $idade_txt   = $faixa_dados['idade'];

        // Monta a categoria concatenada com o grupo do colaborador
        $cat_meta = explode_mao_montar_cat_meta( $faixa_dados['categoria'], $user_id );

        // Categoria ou grupo ausente na taxonomia
        if ( '' === $cat_meta ) {
            return array( 'ok' => false, 'msg' => 'Não foi possível definir a categoria. Procure o RH para verificar o seu cadastro.', 'slot' => 0 );
        }

        // Impede cadastrar duas vezes o mesmo dependente
        foreach ( explode_mao_listar_dependentes( $user_id ) as $dep ) {
            // Compara os nomes ignorando acento e caixa
            if ( explode_mao_chave( $dep['nome'] ) === explode_mao_chave( $nome ) ) {
                return array( 'ok' => false, 'msg' => 'O dependente "' . $dep['nome'] . '" já está cadastrado.', 'slot' => 0 );
            }
        }

        // Procura um slot livre
        $slot = explode_mao_proximo_slot_livre( $user_id );
        // Limite atingido
        if ( $slot < 1 ) {
            return array(
                'ok'   => false,
                'msg'  => 'Você já cadastrou o limite de ' . explode_mao_max_dependentes() . ' dependentes.',
                'slot' => 0,
            );
        }

        // Grava o dependente no slot livre
        update_user_meta( $user_id, 'user_field_dependente_' . $slot . '_nome', $nome );
        update_user_meta( $user_id, 'user_field_dependente_' . $slot . '_idade', $idade_txt );
        update_user_meta( $user_id, 'user_field_dependente_' . $slot . '_cat', $cat_meta );

        // Confirma o cadastro informando a faixa e a categoria resultante
        return array(
            'ok'   => true,
            'msg'  => $nome . ' foi cadastrado(a) na ' . $faixa_dados['categoria'] . ' (' . $faixa_dados['descricao'] . ').',
            'slot' => $slot,
        );
    }
}

/**
 * Remove um dependente cadastrado, desde que ele ainda nao tenha enviado desenho
 */
if ( ! function_exists( 'explode_mao_remover_dependente' ) ) {
    function explode_mao_remover_dependente( $user_id, $slot ) {
        // Normaliza os parametros
        $user_id = (int) $user_id;
        $slot    = (int) $slot;

        // A regra vale apenas para os grupos MAO
        if ( ! explode_usuario_e_mao( $user_id ) ) {
            return array( 'ok' => false, 'msg' => 'Ação disponível apenas para os grupos de Manaus.' );
        }

        // O administrador pode ter desligado o cadastro pelo proprio colaborador
        if ( ! explode_mao_cadastro_dependentes_ativo() ) {
            return array( 'ok' => false, 'msg' => 'A remoção de dependentes pelo colaborador está desativada. Procure o RH.' );
        }
        // Slot fora do intervalo previsto
        if ( $slot < 1 || $slot > explode_mao_max_dependentes() ) {
            return array( 'ok' => false, 'msg' => 'Dependente inválido.' );
        }

        // Prefixo dos campos deste slot
        $prefixo = 'user_field_dependente_' . $slot . '_';
        // Nome gravado
        $nome    = trim( (string) get_user_meta( $user_id, $prefixo . 'nome', true ) );

        // Nada cadastrado neste slot
        if ( '' === $nome ) {
            return array( 'ok' => false, 'msg' => 'Este dependente não está cadastrado.' );
        }

        // SALVAGUARDA: quem ja enviou desenho nao pode ser removido
        if ( 'Sim' === get_user_meta( $user_id, $prefixo . 'desenho', true ) ) {
            return array( 'ok' => false, 'msg' => 'Não é possível remover ' . $nome . ': o desenho dele(a) já foi enviado.' );
        }

        // Limpa os campos do slot
        delete_user_meta( $user_id, $prefixo . 'nome' );
        delete_user_meta( $user_id, $prefixo . 'idade' );
        delete_user_meta( $user_id, $prefixo . 'cat' );
        delete_user_meta( $user_id, $prefixo . 'pcd' );
        delete_user_meta( $user_id, $prefixo . 'laudo' );

        // Confirma a remocao
        return array( 'ok' => true, 'msg' => $nome . ' foi removido(a) da sua lista.' );
    }
}

/**
 * Texto das faixas etarias validas para os grupos MAO
 */
if ( ! function_exists( 'explode_mao_texto_faixas' ) ) {
    function explode_mao_texto_faixas() {
        // Garante o importador carregado
        if ( ! class_exists( 'Explode_Admin_User_Importer' ) ) {
            require_once get_template_directory() . '/inc/admin-importador-usuarios.php';
        }

        // Nome de um grupo MAO qualquer, para acionar as faixas proprias
        $referencia = 'Grupo 1 MAO';
        // Acumula a faixa de cada categoria
        $faixas = array();
        // Percorre as idades possiveis montando os intervalos
        for ( $i = 0; $i <= 20; $i++ ) {
            // Categoria desta idade
            $cat = Explode_Admin_User_Importer::categoria_por_idade( $i, $referencia );
            // Fora das faixas
            if ( '' === $cat ) {
                continue;
            }
            // Abre ou estende o intervalo da categoria
            if ( ! isset( $faixas[ $cat ] ) ) {
                $faixas[ $cat ] = array( $i, $i );
            } else {
                $faixas[ $cat ][1] = $i;
            }
        }

        // Monta o texto final
        $partes = array();
        foreach ( $faixas as $cat => $faixa ) {
            // Usa apenas a letra da categoria
            $letra = trim( str_ireplace( 'categoria', '', $cat ) );
            // Acrescenta o intervalo
            $partes[] = $letra . ': ' . $faixa[0] . ' a ' . $faixa[1] . ' anos';
        }

        // Junta tudo separado por ponto e virgula
        return implode( '; ', $partes );
    }
}

/* =====================================================================
 * PROTOCOLO AUXILIAR SEQUENCIAL (GRUPOS MAO)
 *
 * Alem do protocolo aleatorio que todo desenho recebe, os desenhos dos grupos MAO
 * ganham um numero sequencial proprio: 01, 02, 03... na ordem em que sao enviados.
 * O numero e atribuido uma unica vez por desenho; em um reenvio o desenho mantem
 * o protocolo que ja tinha, para nao furar a sequencia nem confundir a conferencia.
 * ===================================================================== */

/** Nome da option que guarda o contador da sequencia */
if ( ! defined( 'EXPLODE_MAO_PROTOCOLO_SEQ' ) ) {
    define( 'EXPLODE_MAO_PROTOCOLO_SEQ', 'explode_mao_protocolo_seq' );
}

/**
 * Formata o numero da sequencia com pelo menos dois digitos (01, 02, ... 10, 100)
 */
if ( ! function_exists( 'explode_mao_formatar_protocolo' ) ) {
    function explode_mao_formatar_protocolo( $numero ) {
        // Completa com zero a esquerda ate dois digitos
        return str_pad( (string) (int) $numero, 2, '0', STR_PAD_LEFT );
    }
}

/**
 * Reserva o proximo numero da sequencia de forma atomica
 *
 * O incremento e feito em uma unica instrucao SQL usando LAST_INSERT_ID, que guarda
 * o valor por conexao. Assim dois envios simultaneos nunca recebem o mesmo numero.
 */
if ( ! function_exists( 'explode_mao_proximo_protocolo' ) ) {
    function explode_mao_proximo_protocolo() {
        // Acesso direto ao banco
        global $wpdb;

        // Nome da option usada como contador
        $chave = EXPLODE_MAO_PROTOCOLO_SEQ;

        // Cria a linha do contador na primeira execucao, sem autoload
        $existe = $wpdb->get_var( $wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            $chave
        ) );
        // Quando ainda nao existe, comeca do zero
        if ( null === $existe ) {
            add_option( $chave, '0', '', 'no' );
        }

        // Incrementa e guarda o novo valor no LAST_INSERT_ID da propria conexao
        $atualizou = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value = LAST_INSERT_ID(option_value + 1) WHERE option_name = %s",
            $chave
        ) );

        // Le o valor reservado para esta conexao
        if ( $atualizou ) {
            // Recupera o numero gerado
            $novo = (int) $wpdb->get_var( 'SELECT LAST_INSERT_ID()' );
            // Mantem o cache de options coerente com o banco
            wp_cache_delete( $chave, 'options' );
            // Devolve quando valido
            if ( $novo > 0 ) {
                return $novo;
            }
        }

        // Alternativa caso o incremento atomico nao funcione no ambiente
        $atual = (int) get_option( $chave, 0 );
        // Proximo da sequencia
        $novo  = $atual + 1;
        // Grava o novo valor
        update_option( $chave, (string) $novo, 'no' );
        // Devolve o numero
        return $novo;
    }
}

/**
 * Garante que o desenho tenha um protocolo auxiliar e devolve o valor gravado
 * Em um reenvio o protocolo original e preservado
 */
if ( ! function_exists( 'explode_mao_atribuir_protocolo' ) ) {
    function explode_mao_atribuir_protocolo( $post_id ) {
        // Valida o post recebido
        $post_id = (int) $post_id;
        // Sem post nao ha o que gravar
        if ( $post_id < 1 ) {
            return '';
        }

        // Protocolo ja gravado em um envio anterior
        $atual = get_post_meta( $post_id, 'desenhos_box_protocolo_auxiliar', true );
        // Mantem o numero original no reenvio
        if ( '' !== trim( (string) $atual ) ) {
            return (string) $atual;
        }

        // Reserva o proximo numero da sequencia
        $numero = explode_mao_proximo_protocolo();
        // Formata para exibicao
        $texto  = explode_mao_formatar_protocolo( $numero );

        // Grava o protocolo formatado junto do desenho
        update_post_meta( $post_id, 'desenhos_box_protocolo_auxiliar', $texto );
        // Guarda tambem o valor numerico, util para ordenar listagens
        update_post_meta( $post_id, 'desenhos_box_protocolo_auxiliar_num', $numero );

        // Devolve o protocolo gerado
        return $texto;
    }
}

/**
 * Registra no wp-admin os campos do laudo e do protocolo dentro do post do desenho
 */
add_action( 'cmb2_admin_init', 'explode_mao_campos_laudo' );
function explode_mao_campos_laudo() {

    // Cria a caixa exclusiva do laudo no post type desenhos
    $cmb = new_cmb2_box( array(
        'id'           => 'desenhos_box_laudo',
        'title'        => 'Informações dos grupos MAO',
        'object_types' => array( 'desenhos' ),
        'context'      => 'normal',
        'priority'     => 'default',
    ) );

    // Numero sequencial do desenho dentro dos grupos MAO
    $cmb->add_field( array(
        'name'       => 'Protocolo auxiliar',
        'id'         => 'desenhos_box_protocolo_auxiliar',
        'type'       => 'text',
        'desc'       => 'Sequência própria dos grupos MAO (01, 02, 03...), atribuída no primeiro envio deste desenho.',
        'attributes' => array(
            'readonly' => 'readonly',
        ),
        'column'     => array(
            'position' => 3,
        ),
    ) );

    // Aviso do estado atual da regra, para o administrador nao se perder
    $aviso_regra = explode_mao_exige_laudo()
        ? '<br><strong>Regra ligada:</strong> hoje o colaborador PCD precisa anexar o laudo para enviar o desenho.'
        : '<br><strong>Regra desligada:</strong> hoje o laudo não é pedido no envio. Os arquivos abaixo são de envios anteriores ou anexados aqui pelo administrador. Para voltar a exigir, use <em>Opções gerais &gt; Regras dos grupos MAO</em>.';

    // Arquivo do laudo enviado pelo colaborador
    $cmb->add_field( array(
        'name'    => 'Laudo anexado',
        'id'      => 'desenhos_box_laudo_pcd',
        'type'    => 'file',
        'desc'    => explode_mao_texto_laudo() . $aviso_regra,
        'options' => array(
            'url' => false,
        ),
        'text'    => array(
            'add_upload_file_text' => 'Anexar laudo',
        ),
        'query_args' => array(
            'type' => array( 'application/pdf', 'image/jpeg', 'image/png' ),
        ),
        'column' => array(
            'position' => 5,
        ),
    ) );

    // Registro do aceite do termo de uso de imagem e voz
    $cmb->add_field( array(
        'name'       => 'Termo de Uso de Imagem e Voz',
        'id'         => 'desenhos_box_termo_imagem_voz',
        'type'       => 'text',
        'desc'       => 'Preenchido automaticamente no momento do envio do desenho.',
        'attributes' => array(
            'readonly' => 'readonly',
        ),
    ) );

    // Data e hora do aceite, para auditoria
    $cmb->add_field( array(
        'name'       => 'Data do aceite do termo',
        'id'         => 'desenhos_box_termo_data',
        'type'       => 'text',
        'attributes' => array(
            'readonly' => 'readonly',
        ),
    ) );
}
