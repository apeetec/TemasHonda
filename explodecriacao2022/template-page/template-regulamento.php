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
$grupo = get_user_meta(get_current_user_id(),'user_field_funcionario_grupo',true);

get_header();

?>

<div class="main">
  <div class="container">
  	<div class="content">
    <h2 class="center" style="color: #000">Regulamento</h2>
  <?php if($grupo == 'Grupo 1 MAO' || $grupo == 'Grupo 2 MAO' || $grupo == 'Grupo 3 MAO' || $grupo == 'Grupo 4 MAO' || $grupo == 'Grupo 5 MAO' || $grupo == 'Grupo 6 MAO') { ?>
    <iframe src="https://www.explodecriacao.com.br/wp-content/uploads/2026/09/REGULAMENTO_Explode_Criacao_2026_Revisado-1.pdf" id="reg"></iframe>
  <?php } else { ?>
    <iframe src="https://www.explodecriacao.com.br/wp-content/uploads/2026/08/Regulamento_SAO_HAB_V3.pdf" id="reg"></iframe>
  <?php } ?>
   <a id="btnRegulamento" href="?regulamento=sim">Li, e concordo com o regulamento</a>
    <!--<?php if($leitura == 'Não' || empty($leitura)) { ?>
    <a id="btnRegulamento" href="?regulamento=sim">Li, e concordo com o regulamento</a>
	<?php } ?>-->
	</div>
  </div>
</div>

<?php get_footer(); ?>