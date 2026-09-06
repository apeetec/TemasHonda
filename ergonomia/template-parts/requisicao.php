<?php
    if($_POST){
        if(isset($_POST[$sanitiza_term_name])){
            $slug_id = $_POST['slug_id'];
           
            
            $args = [
                'post_type' => 'perguntas', // Tipo de post
                'tax_query' => [
                        [
                            'taxonomy' => 'datas_perguntas', // Nome da taxonomia
                            'field'    => 'term_id',       // Campo de busca ('slug', 'term_id' ou 'name')
                            'terms'    => $slug_id, // Valor a buscar
                            'include_children' => false, //
                        ],
                    ],
            ];
            $post_questoes = get_posts($args);
            foreach ($post_questoes as $questao) {
                $post_id = $questao->ID;
                $pergunta = get_the_title($post_id);
                if(isset($_POST['resp_video'][$post_id])){
                    $resposta = sanitize_text_field($_POST['resp_video'][$post_id]);
                    update_user_meta($id_user, 'todas_alternativa_'.$sanitiza_term_name, 'on');
                }
                $meta_key = 'user_field_'.$sanitiza_term_name.'_'.$post_id;
                update_user_meta($id_user, $meta_key, $resposta);
            }

            if(isset($_POST['presencial'][$sanitiza_term_name])){
                $presenciais = $_POST['presencial'][$sanitiza_term_name];
                // echo $presenciais;
                update_user_meta($id_user, 'presencial_'.$sanitiza_term_name, $presenciais);
            }
           echo '<script>window.location.replace("'.$destino.'");</script>';

        }
    }
?>