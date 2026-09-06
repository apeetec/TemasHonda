<?php

/* Template Name: Nova senha */

$sucesso = false;

if($_POST) {  
     
  $novaSenha = $_POST['password'];
  $confirmPassword = $_POST['confirmPassword'];
  $error = array();     

// Atualizar E-mail
  $idUserMail = get_current_user_id();
  $novoUserMail = sanitize_text_field($_POST['email']);
    
  $tablename = "wp_ec_users";
  $data = array(
    'user_email' => $novoUserMail
  );
  $wherecondition = array(
    'ID' => $idUserMail
  );

  $updated = $wpdb->update($tablename, $data, $wherecondition);
// End Atualizar E-mail

//  Senha
  if(empty($novaSenha) || empty($confirmPassword)) {
      $error[] = 'Digite a senha e a confirmação';
  }
  else {
    if($novaSenha != $confirmPassword) {
      $error[] = 'As senhas não combinam';
    }
    else {
      if(strlen($novaSenha) < 6) {
        $error[] = 'Escolha uma senha maior';
      }
      else {
        update_user_meta(get_current_user_id(),'user_field_senha_alterada','Sim');
        wp_set_password($novaSenha,get_current_user_id());
        $sucesso = true;
      }
    }
  }

}

get_header();

?>
      <div class="form space">
        <div class="container">
          
          <form action="" class="loading" method="POST">

            <?php if($sucesso == false) { ?>
          	<p class="center"><b>Sucesso!</b> <br>Para seu primeiro acesso e por motivos de segurança, será necessário alterar sua senha.</p>

          	<?php if(!empty($error)) { ?>
          	<p class="erro-main">
          		<?php foreach ($error as $erro) { ?>
    	  			<span><?php echo $erro; ?></span>
    	  			<?php } ?>
    	  		</p>
    	  		<?php } ?>

          	<div class="line">
          		<label>Senha</label>
          		<input type="password" name="password" maxlength="20" required>
          	</div>
          	<div class="line">
          		<label>Confirme a senha</label>
          		<input type="password" name="confirmPassword" maxlength="20" required>
          	</div>


            <div class="line">
          		<label>Digite seu e-mail</label>
          		<input type="email" name="email" required>
          	</div>

              
            <!-- <a id="sair" class="main" href="<?php echo wp_logout_url( home_url('login') ); ?>">Sair</a> -->


          	<div class="line">
          		<input type="submit" value="Alterar senha">
          	</div>
            
          <?php } elseif($sucesso == true) { echo '<p class="sucesso">Sua senha e e-mail foram alteradas com sucesso! <a href="'.get_permalink(2).'">Clique aqui</a> para efetuar o login novamente.</p>'; } ?>

          </form>

        </div>
      </div>
      <!-- Fim de exemplo de div -->

<?php get_footer(); ?>