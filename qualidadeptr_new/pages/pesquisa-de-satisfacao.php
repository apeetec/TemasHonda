<?php

/* Template Name: Pesquisa de satisfação*/

get_header();

?>
<article id="">
    <section class="container">
        <?php
        echo do_shortcode( '[contact-form-7 id="464e0f7" title="Pesquisa de satisfação"]' );
        ?>
    </section>
</article>
<?php get_footer(); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const blocos = document.querySelectorAll('.responder');

  blocos.forEach(function (bloco) {
    const radios = bloco.querySelectorAll('input[type="radio"]');
    const terceiroRadio = radios[2]; // índice 2 = terceiro radio

    if (terceiroRadio) {
      terceiroRadio.addEventListener('change', function () {
        // Exemplo: adicionar 'required' a um campo extra dentro do mesmo bloco
        const campoExtra = bloco.querySelector('textarea');
        if (campoExtra) {
          campoExtra.setAttribute('required', 'required');
        }
      });
    }

    // Opcional: remover required se outro radio for selecionado
    radios.forEach(function (radio, index) {
      if (index !== 2) {
        radio.addEventListener('change', function () {
          const campoExtra = bloco.querySelector('textarea');
          if (campoExtra) {
            campoExtra.removeAttribute('required');
          }
        });
      }
    });
  });
});


</script>
