<?php

/* Template Name: Jogo dos sete erros */

get_header();

?>
<div id="jogo_concluido" class="modal" popover>
  <div class="modal-content center">
    <h4>
        Obrigado por participar
    </h4>
  </div>
  <div class="modal-footer">
    <button tabindex="0" class="waves-effect btn-flat" popovertarget="jogo_concluido">Fechar</button>
  </div>
</div>
<div id="acertou_todas" class="modal" popover>
  <div class="modal-content center">
    <h4>
        Parabens, você acertou todos os erros!!!!!!
    </h4>
  </div>
  <div class="modal-footer">
    <button tabindex="0" class="waves-effect btn-flat" popovertarget="acertou_todas">Fechar</button>
  </div>
</div>
<input type="hidden" id="seteerros" name="sete_erros" value="">
<!-- Inicio do jogo -->
<div id="canvas-main" class="canvas">
    <img src="<?php bloginfo('template_url'); ?>/img/game/imagem-esquerda.jpg" draggable="false">
</div>
<div id="canvas-copy" class="canvas">
    <img src="<?php bloginfo('template_url'); ?>/img/game/imagem-direita.jpg" draggable="false">
    <i class="cursor"></i>
</div>
<br />
<input type="button" value="Finalizar jogo" onclick="jogo.verify();">
<input type="button" value="Jogar novamente" onclick="jogo.start();">
<!-- Fim do jogo -->
<?php get_footer(); ?>
<!-- <script src="<?php bloginfo('template_url'); ?>/js/jquery-2.1.3.min.js"></script> -->
<script src="<?php bloginfo('template_url'); ?>/js/game.js"></script>
<script>
	$(document).ready(function(){
			var positions = {
				'0': { x : '195', y : '125' },
				'1': { x : '476', y : '130' },
				'2': { x : '90'	, y : '365' },
				'3': { x : '330', y : '255' },
				'4': { x : '380', y : '425' },
				'5': { x : '190', y : '460' },
				'6': { x : '190', y : '70' 	}
			}

			jogo = new Game(positions);
			// jogo.debug();
	});
</script>
