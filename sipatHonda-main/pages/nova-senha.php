<?php

/* Template Name: Nova senha */

$sucesso = false;

if($_POST) {  
    // CORREÇÃO: Verifica o nonce de segurança (proteção CSRF)
    if (!isset($_POST['sipat_nova_senha_nonce']) || !wp_verify_nonce($_POST['sipat_nova_senha_nonce'], 'sipat_nova_senha_action')) {
        wp_die('Requisição inválida.');
    }
     
 // CORREÇÃO: Sanitiza os inputs de senha
 $novaSenha = sanitize_text_field($_POST['password']);
 $confirmPassword = sanitize_text_field($_POST['confirmPassword']);

 $error = array();

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
<section class="page-login">
    <article class="container">               
        <form action="" class="loading" method="POST">
            <?php wp_nonce_field('sipat_nova_senha_action', 'sipat_nova_senha_nonce'); ?>
            <div class="content">            
                <?php if($sucesso == false) { ?>
                <p class="center white-text"><b>Sucesso!</b> <br>Para seu primeiro acesso e por motivos de segurança, será necessário alterar sua senha.</p>

                <?php if(!empty($error)) { ?>
                <p class="erro-main">
                    <?php foreach ($error as $erro) { ?>
                        <span><?php echo $erro; ?></span>
                        <?php } ?>
                    </p>
                    <?php } ?>

                <div class="line">
                    <label class="white-text">Senha</label>
                    <input type="password" name="password" maxlength="20">
                </div>
                <div class="line">
                    <label class="white-text">Confirme a senha</label>
                    <input type="password" name="confirmPassword" maxlength="20">
                </div>
                <div class="line">
                    <input type="submit" value="Alterar senha">
                </div>

                <?php } elseif($sucesso == true) { echo '<p class="sucesso">Sua senha foi alterada com sucesso! <a href="'.get_site_url().'">Clique aqui</a> para efetuar o login novamente.</p>'; } ?>
            </div>
        </form>
    </article>
</section>
<?php get_footer(); ?>