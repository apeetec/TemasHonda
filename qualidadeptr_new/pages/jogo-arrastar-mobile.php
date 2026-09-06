<?php

/* Template Name: Jogo de arrastar  mobile*/

get_header();
$id_user = get_current_user_id();// Id do usuário  
$setor = get_user_meta($id_user,'empresa_usuario',true);
if($setor == 'FND'){
    $srcCorreto = get_template_directory_uri() . '/img/8erros/fundicao/imagem_fundicao_correta.jpg'; 
    $srcErrado = get_template_directory_uri() . '/img/8erros/fundicao/imagem_fundicao_errada.jpg'; 
}
elseif($setor == 'USI'){
    $srcCorreto = get_template_directory_uri() . '/img/8erros/usinagem/imagem_correta.jpg'; 
    $srcErrado = get_template_directory_uri() . '/img/8erros/usinagem/imagem_errada.jpg'; 
}
elseif($setor == 'MMO'){
    $srcCorreto = get_template_directory_uri() . '/img/8erros/mmo/imagem_correta.jpg'; 
    $srcErrado = get_template_directory_uri() . '/img/8erros/mmo/imagem_errada.jpg'; 
}
?>
<style>
  /* Adicione seus estilos aqui */
 
/* CSS */
body {
    font-family: Arial, sans-serif;
    text-align: center;
}





</style>
<button id="startGame">Iniciar Jogo</button>

<div id="game">
    <div id="sidebar">
        <div class="draggable" id="item1" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/barra.jpg" alt="">
        </div>
        <div class="draggable" id="item2" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/cabeca.jpg" alt="">
        </div>
        <div class="draggable" id="item3" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/celular.jpg" alt="">
        </div>
        <div class="draggable" id="item4" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/fio.jpg" alt="">
        </div>
        <div class="draggable" id="item5" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/maos.jpg" alt="">
        </div>
        <div class="draggable" id="item6" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/parafusos_mesas.jpg" alt="">
        </div>
        <div class="draggable" id="item7" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/parafusos.jpg" alt="">
        </div>
        <div class="draggable" id="item8" draggable="true">
            <img src="<?php bloginfo('template_url'); ?>/img/itens/peca.jpg" alt="">
        </div>


    </div>

    <div id="play-area">
    
        <img src="<?php bloginfo('template_url'); ?>/img/imagem-com-os-buracos.jpg" alt="">
        <div class="drop-zone drop-zone-1" data-match="item1"></div>
        <div class="drop-zone drop-zone-2" data-match="item2"></div>
        <div class="drop-zone drop-zone-3" data-match="item3"></div>
        <div class="drop-zone drop-zone-4" data-match="item4"></div>
        <div class="drop-zone drop-zone-5" data-match="item5"></div>
        <div class="drop-zone drop-zone-6" data-match="item6"></div>
        <div class="drop-zone drop-zone-7" data-match="item7"></div>
        <div class="drop-zone drop-zone-8" data-match="item8"></div>
    </div>
</div>

<div id="info">
    <p>Tempo: <span id="timer">120</span> segundos</p>
    <p>Pontuação: <span id="score">0</span></p>
</div>
<?php get_footer(); ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const dragItems = document.querySelectorAll(".draggable");
    const dropZones = document.querySelectorAll(".drop-zone");
    const scoreDisplay = document.getElementById("score");
    const timerDisplay = document.getElementById("timer");
    const startButton = document.getElementById("startGame");
    let score = 0;
    let timeLeft = 120;
    let gameStarted = false;
    let timerInterval;
    let totalDraggables = dragItems.length;
    let placedItems = 0;
    let selectedItem = null;

    function startTimer() {
        timerInterval = setInterval(() => {
            timeLeft--;
            timerDisplay.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                endGame();
            }
        }, 1000);
    }

    function endGame() {
        alert(`Jogo encerrado! Pontuação final: ${score}`);
        sendResults();
    }

    function sendResults() {
        fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "save_game_results",
                score: score,
                timeTaken: 120 - timeLeft
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("Resultados enviados:", data.message);
            } else {
                console.error("Erro ao enviar resultados:", data.message);
            }
        })
        .catch(error => console.error("Erro na requisição:", error));
    }

    startButton.addEventListener("click", () => {
        if (!gameStarted) {
            gameStarted = true;
            startTimer();
            startButton.disabled = true;
        }
    });

    function selectItem(item) {
        if (!gameStarted || item.classList.contains("used")) return;

        selectedItem = item;
        dragItems.forEach(i => i.classList.remove("selected"));
        item.classList.add("selected");
    }

    function placeItem(zone) {
        if (!gameStarted || !selectedItem || zone.classList.contains("occupied")) return;

        if (zone.dataset.match === selectedItem.id) {
            score++;
            scoreDisplay.textContent = score;
        }

        zone.appendChild(selectedItem);
        selectedItem.classList.remove("selected");
        selectedItem.classList.add("used");
        zone.classList.add("occupied");

        selectedItem = null;
        placedItems++;

        if (placedItems === totalDraggables) {
            clearInterval(timerInterval);
            endGame();
        }
    }

    // Suporte para toque e clique
    dragItems.forEach(item => {
        item.addEventListener("click", () => selectItem(item));
        item.addEventListener("touchstart", () => selectItem(item));
    });

    dropZones.forEach(zone => {
        zone.addEventListener("click", () => placeItem(zone));
        zone.addEventListener("touchend", () => placeItem(zone));
    });
});



</script>

