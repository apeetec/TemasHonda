<style>
p{font-size:1.5rem;}
</style>

<?php if($jogo != 'Mostrar'): ?>
<div id="tutorial" style="max-width:980px;width:100%;margin:0 auto;">
    <p class="white-text">
        <strong>
            Tutorial:
        </strong>
    </p>
    <p class="white-text">
        Leia o texto que aparecerá na tela.
    </p>
    <p class="white-text">
        Clique no item pressionando o botão do mouse e arraste.
    </p>
    <p class="white-text">
        Enquanto arrasta, frases irão surgir acima da imagem para ajudar você.
    </p>  
    <br>
    <div id="modal1" class="modal transparent z-depth-0 zoomImage8Erros" popover>
        <div class="modal-content">
            <img id="main-image" class="responsive-img" src="<?php bloginfo('template_url'); ?>/img/foto_principal.jpg" alt="">
        </div>
        <button tabindex="0" class="btn waves-effect waves-light close" popovertarget="modal1">Fechar</button>
        <!-- <div class="modal-footer">
           
        </div> -->
    </div>
</div>
<div style="max-width:980px;width:100%;margin:0 auto;">
    <button class="btn waves-effect waves-light" id="startGame">Iniciar Jogo</button>
    <button class="btn waves-effect waves-light" id="viewHint" style="display:none;">Ver Dica</button>
    <button class="btn waves-effect waves-light" id="restartGame" style="display:none;">Reiniciar</button>
    <button class="btn waves-effect waves-light" popovertarget="modal1" style="display:none;" id="zoomImage"> Zoom da imagem</button>
</div>
<div id="info" style="max-width:980px;width:100%;margin:0 auto;">
    <p class="white-text" hidden>Tempo: <span id="timer">120</span> segundos</p>
    <p class="white-text">Pontuação: <span id="score">0</span></p>
</div>
<br>
<div id="intro" style="max-width:980px;width:100%;margin:0 auto;">
    <p class="white-text">
    Lembre-se: segurança e qualidade andam juntas.
    </p>
    <p class="white-text">
    Complete a figura corretamente. Boa sorte!
    </p>
</div>
<div class="container" style="max-width:1280px;width:100%;margin:0 auto;">
    <div id="hint-box"></div>
</div>
<div id="game">
    <div id="sidebar">
        <!-- Draggables mantidos -->
        <?php for ($i = 1; $i <= 7; $i++): ?>
            <div class="draggable" id="item<?= $i ?>" draggable="true">
                <img src="<?php bloginfo('template_url'); ?>/img/itens/<?= ['Foto-1','Foto-2','Foto-3','Foto-4','Foto-5','Foto-6','Foto-7'][$i-1] ?>.jpg" alt="">
            </div>
        <?php endfor; ?>
    </div>
    <div id="play-area">
        <img id="main-image" src="<?php bloginfo('template_url'); ?>/img/foto_principal.jpg" alt="">
        <!-- <img id="final-image" style="display:none;" src="<?php bloginfo('template_url'); ?>/img/foto_principal_sem_corte.jpg" alt=""> -->
        <?php for ($i = 1; $i <= 7; $i++): ?>
            <div class="drop-zone drop-zone-<?= $i ?>" data-match="item<?= $i ?>"></div>
        <?php endfor; ?>
    </div>
</div>

<?php else: ?>
<div class="container center">
    <h4 class="white-text">Parabéns, você concluiu o jogo</h4>
    <p class="white-text">O total de acertos é de: <?php echo get_user_meta($id_user,'score_drag',true);?></p>
    <div id="play-area" class="finalizado">
        <img id="final-image" src="<?php bloginfo('template_url'); ?>/img/foto_principal_sem_corte.jpg" alt="">
    </div>
</div>
<?php endif; ?>
