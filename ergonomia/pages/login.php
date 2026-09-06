<?php
/*
Template Name: Login
*/
if($_POST) {       
    global $wpdb;  
    //We shall SQL escape all inputs  
    $username = esc_sql($_REQUEST['matricula']);  
    $password = esc_sql($_REQUEST['password']); 
    // $captcha =  $_POST['g-recaptcha-response'];
  
    $remember = true;  
   
    $login_data = array();  
    $login_data['user_login'] = $username;  
    $login_data['user_password'] = $password;  
    $login_data['remember'] = $remember;  
   
    $user_verify = wp_signon( $login_data, false );   
  
    $error = array();
  
    if(empty($username)) {
        $error[] = 'Digite sua matrícula';
    }
    if(empty($password)) {
        $error[] = 'Digite sua senha';
    }
    /* if(empty($captcha)) {
        $error[] = 'Captcha inválido';
    } */
       
    if(!empty($username) && !empty($password)) { // && !empty($captcha)
  
        if ( is_wp_error($user_verify) )  {
  
            $error[] = 'Dados inválidos';
  
         } else {
  
          wp_set_auth_cookie($user_verify->ID, true, true);
          wp_set_current_user($user_verify->ID); 
  
          $senhaAlterada = get_user_meta($user_verify->ID, 'user_field_senha_alterada', true);	
          $regulamento = get_user_meta($user_verify->ID, 'user_field_leitura_reg', true);	
          $nome = get_user_meta(1, 'first_name', true);
          $objeto_usuario = wp_get_current_user();
          $email = $objeto_usuario->user_email;
          $today = date("Y-m-d"); //Pegando a data atual  
          date_default_timezone_set('America/Sao_Paulo'); // Setando o horário de São Paulo
          $hora = date('H:i'); // Pegando a hora atual
          //Criando o array para passar como parâmetros da função abaixo  
          $new_post = array(
            'post_title' => 'Registro de acesso de'.' '.$username.' '.$today.' '.$hora,
            'post_content' => 'Acessou',
            'post_author' => $username,
            'post_type' => 'registro_acesso',
            'post_status' => 'publish'
           );
           //Criando um post como registro de acesso
           $post_id = wp_insert_post($new_post);
          //Atualizando o campo date desse post de registro de acesso para saber quando ele acessou
           update_post_meta( $post_id, 'data_de_acesso', $today ); 
           update_post_meta( $post_id, 'hora_user_acesso', $hora ); 
           update_post_meta( $post_id, 'nome_user_acesso', $nome ); 
           update_post_meta( $post_id, 'login_user_acesso', $username); 
           update_post_meta( $post_id, 'email_user_acesso', $email); 
          // do_action('wp_login', $username->$user_login, $user);
          // do_action('wp_login', $username);
  
          if ($senhaAlterada == 'Não') {
              wp_redirect(home_url('nova-senha'));
              die();
            }
            else {
                if($regulamento == 'Sim'){
                    wp_redirect(home_url());
                }
                else {
                    wp_redirect(home_url('regulamento'));
                }        
                  die();
            }
         }
      }            
  } 
    get_header();
?>
    <section class="page-login">
        <article class="container">
            <div class="row center">
                <div class="col s12 m12 l12">
                    <img src="<?php bloginfo('template_url'); ?>/img/Logo-SIPAT.png" alt="" class="responsive-img">
                </div>
                <form class="col s12 m12 l12" action="" method="POST">
                    <div class="content">
                        <div class="row">
                            <div class="col s12 m12 l12 loading">
                                <?php if(!empty($error)) { ?>
                                    <p class="erro-main">
                                        <?php foreach ($error as $erro) { ?>
                                        <span><?php echo $erro; ?></span>
                                        <?php } ?>
                                    </p>
                                <?php } ?>
                            </div>
                            <div class="input-field col s12 m12 l12">
                                <i class="fa-solid fa-user prefix"></i>
                                <input type="text" name="matricula" maxlength="10" value="<?php if(!empty($username)) { echo $username; } ?>">  
                                <label for="icon_prefix">Matrícula</label>
                            </div>
                            <div class="input-field col s12 m12 l12">
                                <i class="fa-solid fa-lock prefix"></i>
                                <input type="password" name="password" maxlength="20" value="<?php if(!empty($password)) { echo $password; } ?>">
                                <label for="icon_telephone">Senha</label>
                            </div>
                            <div class="col s12 m12 l12 line">
                                <input type="submit" value="Entrar">
                            </div>
                           <div class="col s12 m12 l12">
                           <span>Senha padrão: data de aniversário sem barras. Exemplo: 01051995</span>
                           </div>
                        </div>
                    </div>
                </form>
            </div>
        </article>
    </section>
<?php
 get_footer();
?>
