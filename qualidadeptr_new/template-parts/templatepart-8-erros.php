<?php
// if(empty($tempo)){
?>
<section class="all">
    <div class="container">
    <h1 class="white-text">Como jogar</h1>
    <p class="white-text">
    COMO JOGAR :

    TOQUE NO LOCAL ONDE VOCÊ IDENTIFICOU O ERRO, MARCANDO-O PARA SABER ONDE VOCÊ SELECIONOU! 
    <br>
    IMPORTANTE:  PRESTE ATENÇÃO, POIS, DEPOIS DE MARCADO, NÃO PODERÁ SER TROCADO.
    <br>
    <strong>VOCÊ TERÁ 12 CLIQUES NO PRAZO DE 2 MINUTOS  PARA ENCONTRAR OS ERROS. </strong>
    <br>    
    <strong>CLIQUE NO BOTÃO "JOGAR" PARA INICIAR O JOGO!</strong> 
    <br>
    A IMAGEM CORRETA IRÁ APARECER NA TELA POR 10 SEGUNDOS E, LOGO EM SEGUIDA, TROCARÁ A IMAGEM E INICIARÁ O JOGO. 
    <br>
    VOCÊ  PODE DAR ZOOM NA IMAGEM UTILIZANDO O BOTÃO DE "EXPANDIR IMAGEM".
    <br>
    BOM JOGO
    </p>
    <button id="start-game">Jogar</button>
        <div class="info bloco" style="display:none;">
            <p class="white-text">Tempo: <span id="timer">2:00</span> | Acertos: <span id="score">0</span></p><button id="stopGamebeforeZoom" class="btn waves-effect waves-light" popovertarget="modal1">Expandir imagem</button>
        </div>
    </div>
    <div class="game-container bloco" style="display:none;">
        <img id="correct-image" class="game-image" src="<?php echo $srcCorreto; ?>" alt="Imagem Correta">
        <img id="error-image" class="game-image" src="<?php echo $srcErrado; ?>" alt="Imagem com Erros">
    </div>
    <input type="hidden" id="result-time" name="tempo_levado" value="tempo">
    <input type="hidden" id="result-score" name="acertos" value="acertou">
</section>

<div class="container" id="blocoAcertos">
    <h4 class="white-text">Obrigado</h4>
    <div class="blocoInformacoes">
        <p>Você acertou: <span id="acertosTotal"></span></p>
        <p>Tempo:  <span id="tempoTotal"></span></p>

    </div>
</div>

<div id="modal1" class="modal zoomImage8Erros" popover>
  <div class="modal-content">
    <img id="" class="zoom" height="" width="" data-magnify-src="<?php echo $srcErrado; ?>" src="<?php echo $srcErrado; ?>" alt="Imagem com Erros">
  </div>
 <button tabindex="0" class="waves-effect btn-flat closeZoom" popovertarget="modal1" id="startGameAfterZoom">Fechar</button>
</div>
<?php
// }
// else{
?>


<!-- <div class="container" id="blocoAcertos" style="display:block;">
    <h4 class="white-text">Obrigado</h4>
    <div class="blocoInformacoes">
        <p>Você acertou: <?php echo $acertos;?></p>
        <p>Tempo: <?php echo $tempo;?></p>
    </div>
</div> -->
<?php
// }
?>
