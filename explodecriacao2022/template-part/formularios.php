<?php
// Impede o acesso direto ao script fora do ambiente do WordPress por seguranca
if ( ! defined( 'ABSPATH' ) ) {
    // Interrompe o processamento se o WordPress nao estiver carregado
    exit;
}

// Obtem o identificador unico do usuario logado no momento
$user_id = get_current_user_id();

// Carrega o termo de unidade atribuido ao colaborador no banco de dados
$unidade = get_user_meta( $user_id, 'user_field_unidade', true );

// Verifica se o colaborador pertence a um grupo MAO, que tem regras proprias
$e_mao = function_exists( 'explode_usuario_e_mao' ) ? explode_usuario_e_mao( $user_id ) : false;

// ############### ESTADO DAS REGRAS DOS GRUPOS MAO ###############
// As tres regras abaixo sao ligadas e desligadas pelo administrador em
// "Opções gerais" > "Regras dos grupos MAO". Todas comecam desligadas.

// O colaborador pode cadastrar os proprios dependentes?
$mao_cadastro_dep = $e_mao && function_exists( 'explode_mao_cadastro_dependentes_ativo' )
    ? explode_mao_cadastro_dependentes_ativo() : false;

// O laudo e obrigatorio quando o dependente e marcado como PCD?
$mao_exige_laudo = $e_mao && function_exists( 'explode_mao_exige_laudo' )
    ? explode_mao_exige_laudo() : false;

// O aceite do Termo de Uso de Imagem e Voz e obrigatorio para PCD?
$mao_exige_termo = $e_mao && function_exists( 'explode_mao_exige_termo' )
    ? explode_mao_exige_termo() : false;

// O bloco de PCD dos grupos MAO so aparece se alguma das duas exigencias estiver ligada
$mao_bloco_pcd = ( $mao_exige_laudo || $mao_exige_termo );
// ############### FIM DO ESTADO DAS REGRAS ###############

// Quantidade de dependentes aceita pelo tema, definida em functions.php
$total_dependentes = function_exists( 'explode_max_dependentes' ) ? explode_max_dependentes() : 6;

// Mensagens do cadastro de dependentes feito pelo proprio colaborador
$mao_dep_avisos = array();

