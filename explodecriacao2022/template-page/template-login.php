<?php
/*
Template Name: Login
*/

// >>>>>> COLOQUE SEU SECRET AQUI (NÃO É O MESMO QUE O SITEKEY) <<<<<<
$RECAPTCHA_SECRET = '6LcS1uErAAAAACkruBIrUTsB-i0Pb6agwrR9sIrq';

if ($_POST) {  

  // Sanitização de inputs
  $username = isset($_REQUEST['matricula']) ? sanitize_text_field($_REQUEST['matricula']) : '';
  $password = isset($_REQUEST['password'])  ? sanitize_text_field($_REQUEST['password'])  : '';
  $captcha  = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';

  $remember = true;  
  $error = array();

  // Validações básicas
  if (empty($username)) { $error[] = 'Digite sua matrícula'; }
  if (empty($password)) { $error[] = 'Digite sua senha'; }
  if (empty($captcha))  { $error[] = 'Captcha inválido'; }

  // Só tenta validar reCAPTCHA se veio algo no POST
  if (empty($error)) {
    // Chamada à API do Google para validar o token
    $response = wp_remote_post(
      'https://www.google.com/recaptcha/api/siteverify',
      array(
        'timeout' => 10,
        'body' => array(
          'secret'   => $RECAPTCHA_SECRET,
          'response' => $captcha,
          'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        )
      )
    );

    if (is_wp_error($response)) {
      // Se a API do Google não responder, bloqueia por segurança
      $error[] = 'Falha ao validar o captcha. Tente novamente.';
    } else {
      $body = wp_remote_retrieve_body($response);
      $json = json_decode($body, true);

      // Validação principal
      $success   = isset($json['success']) ? (bool)$json['success'] : false;
      $hostname  = $json['hostname'] ?? '';
      $challenge = $json['challenge_ts'] ?? '';

      if (!$success) {
        // Você pode inspecionar $json['error-codes'] se quiser mensagens mais específicas
        $error[] = 'Captcha inválido';
      } else {
        // (Opcional) Amarra o hostname para este domínio
        // Troque "seu-dominio.com.br" pelo domínio real se quiser endurecer
        // if (stripos($hostname, 'seu-dominio.com.br') === false) {
        //   $error[] = 'Captcha inválido (domínio divergente).';
        // }
      }
    }
  }

  // Só tenta logar se não houver erros (inclui captcha ok)
  if (empty($error)) {

    $login_data = array(
      'user_login'    => $username,
      'user_password' => $password,
      'remember'      => $remember,
    );

    // false = usa SSL apenas se disponível; ajuste se necessário
    $user_verify = wp_signon($login_data, false);

    if (is_wp_error($user_verify))  {
      $error[] = 'Dados inválidos';
    } else {
      // Já autenticado; wp_signon define cookie. Ajuste fluxo conforme sua regra:
      $senhaAlterada = get_user_meta($user_verify->ID, 'user_field_senha_alterada', true);

      if ($senhaAlterada === 'Não') {
        wp_redirect(home_url('nova-senha'));
        exit;
      } else {
        wp_redirect(home_url());
        exit;
      }
    }
  }

} // Fim POST

get_header();

?>

      <div class="form space">
        <div class="container" style="max-width: 550px;">
          
          <form action="" class="loading" method="POST">

          	<img id="logo" src="<?php bloginfo('template_url'); ?>/img/logo-explode.png" alt="Logo">

            <p id="elegivel">Prezados colaboradores,
              <span>O acesso ao site na primeira fase do programa está restrito apenas para colaboradores elegíveis à participação no Explode Criação. Na fase de exposição e votação dos desenhos, todos os colaboradores terão acesso a plataforma.</span></p>

          	<?php if(!empty($error)) { ?>
          	<p class="erro-main">
          		<?php foreach ($error as $erro) { ?>
    	  			<span><?php echo $erro; ?></span>
    	  			<?php } ?>
    	  		</p>
    	  		<?php } ?>

          	<div class="line">
          		<label>Matrícula</label>
          		<input type="text" name="matricula" maxlength="20" value="<?php if(!empty($username)) { echo $username; } ?>">     
				<span id="desc">Somente números</span>
          	</div>
          	<div class="line">
          		<label>Senha</label>
          		<input type="password" name="password" maxlength="20" value="<?php if(!empty($password)) { echo $password; } ?>">
              <span id="desc">Senha: 4 últimos dígitos do CPF</span>
          	</div>
            <!--<div class="line">
            <img src="<?php bloginfo('template_url'); ?>/img/captcha.jpg">
            </div>-->
            <div class="line">
            	<div class="g-recaptcha" data-sitekey="6LcS1uErAAAAAMroVGRhgvb0kZd4wB0NalGyAPlh"></div>
            </div>
          	<div class="line">
          		<input type="submit" value="Entrar">
          	</div>

			<div class="line">
<!-- 				<a id="resetSenha" target="_blank" href="https://www.explodecriacao.com.br/wp-login.php?loggedout=true&wp_lang=pt_BR">Esqueci minha senha.</a> -->
			</div>

			

          </form>

        </div>
      </div>
      <!-- Fim de exemplo de div -->

      <script src='https://www.google.com/recaptcha/api.js'></script>

<?php get_footer(); ?>