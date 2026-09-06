<?php

/* Template Name: Jogo dos 8 erros */

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
<div class="container">
<h1>Jogo dos 8 Erros</h1>
<button id="start-game">Iniciar</button>
<div class="info">Tempo: <span id="timer">2:00</span> | Acertos: <span id="score">0</span></div>
</div>
<div class="game-container">
    <img id="correct-image" class="game-image" src="<?php echo $srcCorreto; ?>" alt="Imagem Correta">
    <img id="error-image" class="game-image" src="<?php echo $srcErrado; ?>" alt="Imagem com Erros">
</div>
<input type="hidden" id="result-time" name="tempo_levado" value="tempo">
<input type="hidden" id="result-score" name="acertos" value="acertou">

<?php get_footer(); ?>
<script>
function enviarDadosJogo(){
    // var formData = $(this).serialize();
    var param1 = $('#result-time').val();
    var param2 = $('#result-score').val();
    var formData = {
        'tempo_levado': param1,
        'acertos': param2,
        };
    $.ajax({
    url: 'http://localhost/qualidadeptr/wp-content/themes/new_theme_qualidade_ptr/sql/enviar_dados_jogo.php',
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
const maxClicks = 8;
// Fundição
<?php
if($setor == 'FND'):
?>
    const errorPositions = [
        { x: 685, y: 623, width: 40, height: 40, marked: false }, // Tamanho específico (width, height)
        { x: 663, y: 678, width: 40, height: 40, marked: false },
        { x: 638, y: 651, width: 40, height: 40, marked: false },
        { x: 584, y: 655, width: 40, height: 40, marked: false },
        { x: 440, y: 729, width: 40, height: 40, marked: false },
        { x: 186, y: 792, width: 40, height: 40, marked: false },
        { x: 924, y: 858, width: 260, height: 250, marked: false },
        { x: 577, y: 720, width: 40, height: 40, marked: false }
    ];

// Usinagem
<?php
elseif($setor == 'USI'):
?>
    const errorPositions = [
        { x: 906, y: 690, width: 40, height: 40, marked: false }, // Tamanho específico (width, height)
        { x: 859, y: 654, width: 40, height: 40, marked: false },
        { x: 828, y: 589, width: 40, height: 40, marked: false },
        { x: 789, y: 569, width: 40, height: 40, marked: false },
        { x: 750, y: 635, width: 40, height: 40, marked: false },
        { x: 713, y: 631, width: 40, height: 40, marked: false },
        { x: 667, y: 683, width: 40, height: 40, marked: false },
        { x: 470, y: 844, width: 40, height: 40, marked: false }
    ];
<?php
elseif($setor == 'MMO'):
?>
    const errorPositions = [
        { x: 1056, y: 819, width: 100, height: 100, marked: false }, // Tamanho específico (width, height)
        { x: 721, y: 584, width: 40, height: 40, marked: false },
        { x: 660, y: 671, width: 40, height: 40, marked: false },
        { x: 661, y: 847, width: 200, height: 200, marked: false },
        { x: 478, y: 700, width: 40, height: 40, marked: false },
        { x: 481, y: 1076, width: 40, height: 40, marked: false },
        { x: 367, y: 474, width: 40, height: 40, marked: false },
        { x: 931, y: 1136, width: 40, height: 40, marked: false }
    ];
<?php
endif;
?>
// Montagem de motores

// Função para verificar se o jogo está ativo
function isGameActive() {
    return gameActive && timeLeft > 0 && clicks < maxClicks;
}

// Função que será chamada ao tentar atualizar a página
window.addEventListener('beforeunload', function (event) {
    if (isGameActive()) {
        const message = 'Você tem um jogo em andamento. Tem certeza que deseja sair?';
        event.returnValue = message;  // Para navegadores antigos
        return message;  // Para navegadores mais recentes
    }
});

async function getUserMeta(key) {
    const response = await fetch(`http://localhost/qualidadeptr/wp-admin/admin-ajax.php?action=get_game_meta&key=${key}`);
    const data = await response.json();
    return data ? JSON.parse(data) : null;
}

function updateUserMeta(key, value) {
    fetch("http://localhost/qualidadeptr/wp-admin/admin-ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ action: "update_game_meta", key: key, value: JSON.stringify(value) })
    });
}

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
        });
    }
}

function startGame() {
    if (gameActive) return;
    gameActive = true;

    correctImage.style.display = "none";
    errorImage.style.display = "block";

    clicks = 0;
    score = 0;
    scoreDisplay.textContent = score;
    timerDisplay.textContent = formatTime(timeLeft);

    markers = [];  // Resetando os marcadores
    document.querySelectorAll(".marker, .correct-marker, .incorrect-marker").forEach(marker => marker.remove());
    startTimer();
}

function startTimer() {
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

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
}

