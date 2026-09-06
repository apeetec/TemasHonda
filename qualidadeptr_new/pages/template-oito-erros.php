<?php

/* Template Name: Jogo dos 8 erros */

get_header();
$id_user = get_current_user_id();// Id do usuário  
$setor = get_user_meta($id_user,'setor_usuario',true);
if($setor == 'FND' || $setor == 'DIR' || $setor == 'PLQ'){
    $srcCorreto = get_template_directory_uri() . '/img/8erros/fundicao/imagem_fundicao_correta.jpg'; 
    $srcErrado = get_template_directory_uri() . '/img/8erros/fundicao/imagem_fundicao_errada.jpg'; 
}
elseif($setor == 'USI'){
    $srcCorreto = get_template_directory_uri() . '/img/8erros/usinagem/imagem_correta.jpg'; 
    $srcErrado = get_template_directory_uri() . '/img/8erros/usinagem/imagem_errada.jpg'; 
}
elseif($setor == 'MMO' || $setor == 'CQM' || $setor == 'ETG' || $setor == 'MSC'){
    $srcCorreto = get_template_directory_uri() . '/img/8erros/mmo/imagem_correta.jpg'; 
    $srcErrado = get_template_directory_uri() . '/img/8erros/mmo/imagem_errada.jpg'; 
}
else {
    // Fallback para setor não mapeado — usa imagens MMO como padrão
    $srcCorreto = get_template_directory_uri() . '/img/8erros/mmo/imagem_correta.jpg'; 
    $srcErrado = get_template_directory_uri() . '/img/8erros/mmo/imagem_errada.jpg'; 
}

?>
<?php
$today = date('Y-m-d H:i:s');
$inicio = date('2026-03-30 00:00:00');
$fim = date('2026-04-30 23:59:59');
if($today >= $inicio && $today <= $fim){
require_once( get_template_directory() . '/template-parts/templatepart-8-erros.php' );
}
else {
    echo '<h2 class="white-text center-align">O jogo dos 8 erros não está disponível no momento.</h2>';
}
?>
<?php get_footer(); ?>
<script>
    // Função para enviar os dados do usuário em ajax
function enviarDadosJogo(){
    // var formData = $(this).serialize();
    var param1 = $('#result-time').val();
    var param2 = $('#result-score').val();
    var formData = {
        'tempo_levado': param1,
        'acertos': param2,
        };
    $.ajax({
    url: '<?php echo get_template_directory_uri(); ?>/sql/enviar_dados_jogo.php',
    type: 'POST',
    data: formData,
    success: function(response) {
        console.log(response);
    }
    });
}

const startButton = document.getElementById("start-game");
const correctImage = document.getElementById("correct-image");
const errorImage = document.getElementById("error-image");
const timerDisplay = document.getElementById("timer");
const scoreDisplay = document.getElementById("score");
const resultTimeInput = document.getElementById("result-time");
const resultScoreInput = document.getElementById("result-score");

let gameActive = false;
let timeLeft = 120;
let score = 0;
let clicks = 0;
let markers = [];
let timerInterval;
const maxClicks = 12;
// Fundição
<?php
if($setor == 'FND' || $setor == 'DIR' || $setor == 'PLQ'):
?>
    const errorPositions = [
        { x: 685, y: 623, width: 30, height: 30, marked: false }, // Tamanho específico (width, height)
        { x: 665, y: 680, width: 30, height: 30, marked: false },
        { x: 632, y: 641, width: 30, height: 30, marked: false },
        { x: 584, y: 655, width: 30, height: 30, marked: false },
        { x: 440, y: 729, width: 80, height: 80, marked: false },
        { x: 186, y: 792, width: 80, height: 80, marked: false },
        { x: 924, y: 858, width: 260, height: 250, marked: false },
        { x: 577, y: 720, width: 80, height: 80, marked: false }
    ];

// Usinagem
<?php
elseif($setor == 'USI'):
?>
    const errorPositions = [
        { x: 906, y: 690, width: 30, height: 30, marked: false }, // Tamanho específico (width, height)
        { x: 859, y: 654, width: 30, height: 30, marked: false },
        { x: 828, y: 589, width: 30, height: 30, marked: false },
        { x: 789, y: 569, width: 30, height: 30, marked: false },
        { x: 750, y: 635, width: 30, height: 30, marked: false },
        { x: 713, y: 631, width: 30, height: 30, marked: false },
        { x: 667, y: 683, width: 30, height: 30, marked: false },
        { x: 470, y: 844, width: 100, height: 100, marked: false }
    ];
