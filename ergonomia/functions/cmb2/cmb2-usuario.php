<?php

#################################### CAMPOS DO USUARIO ####################################
add_action('cmb2_init', 'custom_user_fields_cmb2');
function custom_user_fields_cmb2()
{
    // Opções de acesso
    $cmb_user = new_cmb2_box(array(
        'id' => 'user_field_box',
        'title' => 'Informações do colaborador',
        'object_types' => array('user'),
        'show_names' => true,
        // 'new_user_section' => 'add-new-user', // where form will show on new user page. 'add-existing-user' is only other valid option.
    ));
    $cmb_user->add_field(array(
        'name' => 'Opções de acesso',
        'id' => 'user_field_title_acesso',
        'type' => 'title',
        'classes' => 'cmb2-divisor',
    ));
    $cmb_user->add_field(array(
        'name' => 'Senha já foi alterada?',
        'id' => 'user_field_senha_alterada',
        'type' => 'select',
        'column' => true,
        'default' => 'Não',
        'options' => array(
            'Não' => 'Não',
            'Sim' => 'Sim',
        ),
    ));
    $cmb_user->add_field(array(
        'name' => apply_filters('str_from_lang', 'Unidade', 'Unidade'),
        'id' => 'user_infos_empresas',
        'type' => 'pw_select',
        'options_cb' => 'user_taxonomy_empresas',
        'column' => array(
            'position' => 2,
            'name' => apply_filters('str_from_lang', 'Unidade', 'Unidade'),
        ),
        'display_cb' => 'user_infos_empresas_coluna'
        // 'type'     => 'taxonomy_select', // Or `taxonomy_select_hierarchical`
        // 'taxonomy' => 'user_empresa', // Taxonomy Slug
    ));
    $cmb_user->add_field(array(
        'name' => 'IP do usuário',
        'desc' => 'IP do usuário',
        'default' => '',
        'id' => 'ip_usuario',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Pontuação obtida',
        'desc' => 'Pontuação obtida',
        'default' => '',
        'id' => 'pontuacao_obtida',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Tempo da partida',
        'desc' => 'Tempo da partida',
        'default' => '',
        'id' => 'tempo_partida',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Horário',
        'desc' => 'Horário',
        'default' => '',
        'id' => 'horario',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Número de tentativas',
        'desc' => 'Número de tentativas',
        'default' => '',
        'id' => 'numero_tentativas',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Data',
        'desc' => 'Data',
        'default' => '',
        'id' => 'data_de_partida',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Unidade',
        'desc' => 'Esse é um backup de segurança caso algo dê errado com a unidade principal para conseguir separar os usuários depois',
        'default' => '',
        'id' => 'unidade_usuario',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Centro de custo',
        'desc' => '',
        'default' => '',
        'id' => 'centro_de_custo',
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Unidade',
        'id' => 'user_field_unidade',
        // 'column'   => true,
        'type' => 'text',
    ));
    $cmb_user->add_field(array(
        'name' => 'Comentários',
        'desc' => '',
        'id' => 'comentario',
        'type' => 'wysiwyg',
        'options' => array(),
    ));

    // ==================== CAMPOS SIMON SAYS ====================

    // Campo: Posição no ranking geral do Simon Says
    $cmb_user->add_field(array(
        'name' => 'Posição no Ranking (Simon Says)',
        'desc' => 'Posição calculada automaticamente com base na pontuação',
        'default' => '',
        'id' => 'simon_posicao_ranking',
        'type' => 'text',
    ));

    // Campo: Tentativas restantes hoje (Simon Says)
    $cmb_user->add_field(array(
        'name' => 'Tentativas restantes hoje (Simon Says)',
        'desc' => 'Número de tentativas disponíveis para hoje (0 a 3)',
        'default' => '3',
        'id' => 'simon_tentativas_restantes_hoje',
        'type' => 'text',
    ));

    // Campo: Data da última tentativa (Simon Says) - usado para reset diário
    $cmb_user->add_field(array(
        'name' => 'Data da última tentativa (Simon Says)',
        'desc' => 'Data da última tentativa realizada (formato: AAAA-MM-DD)',
        'default' => '',
        'id' => 'simon_data_ultima_tentativa',
        'type' => 'text',
    ));

    // ==================== FIM CAMPOS SIMON SAYS ====================

    $terms = get_terms(array(
        'taxonomy' => 'datas_perguntas',
        'hide_empty' => false,
    ));
    $datas = [];

    foreach ($terms as $term) {
        $slug = $term->slug;
        $datas[] = $slug;
    }

    $args = array(
        'post_type' => 'perguntas',
        'posts_per_page' => -1,
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'datas_perguntas',
                'field' => 'slug',
                'terms' => $datas,
            )
        )
    );
    $perguntas = get_posts($args);
    $cont = 0;
    //   Loop das alternativas
    for ($i = 0; $i < count($perguntas); $i++) {
        $post_id = $perguntas[$i]->ID;
        $nome = get_the_title($post_id);
        $prefix = sanitize_title($nome);
        $datas_pergunta = wp_get_object_terms($post_id, 'datas_perguntas', array('fields' => 'names'));
        foreach ($datas_pergunta as $data_pergunta) {
            $data_termo = $data_pergunta;
            $slug_data = sanitize_title($data_termo);
        }
        // Recupera os valores do outro groupbox 'meu_outro_groupbox'
        $entradas = get_post_meta($post_id, 'grupo_de_respostas', true);
        // Inicializa um array para armazenar as opções
        $options = [];
        // Verifica se os valores foram retornados corretamente
        if (!empty($entradas)) {
            // Itera sobre os valores do groupbox
            foreach ($entradas as $group_item) {
                // Aqui você pode acessar os campos dentro do groupbox, por exemplo:
                $valor_campo = $group_item['alternativa'];
                // Adiciona o valor ao array de opções
                $options[$valor_campo] = $valor_campo; // Você pode ajustar isso conforme necessário                  
            }
        }
        $cont++;
        $cmb2 = new_cmb2_box(array(
            'id' => 'user_field_box_' . $slug_data,
            'title' => 'Perguntas de ' . $data_termo,
            'object_types' => array('user'),
            'show_names' => true,
        ));
        $cmb2->add_field(array(
            'name' => 'Perguntas ' . $data_termo,
            'desc' => '',
            'type' => 'title',
            'id' => 'separador_perguntas_' . $slug_data
        ));
        $cmb2->add_field(array(
            'name' => 'Presencial',
            'desc' => 'Acompanhou a palestra presencialmente de ' . $data_termo,
            'id' => 'presencial_' . $slug_data,
            'type' => 'select',
            'show_option_none' => true,
            'default' => 'custom',
            'options' => array(
                'Presencial' => 'Presencial',
                'Não presencial' => 'Não presencial',
            ),
        ));
        $cmb2->add_field(array(
            'name' => 'Assistiu o video todo da data de' . ' ' . $data_termo . ' ' . '?',
            'desc' => '',
            'id' => 'video_concluido_' . $slug_data,
            'type' => 'checkbox',
        ));
        $cmb2->add_field(array(
            'name' => 'Respondeu todas as questões de' . ' ' . $data_termo . ' ' . '?',
            'desc' => '',
            'id' => 'todas_alternativa_' . $slug_data,
            'type' => 'checkbox',
        ));
        $cmb2->add_field(array(
            'name' => 'Acertou todas as questões de' . ' ' . $data_termo . ' ' . '?',
            'desc' => '',
            'id' => 'acertou_todas_alternativas_' . $slug_data,
            'type' => 'checkbox',
        ));
        $cmb2->add_field(array(
            'name' => 'Acessou na data de' . ' ' . $data_termo . ' ' . '?',
            'desc' => '',
            'id' => 'acessou_' . $slug_data,
            'type' => 'checkbox',
        ));
        $cmb2->add_field(array(
            'name' => 'Horário que acessou',
            'id' => 'horario_da_data_de_' . $slug_data,
            'type' => 'text_datetime_timestamp',

        ));
        // $cmb2->add_field( array(
        //     'name'    => 'Classificado para a data de'.' '.$data_termo,
        //     'desc'    => '',
        //     'default' => '',
        //     'id'      => 'classsificado_'.$slug_data,
        //     'type'    => 'text_medium'
        // ) );
        $cmb2->add_field(array(
            'name' => 'Classificado para a data de' . ' ' . $data_termo,
            'desc' => '',
            'id' => 'classificado_' . $slug_data,
            'type' => 'select',
            'show_option_none' => true,
            'default' => 'custom',
            'options' => array(
                'Classificado' => __('Classificado', 'cmb2'),
                'Desclassificado' => __('Desclassificado', 'cmb2'),
            ),
        ));
        $cmb2->add_field(array(
            'name' => '(' . $data_termo . ')' . ' ' . $nome,
            'id' => 'user_field_' . $slug_data . '_' . $post_id,
            'desc' => $nome,
            'column' => true,
            'type' => 'select',
            'show_option_none' => true,
            // 'options_cb' => 'getAlt',
            'options' => $options,
        ));
    }

}


?>