// ############### CADASTRO DE DEPENDENTES PELO COLABORADOR (GRUPOS MAO) ###############
if ( $mao_cadastro_dep && 'POST' === $_SERVER['REQUEST_METHOD']
    && ( isset( $_POST['explode_mao_add_dependente'] ) || isset( $_POST['explode_mao_del_dependente'] ) ) ) {

    // Confere o nonce antes de qualquer alteracao
    $mao_nonce_ok = isset( $_POST['explode_mao_dep_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['explode_mao_dep_nonce'] ) ), 'explode_mao_dep_' . $user_id );

    // Nonce invalido ou expirado
    if ( ! $mao_nonce_ok ) {
        // Registra o aviso e nao altera nada
        $mao_dep_avisos[] = array( 'tipo' => 'erro', 'texto' => 'Sessão expirada. Recarregue a página e tente novamente.' );
    }
    // Cadastro de um novo dependente
    elseif ( isset( $_POST['explode_mao_add_dependente'] ) ) {
        // Le os campos enviados
        $dep_nome_novo  = isset( $_POST['dep_nome'] ) ? sanitize_text_field( wp_unslash( $_POST['dep_nome'] ) ) : '';
        $dep_faixa_nova = isset( $_POST['dep_faixa'] ) ? sanitize_text_field( wp_unslash( $_POST['dep_faixa'] ) ) : '';
        // Executa o cadastro
        $mao_res = explode_mao_adicionar_dependente( $user_id, $dep_nome_novo, $dep_faixa_nova );
        // Guarda o retorno para exibir na tela
        $mao_dep_avisos[] = array( 'tipo' => $mao_res['ok'] ? 'ok' : 'erro', 'texto' => $mao_res['msg'] );
    }
    // Remocao de um dependente cadastrado
    elseif ( isset( $_POST['explode_mao_del_dependente'] ) ) {
        // Slot informado
        $dep_slot_del = isset( $_POST['dep_slot'] ) ? absint( $_POST['dep_slot'] ) : 0;
        // Executa a remocao
        $mao_res = explode_mao_remover_dependente( $user_id, $dep_slot_del );
        // Guarda o retorno para exibir na tela
        $mao_dep_avisos[] = array( 'tipo' => $mao_res['ok'] ? 'ok' : 'erro', 'texto' => $mao_res['msg'] );
    }
}
// ############### FIM DO CADASTRO DE DEPENDENTES ###############

// Quantidade padrao de digitos para composicao do protocolo de envio
$digits = 7;

// Array para armazenar respostas de sucesso ou erro do envio
$resultados_envio = array();

// Carrega a biblioteca nativa do WordPress para manipulacao de imagens
require_once( ABSPATH . 'wp-admin/includes/image.php' );
// Carrega as funcoes auxiliares de manipulacao de arquivos no core do WordPress
require_once( ABSPATH . 'wp-admin/includes/file.php' );
// Carrega as funcoes oficiais da API de midia do WordPress
require_once( ABSPATH . 'wp-admin/includes/media.php' );

// Processa a requisicao quando o formulario for submetido via POST
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ( isset( $_POST['explode_enviar_desenho_action'] ) || isset( $_POST['dependente'] ) ) ) {

    // Identifica o numero do dependente selecionado (1 ate o limite do tema)
    $dep_post_num = isset( $_POST['dependente'] ) ? absint( $_POST['dependente'] ) : 0;

    // Inicializa a lista de mensagens de erro para a submissao
    $mensagens_erro = array();

    // Valida se o identificador do dependente esta dentro dos slots existentes
    if ( $dep_post_num < 1 || $dep_post_num > $total_dependentes ) {
        // Registra mensagem de dependente invalido
        $mensagens_erro[] = 'Identificador de dependente inválido.';
    }

    // Valida se o usuario confirmou expressamente a concordancia com os termos (LGPD)
    $concordou_termos = ( isset( $_POST['confirmacao'] ) && 'sim' === sanitize_text_field( wp_unslash( $_POST['confirmacao'] ) ) );
    // Se o termo nao foi marcado, bloqueia o envio por conformidade legal
    if ( ! $concordou_termos ) {
        // Registra erro de concordancia obrigatoria
        $mensagens_erro[] = 'É obrigatório marcar a caixa de concordância para submeter o desenho.';
    }

    // Obtem o nome do dependente gravado no perfil do colaborador
    $dep_nome_cadastrado = get_user_meta( $user_id, 'user_field_dependente_' . $dep_post_num . '_nome', true );
    // Ajusta a formatacao do nome com letras iniciais maiusculas
    $dep_nome_cadastrado = ucwords( strtolower( trim( (string)$dep_nome_cadastrado ) ) );

    // Se nao houver nome para este dependente, define um identificador padrao
    if ( empty( $dep_nome_cadastrado ) ) {
        // Atribui nome padrao para o dependente
        $dep_nome_cadastrado = 'Dependente #' . $dep_post_num;
    }

    // Verifica se um arquivo de imagem foi enviado no upload
    $tem_arquivo = ( isset( $_FILES['desenho'] ) && ! empty( $_FILES['desenho']['name'] ) && ! empty( $_FILES['desenho']['tmp_name'] ) );

    // Se nenhum arquivo foi enviado ou se o tamanho for zero
    if ( ! $tem_arquivo || 0 === (int)$_FILES['desenho']['size'] ) {
        // Registra mensagem de selecao de imagem obrigatoria
        $mensagens_erro[] = 'Selecione uma imagem nos formatos JPG, JPEG ou PNG.';
    } else {
        // Lista de extensoes aceitas pelo regulamento
        $extensoes_aceitas = array( 'jpg', 'jpeg', 'png' );
        // Obtem o nome original do arquivo sanitizado
        $nome_original = sanitize_file_name( $_FILES['desenho']['name'] );
        // Extrai a extensao em letras minusculas
        $extensao_enviada = strtolower( pathinfo( $nome_original, PATHINFO_EXTENSION ) );

        // Valida se a extensao enviada esta na lista de permitidas
        if ( ! in_array( $extensao_enviada, $extensoes_aceitas, true ) ) {
            // Registra erro de extensao nao permitida
            $mensagens_erro[] = 'Formato de arquivo inválido. Por favor, envie uma imagem JPG, JPEG ou PNG.';
        }
    }

    // ############### REGRAS DOS GRUPOS MAO: LAUDO DE PCD E TERMO DE IMAGEM ###############

    // Verifica se o colaborador marcou este dependente como PCD
    // O campo virou um radio Nao/Sim; "on" continua aceito por compatibilidade
    $pcd_valor   = isset( $_POST['confirmacao_pcd_' . $dep_post_num] ) ? strtolower( trim( (string) wp_unslash( $_POST['confirmacao_pcd_' . $dep_post_num] ) ) ) : 'nao';
    $pcd_marcado = in_array( $pcd_valor, array( 'sim', 'on', '1' ), true );

    // Verifica se veio arquivo de laudo junto com o envio
    $tem_laudo = ( isset( $_FILES['laudo_pcd'] )
        && ! empty( $_FILES['laudo_pcd']['name'] )
        && ! empty( $_FILES['laudo_pcd']['tmp_name'] )
        && (int) $_FILES['laudo_pcd']['size'] > 0 );

    // Recupera um laudo enviado anteriormente para este mesmo dependente
    $laudo_ja_salvo = (int) get_user_meta( $user_id, 'user_field_dependente_' . $dep_post_num . '_laudo', true );
    // Só considera válido se o anexo ainda existir na biblioteca de midia
    $laudo_ja_salvo = ( $laudo_ja_salvo > 0 && get_post( $laudo_ja_salvo ) ) ? $laudo_ja_salvo : 0;

    // As exigencias abaixo valem apenas para os grupos MAO com dependente PCD e
    // somente quando o administrador as tiver ligado em "Opções gerais"
    if ( $e_mao && $pcd_marcado ) {

        // REGRA DO LAUDO: cobrada apenas quando ligada no painel
        if ( $mao_exige_laudo && ! $tem_laudo ) {
            // Registra a obrigatoriedade junto com o texto oficial do servico social
            $mensagens_erro[] = 'Para dependente PCD é obrigatório anexar o laudo. ' . explode_mao_texto_laudo();
        }

        // Valida o arquivo do laudo sempre que ele vier, mesmo sendo opcional.
        // Um arquivo invalido precisa ser recusado, e nao aceito em silencio.
        if ( $tem_laudo ) {
            // Extrai a extensao do laudo
            $ext_laudo = strtolower( pathinfo( sanitize_file_name( $_FILES['laudo_pcd']['name'] ), PATHINFO_EXTENSION ) );
            // Verifica se a extensao e permitida
            if ( ! in_array( $ext_laudo, explode_mao_extensoes_laudo(), true ) ) {
                // Registra erro de formato do laudo
                $mensagens_erro[] = 'O laudo deve ser um arquivo PDF, JPG, JPEG ou PNG.';
            }
            // Verifica o tamanho maximo permitido
            if ( (int) $_FILES['laudo_pcd']['size'] > explode_mao_tamanho_max_laudo() ) {
                // Registra erro de tamanho do laudo
                $mensagens_erro[] = 'O laudo excede o tamanho máximo de ' . size_format( explode_mao_tamanho_max_laudo() ) . '.';
            }
        }

        // REGRA DO TERMO: cobrada apenas quando ligada no painel
        if ( $mao_exige_termo
            && ( ! isset( $_POST['termo_imagem_voz'] ) || 'sim' !== sanitize_text_field( wp_unslash( $_POST['termo_imagem_voz'] ) ) ) ) {
            // Registra a obrigatoriedade do aceite
            $mensagens_erro[] = 'É obrigatório ler e aceitar o Termo de Uso de Imagem e Voz para dependente PCD.';
        }
    }
    // ############### FIM DAS REGRAS DOS GRUPOS MAO ###############

    // Se nao houver erros de validacao, processa o upload do arquivo nativamente
    if ( empty( $mensagens_erro ) ) {

        // Verifica se a opcao de PCD foi selecionada
        // Reaproveita a leitura ja validada do radio Nao/Sim
        $is_pcd = $pcd_marcado ? 'Sim' : '';

        // Configura parametros de upload nativo do WordPress ignorando testes de formulario restritivos
        $overrides = array( 'test_form' => false, 'test_upload' => false );
        
        // Verifica se a funcao nativa wp_handle_upload esta disponivel
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            // Inclui arquivo de funcoes de upload do core do WordPress
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        // Executa o upload nativo do WordPress movendo o arquivo para a pasta de uploads
        $movefile = wp_handle_upload( $_FILES['desenho'], $overrides );

        // Se wp_handle_upload falhar por is_uploaded_file em ambientes especificos, tenta wp_handle_sideload
        if ( isset( $movefile['error'] ) && ! empty( $_FILES['desenho']['tmp_name'] ) && file_exists( $_FILES['desenho']['tmp_name'] ) ) {
            // Tenta o sideload nativo como alternativa robusta
            $movefile = wp_handle_sideload( $_FILES['desenho'], $overrides );
        }

        // Verifica se o upload do arquivo obteve sucesso
        if ( $movefile && ! isset( $movefile['error'] ) ) {

            // Obtem a URL publica do arquivo gravado
            $drawing_url = $movefile['url'];
            // Obtem o caminho absoluto no servidor do arquivo gravado
            $file_path = $movefile['file'];
            // Obtem o mime type do arquivo enviado
            $mime_type = $movefile['type'];

            // Busca se ja existe um post de desenho anterior deste autor para este dependente
            $posts_existentes = get_posts( array(
                // Post type desenhos
                'post_type'   => 'desenhos',
                // Autor logado
                'author'      => $user_id,
                // Sem limite
                'numberposts' => -1,
                // Qualquer status
                'post_status' => 'any',
            ) );

            // Variavel para o ID do post
            $post_desenho_id = 0;
            // Variavel para ID de anexo anterior a ser removido
            $anexo_antigo_id = 0;

            // Percorre os posts do autor para encontrar o post correspondente ao dependente
            foreach ( $posts_existentes as $p_ex ) {
                // Obtem o meta do dependente gravado no post
                $dep_val = get_post_meta( $p_ex->ID, 'desenhos_box_dependente', true );
                // Se corresponder ao dependente atual ou tiver o mesmo titulo
                if ( (string)$dep_val === (string)$dep_post_num || $p_ex->post_title === $dep_nome_cadastrado ) {
                    // Define o ID do post existente
                    $post_desenho_id = (int)$p_ex->ID;
                    // Recupera meta da imagem anterior
                    $meta_antigo = get_post_meta( $post_desenho_id, 'desenhos_box_desenho', true );
                    // Se for numerico pega o ID direto
                    if ( is_numeric( $meta_antigo ) ) {
                        // Atribui o ID numerico do anexo antigo
                        $anexo_antigo_id = (int)$meta_antigo;
                    } elseif ( is_string( $meta_antigo ) && ! empty( $meta_antigo ) ) {
                        // Tenta recuperar ID de anexo pela URL antiga
                        $anexo_antigo_id = attachment_url_to_postid( $meta_antigo );
                    }
                    // Interrompe o loop ao encontrar correspondencia
                    break;
                }
            }

            // Se for um novo post de desenho
            if ( 0 === $post_desenho_id ) {
                // Cria um novo post no CPT desenhos usando wp_insert_post
                $post_desenho_id = wp_insert_post( array(
                    // Define o post type como desenhos
                    'post_type'   => 'desenhos',
                    // Define o titulo como o nome do dependente
                    'post_title'  => $dep_nome_cadastrado,
                    // Status publicado
                    'post_status' => 'publish',
                    // Define o autor como o usuario atual
                    'post_author' => $user_id,
                ) );
            } else {
                // Atualiza o post existente garantindo o titulo e status
                wp_update_post( array(
                    // ID do post existente
                    'ID'          => $post_desenho_id,
                    // Titulo atualizado
                    'post_title'  => $dep_nome_cadastrado,
                    // Status publicado
                    'post_status' => 'publish',
                ) );

                // Se houver anexo antigo anterior, remove da biblioteca de midia
                if ( $anexo_antigo_id > 0 ) {
                    // Exclui anexo antigo permanentemente
                    wp_delete_attachment( $anexo_antigo_id, true );
                }
            }

            // Cria o registro do anexo na biblioteca de midia do WordPress
            $attachment_data = array(
                // URL do arquivo
                'guid'           => $drawing_url,
                // Tipo MIME oficial
                'post_mime_type' => $mime_type,
                // Titulo do anexo
                'post_title'     => $dep_nome_cadastrado,
                // Conteudo vazio
                'post_content'   => '',
                // Status herdado
                'post_status'    => 'inherit'
            );
            // Insere o anexo vinculado ao post do desenho
            $attachment_id = wp_insert_attachment( $attachment_data, $file_path, $post_desenho_id );

            // Gera e atualiza metadados e miniaturas da imagem na biblioteca
            if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
                // Gera metadados de redimensionamento
                $attach_meta = wp_generate_attachment_metadata( $attachment_id, $file_path );
                // Salva metadados do anexo
                wp_update_attachment_metadata( $attachment_id, $attach_meta );
            }

            // Gera numero unico de protocolo com 7 digitos
            $novo_protocolo = '#' . wp_rand( pow( 10, $digits - 1 ), pow( 10, $digits ) - 1 );

            // Atualiza os metadados do post de desenho no banco
            update_post_meta( $post_desenho_id, 'desenhos_box_dependente', (string)$dep_post_num );
            // Salva o protocolo gerado
            update_post_meta( $post_desenho_id, 'desenhos_box_protocolo', $novo_protocolo );

            // PROTOCOLO AUXILIAR: sequencia propria dos grupos MAO (01, 02, 03...)
            // Atribuido apenas uma vez por desenho; no reenvio o numero original e mantido
            $protocolo_auxiliar = '';
            if ( $e_mao && function_exists( 'explode_mao_atribuir_protocolo' ) ) {
                $protocolo_auxiliar = explode_mao_atribuir_protocolo( $post_desenho_id );
            }
            // Salva a URL da imagem (compativel com todos os templates do tema)
            update_post_meta( $post_desenho_id, 'desenhos_box_desenho', $drawing_url );
            // Salva a unidade do colaborador no post
            update_post_meta( $post_desenho_id, 'desenhos_box_unidade', $unidade );
            // Salva o status de PCD no post
            update_post_meta( $post_desenho_id, 'desenhos_box_pcd', $is_pcd );

            // Atualiza o perfil do colaborador registrando que o desenho foi enviado
            update_user_meta( $user_id, 'user_field_dependente_' . $dep_post_num . '_desenho', 'Sim' );
            // Atualiza o status PCD no perfil do usuario
            update_user_meta( $user_id, 'user_field_dependente_' . $dep_post_num . '_pcd', $is_pcd );

            // ############### GRAVACAO DO LAUDO DE PCD (GRUPOS MAO) ###############
            // Só processa quando o colaborador e MAO e marcou o dependente como PCD
            if ( $e_mao && 'Sim' === $is_pcd ) {

                // Registra o aceite do Termo de Uso de Imagem e Voz apenas quando ele
                // realmente foi dado. Antes o aceite era gravado como "Sim" em todo envio
                // de PCD, o que produzia um registro de auditoria falso agora que o termo
                // deixou de ser obrigatorio.
                $termo_aceito = ( isset( $_POST['termo_imagem_voz'] )
                    && 'sim' === sanitize_text_field( wp_unslash( $_POST['termo_imagem_voz'] ) ) );

                // Grava o aceite somente se houve aceite
                if ( $termo_aceito ) {
                    // Marca o aceite no proprio desenho
                    update_post_meta( $post_desenho_id, 'desenhos_box_termo_imagem_voz', 'Sim' );
                    // Guarda a data e hora do aceite para auditoria
                    update_post_meta( $post_desenho_id, 'desenhos_box_termo_data', current_time( 'd/m/Y H:i' ) );
                }

                // Envia o novo laudo apenas quando um arquivo foi selecionado
                if ( $tem_laudo ) {

                    // Recupera o laudo anterior vinculado a este desenho
                    $laudo_antigo_id = (int) get_post_meta( $post_desenho_id, 'desenhos_box_laudo_pcd_id', true );
                    // Remove o laudo anterior para nao acumular arquivos duplicados
                    if ( $laudo_antigo_id > 0 ) {
                        // Exclui o anexo antigo permanentemente
                        wp_delete_attachment( $laudo_antigo_id, true );
                    }

                    // Executa o upload nativo do laudo
                    $move_laudo = wp_handle_upload( $_FILES['laudo_pcd'], $overrides );

                    // Alternativa de sideload quando o upload direto falhar no ambiente
                    if ( isset( $move_laudo['error'] ) && ! empty( $_FILES['laudo_pcd']['tmp_name'] ) && file_exists( $_FILES['laudo_pcd']['tmp_name'] ) ) {
                        // Tenta novamente pelo sideload
                        $move_laudo = wp_handle_sideload( $_FILES['laudo_pcd'], $overrides );
                    }

                    // Verifica se o laudo foi gravado com sucesso
                    if ( $move_laudo && ! isset( $move_laudo['error'] ) ) {

                        // Cria o anexo vinculado ao MESMO post do desenho, mantendo os dois juntos
                        $laudo_id = wp_insert_attachment( array(
                            // URL publica do laudo
                            'guid'           => $move_laudo['url'],
                            // Tipo MIME do arquivo
                            'post_mime_type' => $move_laudo['type'],
                            // Titulo identificando o dependente
                            'post_title'     => 'Laudo PCD - ' . $dep_nome_cadastrado,
                            // Conteudo vazio
                            'post_content'   => '',
                            // Status herdado do post pai
                            'post_status'    => 'inherit',
                        ), $move_laudo['file'], $post_desenho_id );

                        // Se o anexo foi criado corretamente
                        if ( $laudo_id && ! is_wp_error( $laudo_id ) ) {

                            // Gera os metadados do anexo
                            $laudo_meta = wp_generate_attachment_metadata( $laudo_id, $move_laudo['file'] );
                            // Salva os metadados gerados
                            wp_update_attachment_metadata( $laudo_id, $laudo_meta );

                            // Grava a URL no padrao do campo de arquivo do CMB2
                            update_post_meta( $post_desenho_id, 'desenhos_box_laudo_pcd', $move_laudo['url'] );
                            // Grava o ID do anexo no sufixo esperado pelo CMB2
                            update_post_meta( $post_desenho_id, 'desenhos_box_laudo_pcd_id', $laudo_id );
                            // Espelha o laudo no perfil do colaborador para consulta rapida
                            update_user_meta( $user_id, 'user_field_dependente_' . $dep_post_num . '_laudo', $laudo_id );
                        }

                    } else {
                        // Informa que o desenho foi salvo mas o laudo falhou
                        $mensagens_erro[] = 'O desenho foi salvo, mas houve falha ao anexar o laudo: '
                            . ( isset( $move_laudo['error'] ) ? $move_laudo['error'] : 'erro desconhecido' )
                            . '. Reenvie o desenho com o laudo.';
                    }
                }

            }
            // ############### FIM DA GRAVACAO DO LAUDO ###############

            // Extrai as categorias cadastradas para o dependente
            $cat_string = get_user_meta( $user_id, 'user_field_dependente_' . $dep_post_num . '_cat', true );
            // Array para armazenar IDs inteiros de categorias
            $term_ids = array();
            // Processa a string de categorias
            if ( ! empty( $cat_string ) ) {
                // Separa tokens por virgula, ponto ou espaco
                $tokens = preg_split( '/[,\.\s]+/u', (string)$cat_string, -1, PREG_SPLIT_NO_EMPTY );
                // Converte cada token para inteiro valido
                foreach ( $tokens as $tk ) {
                    // Converte para valor inteiro
                    $tid = absint( trim( $tk ) );
                    // Se for maior que zero adiciona
                    if ( $tid > 0 ) {
                        // Adiciona ID ao array
                        $term_ids[] = $tid;
                    }
                }
            }

            // Se for PCD, associa tambem a taxonomia pcd se existir
            if ( 'Sim' === $is_pcd ) {
                // Busca o termo pcd
                $pcd_term = get_term_by( 'slug', 'pcd', 'desenhos_cat' );
                // Se o termo existir adiciona
                if ( $pcd_term && ! is_wp_error( $pcd_term ) ) {
                    // Adiciona o ID do termo PCD
                    $term_ids[] = (int)$pcd_term->term_id;
                }
            }

            // Remove duplicatas de categorias
            $term_ids = array_values( array_unique( $term_ids ) );
            // Se houver termos, associa ao post na taxonomia desenhos_cat
            if ( ! empty( $term_ids ) ) {
                // Grava os termos no post do desenho
                wp_set_post_terms( $post_desenho_id, $term_ids, 'desenhos_cat', false );
            }

            // Armazena resultado positivo para renderizacao na tela
            $resultados_envio[$dep_post_num] = array(
                // Sem erros
                'errors'    => false,
                // Sem mensagens de erro
                'warnings'  => array(),
                // Nome do dependente
                'nome'      => $dep_nome_cadastrado,
                // Protocolo gerado
                'protocolo' => $novo_protocolo,
                // Protocolo auxiliar dos grupos MAO (vazio nos demais grupos)
                'auxiliar'  => $protocolo_auxiliar,
            );

        } else {
            // Registra mensagem de erro de upload retornada pelo WordPress
            $mensagens_erro[] = 'Falha ao salvar imagem: ' . ( isset( $movefile['error'] ) ? $movefile['error'] : 'Erro desconhecido.' );
        }
    }

    // Se houve erros de validacao ou upload, armazena no array de retorno
    if ( ! empty( $mensagens_erro ) ) {
        // Registra erro para este dependente
        $resultados_envio[$dep_post_num] = array(
            // Flag de erro
            'errors'    => true,
            // Lista de avisos
            'warnings'  => $mensagens_erro,
            // Nome do dependente
            'nome'      => $dep_nome_cadastrado,
            // Protocolo vazio
            'protocolo' => '',
            // Sem protocolo auxiliar quando o envio falha
            'auxiliar'  => '',
        );
    }
}

