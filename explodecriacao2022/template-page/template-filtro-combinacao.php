<?php

/* Template Name: Filtro (Regra: Cat & Grupo) */

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

<div class="erro-full" id="max-cat">
  <div class="in">
    <div class="content">
      <a href="#" class="notscrollable close">X</a>
        <p>Você deve manter ao menos uma categoria selecionada!</p>
      </div>
  </div>
</div>

<div class="erro-full" id="max-grupo">
  <div class="in">
    <div class="content">
      <a href="#" class="notscrollable close">X</a>
        <p>Você deve manter ao menos um grupo selecionado!</p>
      </div>
  </div>
</div>

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
                <span id="cat"><?php echo $categorias[0]->name; if($pcd == true) { echo ' (PCD)'; } ?></span>
                <label style="background-image: url('<?php echo $desenho; ?>');">
                <input type="checkbox" data-img="<?php echo $desenho; ?>" class="selected" name="<?php echo $categorias[0]->slug; ?>" data-id="<?php echo get_the_ID(); ?>">
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
    <div class="outros">
      <div class="container">
        <div class="listagem">   

          <div class="coluna noselect" id="left">
            <form action="">
              <h2>Categorias</h2>
              <div class="line">
                <label>
                  <input id="categorias" type="checkbox" name="filtro-categoria-a" value="categoria-a" checked>
                  <span>Categoria A <span id="desc">04 a 06 anos</span></span>
                </label>
              </div>
              <div class="line">
                <label>
                  <input id="categorias" type="checkbox" name="filtro-categoria-b" value="categoria-b" checked>
                  <span>Categoria B <span id="desc">07 a 09 anos</span></span>                  
                </label>
              </div>
              <div class="line">
                <label>
                  <input id="categorias" type="checkbox" name="filtro-categoria-c" value="categoria-c" checked>
                  <span>Categoria C <span id="desc">10 a 11 anos</span></span>                  
                </label>
              </div>
              <!--<div class="line">
                <label>
                  <input type="checkbox" name="filtro-categoria-pcd" value="pcd" checked>
                  <span>PCD</span>
                </label>
              </div>-->

              <h2>Grupos</h2>
              <div class="line">
                <label>
                  <input id="grupos" type="checkbox" name="filtro-grupo-1" value="grupo-1" checked>
                  <span>Grupo 1 <span id="desc">HAB Sumaré, Itirapina, Paulínia, Cariacica e Honda Energy</span></span>
                </label>
              </div>
              <div class="line">
                <label>
                  <input id="grupos" type="checkbox" name="filtro-grupo-2" value="grupo-2" checked>
                  <span>Grupo 2 <span id="desc">HSA Sumaré, HDA Peças Sumaré e Indaiatuba, CT e HAB Comercial; e Peças Indaiatuba</span></span>
                </label>
              </div>
              <div class="line">
                <label>
                  <input id="grupos" type="checkbox" name="filtro-grupo-3" value="grupo-3" checked>
                  <span>Grupo 3 <span id="desc">HDA e HRB-S Morumbi, HSF, Banco Honda, CNH, CT e CETH Recife, Jaboatão e CETH Indaiatuba.</span></span>
                </label>
              </div>

              <!--<h2>Unidades</h2>
              <div class="line">
                <label>
                  <input type="checkbox" name="filtro-unidade-itirapina" value="unidade-itirapina" checked>
                  <span>Itirapina</span>
                </label>
              </div>
              <div class="line">
                <label>
                  <input type="checkbox" name="filtro-unidade-sumare" value="unidade-sumare" checked>
                  <span>Sumaré</span>
                </label>
              </div>-->
            </form>
          </div>
          <div class="coluna" id="right">
            <div class="loop-drawings">
              <?php

              /* LOOP PELA CATEGORIA
              $args = array(
                'taxonomy'   => "desenhos_cat",
                'orderby'    => 'title',
                'order'      => 'ASC',
                'hide_empty' => true,
              );
              $product_categories = get_terms($args);

              foreach( $product_categories as $cat ) {
              FIM LOOP PELA CATEGORIA */              

                ############### LOOP DOS DESENHOS DOS OUTROS ###############
              $args = array(
                  "post_type" => 'desenhos',
                  "posts_per_page" => -1,
                  'orderby' => 'rand',
                  /* 'tax_query' => array(
                    array(
                    'taxonomy' => 'desenhos_cat',
                    'field' => 'term_id',
                    'terms' => $cat->term_id
                     )
                  ), */
                  'author__not_in' => get_current_user_id(),
              );
               
              $loop = new WP_Query( $args );  

              $dependentes_enviados = array();

                if ($loop->have_posts()) {

                    while ( $loop->have_posts() ) {
                      $loop->the_post();

                      // Respeita a configuracao de "Visibilidade" do painel: cada grupo
                      // enxerga apenas os grupos e as categorias que o administrador liberou.
                      // Antes esta tela mostrava os desenhos de todo mundo, sem filtro nenhum.
                      if ( function_exists( 'explode_visibilidade_pode_ver_post' )
                          && ! explode_visibilidade_pode_ver_post( get_the_ID() ) ) {
                        continue;
                      }

                      $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
                      $desenho = str_replace('-150x150', '', $desenho);
                      $thumb_id = attachment_url_to_postid( $desenho );
                      $first_image_url = wp_get_attachment_image_src($thumb_id, 'desenho_thumb');
                      // Pega a primeira imagem e utiliza a versão pequena
                      $desenho = $first_image_url[0];                      

                      $classes = array();

                      $unidade = get_post_meta(get_the_ID(),'desenhos_box_unidade',true);
                      // $classes[] = sanitize_title($unidade);

                      $pcd = false;

                      $categorias = get_the_terms(get_the_ID(), 'desenhos_cat');
                      foreach($categorias as $info){
                        $classes[] = $info->slug;
                        if($info->slug == 'pcd') {
                          $pcd = true;
                        }
                      }

                      ?>

                      <div class="single unidade-<?php echo sanitize_title($unidade); ?> <?php foreach($classes as $class) { echo $class . ' '; } ?>" data-unidade="<?php echo sanitize_title($unidade); ?>" data-check="filtro-unidade-<?php echo sanitize_title($unidade); echo ',filtro-' . $categorias[0]->slug; if($pcd == true) { echo ',filtro-categoria-pcd'; } ?>">
                        <span id="cat"><?php echo $categorias[0]->name; if($pcd == true) { echo ' (PCD)'; } ?></span>
                        <label style="background-image: url('<?php echo $desenho; ?>');">                        
                        <input type="checkbox" data-img="<?php echo $desenho; ?>" class="selected" name="<?php echo $categorias[0]->slug; ?>" data-id="<?php echo get_the_ID(); ?>">
                        <span><i class="fas fa-check"></i></span>
                        </label>
                      </div>
                  
                    <?php

                      }
                }
              ############### LOOP DOS DESENHOS DOS FILHOS ###############
              
              /* LOOP PELA CATEGORIA } */
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

        // ENCONTRA O PRIMEIRO E ADICIONA NA SELEÇÃO NO MENU
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
// FILTRO COM BASE EM TODAS AS SELEÇÕES
/*
$(function() {
  $('#left form input').change(function() {

    const selecoes = [];

    $('.listagem .loop-drawings .single').hide();

    $("#left form input:checkbox:checked").each(function(){
      selecoes.push($(this).val());
      var show = $(this).val();
      $('.' + show).show();
    });

  });
}); */
</script>

