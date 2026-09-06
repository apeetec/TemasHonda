<?php 


############## ENVIA FOTOS
if($_POST) {

	$ids_update = array();
	$ids_update[] = $_POST['desenho1'];
	$ids_update[] = $_POST['desenho2'];
	$ids_update[] = $_POST['desenho3'];
	$ids_update[] = $_POST['desenho4'];

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

if(!$_POST && $votacao_status == 'Sim') { ?>

	<div class="votou">
		<div class="container">
		<p>J Votou acesso direto</p>
		</div>
	</div>

<?php }

else { ?>

<div class="erro-full">
	<div class="in">
		<div class="content">
			<a href="#" class="notscrollable close">X</a>
		  	<p>Você não selecionou todos os desenhos!</p>
		  </div>
	</div>
</div>

<div class="body-filtros">
	<div class="filtros">
		<?php
		if($votacao_status == 'Sim') { ?>
		<div class="votou fim">
			<div class="container">
			<p>Já Votou</p>
			</div>
		</div>
		<?php } else { ?>
		<div class="dependentes">
			<div class="container">
				<h2 id="desc">Clique para selecionar os desenhos</h2>
				<br>
				<h2>Desenhos de seus filhos</h2>
				<div class="all loop-drawings">
					<?php
					############### LOOP DOS DESENHOS DOS FILHOS ###############
					$args = array(
					    "post_type" => 'desenhos',
					    "posts_per_page" => -1,
					    'orderby' => 'date',
					    'order' => 'ASC',
					    'author' => get_current_user_id()
					);
					 
					$loop = new WP_Query( $args );  

					$dependentes_enviados = array();

					if (have_posts()) {

					    while ( $loop->have_posts() ) {
					    $loop->the_post();

					    $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
					    $thumb_id = attachment_url_to_postid( $desenho );
						$first_image_url = wp_get_attachment_image_src($thumb_id, 'desenho_thumb');
						// Pega a primeira imagem e utiliza a versão pequena
						$desenho = $first_image_url[0];

					    ?>

					    <div class="single" style="background-image: url('<?php echo $desenho; ?>');">
					    	<label>
								<input type="checkbox" data-img="<?php echo $desenho; ?>" class="selected" name="dependentes" data-id="<?php echo get_the_ID(); ?>">
								<span><i class="fas fa-check"></i></span>
							</label>
						</div>
					
					<?php

					    }
					}
					############### LOOP DOS DESENHOS DOS FILHOS ###############
					?>					
				</div>
			</div>
		</div>
		<div class="outros">
			<div class="container">
				<div class="listagem">		
					<div class="coluna" id="left">
						FILTROS
					</div>
					<div class="coluna" id="right">
						<div class="loop-drawings">
							<?php
							############### LOOP DOS DESENHOS DOS FILHOS ###############
							$args = array(
							    "post_type" => 'desenhos',
							    "posts_per_page" => -1,
							    'orderby' => 'date',
							    'order' => 'ASC',
							    'author__not_in' => get_current_user_id()
							);
							 
							$loop = new WP_Query( $args );  

							$dependentes_enviados = array();

							if (have_posts()) {

							    while ( $loop->have_posts() ) {
							    $loop->the_post();

							    // Respeita a configuracao de "Visibilidade" do painel: cada grupo
							    // enxerga apenas os grupos e as categorias liberados pelo administrador.
							    // Antes esta tela listava os desenhos de todos os colaboradores.
							    if ( function_exists( 'explode_visibilidade_pode_ver_post' )
							        && ! explode_visibilidade_pode_ver_post( get_the_ID() ) ) {
							      continue;
							    }

							    $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
							    $thumb_id = attachment_url_to_postid( $desenho );
								$first_image_url = wp_get_attachment_image_src($thumb_id, 'desenho_thumb');
								// Pega a primeira imagem e utiliza a versão pequena
								$desenho = $first_image_url[0];

							    ?>

							    <div class="single" style="background-image: url('<?php echo $desenho; ?>');">
							    	<label>
										<input type="checkbox" data-img="<?php echo $desenho; ?>" class="selected" name="dependentes" data-id="<?php echo get_the_ID(); ?>">
										<span><i class="fas fa-check"></i></span>
									</label>
								</div>
							
							<?php

							    }
							}
							############### LOOP DOS DESENHOS DOS FILHOS ###############
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
		$('.erro-full').show();
		return false;
	}
	else {
    	$('.msg-loading').fadeIn(500);
    }

});
</script>

<script>
$(function(){
    $('.body-filtros input[type=checkbox]').prop("checked", false);
    $('.menu form .single input').removeAttr('value');
});
</script>

<?php

} // Fim else caso não tenha votado ainda

?>