// Busca todos os desenhos enviados pelo autor para composicao da interface
$query_desenhos = new WP_Query( array(
    // Post type desenhos
    'post_type'      => 'desenhos',
    // Todos os posts
    'posts_per_page' => -1,
    // Ordenacao cronologica
    'orderby'        => 'date',
    // Crescente
    'order'          => 'ASC',
    // Autor atual
    'author'         => $user_id,
    // Status publicado
    'post_status'    => 'publish',
) );

// Array de mapa para registrar dependentes com desenho enviado
$mapa_enviados = array();
// Array de mapa para status PCD
$mapa_pcd = array();

// Processa os posts encontrados
if ( $query_desenhos->have_posts() ) {
    // Loop de posts
    while ( $query_desenhos->have_posts() ) {
        // Prepara post
        $query_desenhos->the_post();
        // ID do post
        $pid = get_the_ID();
        // Titulo do post
        $p_title = get_the_title();
        // Dependente gravado (1 a 4)
        $dep_meta = get_post_meta( $pid, 'desenhos_box_dependente', true );
        // Mapeia por titulo
        $mapa_enviados[$p_title] = true;
        // Mapeia por chave de dependente
        if ( ! empty( $dep_meta ) ) {
            // Mapeia o indice
            $mapa_enviados['dep_' . $dep_meta] = true;
        }
        // Mapeia status PCD
        $mapa_pcd[$p_title] = get_post_meta( $pid, 'desenhos_box_pcd', true );
    }
    // Restaura o postdata global
    wp_reset_postdata();
}
?>

<?php
// Slots que realmente possuem formulario nesta tela: sao os dependentes com nome
// gravado no perfil. Serve para saber se uma mensagem de erro tem onde aparecer.
$slots_com_formulario = array();
// Percorre todos os slots do tema
for ( $s_chk = 1; $s_chk <= $total_dependentes; $s_chk++ ) {
    // Nome gravado neste slot
    $nome_chk = trim( (string) get_user_meta( $user_id, 'user_field_dependente_' . $s_chk . '_nome', true ) );
    // Slot preenchido gera formulario
    if ( '' !== $nome_chk ) {
        $slots_com_formulario[] = $s_chk;
    }
}
?>

