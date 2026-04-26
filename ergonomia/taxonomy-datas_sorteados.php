<?php get_header(); 
$term = get_queried_object();
$term_id = $term->term_id; // Categoria principal ou seja, da data principal
$term_name = $term->name; // Nome da categoria principal
$codigo_sorteado = get_term_meta($term_id, 'codigo_sorteado', true);
?>
<div class="container">
    <h5>Sorteados <?php echo $term_name;?></h5>
</div>
<?php
echo $codigo_sorteado;
?>
<?php get_footer(); ?>
<script>
	    document.addEventListener('DOMContentLoaded', function() {
    const elems = document.querySelectorAll('.collapsible');
    const instances = M.Collapsible.init(elems, {
      // specify options here
    });
  });
</script>