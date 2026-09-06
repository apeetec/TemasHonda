<?php



/* Template Name: Filtro */



get_header();



?>



<div class="main">

  <div class="content">

    <?php 



    ############## ENVIA FOTOS

    if($_POST) {



      $ids_update = array();

      $ids_update[] = $_POST['desenho1'];

      $ids_update[] = $_POST['desenho2'];

      $ids_update[] = $_POST['desenho3'];



      // ATUALIZA STATUS DE VOTAÇÃO DO USUÁRIO

      update_user_meta(get_current_user_id(),'user_field_votacao','Sim');



      foreach($ids_update as $post) {

        $votos = get_post_meta($post,'desenhos_box_votos',true);

        if(empty($votos) || $votos == '') {

          update_post_meta($post,'desenhos_box_votos',1);

        }

        else {

          $votos = $votos + 1;

          update_post_meta($post,'desenhos_box_votos',$votos);

        }

      }



    }

    ############## ENVIA FOTOS



    $votacao_status = get_user_meta(get_current_user_id(),'user_field_votacao',true);



    if(!$_POST && $votacao_status == 'Sim') {  ?>



  <div class="votou">

    <div class="container">

    <p>Seus votos já foram computados! Aguarde a divulgação dos resultados.</p>

    </div>

  </div>



<?php } else { ?>



<div class="erro-full" id="aviso">

  <div class="in">

    <div class="content">

      <a href="#" class="notscrollable close">X</a>

        <p>Você não selecionou todos os desenhos!</p>

      </div>

  </div>

</div>



<div class="erro-full" id="maximo">

  <div class="in">

    <div class="content">

      <a href="#" class="notscrollable close">X</a>

        <p>Você só pode selecionar 3 desenhos!</p>

      </div>

  </div>

</div>



<div class="erro-full" id="cat-aviso">

  <div class="in">

    <div class="content">

      <a href="#" class="notscrollable close">X</a>

        <p>Você só pode selecionar 1 desenho da <span></span>!</p>

      </div>

  </div>

</div>



<div class="body-filtros">

  <div class="filtros">

    <?php

    if($votacao_status == 'Sim') { ?>

    <div class="votou fim">

      <div class="container">

      <p>Votos enviados com sucesso! Aguarde a divulgação dos resultados.</p>

      </div>

    </div>

    <?php } else { ?>

    <div class="dependentes">

      <div class="container">

        <h2 id="desc">Clique para selecionar os desenhos</h2>

        <br>





          <?php

          ############### LOOP DOS DESENHOS DOS FILHOS ###############

          $args = array(

              "post_type" => 'desenhos',

              "posts_per_page" => -1,

              'orderby' => 'date',

              'order' => 'ASC',

              'author' => get_current_user_id(),                  

          );

           

          $loop = new WP_Query( $args );  



          $dependentes_enviados = array();



          if ($loop->have_posts()) { ?>



             <h2>Desenhos de seus filhos</h2>

              <div class="all loop-drawings">



          <?php



              while ( $loop->have_posts() ) {

              $loop->the_post();



              $pcd = false;



              $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);

              $desenho = str_replace('-150x150', '', $desenho);

              $desenhoBig = str_replace('1280x720', '', $desenho);

              $thumb_id = attachment_url_to_postid( $desenho );

              $first_image_url = wp_get_attachment_image_src($thumb_id, 'desenho_thumb');

              // Pega a primeira imagem e utiliza a versão pequena

              $desenho = $first_image_url[0];



              $categorias = get_the_terms(get_the_ID(), 'desenhos_cat');

              foreach($categorias as $info){

                  if($info->slug == 'pcd') {

                    $pcd = true;

                  }

              }



              ?>



              <div class="single">

                <span id="cat"><?php the_title(); ?></span>

                <span id="cat"><?php echo $categorias[0]->name; if($pcd == true) { echo ' (PCD)'; } ?></span>

                <label style="background-image: url('<?php echo $desenhoBig; ?>');">

                <input type="checkbox" data-img="<?php echo $desenhoBig; ?>" class="selected" name="<?php echo $categorias[0]->slug; ?>" data-id="<?php echo get_the_ID(); ?>">

                <span><i class="fas fa-check"></i></span>

              </label>

            </div>

          

          <?php



              } ?>



              </div>



          <?php

          }

          ############### LOOP DOS DESENHOS DOS FILHOS ###############

          ?>                  

      </div>

    </div>



    <!-- Grupo funcionario -->

    <div class="container funcionario_grupo space-top">

      <?php

        

       $user_id = get_current_user_id();

        $funcionario_grupo_string = get_user_meta($user_id,'user_field_funcionario_grupo',true);

        

        $funcionario_groups = array();

        if ( ! empty( $funcionario_grupo_string ) ) {

            $funcionario_groups = array_filter( array_map( 'trim', explode( ',', $funcionario_grupo_string ) ) );

        }

        

        $access_granted = false; // Flag para saber se o usuário pertence a algum grupo permitido

        $displayed_groups_info = ''; // Variável para acumular todas as informações dos grupos permitidos

        

        // Lista de todos os grupos que têm informações associadas para exibição

        $allowed_groups_for_info = array('Grupo 1', 'Grupo 2', 'Grupo 3', 'Grupo 4', 'Grupo 5', 'Grupo 6', 'Grupo 7', 'Grupo 8', 'Grupo 9', 'Grupo 10');

        

        if ( ! empty( $funcionario_groups ) ) {

            // Percorre a lista de grupos permitidos para ver quais o usuário possui

            foreach ( $allowed_groups_for_info as $allowed_group ) {

                // Verifica se o usuário pertence a este grupo permitido

                if ( in_array( $allowed_group, $funcionario_groups ) ) {

                    $access_granted = true; // Marca que o acesso é concedido

                    

                    // Acumula as informações do grupo correspondente na variável $displayed_groups_info

                    switch ($allowed_group) {

                      case "Grupo 1":

                        $displayed_groups_info .= "<p><spam>Grupo 01:</spam><br> SUM-HAB | SUM-HAB-PAULINIA | SUM-HAB-ITAJAI | SUM-HAB-CARIACICA | SUM-HEN XANGRI-LA | MANPOWER</p>";

                        break;

                      case "Grupo 2":

                        $displayed_groups_info .= "<p><spam>Grupo 02:</spam><br> SUM-HAB-ITIRAPINA | Brasil RH</p>";

                        break;

                      case "Grupo 3":

                        $displayed_groups_info .= "<p><spam>Grupo 03:</spam><br> SAO-HDA-CT SUMARE | SAO-HDA SUMARE ADM | SAO-HAB-SUM-CO | SAO-HDA-SUM-PEÇAS | SAO-PREVIHONDA</p>";

                        break;

                      case "Grupo 4":

                        $displayed_groups_info .= "<p><spam>Grupo 04:</spam><br> SAO-CNH S. C. SUL | SAO-HSF-MORUMBI | SAO-HDA-RECIFE | SAO-HDA-MORUMBI | SAO-HDA-INDAIATUBA | SAO-BHB-MORUMBI | SAO-HDA-SP2 | SAO-HDA-JABOATAO</p>";

                        break;

                      case "Grupo 5":

                        $displayed_groups_info .= "<p><spam>Grupo 05:</spam><br> ESTAMPARIA | SOLDA</p>";

                        break;   

                      case "Grupo 6":

                        $displayed_groups_info .= "<p><spam>Grupo 06:</spam><br> FUNDIÇÃO | USINAGEM | MONT. MOTOR</p>";

                        break;

                      case "Grupo 7":

                        $displayed_groups_info .= "<p><spam>Grupo 07:</spam><br> PINTURA | INJ. PLÁSTICA</p>";

                        break;

                      case "Grupo 8":

                        $displayed_groups_info .= "<p><spam>Grupo 08:</spam><br> MONT. MOTOCICLETA | CTP</p>";

                        break;   

                      case "Grupo 9":

                        $displayed_groups_info .= "<p><spam>Grupo 09:</spam><br> LOGÍSTICA | INFRAESTRUTURA</p>";

                        break;

                      case "Grupo 10":

                        $displayed_groups_info .= "<p><spam>Grupo 10:</spam><br> CDT | RH | CQ | COMPRAS | TI | CONTROLADORIA | COMPRAS INDIRETAS | PLAIN | CONTROLE FABRIL | DIV. PEÇAS | CETH | SEGURANÇA FÁBRICA | MONO-C | AUDITORIA | PROJ. TAIKAI | COMERCIAL 2W | JURÍDICO | PROJ. MANUFATURA | RI</p>";

                        break;        

                    }

                    // NÃO usamos mais o 'break;' aqui, para que ele continue verificando outros grupos permitidos

                }

            }

        }

        

        // Se nenhum grupo permitido foi encontrado após a verificação, exibe a mensagem de erro

        if ( ! $access_granted ) {

            echo "Voce precisa participar de um grupo para ver estas informacoes.";

            // Opcional: Redirecionar para uma página de erro ou home page

            // wp_redirect( home_url('/pagina-de-erro-grupo/') ); exit;

        } else {

            // Se o acesso foi concedido (ou seja, $access_granted é true), exibe as informações acumuladas

            echo $displayed_groups_info;

        }



      ?>

    </div>



    <div class="outros">

      <div class="container">

        <div class="listagem">   



          <div class="coluna noselect" id="left">

            <form action="">

              <h2>Categoriass</h2>

              <div class="line">

                <label>

                  <input type="checkbox" name="filtro-categoria-a" value="categoria-a" checked>

                  <span>Categoria A <span id="desc">04 a 06 anos</span></span>

                </label>

              </div>

              <div class="line">

                <label>

                  <input type="checkbox" name="filtro-categoria-b" value="categoria-b" checked>

                  <span>Categoria B <span id="desc">07 a 09 anos</span></span>                  

                </label>

              </div>

              <div class="line">

                <label>

                  <input type="checkbox" name="filtro-categoria-c" value="categoria-c" checked>

                  <span>Categoria C <span id="desc">10 a 11 anos</span></span>                  

                </label>

              </div>

            </form>

          </div>

          <div class="coluna" id="right">

            <div class="loop-drawings">



<?php 

                      $args_desenhos_outros = array(

                        "post_type" => 'desenhos',

                        "posts_per_page" => -1,

                        'orderby' => 'rand', 

                        'author__not_in' => get_current_user_id(), // Desenhos de outros autores

                      );



                      $loop_outros = new WP_Query( $args_desenhos_outros );  



                      while ( $loop_outros->have_posts() ) {

                        $loop_outros->the_post();

                        

                        $desenho_meta = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);

                        $desenho_thumbnail_url = '';

                        if($desenho_meta){

                            if(is_numeric($desenho_meta)){

                                $img_data = wp_get_attachment_image_src(intval($desenho_meta), 'desenho_thumb');

                                if($img_data) $desenho_thumbnail_url = $img_data[0];

                            } elseif(is_array($desenho_meta) && !empty($desenho_meta['id'])){

                                $img_data = wp_get_attachment_image_src($desenho_meta['id'], 'desenho_thumb');

                                if($img_data) $desenho_thumbnail_url = $img_data[0];

                            } elseif(is_string($desenho_meta)){

                                $desenho_thumbnail_url = $desenho_meta; // Tenta usar a URL direta se for string

                            }

                        }

                        // Tenta obter a imagem original maior para o lightbox

                        $desenhoBig_url = $desenho_thumbnail_url ? str_replace('-250x250', '', $desenho_thumbnail_url) : ''; // Ajuste o tamanho se necessário



                        $pcd = false;

                        $categorias_do_desenho = get_the_terms(get_the_ID(), 'desenhos_cat');

                        $desenho_categorias_slugs = array(); // Slugs das categorias do desenho



                        if($categorias_do_desenho && !is_wp_error($categorias_do_desenho)){

                            foreach($categorias_do_desenho as $cat_info){

                                // Normaliza o slug da categoria para minúsculas e com hífens (se necessário)

                                $normalized_category_slug = strtolower(str_replace(' ', '-', $cat_info->name)); 

                                $desenho_categorias_slugs[] = $normalized_category_slug; // Armazena o slug normalizado

                                

                                if($cat_info->slug == 'pcd') { // Mantém a verificação do slug 'pcd' como está

                                  $pcd = true;

                                }

                            }

                        }



                        // Verifica se o desenho pertence a algum dos grupos do funcionário OU se o desenho é PCD

                        $can_display_drawing = false;

                        $drawing_group_match = false;

                        if ( ! empty( $funcionario_groups ) ) {

                            foreach ( $funcionario_groups as $user_group ) {

                                // Normaliza o grupo do usuário para minúsculas e com hífens para comparar com os slugs normalizados

                                $normalized_user_group = strtolower(str_replace(' ', '-', $user_group)); 

                                

                                // Verifica se o grupo do funcionário normalizado está entre os slugs normalizados das categorias do desenho

                                if ( in_array( $normalized_user_group, $desenho_categorias_slugs ) ) {

                                    $drawing_group_match = true;

                                    break; // Encontrou uma correspondência, pode parar de verificar para este desenho

                                }

                            }

                        }



                        // O desenho deve ser exibido se houver correspondência de grupo OU se o desenho for PCD.

                        if ( $drawing_group_match || $pcd ) {

                            $can_display_drawing = true;

                        } else {

                            $can_display_drawing = false; // Garante que seja false se não houver match e não for PCD

                        }



                        if ( $can_display_drawing && $desenho_thumbnail_url && $desenhoBig_url ) { // Só exibe se puder e se tiver imagem

                            $unidade_post_id = get_post_meta(get_the_ID(),'desenhos_box_unidade',true);

                            $unidade_slug = '';

                            if($unidade_post_id){

                                $term = get_term($unidade_post_id);

                                if($term && !is_wp_error($term)){

                                    $unidade_slug = sanitize_title($term->name);

                                }

                            }

                            $unidade_slug = !empty($unidade_slug) ? $unidade_slug : 'sem-unidade';



                            // Constrói a classe CSS para filtragem por unidade

                            $css_classes_array = array(

                                'single',

                                'unidade-' . $unidade_slug,

                                'data-unidade="' . $unidade_slug . '"',

                                'data-check="filtro-unidade-' . $unidade_slug . '"'

                            );



                            // Adiciona classes de categoria (slugs normalizados)

                            if ( ! empty( $desenho_categorias_slugs ) ) {

                                foreach ( $desenho_categorias_slugs as $cat_slug_normalized ) {

                                    $css_classes_array[] = $cat_slug_normalized; // Usa o slug normalizado como classe

                                    $css_classes_array[] = 'filtro-' . $cat_slug_normalized; // Classe para o filtro da esquerda

                                    if ( $cat_slug_normalized === 'pcd' ) { // Verifica se o slug normalizado é 'pcd'

                                        $css_classes_array[] = 'filtro-categoria-pcd';

                                    }

                                }

                            }

                            ?>



                            <div class="<?php echo implode(' ', $css_classes_array); ?>">

                                <span id="cat"><?php the_title(); ?></span>

                                <span id="cat"><?php 

                                    if ( ! empty( $desenho_categorias_slugs ) ) {

                                        // Exibe o nome da primeira categoria normalizada, com espaços de volta

                                        echo ucfirst( str_replace('-', ' ', $desenho_categorias_slugs[0]) ); 

                                        if($pcd) { echo ' (PCD)'; }

                                    }

                                ?></span>

                                <a href="<?php echo esc_url($desenhoBig_url); ?>" data-toggle="lightbox" data-gallery="galeriastander">

                                  <label class="borderImg" style="background-image: url('<?php echo esc_url($desenho_thumbnail_url); ?>');"></label>

                                </a>

                                <div class="menuVotacao">

                                  <label id="voltoDesenho">                        

                                    <input type="checkbox" data-img="<?php echo esc_url($desenhoBig_url); ?>" class="selected" name="<?php echo ($desenho_categorias_slugs) ? $desenho_categorias_slugs[0] : 'sem-categoria'; ?>" data-id="<?php echo get_the_ID(); ?>">

                                    <span><i class="fas fa-check"></i></span>

                                  </label>

                                  <p>Votar</p>

                                </div>

                            </div>

                        <?php

                        }

                      } // Fim do loop while $loop_outros

                      ?>

            </div>

            

          </div>

        </div>

      </div>

    </div>

    <?php } ?>

  </div>

