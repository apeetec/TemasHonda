

<footer>
    <?php wp_footer(); ?>
    <script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/materialize.min.js"></script>
    <script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/jquery.min.js"></script> 
    <script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/script.js"></script>
    <!-- ////////////////////// FIM PARTE #3 ////////////////////// -->
    <script defer src="<?php bloginfo('template_url'); ?>/js/slick.min.js"></script>
    <script src="<?php bloginfo('template_url'); ?>/js/cookies.min.js"></script>
    <script>
    window.onload = function() {    
      <?php
      // Pega o site atual sem https para ser utilizado no nome do cookie para que cada cookie seja único
      $website = home_url(); $website = preg_replace('#^https?://#', '', $website);
      ?>
      // Verifica se Cookie existe e executa a ação
      if (document.cookie.indexOf('cookielgpd_<?php echo $website; ?>') >= 0) {
          // Se Existe, não exibe o aviso          
        }
      else {
          // Se não existe, exibe o aviso
          $('.lgpd').fadeIn(300);
      }

      // Seta o Cookie quando clicar para fechar o aviso
      $('.lgpd #fecha').click(function() {

        // Esconde o aviso
        $('.lgpd').hide();

        var date = new Date();

        // Minutos: o primeiro número dos 3 é a quantidade de minutos
        date.setTime(date.getTime() + (30 * 60 * 1000));
        // Usar 'date' no 'expires' para transformar em minutos, algum valor numérico diretamente em 'expires' é a quantidade de dias desejada
        Cookies.set('cookielgpd_<?php echo $website; ?>', 'cookielgpd_<?php echo $website; ?>_value', { expires: 30, path: '/' });

      });
    };
    </script>
    <script type="text/javascript" src="<?php bloginfo('template_url'); ?>/js/functions/custom-scripts.js"></script>
</footer>
  </body>
</html>