<div class="all-forms">
    <!-- Titulo da secao de dependentes -->
    <h2 style="color: #000;">Dependentes</h2>
    <div class="all">

        <?php
        // Exibe modal flutuante de confirmacao de sucesso com o protocolo
        foreach ( $resultados_envio as $res_item ) {
            // Se nao houver erros e o protocolo existir
            if ( ! $res_item['errors'] && ! empty( $res_item['protocolo'] ) ) { ?>
                <div class="sucesso-float">
                    <div class="in">
                        <p id="sucesso">
                            <!-- Nome do dependente -->
                            <span>O desenho do(a) dependente <u><?php echo esc_html( $res_item['nome'] ); ?></u> foi enviado com sucesso!</span>
                            <!-- Mensagem de agradecimento -->
                            <span>Agradecemos sua participação no Explode Criação.</span>
                            <!-- Numero do protocolo destacado -->
                            <span id="warning"><b>Atenção:</b> Este número será perdido ao carregar a página! Número do protocolo: <b><?php echo esc_html( $res_item['protocolo'] ); ?></b></span>
                            <?php if ( ! empty( $res_item['auxiliar'] ) ) : ?>
                                <!-- Protocolo auxiliar sequencial dos grupos MAO -->
                                <span id="warning">Protocolo auxiliar: <b><?php echo esc_html( $res_item['auxiliar'] ); ?></b></span>
                            <?php endif; ?>
                            <!-- Botao para fechar e recarregar a pagina -->
                            <a id="btn" href="<?php echo esc_url( home_url() ); ?>">Concordar e fechar</a>
                        </p>
                    </div>
                </div>
            <?php }
        }

        // Erros de envios que NAO tem formulario na tela para exibi-los.
        // Sem este bloco a recusa acontecia em silencio: a mensagem era criada e
        // nunca chegava ao colaborador, que via a pagina recarregar sem explicacao.
        foreach ( $resultados_envio as $slot_res => $res_item ) {
            // Ignora os envios que deram certo
            if ( empty( $res_item['errors'] ) ) {
                continue;
            }
            // Ignora quem tem formulario proprio, que ja mostra o erro no lugar certo
            if ( in_array( (int) $slot_res, $slots_com_formulario, true ) ) {
                continue;
            }
            // Imprime a mensagem em destaque no topo da lista
            echo '<p class="erro-main erro-geral">';
            // Percorre os avisos deste envio
            foreach ( (array) $res_item['warnings'] as $aviso ) {
                echo '<span>' . esc_html( $aviso ) . '</span>';
            }
            echo '</p>';
        }
        ?>

        <?php if ( $mao_cadastro_dep ) :
            // Dependentes ja cadastrados por este colaborador
            $mao_deps  = explode_mao_listar_dependentes( $user_id );
            // Proximo slot livre, zero quando o limite foi atingido
            $mao_livre = explode_mao_proximo_slot_livre( $user_id );
        ?>
        <!-- CADASTRO DE DEPENDENTES: exclusivo dos grupos MAO -->
        <div class="mao-dependentes">

            <div class="mao-dep-topo">
                <h3><i class="fas fa-users"></i> Meus dependentes</h3>
                <p>
                    Cadastre o nome e a idade de cada filho(a) ou tutelado(a). Depois de cadastrar,
                    o formulário de envio do desenho aparece logo abaixo, um para cada dependente.
                    A categoria é definida automaticamente pela idade
                    (<?php echo esc_html( explode_mao_texto_faixas() ); ?>).
                </p>
            </div>

            <?php // Mensagens do cadastro ou da remocao
            foreach ( $mao_dep_avisos as $mao_aviso ) : ?>
                <div class="mao-dep-aviso <?php echo 'ok' === $mao_aviso['tipo'] ? 'ok' : 'erro'; ?>">
                    <i class="fas <?php echo 'ok' === $mao_aviso['tipo'] ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo esc_html( $mao_aviso['texto'] ); ?></span>
                </div>
            <?php endforeach; ?>

            <?php if ( ! empty( $mao_deps ) ) : ?>
                <!-- Lista dos dependentes ja cadastrados -->
                <ul class="mao-dep-lista">
                    <?php foreach ( $mao_deps as $mao_dep ) :
                        // Nome da categoria para exibicao
                        $mao_rotulo = explode_mao_rotulo_categoria( $mao_dep['cat'] );
                    ?>
                        <li class="mao-dep-item">
                            <div class="mao-dep-info">
                                <strong><?php echo esc_html( $mao_dep['nome'] ); ?></strong>
                                <span>
                                    <?php echo '' !== $mao_dep['idade'] ? esc_html( $mao_dep['idade'] . ' ano(s)' ) : 'idade não informada'; ?>
                                    <?php if ( '' !== $mao_rotulo ) : ?>
                                        &middot; <?php echo esc_html( $mao_rotulo ); ?>
                                    <?php else : ?>
                                        &middot; <em>sem categoria</em>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ( $mao_dep['desenho'] ) : ?>
                                <span class="mao-dep-tag enviado"><i class="fas fa-check"></i> Desenho enviado</span>
                            <?php else : ?>
                                <form method="post" class="mao-dep-remover" onsubmit="return confirm('Remover <?php echo esc_js( $mao_dep['nome'] ); ?> da sua lista?');">
                                    <?php wp_nonce_field( 'explode_mao_dep_' . $user_id, 'explode_mao_dep_nonce' ); ?>
                                    <input type="hidden" name="explode_mao_del_dependente" value="1">
                                    <input type="hidden" name="dep_slot" value="<?php echo esc_attr( $mao_dep['slot'] ); ?>">
                                    <button type="submit"><i class="fas fa-trash"></i> Remover</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ( $mao_livre > 0 ) : ?>
                <!-- Formulario para acrescentar mais um dependente -->
                <form method="post" class="mao-dep-form">
                    <?php wp_nonce_field( 'explode_mao_dep_' . $user_id, 'explode_mao_dep_nonce' ); ?>
                    <input type="hidden" name="explode_mao_add_dependente" value="1">

                    <!-- Nome do dependente -->
                    <div class="mao-dep-campo nome">
                        <label for="mao-dep-nome"><i class="fas fa-child"></i> Nome completo do dependente</label>
                        <input type="text" id="mao-dep-nome" name="dep_nome" maxlength="120" required
                               autocomplete="off" placeholder="Ex.: Maria Clara Souza">
                    </div>

                    <!-- Faixa de idade: define a categoria em que o desenho vai concorrer -->
                    <div class="mao-dep-campo faixa">
                        <span class="mao-dep-rotulo"><i class="fas fa-birthday-cake"></i> Idade do dependente</span>
                        <div class="mao-faixas">
                            <?php foreach ( explode_mao_faixas_opcoes( $user_id ) as $mao_op ) : ?>
                                <label class="mao-faixa">
                                    <input type="radio" name="dep_faixa" value="<?php echo esc_attr( $mao_op['slug'] ); ?>" required>
                                    <span class="mao-faixa-card">
                                        <span class="mao-faixa-idade"><?php echo esc_html( $mao_op['descricao'] ); ?></span>
                                        <span class="mao-faixa-cat"><?php echo esc_html( $mao_op['categoria'] ); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Botao de acrescentar -->
                    <div class="mao-dep-campo acao">
                        <button type="submit"><i class="fas fa-plus-circle"></i> Adicionar dependente</button>
                        <span class="mao-dep-contador">
                            <?php echo esc_html( count( $mao_deps ) ); ?> de <?php echo esc_html( explode_mao_max_dependentes() ); ?> cadastrados
                        </span>
                    </div>
                </form>
            <?php else : ?>
                <!-- Limite de dependentes atingido -->
                <div class="mao-dep-aviso limite">
                    <i class="fas fa-info-circle"></i>
                    <span>Você já cadastrou o limite de <?php echo esc_html( explode_mao_max_dependentes() ); ?> dependentes. Para trocar algum, remova um que ainda não enviou desenho.</span>
                </div>
            <?php endif; ?>

            <?php if ( empty( $mao_deps ) ) : ?>
                <!-- Orientacao quando ainda nao ha nenhum dependente -->
                <div class="mao-dep-vazio">
                    <i class="fas fa-arrow-up"></i>
                    <span>Cadastre o primeiro dependente acima para liberar o envio do desenho.</span>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- LOOP DOS DEPENDENTES POSSIVEIS DO COLABORADOR (limite definido em functions.php) -->
        <?php for ( $i = 1; $i <= $total_dependentes; $i++ ) :

            // Obtem o nome do dependente no usermeta
            $dep_nome_raw = get_user_meta( $user_id, 'user_field_dependente_' . $i . '_nome', true );

            // Se o nome do dependente estiver vazio, nao renderiza o formulario
            if ( empty( $dep_nome_raw ) ) {
                // Pula para o proximo indice
                continue;
            }

            // Formata o nome para apresentacao visual
            $dep_nome = ucwords( strtolower( trim( (string)$dep_nome_raw ) ) );

            // Obtem categorias do dependente
            $dep_cat_raw = get_user_meta( $user_id, 'user_field_dependente_' . $i . '_cat', true );
            // Array com os nomes das categorias
            $lista_categorias = array();
            // Processa as categorias se existirem
            if ( ! empty( $dep_cat_raw ) ) {
                // Divide por virgula, ponto ou espaco
                $cat_ids_arr = preg_split( '/[,\.\s]+/u', (string)$dep_cat_raw, -1, PREG_SPLIT_NO_EMPTY );
                // Busca o nome de cada termo
                foreach ( $cat_ids_arr as $c_id_raw ) {
                    // Converte para inteiro
                    $c_id_int = absint( trim( $c_id_raw ) );
                    // Se for valido
                    if ( $c_id_int > 0 ) {
                        // Busca o termo
                        $termo_cat = get_term( $c_id_int, 'desenhos_cat' );
                        // Se existir adiciona o nome
                        if ( $termo_cat && ! is_wp_error( $termo_cat ) ) {
                            // Adiciona a lista
                            $lista_categorias[] = $termo_cat->name;
                        }
                    }
                }
            }

            // Verifica se o dependente ja possui desenho enviado
            $is_enviado = isset( $mapa_enviados[$dep_nome] ) || isset( $mapa_enviados['dep_' . $i] );

            // Verifica se este dependente tem status PCD
            $pcd_user_meta = get_user_meta( $user_id, 'user_field_dependente_' . $i . '_pcd', true );
            // Define se o checkbox de PCD deve vir marcado
            $is_pcd_marcado = ( ( isset( $mapa_pcd[$dep_nome] ) && 'Sim' === $mapa_pcd[$dep_nome] ) || 'Sim' === $pcd_user_meta );

            // Recupera o laudo de PCD ja enviado para este dependente, quando houver
            $laudo_atual_id  = (int) get_user_meta( $user_id, 'user_field_dependente_' . $i . '_laudo', true );
            // Confirma que o anexo continua existindo na biblioteca de midia
            $laudo_atual_id  = ( $laudo_atual_id > 0 && get_post( $laudo_atual_id ) ) ? $laudo_atual_id : 0;
            // Monta a URL do laudo para o link de conferencia
            $laudo_atual_url = $laudo_atual_id ? wp_get_attachment_url( $laudo_atual_id ) : '';

            // Verifica se houve erro no envio deste dependente
            $tem_erro_atual = isset( $resultados_envio[$i] ) && $resultados_envio[$i]['errors'];
        ?>
            <!-- Bloco do dependente individual -->
            <div class="single <?php echo $is_enviado ? 'enviado' : ''; ?> <?php echo $tem_erro_atual ? 'open' : ''; ?>" id="dep<?php echo esc_attr( $i ); ?>">
                <!-- Cabecalho do dependente com trigger para abrir/fechar acordeon -->
                <div class="head">
                    <div class="limit">
                        <!-- Link com icone e nome do dependente -->
                        <a id="name" href="#" class="notscrollable"><i class="fas fa-check"></i> <?php echo esc_html( $dep_nome ); ?></a>
                        <!-- Link com as categorias vinculadas -->
                        <a id="cat" href="#" class="notscrollable">
                            <?php echo ! empty( $lista_categorias ) ? esc_html( implode( ', ', $lista_categorias ) ) : 'Sem categoria'; ?>
                        </a>
                    </div>
                    <!-- Indicador quando pendente -->
                    <a id="wait" class="waiting notscrollable" href="#">
                        <?php echo $is_enviado ? 'Clique para reenviar outro desenho' : 'Clique para enviar o desenho'; ?>
                    </a>
                    <!-- Indicador quando ja enviado -->
                    <a id="wait" class="sent notscrollable" href="#">
                        Enviado<br><span style="font-size: 10px;">Clique para reenviar o desenho.</span>
                    </a>
                </div>

                <!-- Corpo do formulario de submissao -->
                <div class="body">
                    <?php
                    // Se houver mensagens de erro para este dependente, exibe em destaque
                    if ( $tem_erro_atual && ! empty( $resultados_envio[$i]['warnings'] ) ) {
                        // Abre paragrafo de erro
                        echo '<p class="erro-main">';
                        // Itera sobre as mensagens
                        foreach ( $resultados_envio[$i]['warnings'] as $aviso ) {
                            // Imprime cada mensagem
                            echo '<span>' . esc_html( $aviso ) . '</span>';
                        }
                        // Fecha paragrafo de erro
                        echo '</p>';
                    }
                    ?>

                    <!-- Formulario de envio do desenho submetendo para a pagina atual -->
                    <form action="" class="loading form-envio-desenho" method="POST" enctype="multipart/form-data">
                        
                        <!-- Identificador de acao de envio -->
                        <input type="hidden" name="explode_enviar_desenho_action" value="1">
                        <!-- Numero do dependente -->
                        <input type="hidden" name="dependente" value="<?php echo esc_attr( $i ); ?>">

                        <!-- Campo de selecao do arquivo de imagem -->
                        <div class="line first">
                            <label for="campo-arquivo-<?php echo esc_attr( $i ); ?>">Selecione o desenho:</label>
                            <input type="file" id="campo-arquivo-<?php echo esc_attr( $i ); ?>" name="desenho" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                        </div>

                        <!-- Termo de concordancia e informacao PCD -->
                        <div class="line check">
                            <!-- Checkbox de concordancia obrigatoria -->
                            <label>
                                <input type="checkbox" class="submeter" name="confirmacao" value="sim">
                                Ao submeter o desenho do(a) meu(minha) filho(a) / tutelado(a) ao concurso "Explode Criação", manifesto expressa concordância com o armazenamento e tratamento dos dados pessoais submetidos, bem como concordo com a divulgação do nome e do desenho do(a) menor.
                            </label>
                            
                            <!-- Escolha PCD: radio Nao/Sim, com Nao marcado por padrao -->
                            <div class="pcd-escolha">
                                <span class="pcd-escolha-pergunta">Este dependente é PCD (Pessoa com Deficiência)?</span>
                                <div class="pcd-escolha-opcoes">
                                    <label class="pcd-opcao">
                                        <input type="radio" name="confirmacao_pcd_<?php echo esc_attr( $i ); ?>" value="nao" <?php checked( ! $is_pcd_marcado ); ?>>
                                        <span>Não</span>
                                    </label>
                                    <label class="pcd-opcao">
                                        <input type="radio" name="confirmacao_pcd_<?php echo esc_attr( $i ); ?>" value="sim" <?php checked( $is_pcd_marcado ); ?>>
                                        <span>Sim</span>
                                    </label>
                                </div>
                            </div>

                            <?php if ( $mao_bloco_pcd ) : ?>
                            <!-- BLOCO DE PCD DOS GRUPOS MAO: so existe quando o administrador liga
                                 o laudo ou o termo em "Opções gerais" > "Regras dos grupos MAO" -->
                            <div class="pcd-laudo" id="pcd-laudo-<?php echo esc_attr( $i ); ?>"
                                 data-tem-laudo="<?php echo $laudo_atual_id ? '1' : '0'; ?>"
                                 data-exige-laudo="<?php echo $mao_exige_laudo ? '1' : '0'; ?>"
                                 data-exige-termo="<?php echo $mao_exige_termo ? '1' : '0'; ?>"
                                 style="display: none;">

                                <!-- Aviso do que está sendo exigido neste momento -->
                                <div class="pcd-laudo-alerta">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div class="pcd-laudo-alerta-texto">
                                        <strong>
                                            <?php if ( $mao_exige_laudo && $mao_exige_termo ) : ?>
                                                Laudo e termo obrigatórios para dependente PCD
                                            <?php elseif ( $mao_exige_laudo ) : ?>
                                                Laudo obrigatório para dependente PCD
                                            <?php else : ?>
                                                Termo obrigatório para dependente PCD
                                            <?php endif; ?>
                                        </strong>
                                        <?php if ( $mao_exige_laudo ) : ?>
                                            <span><?php echo esc_html( explode_mao_texto_laudo() ); ?></span>
                                        <?php endif; ?>
                                        <span class="pcd-laudo-regra">O botão de envio permanece bloqueado até que a exigência seja cumprida.</span>
                                    </div>
                                </div>

                                <?php if ( $mao_exige_laudo ) : ?>
                                    <!-- Campo de anexo do laudo -->
                                    <label class="pcd-laudo-label" for="campo-laudo-<?php echo esc_attr( $i ); ?>">Anexe o laudo do(a) dependente:</label>
                                    <input type="file" id="campo-laudo-<?php echo esc_attr( $i ); ?>" name="laudo_pcd" class="pcd-laudo-arquivo" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                    <span class="pcd-laudo-formatos">Formatos aceitos: PDF, JPG, JPEG ou PNG (até <?php echo esc_html( size_format( explode_mao_tamanho_max_laudo() ) ); ?>).</span>

                                    <!-- Situacao do anexo, atualizada em tempo real -->
                                    <p class="pcd-laudo-status pendente"><i class="fas fa-exclamation-circle"></i> Nenhum laudo anexado.</p>

                                    <?php if ( $laudo_atual_id ) : ?>
                                        <!-- Confirmacao de que ja existe um laudo enviado anteriormente -->
                                        <p class="pcd-laudo-enviado">
                                            Laudo do envio anterior:
                                            <a href="<?php echo esc_url( $laudo_atual_url ); ?>" target="_blank" rel="noopener">Ver arquivo salvo</a>.
                                            <br><span style="color: #ffcc00; font-weight: bold;">Atenção: É obrigatório anexar o arquivo do laudo novamente para este envio.</span>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ( $mao_exige_termo ) : ?>
                                    <!-- Aceite obrigatorio do Termo de Uso de Imagem e Voz -->
                                    <label class="pcd-laudo-termo">
                                        <input type="checkbox" name="termo_imagem_voz" value="sim" class="termo-imagem-voz">
                                        <?php
                                        // Permite ligar um documento do termo pelo filtro explode_mao_url_termo_imagem
                                        $url_termo = apply_filters( 'explode_mao_url_termo_imagem', '' );
                                        ?>
                                        Li e aceito o
                                        <?php if ( ! empty( $url_termo ) ) : ?>
                                            <a href="<?php echo esc_url( $url_termo ); ?>" target="_blank" rel="noopener"><strong>Termo de Uso de Imagem e Voz</strong></a>.
                                        <?php else : ?>
                                            <strong>Termo de Uso de Imagem e Voz</strong>.
                                        <?php endif; ?>
                                    </label>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( $mao_bloco_pcd ) : ?>
                        <!-- Lista do que ainda falta para liberar o envio (grupos MAO) -->
                        <div class="pcd-pendencias" style="display: none;">
                            <i class="fas fa-lock"></i>
                            <div>
                                <strong>Envio bloqueado</strong>
                                <ul></ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Botao de submissao do formulario -->
                        <div class="line">
                            <input type="submit" name="dep<?php echo esc_attr( $i ); ?>" value="<?php echo $is_enviado ? 'Reenviar Desenho' : 'Enviar'; ?>">
                        </div>
                    </form>
                </div>
            </div>
        <?php endfor; ?>
        <!-- FIM DO LOOP DE DEPENDENTES -->

        <!-- GALERIA DE DESENHOS JA ENVIADOS -->
        <div class="dependentes">
            <?php
            // Re-executa query dos desenhos para exibir a galeria de trabalhos
            $query_galeria = new WP_Query( array(
                // Post type desenhos
                'post_type'      => 'desenhos',
                // Sem limite
                'posts_per_page' => -1,
                // Ordem por data
                'orderby'        => 'date',
                // Crescente
                'order'          => 'ASC',
                // Autor atual
                'author'         => $user_id,
                // Status publicado
                'post_status'    => 'publish',
            ) );

            // Se houver posts de desenhos enviados pelo usuario
            if ( $query_galeria->have_posts() ) : ?>
                <div class="all loop-drawings desenhos_enviados" style="margin-top: 30px;">
                    <!-- Titulo da galeria -->
                    <h2 style="color: #fff; text-align: left; margin-bottom: 15px;">Desenhos enviados</h2>
                    
                    <?php while ( $query_galeria->have_posts() ) :
                        // Prepara o post atual
                        $query_galeria->the_post();
                        // ID do post do desenho
                        $gid = get_the_ID();
                        // Titulo do post (nome do dependente)
                        $gnome = get_the_title();
                        // URL da imagem gravada no meta
                        $gurl = get_post_meta( $gid, 'desenhos_box_desenho', true );
                        // URL para thumbnail
                        $gthumb = $gurl;

                        // Se a URL for valida, tenta obter thumbnail customizado
                        if ( ! empty( $gurl ) ) {
                            // Tenta encontrar ID do anexo a partir da URL
                            $aid = attachment_url_to_postid( $gurl );
                            // Se encontrar anexo
                            if ( $aid > 0 ) {
                                // Obtem miniatura
                                $src_arr = wp_get_attachment_image_src( $aid, 'desenho_thumb' );
                                // Se existir atribui
                                if ( $src_arr && ! empty( $src_arr[0] ) ) {
                                    // Atribui miniatura
                                    $gthumb = $src_arr[0];
                                }
                            }
                        }
                    ?>
                        <!-- Item da galeria com imagem e lightbox -->
                        <div class="single">
                            <!-- Titulo do dependente -->
                            <span id="cat" style="color: #fff; font-weight: 600;"><?php echo esc_html( $gnome ); ?></span>
                            <!-- Link para visualizacao em tamanho real -->
                            <a href="<?php echo esc_url( $gurl ); ?>" data-toggle="lightbox" data-gallery="galeriastander">  
                                <label class="borderImg" style="background-image: url('<?php echo esc_url( $gthumb ); ?>');"></label>
                            </a>
                        </div>
                    <?php endwhile; ?>
                    <!-- Restaura o postdata global -->
                    <?php wp_reset_postdata(); ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- FIM DA GALERIA DE DESENHOS -->

    </div>
