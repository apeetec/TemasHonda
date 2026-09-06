<?php

/* Template Name: Jogo de arrastar */

get_header();
$id_user = get_current_user_id();// Id do usuário  
$setor = get_user_meta($id_user,'empresa_usuario',true);
$jogo = get_user_meta($id_user,'iniciou_jogo_arrastar',true);
// $acertos = get_user_meta($id_user,'score_drag',true);
?>
<?php
$today = date('Y-m-d H:i:s');
$inicio = date('2025-05-12 08:00:00');
$fim = date('2025-05-16 08:00:00');
if($today >= $inicio){
require_once( get_template_directory() . '/template-parts/template-jogo-arrastar.php' );
}
?>
<?php get_footer(); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const dragItems = document.querySelectorAll(".draggable");
    const dropZones = document.querySelectorAll(".drop-zone");
    const scoreDisplay = document.getElementById("score");
    const timerDisplay = document.getElementById("timer");
    const startButton = document.getElementById("startGame");
    const viewHintBtn = document.getElementById("viewHint");
    const restartButton = document.getElementById("restartGame");
    const hintBox = document.getElementById("hint-box");
    const mainImage = document.getElementById("main-image");
    const finalImage = document.getElementById("final-image");

    let score = 0;
    let timeLeft = 120;
    let gameStarted = false;
    let timerInterval;
    let droppedItems = 0;
    let hintUsed = false;
    let totalDraggables = dragItems.length;
    let currentItemIndex = 0;

    const hints = {
        item1: "Luminárias - identificar lâmpadas queimadas e informar o lider do setor",
        item2: "Barreiras de segurança  - ao identificar a falhas de funcionalidades da barreira de segurança, informe a manutenção imediatamente",
        item3: "Apertadeiras - As apertadeiras devem estar suspensas e devidamente travadas",
        item4: "Dispositivo - Ao manusear os dispositivos, certifique-se que estão condição de uso",
        item5: "Conexões - Ao identificar  mangueiras e conexões com vazamento de ar, informe a manutenção",
        item6: "EPI - Utilize todos os EPIS necessários para operação",
        item7: "Equipamento - Realize diaramente o check list de equipamentos",
        item8: "Dica para item 8",
    };
    // Começa o tempo
    function startTimer() {
        timerInterval = setInterval(() => {
            timeLeft--;
            timerDisplay.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                console.log("Tempo encerrado");
                // endGame();
            }
        }, 1000);
        sendResults();
    }
    // Encerra o jogo
    // function endGame() {
    //     alert(`Jogo encerrado! Pontuação final: ${score}`);
    //     sendResults();
    //     dragItems.forEach(item => item.draggable = false);
    //     dragItems.forEach(item => item.style.display = 'none');
    //     dropZones.forEach(item => item.style.display = 'none');
    //     mainImage.style.display = 'none';
    //     finalImage.style.display = 'block';
    //     window.location.href = "https://qualidadeptr.com.br/";
    // }
    function endGame() {
    clearInterval(timerInterval); // Para o cronômetro se ainda estiver rodando

    // Exibe pontuação final ao jogador
    // alert(`Jogo encerrado!\n\nPontuação final: ${score}\nTempo usado: ${120 - timeLeft} segundos`);

    // Envia os dados para o servidor via AJAX
    sendResults();

    // Desativa os elementos interativos do jogo
    dragItems.forEach(item => item.draggable = false);
    dragItems.forEach(item => item.style.display = 'none');
    dropZones.forEach(item => item.style.display = 'none');

    // Esconde a imagem principal e mostra a imagem final
    if (mainImage) mainImage.style.display = 'none';
    if (finalImage) finalImage.style.display = 'block';

    // (Opcional) Espera 3 segundos antes de redirecionar
    setTimeout(() => {
        window.location.href = "https://qualidadeptr.com.br/jogo-de-arrastar/";
    }, 3000);
}

    // envia o dados via ajax
    // function sendResults() {
    //     fetch("<?php echo admin_url('admin-ajax.php');?>", {
    //         method: "POST",
    //         headers: { "Content-Type": "application/x-www-form-urlencoded" },
    //         body: new URLSearchParams({
    //             action: "save_game_results",
    //             score: score,
    //             timeTaken: 120 - timeLeft
    //         })
    //     });
    // }

    function sendResults() {
    fetch("<?php echo admin_url('admin-ajax.php');?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            action: "save_game_results",
            score: score,
            timeTaken: 120 - timeLeft
        })
    })
    .then(res => res.json())
    .then(data => {
        console.log("Resposta do servidor:", data);
    })
    .catch(err => {
        console.error("Erro no envio:", err);
    });
}


    function showHint(itemId) {
        if (!hintUsed && hints[itemId]) {
            hintBox.textContent = hints[itemId];
            hintBox.style.display = 'block';
            hintUsed = true;
            viewHintBtn.disabled = true;
            setTimeout(() => {
                hintBox.style.display = 'none';
            }, 15000);
        }
    }
    // Função para ir avançando as dicas
    function showNextHintAutomatically(itemId) {
        if (hints[itemId]) {
            hintBox.textContent = hints[itemId];
            hintBox.style.display = 'block';
            setTimeout(() => {
                hintBox.style.display = 'none';
            }, 15000);
        }
        if(hintUsed == true){
            hintBox.style.display = 'none';
        }
    }
    // Inicia o jogo
    startButton.addEventListener("click", () => {
        if (!gameStarted) {
            gameStarted = true;
            startTimer();
            let intro = document.getElementById('intro');
            let zoomImage = document.getElementById('zoomImage');
            intro.style.display = 'none';
            viewHintBtn.style.display = 'inline-block';
            restartButton.style.display = 'inline-block';
            zoomImage.style.display = 'inline-block';
            startButton.disabled = true;
            showNextHintAutomatically(dragItems[currentItemIndex].id);
        }
    });
    // Função para ver a dica
    viewHintBtn.addEventListener("click", () => {
        if (gameStarted && !hintUsed) {
            const item = dragItems[currentItemIndex];
            console.log(item);
            if (item && item.draggable) {
                showHint(item.id);
                setTimeout(() => {
                document.getElementById('hint-box').style.display = 'none';
            }, 15000);
            }
        }
    });
    // Reinicia o jogo
    restartButton.addEventListener("click", () => {
        if (gameStarted) {
            droppedItems = 0;
            score = 0;
            scoreDisplay.textContent = 0;
            dragItems.forEach(item => {
                item.draggable = true;
                document.getElementById("sidebar").appendChild(item);
            });
            hintBox.style.display = 'none';
            mainImage.style.display = 'block';
            finalImage.style.display = 'none';
            restartButton.style.display = 'none';
        }
    });

    dragItems.forEach(item => {
        item.addEventListener("dragstart", (e) => {
            if (!gameStarted) return;
            e.dataTransfer.setData("text", e.target.id);
        });
    });

    dropZones.forEach(zone => {
        zone.addEventListener("dragover", (e) => e.preventDefault());
        zone.addEventListener("drop", (e) => {
            e.preventDefault();
            if (!gameStarted) return;

            const draggedId = e.dataTransfer.getData("text");
            const draggedElement = document.getElementById(draggedId);
            if (!draggedElement) return;

            zone.appendChild(draggedElement);
            draggedElement.draggable = false;
            droppedItems++;

            if (zone.dataset.match === draggedId) {
                score++;
                scoreDisplay.textContent = score;
            }

            hintBox.style.display = 'none';
            currentItemIndex++;
            if (currentItemIndex < dragItems.length) {
                showNextHintAutomatically(dragItems[currentItemIndex].id);
            }

            if (droppedItems === totalDraggables) {
                clearInterval(timerInterval);
                endGame();
            }
        });
    });
});


// suavizar o scroll
document.addEventListener("DOMContentLoaded", () => {
    const dragItems = document.querySelectorAll(".draggable");

    function disableScroll(e) {
        e.preventDefault();
    }

    dragItems.forEach(item => {
        item.addEventListener("dragstart", () => {
            document.addEventListener("touchmove", disableScroll, { passive: false });
        });

        item.addEventListener("dragend", () => {
            document.removeEventListener("touchmove", disableScroll);
        });
    });
});



</script>
