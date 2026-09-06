<div class="resultados">
	<div class="container">
    <?php
        $user_id = get_current_user_id(); // ID do usuário
        $nome = get_user_meta($user_id, 'first_name', true); // Nome do usuário
        $current_user = wp_get_current_user();
        $grupo = get_user_meta($user_id, 'user_field_funcionario_grupo',true); //grupo do usuário
    ?>

		<h2 class="center">Resultado</h2>

        <br><br>

        <div class="result-loop">
            <div class="botoes">
              <?php
              if($grupo == 'Grupo 1'){
              ?>
              <a href="#" data-grupo="grupo-1" class="single active notscrollable">Grupo 1</a>
              <?php
              }
              if($grupo == 'Grupo 2'){
              ?>
              <a href="#" data-grupo="grupo-2" class="single notscrollable">Grupo 2</a>
              <?php
              }
              if($grupo == 'Grupo 3'){
              ?>
              <a href="#" data-grupo="grupo-3" class="single notscrollable">Grupo 3</a>
              <?php
              }
              if($grupo == 'Grupo 4'){
              ?>
              <a href="#" data-grupo="grupo-4" class="single notscrollable">Grupo 4</a>
              <?php
              }
              if($grupo == 'Grupo 5'){
              ?>
              <a href="#" data-grupo="grupo-5" class="single notscrollable">Grupo 5</a>
              <?php
              }
              if($grupo == 'Grupo 6'){
              ?>
              <a href="#" data-grupo="grupo-6" class="single notscrollable">Grupo 6</a>
              <?php
              }
              if($grupo == 'Grupo 7'){
              ?>
              <a href="#" data-grupo="grupo-7" class="single notscrollable">Grupo 7</a>
              <?php
              }
              if($grupo == 'Grupo 8'){
              ?>
              <a href="#" data-grupo="grupo-8" class="single notscrollable">Grupo 8</a>
              <?php
              }
              if($grupo == 'Grupo 9'){
              ?>
              <a href="#" data-grupo="grupo-9" class="single notscrollable">Grupo 9</a>
              <?php
              }
              if($grupo == 'Grupo 10'){
              ?>
              <a href="#" data-grupo="grupo-10" class="single notscrollable">Grupo 10</a>
              <?php
              }
              ?>
            </div>

            <div class="all">

              <!-- INICIO GRUPO 1 -->
              <?php
              if($grupo == 'Grupo 1'){
              ?>
              <div class="single" id="grupo-1" style="display: block;">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_1.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 1 -->

              <!-- INICIO GRUPO 2 -->
              <?php
              if($grupo == 'Grupo 2'){
              ?>
              <div class="single" id="grupo-2">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_2.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 2 -->

              <!-- INICIO GRUPO 3 -->
              <?php
              if($grupo == 'Grupo 3'){
              ?>
              <div class="single" id="grupo-3">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_3.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 3 -->

              <!-- INICIO GRUPO 4 -->
              <?php
              if($grupo == 'Grupo 4'){
              ?>
              <div class="single" id="grupo-4">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_4.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 4 -->
              
              <!-- INICIO GRUPO 5 -->
              <?php
              if($grupo == 'Grupo 5'){
              ?>
              <div class="single" id="grupo-5">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_5.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 5 -->
              
              <!-- INICIO GRUPO 6 -->
              <?php
              if($grupo == 'Grupo 6'){
              ?>
              <div class="single" id="grupo-6">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_6.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 6 -->

              <!-- INICIO GRUPO 7 -->
              <?php
              if($grupo == 'Grupo 7'){
              ?>
              <div class="single" id="grupo-7">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_7.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 7 -->

              <!-- INICIO GRUPO 8 -->
              <?php
              if($grupo == 'Grupo 8'){
              ?>
              <div class="single" id="grupo-8">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_8.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 8 -->

              
              <!-- INICIO GRUPO 9 -->
              <?php
              if($grupo == 'Grupo 9'){
              ?>
              <div class="single" id="grupo-9">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_9.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 9 -->
             
              <!-- INICIO GRUPO 10 -->
              <?php
              if($grupo == 'Grupo 10'){
              ?>
              <div class="single" id="grupo-10">                 

                <div class="content">
                    <img src="<?php bloginfo('template_url'); ?>/img/2024/grupo_10.jpg">
                </div>            

              </div>
              <?php
              }
              ?>
              <!-- FIM GRUPO 10 -->
            </div>    

    	</div>
    </div>
</div>

<script>
$(".botoes a").on('click',function(e){
  var grupo = $(this).data('grupo');

  // e.preventDefault();

  // ATIVA BOTAO
  $('.botoes a').removeClass('active');
  $(this).addClass('active');

  // EXIBE DIV
  $('.all .single').hide();
  $('#'+grupo).fadeIn(300);

  $('html, body').animate({
        scrollTop: $(".all").offset().top - 150
    }, 1000);

});
</script>