</div>

<?php if ( $mao_bloco_pcd ) : ?>
<!-- ESTILO DO BLOCO DE LAUDO DE PCD (GRUPOS MAO) -->
<style>
    .pcd-laudo {
        margin-top: 14px;
        padding: 14px 16px;
        border: 1px dashed rgba(255, 255, 255, .45);
        border-radius: 8px;
        background: rgba(255, 255, 255, .07);
    }
    /* Aviso destacado da obrigatoriedade do laudo */
    .pcd-laudo .pcd-laudo-alerta {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin: 0 0 16px;
        padding: 14px 16px;
        border-radius: 8px;
        border-left: 4px solid #ffc107;
        background: rgba(255, 193, 7, .16);
    }
    .pcd-laudo .pcd-laudo-alerta > i {
        flex-shrink: 0;
        margin-top: 2px;
        font-size: 18px;
        color: #ffc107;
    }
    .pcd-laudo .pcd-laudo-alerta-texto {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .pcd-laudo .pcd-laudo-alerta-texto strong {
        font-size: 14px;
        letter-spacing: .2px;
    }
    .pcd-laudo .pcd-laudo-alerta-texto span {
        font-size: 12.5px;
        line-height: 1.55;
    }
    .pcd-laudo .pcd-laudo-regra {
        font-weight: 700;
        opacity: .95;
    }

    /* Situacao do anexo */
    .pcd-laudo .pcd-laudo-status {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 10px 0 0;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
    }
    .pcd-laudo .pcd-laudo-status strong { font-weight: 700; word-break: break-all; }
    .pcd-laudo .pcd-laudo-status.pendente {
        background: rgba(220, 53, 69, .18);
        border: 1px solid rgba(220, 53, 69, .45);
    }
    .pcd-laudo .pcd-laudo-status.ok {
        background: rgba(40, 167, 69, .18);
        border: 1px solid rgba(40, 167, 69, .45);
    }

    /* Caixa que lista o que falta para liberar o envio */
    .pcd-pendencias {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin: 0 0 14px;
        padding: 14px 16px;
        border-radius: 8px;
        border-left: 4px solid #dc3545;
        background: rgba(220, 53, 69, .14);
    }
    .pcd-pendencias > i {
        flex-shrink: 0;
        margin-top: 2px;
        font-size: 17px;
        color: #dc3545;
    }
    .pcd-pendencias strong {
        display: block;
        margin-bottom: 6px;
        font-size: 13.5px;
    }
    .pcd-pendencias ul {
        margin: 0;
        padding-left: 18px;
        font-size: 12.5px;
        line-height: 1.7;
    }
    .pcd-pendencias li { list-style: disc; }
    .pcd-laudo .pcd-laudo-label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
    }
    .pcd-laudo .pcd-laudo-arquivo { display: block; max-width: 100%; }
    .pcd-laudo .pcd-laudo-formatos {
        display: block;
        margin-top: 6px;
        font-size: 11.5px;
        opacity: .8;
    }
    .pcd-laudo .pcd-laudo-enviado {
        margin: 10px 0 0;
        font-size: 12px;
        line-height: 1.5;
    }
    .pcd-laudo .pcd-laudo-enviado a { text-decoration: underline; }
    .pcd-laudo .pcd-laudo-termo {
        display: block;
        margin-top: 12px;
        font-size: 13px;
        line-height: 1.5;
    }
