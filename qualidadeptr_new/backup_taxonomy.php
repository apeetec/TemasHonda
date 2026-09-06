<?php 
    get_header(); 
    // Id do usuário      
        $id_user = get_current_user_id();
    // Dados da categoria
        $term = get_queried_object();
        $term_id = $term->term_id;
        $term_name = $term->name;
        $sanitiza_term_name = sanitize_title($term_name);
        $checagem = get_user_meta($id_user,'todas_alternativa_'.$sanitiza_term_name,true);
        $atracao = get_term_meta( $term_id, 'atracao', true );
        $tema = get_term_meta( $term_id, 'tema', true );
    // Dados do usuário
        date_default_timezone_set('America/Sao_Paulo');
        $today = date('Y-m-d H:i:s');
        update_user_meta($id_user, 'acessou_'.$sanitiza_term_name, 'on');
        update_user_meta($id_user, 'horario_da_data_de_'.$sanitiza_term_name, $today);
    // Subcategoria
        $children = get_term_children($term_id,'datas_perguntas');
    // Datas 
        // if(!empty( get_term_meta( $term_id, 'horario_inicio', true ))){
        //     $inicio = get_term_meta( $term_id, 'horario_inicio', true );
        //     $data_inicio = date( 'Y-m-d H:i:s', $inicio );
        // }
        // else {
        //     $data_inicio = 'Sem data de inicio';
        // }
        $data_inicio = !empty(get_term_meta($term_id, 'horario_inicio', true)) ? date('Y-m-d H:i:s', get_term_meta($term_id, 'horario_inicio', true)) : 'Sem data de inicio';
        // if(!empty( get_term_meta( $term_id, 'horario_inicio', true ))){
        //     $fim = get_term_meta( $term_id, 'horario_fim', true );
        //     $data_fim = date( 'Y-m-d H:i:s', $fim );
        // }
        // else {
        //     $data_inicio = 'Sem data de fim';
        // }
        $data_fim = !empty(get_term_meta($term_id, 'horario_inicio', true)) ? date('Y-m-d H:i:s', get_term_meta($term_id, 'horario_fim', true)) : 'Sem data de fim';

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
        require_once( get_template_directory() . '/template-parts/cabecalho.php' );
    ?>
<!-- Questões -->
    <?php
        if($today >= $data_inicio && $today <= $data_fim){
            require_once( get_template_directory() . '/template-parts/formulario_de_perguntas.php' );
        }      
    ?>
<!--  -->
  <?php
  if(!empty($children)){
    foreach($children as $child){
        $child_id = $child;
    }
  }
  ?>
<?php get_footer(); ?>