function handleClick(event) {
    if (!gameActive || clicks >= maxClicks) return;

    clicks++;
    const { offsetX, offsetY } = event;
    // Adicionando o console.log para mostrar as coordenadas do clique
    console.log(`Clique detectado em: X = ${offsetX}, Y = ${offsetY}`);
    checkError(offsetX, offsetY);

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

function endGame() {
    gameActive = false;
    clearInterval(timerInterval);
    resultTimeInput.value = 120 - timeLeft;
    resultScoreInput.value = score;
    // showCorrectErrors();
    updateUserMeta('game_time_left', 0);  // Resetando o tempo ao finalizar o jogo
    updateUserMeta('game_score', score);  // Salvando o score final
    alert("Jogo encerrado com sucesso!");
    enviarDadosJogo();
}

startButton.addEventListener("click", startGame);
errorImage.addEventListener("click", handleClick);

// Carregar o estado do jogo quando a página for carregada
loadGameState();

</script>


<script>
    // const startButton = document.getElementById("start-game");
    // const correctImage = document.getElementById("correct-image");
    // const errorImage = document.getElementById("error-image");
    // const timerDisplay = document.getElementById("timer");
    // const scoreDisplay = document.getElementById("score");
    // const resultTimeInput = document.getElementById("result-time");
    // const resultScoreInput = document.getElementById("result-score");

    // let gameActive = false;
    // let timeLeft = 120;
    // let score = 0;
    // let clicks = 0;
    // let markers = [];
    // let timerInterval;
    // const maxClicks = 8;

    // // Posições dos erros
    // const errorPositions = [
    //     { x: 101, y: 301 },
    //     { x: 200, y: 250 },
    //     { x: 300, y: 350 },
    //     { x: 400, y: 120 },
    //     { x: 500, y: 230 },
    //     { x: 120, y: 400 },
    //     { x: 330, y: 270 },
    //     { x: 550, y: 330 }
    // ];

    // async function getUserMeta(key) {
    //     const response = await fetch(`http://localhost/qualidadeptr/wp-admin/admin-ajax.php?action=get_game_meta&key=${key}`);
    //     const data = await response.json();
    //     return data ? JSON.parse(data) : null;
    // }

    // function updateUserMeta(key, value) {
    //     fetch("http://localhost/qualidadeptr/wp-admin/admin-ajax.php", {
    //         method: "POST",
    //         headers: { "Content-Type": "application/x-www-form-urlencoded" },
    //         body: new URLSearchParams({ action: "update_game_meta", key: key, value: JSON.stringify(value) })
    //     });
    // }

    // async function loadGameState() {
    //     const savedTime = await getUserMeta('game_time_left');
    //     const savedScore = await getUserMeta('game_score');
        
    //     if (savedTime !== null) {
    //         timeLeft = savedTime;
    //         timerDisplay.textContent = formatTime(timeLeft);
    //     }

    //     if (savedScore !== null) {
    //         score = savedScore;
    //         scoreDisplay.textContent = score;
    //     }
    // }

    // function startGame() {
    //     if (gameActive) return;
    //     gameActive = true;

    //     correctImage.style.display = "none";
    //     errorImage.style.display = "block";

    //     clicks = 0;
    //     score = 0;
    //     scoreDisplay.textContent = score;
    //     timerDisplay.textContent = formatTime(timeLeft);

    //     markers = [];
    //     document.querySelectorAll(".marker, .correct-marker").forEach(marker => marker.remove());
    //     startTimer();
    // }

    // function startTimer() {
    //     timerInterval = setInterval(() => {
    //         if (timeLeft <= 0) {
    //             endGame();
    //         } else {
    //             timeLeft--;
    //             timerDisplay.textContent = formatTime(timeLeft);
    //             updateUserMeta('game_time_left', timeLeft);  // Atualizando o tempo a cada segundo
    //         }
    //     }, 1000);
    // }

    // function formatTime(seconds) {
    //     const minutes = Math.floor(seconds / 60);
    //     const secs = seconds % 60;
    //     return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
    // }

    // function handleClick(event) {
    //     if (!gameActive || clicks >= maxClicks) return;

    //     clicks++;
    //     const { offsetX, offsetY } = event;

    //     checkError(offsetX, offsetY);

    //     if (clicks >= maxClicks) {
    //         endGame();
    //     }
    // }

    // function checkError(x, y) {
    //     let correct = false;

    //     errorPositions.forEach(pos => {
    //         if (Math.abs(pos.x - x) <= 20 && Math.abs(pos.y - y) <= 20) {
    //             correct = true;
    //         }
    //     });

    //     markClick(x, y, correct);
    //     if (correct) {
    //         score++;
    //         scoreDisplay.textContent = score;
    //         updateUserMeta('game_score', score);  // Atualizando o score a cada clique correto
    //     }
    // }

    // function markClick(x, y, correct) {
    //     const marker = document.createElement("div");
    //     marker.className = "marker";
    //     marker.style.left = `${x}px`;
    //     marker.style.top = `${y}px`;
    //     marker.style.backgroundColor = correct ? "green" : "red";
    //     marker.style.position = "absolute";
    //     marker.style.transform = "translate(-50%, -50%)";
    //     errorImage.parentElement.appendChild(marker);
    //     markers.push({ x, y, correct });
    // }

    // function showCorrectErrors() {
    //     errorPositions.forEach(pos => {
    //         const marker = document.createElement("div");
    //         marker.className = "correct-marker";
    //         marker.style.left = `${pos.x}px`;
    //         marker.style.top = `${pos.y}px`;
    //         marker.style.position = "absolute";
    //         marker.style.transform = "translate(-50%, -50%)";
    //         errorImage.parentElement.appendChild(marker);
    //     });
    // }

    // function endGame() {
    //     gameActive = false;
    //     clearInterval(timerInterval);
    //     resultTimeInput.value = 120 - timeLeft;
    //     resultScoreInput.value = score;
    //     showCorrectErrors();
    //     updateUserMeta('game_time_left', 0);  // Resetando o tempo ao finalizar o jogo
    //     updateUserMeta('game_score', score);  // Salvando o score final
    //     alert("Jogo encerrado com sucesso!");
    // }

    // startButton.addEventListener("click", startGame);
    // errorImage.addEventListener("click", handleClick);

    // // Carregar o estado do jogo quando a página for carregada
    // loadGameState();
</script>



        

