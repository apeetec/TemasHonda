</div>

<footer>

    <?php wp_footer(); ?>

    <script>
    $("form.loading").submit(function(){
      $('.msg-loading').fadeIn(500);
    });
    </script>

    <script>
    $(".erro-full .close").on('click',function(){
      $('.erro-full').hide();
    });
    </script>

    <script>
    // AVOID RESUBMISSION ON REFRESH
    if (window.history.replaceState){window.history.replaceState( null, null, window.location.href );}
    </script>

    <!-- ////////////////////// LGPD - PARTE #3/3 || Colocar após o jQuery ////////////////////// -->
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@2/src/js.cookie.min.js"></script>
    <script>
      <?php
      // Pega o site atual sem https para ser utilizado no nome do cookie para que cada cookie seja único
      $website = home_url(); $website = preg_replace('#^https?://#', '', $website);
      ?>
      // Verifica se Cookie existe e executa a ação
      if (document.cookie.indexOf('cookielgpd_explode') >= 0) {
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

        // Cookies.set('cookielgpd_<?php echo $website; ?>', 'cookielgpd_<?php echo $website; ?>_value', { expires: 30, path: '/' });

        Cookies.set("cookielgpd_explode", "cookielgpd_explode_value", { expires : 30, path: "/;SameSite=Lax", secure: true});

      });
    </script>
    <!-- ////////////////////// FIM PARTE #3 ////////////////////// -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/5.3.0/ekko-lightbox.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ekko-lightbox/5.3.0/ekko-lightbox.min.js"></script>
    
    <script src="<?php bloginfo('template_url'); ?>/js/bootstrap.min.js"></script>

    <script>$(document).on("click",'[data-toggle="lightbox"]',function(t){t.preventDefault(),$(this).ekkoLightbox()});</script>
    <script src="<?php bloginfo('template_url'); ?>/js/script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip-utils/0.0.2/jszip-utils.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
  </footer>
  </body>
</html>