<?php
elseif($setor == 'MMO' || $setor == 'CQM' || $setor == 'ETG' || $setor == 'MSC'):
?>
    const errorPositions = [
        { x: 1056, y: 819, width: 100, height: 100, marked: false }, // Tamanho específico (width, height)
        { x: 721, y: 584, width: 80, height: 80, marked: false },
        { x: 660, y: 671, width: 80, height: 80, marked: false },
        { x: 661, y: 847, width: 200, height: 200, marked: false },
        { x: 478, y: 700, width: 80, height: 80, marked: false },
        { x: 481, y: 1076, width: 80, height: 80, marked: false },
        { x: 367, y: 474, width: 80, height: 80, marked: false },
        { x: 931, y: 1136, width: 80, height: 80, marked: false }
    ];
<?php
else:
?>
    // Fallback — usa posições do MMO como padrão
    const errorPositions = [
        { x: 1056, y: 819, width: 100, height: 100, marked: false },
        { x: 721, y: 584, width: 80, height: 80, marked: false },
        { x: 660, y: 671, width: 80, height: 80, marked: false },
        { x: 661, y: 847, width: 200, height: 200, marked: false },
        { x: 478, y: 700, width: 80, height: 80, marked: false },
        { x: 481, y: 1076, width: 80, height: 80, marked: false },
        { x: 367, y: 474, width: 80, height: 80, marked: false },
        { x: 931, y: 1136, width: 80, height: 80, marked: false }
    ];
<?php
endif;
?>

