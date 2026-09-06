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

// Posições dos erros com tamanho específico de largura e altura
const errorPositions = [
    { x: 685, y: 623, width: 40, height: 40 }, // Tamanho específico (width, height)
    { x: 663, y: 678, width: 40, height: 40 },
    { x: 638, y: 651, width: 40, height: 40 },
    { x: 584, y: 655, width: 40, height: 40 },
    { x: 440, y: 729, width: 40, height: 40 },
    { x: 186, y: 792, width: 40, height: 40 },
    { x: 924, y: 858, width: 260, height: 250 },
    { x: 577, y: 720, width: 40, height: 40 }
];

// const errorPositions = [
//     { x: 685, y: 623 },
//     { x: 663, y: 678 },
//     { x: 638, y: 651 },
//     { x: 584, y: 655 },
//     { x: 440, y: 729 },
//     { x: 186, y: 792 },
//     { x: 924, y: 858 },
//     { x: 577, y: 720 }
// ];

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
        const toleranceX = pos.width / 2 + 20;  // Tolerância no eixo X (largura)
        const toleranceY = pos.height / 2 + 20; // Tolerância no eixo Y (altura)

        // Verificando se a posição clicada está dentro da área do erro (tolerância de width e height)
        if (Math.abs(pos.x - x) <= toleranceX && Math.abs(pos.y - y) <= toleranceY) {
            correct = true;
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
    showCorrectErrors();
    updateUserMeta('game_time_left', 0);  // Resetando o tempo ao finalizar o jogo
    updateUserMeta('game_score', score);  // Salvando o score final
    alert("Jogo encerrado com sucesso!");
}

startButton.addEventListener("click", startGame);
errorImage.addEventListener("click", handleClick);

// Carregar o estado do jogo quando a página for carregada
loadGameState();