<?php

/* Template Name: Regulamento */

if(!empty($_GET['regulamento']) && $_GET['regulamento'] == 'sim') {
	update_user_meta(get_current_user_id(),'user_field_leitura_reg','Sim');
}

$leitura = get_user_meta(get_current_user_id(),'user_field_leitura_reg',true);
if(!empty($_GET['regulamento']) && $leitura == 'Sim') {
	wp_redirect( home_url() );
	exit;
}

get_header();

?>

<div class="main">
    <div class="container">
        <h2 class="center">Regulamento</h2>
    </div>
    <br>
    <div class="container">
        <iframe width="100%" src="https://explodecriacao.com.br/wp-content/uploads/2023/09/regulamento-2023.pdf" id="reg"></iframe>
    </div>
    <br>
    <div class="container">
        <?php if($leitura == 'Não' || empty($leitura)) { ?>
            <a class="btn-large" id="btnRegulamento" href="?regulamento=sim">Li, e concordo com o regulamento</a>
        <?php } ?>
    </div>
</div>

<?php get_footer(); ?>