// Função para verificar se o jogo está ativo
function isGameActive() {
    return gameActive && timeLeft > 0 && clicks < maxClicks;
}
// Função que busca os dados do usuário para validar se ele realizou o jogo e iniciar de onde o jogo parou
async function getUserMeta(key) {
    const response = await fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=get_game_meta&key=${key}`);
    const data = await response.json();
    return data ? JSON.parse(data) : null;
}
function updateUserMeta(key, value) {
    fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "update_game_meta", key: key, value: JSON.stringify(value) })
    });
}
// Função para começar o jogo com o tempo e os acertos de onde para caso o usuário tenha atualizado
async function loadGameState() {
    const savedTime = await getUserMeta('game_time_left');
    const savedScore = await getUserMeta('game_score');
    const savedMarkers = await getUserMeta('game_markers');
    
    if (savedTime !== null) {
        timeLeft = savedTime;
        timerDisplay.textContent = formatTime(timeLeft);
    }

    if (savedScore !== null) {
        score = savedScore;
        scoreDisplay.textContent = score;
    }

    if (savedMarkers !== null) {
        markers = savedMarkers;
        markers.forEach(marker => {
            markClick(marker.x, marker.y, marker.correct);
            if (marker.correct) {
                clicks++;
                // Restaurar flag marked na errorPosition correspondente
                errorPositions.forEach(pos => {
                    const toleranceX = pos.width / 2 + 5;
                    const toleranceY = pos.height / 2 + 5;
                    if (!pos.marked && Math.abs(pos.x - marker.x) <= toleranceX && Math.abs(pos.y - marker.y) <= toleranceY) {
                        pos.marked = true;
                    }
                });
            } else {
                clicks++;
            }
        });
    }
}
// Função para começar o jogo
function startGame() {
    if (gameActive) return;
    gameActive = true;
    let bloco = document.querySelectorAll('.bloco');
    let btStartGame = document.querySelector('#start-game');
    btStartGame.style.display = "none";
    bloco.forEach(elemento => elemento.style.display = "block");
    setTimeout(() => {
        errorImage.style.display = "block";
        correctImage.style.display = "none";
        const scoreValue = parseInt(scoreDisplay.textContent.trim(), 10);
        const [minutes, seconds] = timerDisplay.textContent.trim().split(":").map(Number);
        const totalSeconds = minutes * 60 + seconds;     
        clicks = 0;
        // score = 0;
        scoreDisplay.textContent = score;
        timerDisplay.textContent = formatTime(timeLeft);
        if (!isNaN(scoreValue) && scoreValue <= 0 && totalSeconds == 120) {
            score = 0;
            markers = [];  // Resetando os marcadores
            document.querySelectorAll(".marker, .correct-marker, .incorrect-marker").forEach(marker => marker.remove());
        }
        else {
            score = parseInt(document.getElementById('score').textContent, 10) || 0;
        }
        startTimer();
    }, 10000); // 5000ms = 5 segundos
}
// Função de inciar o tempo
function startTimer() {
    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (timeLeft <= 0) {
            endGame();
        } else {
            timeLeft--;
            timerDisplay.textContent = formatTime(timeLeft);
            updateUserMeta('game_time_left', timeLeft);  // Atualizando o tempo a cada segundo
        }
    }, 1000);
}
// Função para formatar o tempo
function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
}
// Função dos clicks
function handleClick(event) {
    if (!gameActive || clicks >= maxClicks) return;
    clicks++;
    const { offsetX, offsetY } = event;
    // Adicionando o console.log para mostrar as coordenadas do clique
    console.log(`Clique detectado em: X = ${offsetX}, Y = ${offsetY}`);
    checkError(offsetX, offsetY);
    // Se tiver excedido o máximo de cliques, o jogo encerra
    if (clicks >= maxClicks) {
        endGame();
    }
}
// Função para verificar se o clique foi um erro
function checkError(x, y) {
    let correct = false;
    // Verificando se a posição clicada corresponde a um erro
    errorPositions.forEach(pos => {
        // Calculando a tolerância com base no tamanho de width e height
        const toleranceX = pos.width / 2 + 5;  // Tolerância no eixo X (largura)
        const toleranceY = pos.height / 2 + 5; // Tolerância no eixo Y (altura)

        // Verificando se a posição clicada está dentro da área do erro (tolerância de width e height)
        if (!pos.marked && Math.abs(pos.x - x) <= toleranceX && Math.abs(pos.y - y) <= toleranceY) {
            correct = true;
            pos.marked = true; // Marcar como já corrigido
        }
    });
    markClick(x, y, correct);
    if (correct) {
        score++;
        scoreDisplay.textContent = score;
        updateUserMeta('game_score', score);  // Atualizando o score a cada clique correto
        // se a quantidade de acertos for igual ao tamanho do objeto dos erros o jogo encerra
        if(score == errorPositions.length){
            endGame();
        }
    }
}
// Função para marcar os cliques do usuário
function markClick(x, y, correct) {
    const marker = document.createElement("div");
    marker.className = correct ? "correct-marker" : "incorrect-marker";
    marker.style.left = `${x}px`;
    marker.style.top = `${y}px`;
    marker.style.position = "absolute";
    marker.style.transform = "translate(-50%, -50%)"; 
    // Ajustando o marcador com base no tamanho de cada erro
    const error = errorPositions.find(pos => Math.abs(pos.x - x) <= pos.width / 2 && Math.abs(pos.y - y) <= pos.height / 2);
    if (error) {
        marker.style.width = `${error.width}px`;  // Largura do marcador
        marker.style.height = `${error.height}px`;  // Altura do marcador
    }
    marker.style.borderRadius = "50%";  // Deixa o marcador redondo
    errorImage.parentElement.appendChild(marker);

    markers.push({ x, y, correct });
    updateUserMeta('game_markers', markers);  // Atualizando os cliques a cada novo marcador
}
// Função para mostrar os erros
function showCorrectErrors() {
    errorPositions.forEach(pos => {
        const marker = document.createElement("div");
        marker.className = "correct-marker";
        marker.style.left = `${pos.x}px`;
        marker.style.top = `${pos.y}px`;
        marker.style.position = "absolute";
        marker.style.transform = "translate(-50%, -50%)";
        errorImage.parentElement.appendChild(marker);
    });
}
// Função de encerrar o jogo
function endGame() {
    gameActive = false;
    clearInterval(timerInterval);
    resultTimeInput.value = 120 - timeLeft;
    resultScoreInput.value = score;   
    let blocoAcertos = document.querySelector('#blocoAcertos');
    let all = document.querySelector('.all');
    let acertosTotal = document.querySelector('#acertosTotal');
    let tempoTotal = document.querySelector('#tempoTotal');  

    acertosTotal.textContent = score;
    tempoTotal.textContent = 120 - timeLeft;

    updateUserMeta('game_time_left', 0);  // Resetando o tempo ao finalizar o jogo
    updateUserMeta('game_score', score);  // Salvando o score final

    all.style.display = "none";
    blocoAcertos.style.display = "block";
    enviarDadosJogo();
}

    startButton.addEventListener("click", startGame);
    errorImage.addEventListener("click", handleClick);
// Eventos on touch caso precise
    // startButton.addEventListener("pointerdown", startGame);
    // errorImage.addEventListener("pointerdown", handleClick);

// Carregar o estado do jogo quando a página for carregada
    loadGameState();

// Função que será chamada ao tentar atualizar a página
window.addEventListener('beforeunload', function (event) {
    if (isGameActive()) {
        const message = 'Você tem um jogo em andamento. Tem certeza que deseja sair?';
        event.returnValue = message;  // Para navegadores antigos
        return message;  // Para navegadores mais recentes
    }
});

const popover = document.getElementById("modal1");
  popover.addEventListener("toggle", (event) => {
      if (event.newState === "open") {
        clearInterval(timerInterval);
      } 
      else {
        startTimer();
         
      }
  });
</script>