</div>



<script>

$("input.selected").change(function() {



  var img = $(this).data('img');

  var id = $(this).data('id');



    if($( ".body-filtros input.selected:checked" ).length > 3) {

      $(this).prop('checked',false);

      $('.erro-full#maximo').show();          

    }

    else {



      // ####################### PERMITE APENAS 1 SELECAO DE CADA CATEGORIA #######################

      var numberOfChecked_cat_a = $('input[name="categoria-a"]:checked').length;

      if(numberOfChecked_cat_a > 1) {

        $('.erro-full#cat-aviso p span').text('Categoria A');

        $('.erro-full#cat-aviso').show();

        $(this).prop("checked", false);

        return false;

      }



      var numberOfChecked_cat_b = $('input[name="categoria-b"]:checked').length;

      if(numberOfChecked_cat_b > 1) {

        $('.erro-full#cat-aviso p span').text('Categoria B');

        $('.erro-full#cat-aviso').show();

        $(this).prop("checked", false);

        return false;

      }



      var numberOfChecked_cat_c = $('input[name="categoria-c"]:checked').length;

      if(numberOfChecked_cat_c > 1) {

        $('.erro-full#cat-aviso p span').text('Categoria C');

        $('.erro-full#cat-aviso').show();

        $(this).prop("checked", false);

        return false;

      }

      // ####################### FIM PERMITE APENAS 1 SELECAO DE CADA CATEGORIA #######################





      if(this.checked) {



        // ENCONTRA O PRIMEIRO E ADICIONA NA SELEÃO NO MENU

        $('.menu .filtros form .single input').each(function() {

          if ( this.value === '' ) {

              $(this).val(id);

              $(this).parent().css('background-image','url('+img+')');

              this.focus();

              return false;

          }

        });         

          

      }

      else {       



        // ENCONTRA O PRIMEIRO E ADICIONA NA SELEÇÃO NO MENU

        $('.menu .filtros form .single input').each(function(){

        var encontrado = $(this).val();

        if(encontrado == id) {

            $(this).val('');

            $(this).parent().css('background-image','');

            this.focus();

            return false;

          }

        }); 



      }

    }



});

</script>



<script>

// ERRO CASO ESTEJA VAZIOS

$('.menu form').submit(function() {



  var erro = false;

  $('.menu .filtros form .single input').each(function() {

      if ( this.value === '' ) {

          erro = true;

      }

  });



  if(erro == true) {

    $('.erro-full#aviso').show();

    return false;

  }

  else {

      $('.msg-loading').fadeIn(500);

    }



});

</script>



<script>

$(function(){

    $('.body-filtros .loop-drawings input[type=checkbox]').prop("checked", false);

    $('.coluna form input[type=checkbox]').prop("checked", true);

    $('.menu form .single input').removeAttr('value');

});

</script>



<script>

$(function() {

  $('#left form input').change(function() {



    const selecoes = [];



    $('.listagem .loop-drawings .single').hide();



    $("#left form input:checkbox:checked").each(function(){

      selecoes.push($(this).val());

      var show = $(this).val();

      $('.' + show).show();

    });



    //alert(JSON.stringify(selecoes));//or alert(p)



  });

});

</script>



<?php

} // Fim else caso no tenha votado ainda

?>





<?php get_footer(); ?>