<script>
$('#left form input').on('change',function(){

    // ############# CATEGORIAS #############
    var getArrVal_categorias = $('#left form input#categorias:checked').map(function(){
        return this.value;
    }).toArray();

    if(getArrVal_categorias.length){
        //execute the code
        //alert('mais de um selecionado');
    } else {
        $(this).prop("checked",true);
        $('.erro-full#max-cat').show();
        return false;
    }

    // ############# GRUPOS #############
    var getArrVal_grupos = $('#left form input#grupos:checked').map(function(){
        return this.value;
    }).toArray();

    if(getArrVal_grupos.length){
        //execute the code
        //alert('mais de um selecionado');
    } else {
        $(this).prop("checked",true);
        $('.erro-full#max-grupo').show();
        return false;
    }

    // const all_classes = getArrVal_categorias.concat(getArrVal_grupos);
    // alert(all_classes);

    const drawings_to_show = [];

    $.each(getArrVal_categorias, function(index, item_cat) {
      $.each(getArrVal_grupos, function(index, item_grupo) {
        
        // alert('.' + item_cat + '.' + item_grupo);
        // $('.' + item_cat + '.' + item_grupo).show();

        if(!$('.loop-drawings .single').hasClass(item_cat) && !$('.loop-drawings .single').hasClass(item_grupo)) {
          // $('.loop-drawings .single').hide();
        }
        else {
          // $('.' + item_cat + '.' + item_grupo).show();
          drawings_to_show.push('.' + item_cat + '.' + item_grupo);
        }
        
      });
    });

    $('.loop-drawings .single').hide();
    $.each(drawings_to_show, function(index, drawing_item) {
      //alert(drawing_item);
      $(drawing_item).css('display','inline-block');
    });

});
</script>

<?php
} // Fim else caso não tenha votado ainda
?>


<?php get_footer(); ?>