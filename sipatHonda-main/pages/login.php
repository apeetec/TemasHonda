<?php
/*
Template Name: Login
*/
if($_POST) {
    // CORREÇÃO: Verifica o nonce de segurança (proteção CSRF)
    if (!isset($_POST['sipat_login_nonce']) || !wp_verify_nonce($_POST['sipat_login_nonce'], 'sipat_login_action')) {
        wp_die('Requisição inválida.');
    }

    global $wpdb;  
    //We shall SQL escape all inputs  
    $username = esc_sql($_REQUEST['matricula']);  
    $password = esc_sql($_REQUEST['password']); 
  
    $error = array();
  
    // CORREÇÃO: Validação ANTES do wp_signon() para não bater no banco com campos vazios
    if(empty($username)) {
        $error[] = 'Digite sua matrícula';
    }
    if(empty($password)) {
        $error[] = 'Digite sua senha';
    }
       
    // Só tenta o login se não houver erros de validação
    if(empty($error)) {
  
        $login_data = array();  
        $login_data['user_login'] = $username;  
        $login_data['user_password'] = $password;  
        $login_data['remember'] = true;  
   
        $user_verify = wp_signon( $login_data, false );   
  
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
                    <img src="https://sipathonda2025.com.br/wp-content/uploads/2025/11/LOGO.png" alt="" class="responsive-img">
                </div>
                <form class="col s12 m12 l12" action="" method="POST">
                    <?php wp_nonce_field('sipat_login_action', 'sipat_login_nonce'); ?>
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
                                <!-- CORREÇÃO: Senha NUNCA é colocada de volta no HTML por segurança -->
                                <input type="password" name="password" maxlength="20" value="">
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
<style>
    /* Reset e configurações gerais */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .page-login {
/*         min-height: 100vh; */
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #029d6a 0%, #016b4a 100%);
        padding: 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .page-login .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }

    .page-login .row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
    }

    .page-login .row.center {
        text-align: center;
    }

    .page-login .col {
        width: 100%;
        padding: 10px;
    }

    /* Card do formulário */
    .page-login form {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 0;
        max-width: 450px;
        margin: 20px auto;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .page-login .content {
        padding: 40px 30px;
    }

    /* Logo */
    .page-login img.responsive-img {
        max-width: 280px;
        width: 100%;
        height: auto;
        margin-bottom: 30px;
        filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.2));
        animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Mensagens de erro */
    .page-login .erro-main {
        background: #ff4757;
        color: #ffffff;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.6;
        animation: shake 0.5s ease-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }

    .page-login .erro-main span {
        display: block;
        margin: 5px 0;
    }

    /* Campos de input */
    .page-login .input-field {
        position: relative;
        margin-bottom: 30px;
    }

    .page-login .input-field .prefix {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #029d6a;
        font-size: 18px;
        z-index: 1;
        transition: color 0.3s ease;
    }

    .page-login .input-field input[type="text"],
    .page-login .input-field input[type="password"] {
        width: 100%;
        padding: 15px 15px 15px 50px;
        border: 2px solid #e1e8ed;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #f7f9fc;
        color: #2c3e50;
    }

    .page-login .input-field input:focus {
        outline: none;
        border-color: #029d6a;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(2, 157, 106, 0.15);
    }

    .page-login .input-field input:focus ~ .prefix {
        color: #016b4a;
    }

    .page-login .input-field label {
        position: absolute;
        left: 50px;
        top: 50%;
        transform: translateY(-50%);
        color: #95a5a6;
        font-size: 16px;
        pointer-events: none;
        transition: all 0.3s ease;
    }

    .page-login .input-field input:focus ~ label,
    .page-login .input-field input:not(:placeholder-shown) ~ label {
        top: -10px;
        left: 15px;
        font-size: 12px;
        color: #029d6a;
        background: #ffffff;
        padding: 0 5px;
    }

    /* Botão de submit */
    .page-login .line {
        margin-top: 10px;
    }

    .page-login input[type="submit"] {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #029d6a 0%, #027d54 100%);
        border: none;
        border-radius: 10px;
        color: #ffffff;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(2, 157, 106, 0.4);
    }

    .page-login input[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(2, 157, 106, 0.6);
        background: linear-gradient(135deg, #02b87c 0%, #029d6a 100%);
    }

    .page-login input[type="submit"]:active {
        transform: translateY(0);
    }

    /* Texto informativo */
    .page-login span {
        display: block;
        color: #555;
        font-size: 13px;
        line-height: 1.6;
        margin-top: 15px;
        padding: 12px;
        background: #e8f5f0;
        border-radius: 8px;
        border-left: 4px solid #029d6a;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .page-login {
            padding: 15px;
        }

        .page-login form {
            max-width: 100%;
            border-radius: 15px;
        }

        .page-login .content {
            padding: 30px 20px;
        }

        .page-login img.responsive-img {
            max-width: 220px;
            margin-bottom: 20px;
        }

        .page-login .input-field input[type="text"],
        .page-login .input-field input[type="password"] {
            font-size: 14px;
            padding: 12px 12px 12px 45px;
        }

        .page-login .input-field .prefix {
            font-size: 16px;
            left: 12px;
        }

        .page-login .input-field label {
            left: 45px;
            font-size: 14px;
        }

        .page-login input[type="submit"] {
            padding: 12px;
            font-size: 14px;
        }

        .page-login span {
            font-size: 12px;
        }
    }

    @media (max-width: 480px) {
        .page-login img.responsive-img {
            max-width: 180px;
        }

        .page-login .content {
            padding: 25px 15px;
        }

        .page-login .input-field {
            margin-bottom: 20px;
        }
    }

    /* Loading state */
    .page-login .loading {
        width: 100%;
    }

    /* Animação para o card ao carregar */
    @media (prefers-reduced-motion: no-preference) {
        .page-login form {
            animation: slideUp 0.6s ease-out;
        }
    }
</style>

<?php
 get_footer();
?>


