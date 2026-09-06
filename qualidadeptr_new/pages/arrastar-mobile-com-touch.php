<?php

/* Template Name: Jogo de arrastar com o touch*/

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


</style>
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
    <div id="info">
    <div>
        <button id="startGame">Iniciar Jogo</button>    
    </div>
    <p>Tempo: <span id="timer">120</span> segundos</p>
    <p>Pontuação: <span id="score">0</span></p>
    </div>
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
    let droppedItems = 0;

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
        dragItems.forEach(item => item.draggable = false); // Impede mais movimentações
    }

    function sendResults() {
        fetch("http://localhost/qualidadeptr/wp-admin/admin-ajax.php", {
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

    // Iniciar o jogo ao clicar no botão
    startButton.addEventListener("click", () => {
        if (!gameStarted) {
            gameStarted = true;
            startTimer();
            startButton.disabled = true; // Desabilita o botão após o início
        }
    });

    dragItems.forEach(item => {
        item.addEventListener("dragstart", (e) => {
            if (!gameStarted) return; // Só permite arrastar se o jogo começou
            e.dataTransfer.setData("text", e.target.id);
        });
    });

    dropZones.forEach(zone => {
        zone.addEventListener("dragover", (e) => {
            e.preventDefault();
        });

        zone.addEventListener("drop", (e) => {
            e.preventDefault();
            if (!gameStarted) return;

            const draggedId = e.dataTransfer.getData("text");
            const draggedElement = document.getElementById(draggedId);

            if (!draggedElement) return;

            zone.appendChild(draggedElement);
            draggedElement.draggable = false;
            droppedItems++; // Conta os itens arrastados

            if (zone.dataset.match === draggedId) {
                score++;
                scoreDisplay.textContent = score;
            }

            // Se todos os itens forem arrastados, encerra o jogo
            if (droppedItems === totalDraggables) {
                clearInterval(timerInterval);
                endGame();
            }
        });
    });
});

    </script>

