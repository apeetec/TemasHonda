<?php 
    get_header(); 
    ///////////////////////////////// Variáveis globais para uso /////////////////////////////////////////////////////////////////   
        $id_user = get_current_user_id();// Id do usuário  
    // Dados da categoria
        $term = get_queried_object();
        $destino = get_term_link($term);
        $term_id = $term->term_id; // Categoria principal ou seja, da data principal
        $term_name = $term->name; // Nome da categoria principal
        $sanitiza_term_name = sanitize_title($term_name); // Nome da categoria sem caracteres especiais
    /////////////////// Subcategoria
            $children = get_term_children( $term_id,'datas_perguntas' ); // ID da subcategoria

    // Checagens,datas e temas
        $checagem = get_user_meta($id_user,'todas_alternativa_'.$sanitiza_term_name,true); // usado para checar se o usuário respondeu todas as perguntas
        $atracao = get_term_meta( $term_id, 'atracao', true ); // Nome da atração
        $tema = get_term_meta( $term_id, 'tema', true ); // Tema da atracão

        date_default_timezone_set('America/Sao_Paulo'); // Timezone
        $today = date('Y-m-d H:i:s'); // Data atual
        update_user_meta($id_user, 'acessou_'.$sanitiza_term_name, 'on'); //Atualiza se ele acessou no dia da questão ou não
        update_user_meta($id_user, 'horario_da_data_de_'.$sanitiza_term_name, $today); // Atualiza com a data exata que acessou
 

        // $data_inicio = !empty(get_term_meta($term_id, 'horario_inicio', true)) ? date('Y-m-d H:i:s', get_term_meta($term_id, 'horario_inicio', true)) : date('Y-m-d H:i:s');
        // $data_fim = !empty(get_term_meta($term_id, 'horario_inicio', true)) ? date('Y-m-d H:i:s', get_term_meta($term_id, 'horario_fim', true)) : date('Y-m-d H:i:s');

        $data_inicio_timestamp = !empty(get_term_meta($term_id, 'horario_inicio', true))
        ? get_term_meta($term_id, 'horario_inicio', true)
        : time();
        // Adiciona 3 horas ao timestamp
        $data_inicio_timestamp += 3 * 3600; // 3 horas em segundos

        // Converte o timestamp em formato de data
        $data_inicio = date('Y-m-d H:i:s', $data_inicio_timestamp);

        // Obtém o valor de 'horario_fim'
            $data_fim_timestamp = !empty(get_term_meta($term_id, 'horario_fim', true))
            ? get_term_meta($term_id, 'horario_fim', true)
            : time();

            // Adiciona 3 horas ao timestamp
            $data_fim_timestamp += 3 * 3600; // 3 horas em segundos

            // Converte o timestamp em formato de data
            $data_fim = date('Y-m-d H:i:s', $data_fim_timestamp);


    // Video
        $video = get_term_meta( $term_id, 'video_categoria', true );
        $tag_video =  do_shortcode('[video src="'.$video.'" width="780" height="400px"]');
    // Código quem assistiu presencialmente
        $codigo = get_term_meta( $term_id, 'codigo', true );
        
    // Requisição
        require_once( get_template_directory() . '/template-parts/requisicao.php' );
?>

<!-- Cabeçalho do site -->
    <?php
    // if(current_user_can('administrator')){
        require_once( get_template_directory() . '/template-parts/cabecalhos/cabecalho.php' );
    // }
    ?>
<!-- Questões -->
    <?php
        if($today >= $data_inicio && $today <= $data_fim){
            require_once( get_template_directory() . '/template-parts/formulario_de_perguntas.php' );
        }      
    ?>
    <?php
    if($today >= $data_inicio && $today <= $data_fim){
        foreach ($children as $child) {
            $term_id = $child; // Categoria principal ou seja, da data principal
            $video = get_term_meta( $term_id, 'video_categoria', true );
            $sub_term = get_term_by('id',$term_id,'datas_perguntas');
            $sub_name = $sub_term->name;
            $sanitiza_term_name = sanitize_title($sub_name);
            $tag_video =  do_shortcode('[video src="'.$video.'" width="780" height="400px"]');
            $codigo = get_term_meta( $term_id, 'codigo', true );
            $atracao = get_term_meta( $term_id, 'atracao', true ); // Nome da atração
            $tema = get_term_meta( $term_id, 'tema', true ); // Tema da atracão   
            $checagem = get_user_meta($id_user,'todas_alternativa_'.$sanitiza_term_name,true);

            if(!empty( $video )){
                require( get_template_directory() . '/template-parts/requisicao.php' );
                require( get_template_directory() . '/template-parts/cabecalhos/atracao_e_video.php' );
                require( get_template_directory() . '/template-parts/formulario_de_perguntas.php' );
            }
        }
    }
    ?>


<?php get_footer(); ?>