</style>
<?php endif; ?>

<?php if ( $mao_cadastro_dep ) : ?>
<!-- ESTILO DO CADASTRO DE DEPENDENTES (grupos MAO) -->
<style>
    .mao-dependentes {
        margin: 0 0 30px;
        padding: 24px 26px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, .28);
        background: rgba(255, 255, 255, .06);
        color: #fff;
    }
    .mao-dependentes .mao-dep-topo h3 {
        margin: 0 0 8px;
        font-size: 19px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .mao-dependentes .mao-dep-topo p {
        margin: 0 0 18px;
        font-size: 13.5px;
        line-height: 1.6;
        opacity: .92;
    }

    /* Mensagens de retorno */
    .mao-dependentes .mao-dep-aviso {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 14px;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13.5px;
        line-height: 1.5;
        font-weight: 600;
    }
    .mao-dependentes .mao-dep-aviso.ok     { background: rgba(40,167,69,.20);  border: 1px solid rgba(40,167,69,.55); }
    .mao-dependentes .mao-dep-aviso.erro   { background: rgba(220,53,69,.20);  border: 1px solid rgba(220,53,69,.55); }
    .mao-dependentes .mao-dep-aviso.limite { background: rgba(84,197,207,.16); border: 1px solid rgba(84,197,207,.5); }

    /* Lista dos dependentes cadastrados */
    .mao-dependentes .mao-dep-lista { margin: 0 0 18px; padding: 0; list-style: none; }
    .mao-dependentes .mao-dep-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 8px;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, .22);
        background: rgba(0, 0, 0, .16);
    }
    .mao-dependentes .mao-dep-info { display: flex; flex-direction: column; gap: 3px; }
    .mao-dependentes .mao-dep-info strong { font-size: 16px; }
    .mao-dependentes .mao-dep-info span { font-size: 12.5px; opacity: .85; }
    .mao-dependentes .mao-dep-tag.enviado {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        background: rgba(40, 167, 69, .22);
        border: 1px solid rgba(40, 167, 69, .55);
        font-size: 12px;
        font-weight: 700;
    }
    .mao-dependentes .mao-dep-remover { margin: 0; }
    .mao-dependentes .mao-dep-remover button {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 16px;
        border-radius: 7px;
        border: 1px solid rgba(220, 53, 69, .6);
        background: rgba(220, 53, 69, .16);
        color: #fff;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background .18s ease;
    }
    .mao-dependentes .mao-dep-remover button:hover { background: rgba(220, 53, 69, .34); }

    /* Formulario de cadastro */
    .mao-dependentes .mao-dep-form {
        display: flex;
        gap: 20px;
        flex-direction: column;
        padding-top: 20px;
        border-top: 1px dashed rgba(255, 255, 255, .28);
    }
    .mao-dependentes .mao-dep-campo { display: flex; flex-direction: column; gap: 9px; }
    .mao-dependentes .mao-dep-campo label,
    .mao-dependentes .mao-dep-rotulo {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .2px;
    }
    .mao-dependentes .mao-dep-campo label i,
    .mao-dependentes .mao-dep-rotulo i { color: #54c5cf; font-size: 16px; }

    /* Campo de nome */
    .mao-dependentes .mao-dep-campo.nome input {
        width: 100%;
        padding: 17px 20px;
        border-radius: 10px;
        border: 2px solid rgba(255, 255, 255, .35);
        background: #fff;
        color: #143240;
        font-size: 17px;
        font-weight: 600;
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .mao-dependentes .mao-dep-campo.nome input::placeholder { color: #9aa8b0; font-weight: 400; }
    .mao-dependentes .mao-dep-campo.nome input:focus {
        outline: none;
        border-color: #54c5cf;
        box-shadow: 0 0 0 5px rgba(84, 197, 207, .24);
    }

    /* Cartoes de faixa etaria */
    .mao-dependentes .mao-faixas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 12px;
    }
    .mao-dependentes .mao-faixa { position: relative; display: block; margin: 0; }
    .mao-dependentes .mao-faixa input {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        margin: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .mao-dependentes .mao-faixa-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 100%;
        padding: 20px 14px;
        border-radius: 12px;
        border: 2px solid rgba(255, 255, 255, .32);
        background: rgba(255, 255, 255, .07);
        text-align: center;
        cursor: pointer;
        transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .12s ease;
    }
    .mao-dependentes .mao-faixa-idade {
        font-size: 19px;
        font-weight: 800;
        line-height: 1.15;
    }
    .mao-dependentes .mao-faixa-cat {
        font-size: 12.5px;
        font-weight: 600;
        letter-spacing: .4px;
        text-transform: uppercase;
        opacity: .8;
    }
    .mao-dependentes .mao-faixa:hover .mao-faixa-card {
        border-color: rgba(255, 255, 255, .75);
        background: rgba(255, 255, 255, .13);
    }
    .mao-dependentes .mao-faixa input:active + .mao-faixa-card { transform: scale(.98); }
    .mao-dependentes .mao-faixa input:checked + .mao-faixa-card {
        border-color: #54c5cf;
        background: rgba(84, 197, 207, .24);
        box-shadow: 0 0 0 4px rgba(84, 197, 207, .18);
    }
    .mao-dependentes .mao-faixa input:checked + .mao-faixa-card .mao-faixa-cat { opacity: 1; color: #bdf0f5; }
    .mao-dependentes .mao-faixa input:focus-visible + .mao-faixa-card {
        outline: 3px solid #fff;
        outline-offset: 3px;
    }

    /* Botao de acrescentar */
    .mao-dependentes .mao-dep-campo.acao {
        flex-direction: row;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .mao-dependentes .mao-dep-campo.acao button {
        display: inline-flex;
        align-items: center;
        gap: 11px;
        padding: 17px 34px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(90deg, #5b9b99, #54c5cf);
        color: #fff;
        font-size: 16px;
        font-weight: 800;
        letter-spacing: .3px;
        cursor: pointer;
        box-shadow: 0 6px 18px -6px rgba(84, 197, 207, .8);
        transition: filter .18s ease, transform .12s ease;
    }
    .mao-dependentes .mao-dep-campo.acao button i { font-size: 19px; }
    .mao-dependentes .mao-dep-campo.acao button:hover { filter: brightness(1.09); }
    .mao-dependentes .mao-dep-campo.acao button:active { transform: translateY(1px); }
    .mao-dependentes .mao-dep-contador {
        font-size: 13px;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 20px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .28);
    }

    /* Orientacao quando ainda nao ha dependentes */
    .mao-dependentes .mao-dep-vazio {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 16px;
        padding: 12px 16px;
        border-radius: 8px;
        background: rgba(255, 193, 7, .16);
        border: 1px solid rgba(255, 193, 7, .5);
        font-size: 13px;
        font-weight: 600;
    }

    /* Telas estreitas */
    @media (max-width: 640px) {
        .mao-dependentes .mao-dep-campo.nome,
        .mao-dependentes .mao-dep-campo.idade,
        .mao-dependentes .mao-dep-campo.acao { flex: 1 1 100%; }
        .mao-dependentes .mao-dep-campo.acao button { width: 100%; justify-content: center; }
        .mao-dependentes .mao-dep-item { flex-direction: column; align-items: flex-start; }
    }
</style>
<?php endif; ?>

<!-- CAIXA DE CONFIRMACAO DE PCD (todos os grupos)
     Aparece ao clicar em enviar quando o dependente foi marcado como PCD, para que
     ninguem envie o desenho na categoria PCD por engano. Fica uma unica vez na pagina
     e atende todos os formularios de dependente. -->
<div class="pcd-confirma" id="pcd-confirma" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="pcd-confirma-titulo">
    <div class="pcd-confirma-caixa">
        <div class="pcd-confirma-icone"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 class="pcd-confirma-titulo" id="pcd-confirma-titulo">Confirme antes de enviar</h3>
        <p class="pcd-confirma-texto">
            Você selecionou que o(a) dependente <strong class="pcd-confirma-nome"></strong>
            é <strong>PCD (Pessoa com Deficiência)</strong>.
        </p>
        <p class="pcd-confirma-texto">
            Tem certeza disso? Se estiver correto, confirme no botão abaixo. Caso contrário,
            volte e altere a resposta para <strong>Não</strong>.
        </p>
        <div class="pcd-confirma-acoes">
            <button type="button" class="pcd-confirma-voltar">Voltar e corrigir</button>
            <button type="button" class="pcd-confirma-ok">Sim, confirmo que é PCD</button>
        </div>
    </div>
</div>

<!-- ESTILO DA CAIXA DE CONFIRMACAO DE PCD -->
<style>
    .pcd-confirma {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(4, 20, 28, .78);
    }
    .pcd-confirma .pcd-confirma-caixa {
        width: 100%;
        max-width: 520px;
        max-height: 90vh;
        overflow-y: auto;
        padding: 30px 32px 26px;
        border-radius: 14px;
        border-top: 6px solid #ffc107;
        background: #143240;
        color: #fff;
        text-align: center;
        box-shadow: 0 24px 60px -18px rgba(0, 0, 0, .75);
    }
    .pcd-confirma .pcd-confirma-icone {
        margin-bottom: 14px;
        font-size: 38px;
        line-height: 1;
        color: #ffc107;
    }
    .pcd-confirma .pcd-confirma-titulo {
        margin: 0 0 14px;
        font-size: 22px;
        font-weight: 800;
        color: #fff;
    }
    .pcd-confirma .pcd-confirma-texto {
        margin: 0 0 12px;
        font-size: 15px;
        line-height: 1.6;
    }
    .pcd-confirma .pcd-confirma-nome { color: #54c5cf; }
    .pcd-confirma .pcd-confirma-acoes {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 22px;
    }
    .pcd-confirma .pcd-confirma-acoes button {
        flex: 1 1 200px;
        padding: 15px 22px;
        border-radius: 9px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        border: 2px solid transparent;
        transition: filter .18s ease, background .18s ease, transform .12s ease;
    }
    .pcd-confirma .pcd-confirma-ok {
        border-color: #54c5cf;
        background: linear-gradient(90deg, #5b9b99, #54c5cf);
        color: #fff;
    }
    .pcd-confirma .pcd-confirma-ok:hover { filter: brightness(1.09); }
    .pcd-confirma .pcd-confirma-voltar {
        border-color: rgba(255, 255, 255, .5);
        background: transparent;
        color: #fff;
    }
    .pcd-confirma .pcd-confirma-voltar:hover { background: rgba(255, 255, 255, .12); }
    .pcd-confirma .pcd-confirma-acoes button:active { transform: translateY(1px); }

    /* Telas estreitas: um botao por linha */
    @media (max-width: 480px) {
        .pcd-confirma .pcd-confirma-caixa { padding: 26px 20px 22px; }
        .pcd-confirma .pcd-confirma-acoes button { flex: 1 1 100%; }
    }
</style>

<!-- ESTILO DA ESCOLHA DE PCD (todos os grupos) -->
<style>
    /* Bloco da pergunta */
    .pcd-escolha {
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid rgba(255, 255, 255, .18);
    }
    .pcd-escolha .pcd-escolha-pergunta {
        display: block;
        margin-bottom: 14px;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.5;
        letter-spacing: .2px;
    }
    .pcd-escolha .pcd-escolha-opcoes {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
    }

    /* Cada alternativa: o radio nativo fica invisivel por cima, o span e o botao visivel */
    .pcd-escolha .pcd-opcao {
        position: relative;
        display: inline-flex;
        margin: 0;
    }
    .pcd-escolha .pcd-opcao input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        margin: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .pcd-escolha .pcd-opcao span {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        min-width: 160px;
        padding: 18px 28px;
        border-radius: 10px;
        border: 2px solid rgba(255, 255, 255, .35);
        background: rgba(255, 255, 255, .07);
        font-size: 18px;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .12s ease;
    }

    /* Bolinha desenhada por CSS, bem maior que o radio padrao */
    .pcd-escolha .pcd-opcao span::before {
        content: "";
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, .65);
        background: transparent;
        box-shadow: inset 0 0 0 6px transparent;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    /* Passagem do mouse */
    .pcd-escolha .pcd-opcao:hover span {
        border-color: rgba(255, 255, 255, .8);
        background: rgba(255, 255, 255, .13);
    }
    .pcd-escolha .pcd-opcao input:active + span { transform: scale(.98); }

    /* Alternativa escolhida */
    .pcd-escolha .pcd-opcao input:checked + span {
        border-color: #54c5cf;
        background: rgba(84, 197, 207, .22);
        font-weight: 800;
        box-shadow: 0 0 0 4px rgba(84, 197, 207, .16);
    }
    .pcd-escolha .pcd-opcao input:checked + span::before {
        border-color: #54c5cf;
        box-shadow: inset 0 0 0 6px #54c5cf;
    }

    /* Navegacao por teclado */
    .pcd-escolha .pcd-opcao input:focus-visible + span {
        outline: 3px solid #fff;
        outline-offset: 3px;
    }

    /* Telas estreitas: uma alternativa por linha, ocupando a largura toda */
    @media (max-width: 560px) {
        .pcd-escolha .pcd-escolha-opcoes { flex-direction: column; gap: 10px; }
        .pcd-escolha .pcd-opcao { width: 100%; }
        .pcd-escolha .pcd-opcao span { width: 100%; min-width: 0; justify-content: flex-start; }
    }
</style>

<!-- SCRIPT DE CONTROLE DO ACORDEON E HABILITACAO DO BOTAO -->
<script>
(function($) {
    'use strict';

    // Alterna a abertura e fechamento dos acordeons ao clicar no cabecalho
    $(document).on('click', '.all-forms .all .single .head a', function(e) {
        // Evita a navegacao padrao da tag a
        e.preventDefault();
        // Alterna a classe open no container pai
        $(this).closest('.single').toggleClass('open');
    });

    // Se houver hash ou parametro de dependente na URL, abre o bloco correspondente
    $(window).on('load', function() {
        <?php if ( ! empty( $_GET['dependente'] ) ) : ?>
            // Obtem o id do dependente sanitizado
            var depAlvo = '<?php echo esc_js( sanitize_key( $_GET['dependente'] ) ); ?>';
            // Abre o bloco correspondente
            $('#' + depAlvo).addClass('open');
        <?php endif; ?>
    });

    // ############### LAUDO DE PCD DOS GRUPOS MAO ###############

    // Informa se o dependente deste formulario foi marcado como PCD
    // O campo e um par de radios Nao/Sim; o checkbox antigo continua funcionando
    function explodePcdAtivo($form) {
        // Opcao "Sim" do radio
        var $sim = $form.find('input[name^="confirmacao_pcd_"][value="sim"]');
        // Quando o radio existe, vale o estado dele
        if ($sim.length) { return $sim.is(':checked'); }
        // Compatibilidade com a versao em checkbox
        return $form.find('input[name^="confirmacao_pcd_"]').is(':checked');
    }

    // Abre ou fecha o bloco do laudo conforme a escolha de PCD
    function explodeAlternarLaudo($origem) {
        // Aceita tanto o campo alterado quanto o proprio formulario
        var $form  = $origem.is('form') ? $origem : $origem.closest('form');
        var $bloco = $form.find('.pcd-laudo');
        // Grupos que nao sao MAO nao possuem o bloco
        if (!$bloco.length) { return; }

        // Campos internos do bloco
        var $arquivo = $bloco.find('.pcd-laudo-arquivo');
        var $termo   = $bloco.find('.termo-imagem-voz');
        // Indica se ja existe um laudo enviado anteriormente
        var jaTem    = String($bloco.data('tem-laudo')) === '1';

        // Exigencias ligadas pelo administrador neste bloco
        var exigeLaudo = String($bloco.data('exige-laudo')) === '1';
        var exigeTermo = String($bloco.data('exige-termo')) === '1';

        // Dependente marcado como PCD
        if (explodePcdAtivo($form)) {
            // Mostra o bloco
            $bloco.stop(true, true).slideDown(150);
            // O arquivo so e obrigatorio quando a regra do laudo esta ligada
            if (exigeLaudo) { $arquivo.attr('required', 'required'); }
            else { $arquivo.removeAttr('required'); }
            // O aceite so e obrigatorio quando a regra do termo esta ligada
            if (exigeTermo) { $termo.attr('required', 'required'); }
            else { $termo.removeAttr('required'); }
        } else {
            // Esconde o bloco e limpa os campos para nao enviar dado indevido
            $bloco.stop(true, true).slideUp(150);
            $arquivo.removeAttr('required').val('');
            $termo.removeAttr('required').prop('checked', false);
        }

        // Atualiza a situacao do anexo e o estado do botao
        explodeStatusLaudo($bloco);
        explodeAtualizarEnvio($form);
    }

    // Atualiza a linha que informa se o laudo ja foi anexado
    function explodeStatusLaudo($bloco) {
        // Sem bloco nao ha o que atualizar
        if (!$bloco.length) { return; }

        // Elementos envolvidos
        var $arquivo = $bloco.find('.pcd-laudo-arquivo');
        var $status  = $bloco.find('.pcd-laudo-status');
        // Laudo gravado em um envio anterior
        var jaTem    = String($bloco.data('tem-laudo')) === '1';
        // Arquivo escolhido agora
        var arquivos = ($arquivo.length && $arquivo[0].files) ? $arquivo[0].files : null;

        // Arquivo novo selecionado
        if (arquivos && arquivos.length > 0) {
            $status.attr('class', 'pcd-laudo-status ok')
                   .html('<i class="fas fa-check-circle"></i> Laudo selecionado: <strong></strong>');
            // Usa text() para nao interpretar o nome do arquivo como HTML
            $status.find('strong').text(arquivos[0].name);
        }
        // Ainda sem laudo
        else {
            $status.attr('class', 'pcd-laudo-status pendente')
                   .html('<i class="fas fa-exclamation-circle"></i> Nenhum laudo anexado.');
        }
    }

    // Descobre o que ainda falta para liberar o envio deste formulario
    function explodePendencias($form) {
        // Lista de exigencias nao atendidas
        var pendencias = [];

        // Concordancia com o uso dos dados, exigida de todos os grupos
        if (!$form.find('input.submeter').is(':checked')) {
            pendencias.push('Marcar a caixa de concordancia com o uso dos dados.');
        }

        // Desenho obrigatório
        var $desenho = $form.find('input[name="desenho"]');
        if ($desenho.length && (!$desenho[0].files || $desenho[0].files.length === 0)) {
            pendencias.push('Anexar o arquivo do desenho.');
        }

        // Exigencias do laudo: valem apenas quando o bloco existe (grupos MAO)
        // e o dependente foi marcado como PCD
        var $bloco = $form.find('.pcd-laudo');

        if ($bloco.length && explodePcdAtivo($form)) {
            // Exigencias ligadas pelo administrador neste bloco
            var exigeLaudo = String($bloco.data('exige-laudo')) === '1';
            var exigeTermo = String($bloco.data('exige-termo')) === '1';

            // LAUDO: cobrado apenas quando a regra esta ligada
            if (exigeLaudo) {
                // Campo do arquivo
                var $arquivo = $bloco.find('.pcd-laudo-arquivo');
                // Arquivo escolhido agora
                var temNovo  = !!($arquivo.length && $arquivo[0].files && $arquivo[0].files.length > 0);
                // Sem laudo novo o envio fica bloqueado
                if (!temNovo) {
                    pendencias.push('Anexar o laudo do dependente PCD.');
                }
            }

            // TERMO: cobrado apenas quando a regra esta ligada
            if (exigeTermo && !$bloco.find('.termo-imagem-voz').is(':checked')) {
                pendencias.push('Ler e aceitar o Termo de Uso de Imagem e Voz.');
            }
        }

        // Devolve as pendencias encontradas
        return pendencias;
    }

    // Libera ou bloqueia o botao de envio de acordo com as pendencias
    function explodeAtualizarEnvio($form) {
        // Nada a fazer fora de um formulario de envio
        if (!$form.length) { return; }

        // Exigencias em aberto
        var pendencias = explodePendencias($form);
        // Botao e caixa de aviso deste formulario
        var $botao = $form.find('input[type="submit"]');
        var $aviso = $form.find('.pcd-pendencias');

        // Tudo atendido: libera o botao
        if (pendencias.length === 0) {
            $botao.addClass('allow').css({ 'pointer-events': 'all', 'opacity': '1', 'cursor': 'pointer' });
            $aviso.stop(true, true).slideUp(120);
            return;
        }

        // Ainda ha exigencias: bloqueia o botao
        $botao.removeClass('allow').css({ 'pointer-events': 'none', 'opacity': '0.5', 'cursor': 'default' });

        // A caixa de pendencias existe apenas nos grupos MAO
        if (!$aviso.length) { return; }

        // Reescreve a lista com o que falta
        var $lista = $aviso.find('ul').empty();
        $.each(pendencias, function(i, texto) {
            $lista.append($('<li>').text(texto));
        });

        // Mostra a caixa somente quando a exigencia do laudo esta em jogo
        if (explodePcdAtivo($form)) {
            $aviso.stop(true, true).slideDown(120);
        } else {
            $aviso.stop(true, true).slideUp(120);
        }
    }

    // Escolha de PCD (Nao/Sim) abre ou fecha o bloco e reavalia o envio
    $(document).on('change', 'input[name^="confirmacao_pcd_"]', function() {
        explodeAlternarLaudo($(this).closest('form'));
    });

    // Escolher o arquivo do laudo atualiza a situacao e o botao
    $(document).on('change', '.pcd-laudo-arquivo', function() {
        var $form = $(this).closest('form');
        explodeStatusLaudo($form.find('.pcd-laudo'));
        explodeAtualizarEnvio($form);
    });

    // Aceite do termo reavalia o envio
    $(document).on('change', '.termo-imagem-voz', function() {
        explodeAtualizarEnvio($(this).closest('form'));
    });

    // Concordancia com o uso dos dados reavalia o envio
    $(document).on('change', '.submeter', function() {
        explodeAtualizarEnvio($(this).closest('form'));
    });

    // Escolher o desenho principal atualiza o envio
    $(document).on('change', 'input[name="desenho"]', function() {
        explodeAtualizarEnvio($(this).closest('form'));
    });

    // ############### CONFIRMACAO DE PCD (TODOS OS GRUPOS) ###############

    // Formulario que está aguardando a resposta da caixa de confirmacao
    var $formAguardando = null;

    // Esconde a faixa "Aguarde..." do rodape.
    //
    // O footer.php liga um handler direto em "form.loading" que mostra essa faixa a
    // cada submit. Como esse handler esta no proprio formulario, ele roda ANTES do
    // nosso, que e delegado no document. Resultado: ao segurar o envio com
    // preventDefault a faixa ja tinha aparecido e ficava na tela para sempre,
    // cobrindo a pagina inteira e impedindo qualquer clique.
    //
    // O stop(true, true) e necessario porque o footer usa fadeIn(500): sem encerrar
    // a animacao em andamento, ela voltaria a exibir a faixa logo depois do hide().
    function explodeEsconderAguarde() {
        $('.msg-loading').stop(true, true).hide();
    }

    // Mostra a faixa "Aguarde..." quando o envio realmente vai acontecer.
    // Sem animacao de proposito: a pagina ja esta sendo enviada e o retorno
    // visual precisa estar na tela no mesmo instante.
    function explodeMostrarAguarde() {
        $('.msg-loading').stop(true, true).show();
    }

    // Nome do dependente deste formulario, usado no texto da confirmacao
    function explodeNomeDependente($form) {
        // O nome fica no cabecalho do acordeon, ao lado do icone de check
        var nome = $form.closest('.single').find('.head #name').text();
        // Remove espacos das pontas
        return $.trim(nome || '');
    }

    // Mostra a caixa de confirmacao para o formulario informado
    function explodeAbrirConfirmacao($form) {
        // Guarda quem pediu a confirmacao
        $formAguardando = $form;
        // Caixa unica da pagina
        var $caixa = $('#pcd-confirma');
        // Nome do dependente, quando houver
        var nome   = explodeNomeDependente($form);
        // Escreve o nome com text() para nao interpretar HTML
        $caixa.find('.pcd-confirma-nome').text(nome !== '' ? nome : 'selecionado');
        // Exibe a caixa
        $caixa.css('display', 'flex');
        // Trava a rolagem do fundo enquanto a caixa estiver aberta
        $('body').css('overflow', 'hidden');
        // Leva o foco para o botao de confirmar
        $caixa.find('.pcd-confirma-ok').trigger('focus');
    }

    // Fecha a caixa de confirmacao
    function explodeFecharConfirmacao() {
        // Esconde a caixa
        $('#pcd-confirma').hide();
        // Devolve a rolagem da pagina
        $('body').css('overflow', '');
        // Garante que a faixa "Aguarde..." nao fique travando a tela
        explodeEsconderAguarde();
    }

    // Botao "Sim, confirmo": marca o formulario como confirmado e envia
    $(document).on('click', '.pcd-confirma-ok', function() {
        // Sem formulario pendente nao ha o que enviar
        if (!$formAguardando || !$formAguardando.length) {
            explodeFecharConfirmacao();
            return;
        }
        // Guarda o formulario antes de limpar a referencia
        var $form = $formAguardando;
        // Marca a confirmacao para o envio nao ser interceptado de novo
        $form.data('pcdConfirmado', true);
        // Limpa o estado da caixa
        $formAguardando = null;
        explodeFecharConfirmacao();
        // O envio nativo nao dispara o evento submit, entao a faixa do rodape
        // nao apareceria sozinha. Mostramos aqui para o colaborador ter retorno
        // visual e nao clicar duas vezes enquanto a pagina carrega.
        explodeMostrarAguarde();
        // Envia pelo DOM nativo, que nao dispara o evento submit outra vez
        $form[0].submit();
    });

    // Botao "Voltar e corrigir": apenas fecha, o envio nao acontece
    $(document).on('click', '.pcd-confirma-voltar', function() {
        // Descarta o formulario pendente
        $formAguardando = null;
        explodeFecharConfirmacao();
    });

    // Clique fora da caixa tambem cancela
    $(document).on('click', '#pcd-confirma', function(e) {
        // Ignora cliques dentro da caixa
        if (e.target !== this) { return; }
        // Cancela como se fosse o botao voltar
        $formAguardando = null;
        explodeFecharConfirmacao();
    });

    // Tecla ESC cancela a confirmacao
    $(document).on('keydown', function(e) {
        // Só age com a caixa aberta.
        // A conferencia olha o display que o proprio codigo define, e nao o
        // seletor :hidden, que depende de medicao de layout e falha quando o
        // elemento ainda nao foi renderizado.
        if (e.key !== 'Escape' || 'none' === $('#pcd-confirma').css('display')) { return; }
        // Cancela
        $formAguardando = null;
        explodeFecharConfirmacao();
    });

    // ############### FIM DA CONFIRMACAO DE PCD ###############

    // Trava final: impede o envio se algo ainda estiver pendente
    $(document).on('submit', '.form-envio-desenho', function(e) {
        var $form = $(this);
        // Reavalia no momento do envio
        if (explodePendencias($form).length > 0) {
            // Cancela o envio
            e.preventDefault();
            // O handler do rodape ja tinha mostrado a faixa "Aguarde..."; como o
            // envio nao vai acontecer, ela precisa sair da frente
            explodeEsconderAguarde();
            // Reexibe o aviso
            explodeAtualizarEnvio($form);
            return false;
        }

        // Dependente marcado como PCD ainda precisa confirmar a escolha uma vez
        if (explodePcdAtivo($form) && !$form.data('pcdConfirmado')) {
            // Segura o envio ate a resposta da caixa
            e.preventDefault();
            // Retira a faixa "Aguarde..." enquanto a caixa de confirmacao decide
            explodeEsconderAguarde();
            // Abre a confirmacao
            explodeAbrirConfirmacao($form);
            return false;
        }
    });

    // Estado inicial de cada formulario da pagina
    $(document).ready(function() {
        // A concordancia comeca sempre desmarcada
        $('.form-envio-desenho input.submeter').prop('checked', false);

        // Percorre cada formulario ajustando bloco, status e botao
        $('.form-envio-desenho').each(function() {
            var $form = $(this);
            // Abre o bloco do laudo se o dependente ja vem marcado como PCD
            if (explodePcdAtivo($form)) {
                explodeAlternarLaudo($form);
            } else {
                explodeStatusLaudo($form.find('.pcd-laudo'));
                explodeAtualizarEnvio($form);
            }
        });
    });

    // ############### FIM DO LAUDO DE PCD ###############

})(jQuery);
</script>
