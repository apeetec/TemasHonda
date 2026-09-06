<?php

/* Template Name: forms pmq */

get_header();

?>
<section class="forms">
	<article class="container">
	<?php echo do_shortcode('[contact-form-7 id="3ff7a48" title="Proposta de Melhorias de Qualidade"]');?>
	</article>
</section>
<?php
get_footer();
?>
<script>
$(document).ready(function() {
    $('input[name="nome"]').val('<?php echo $nome;?>');
	$('input[name="matricula"]').val('<?php echo $login;?>');
	$('input[name="setor"]').val('<?php echo $setor;?>');
  });
</script>