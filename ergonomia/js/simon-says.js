/**
 * ============================================================================
 * Simon Says — Game Engine (Vanilla JavaScript)
 * ============================================================================
 *
 * Motor completo do jogo Simon Says para WordPress.
 *
 * Funcionalidades:
 * - Geração de sequências aleatórias de cores
 * - Reprodução animada da sequência (iluminação dos pads)
 * - Captura de input do jogador via click/touch
 * - Cronômetro em tempo real (MM:SS)
 * - Dificuldade progressiva (velocidade da sequência aumenta)
 * - Comunicação AJAX com WordPress (salvar partida, verificar status)
 * - Controle de tentativas diárias (3 por dia)
 * - Feedback visual e sonoro
 *
 * Arquivo: js/simon-says.js
 * Usado em: template-jogo-simonsays.php (wp_enqueue_script)
 * ============================================================================
 */

(function () {
  "use strict";

  // ========================================================================
  // CONSTANTES DO JOGO
  // ========================================================================

  /** Cores disponíveis (IDs dos pads no HTML) */
  const COLORS = ["green", "red", "yellow", "blue"];

  /** Velocidade base da animação da sequência em ms */
  const BASE_SPEED = 800;

  /** Redução de velocidade por nível (ms) — a cada nível fica mais rápido */
  const SPEED_DECREMENT = 30;

  /** Velocidade mínima permitida (ms) */
  const MIN_SPEED = 250;

  /** Duração do flash ao iluminar um pad (ms) */
  const FLASH_DURATION = 400;

  /** Máximo de tentativas por dia */
  const MAX_ATTEMPTS = 3;

  // ========================================================================
  // ESTADO DO JOGO
  // ========================================================================

  let gameState = {
    sequence: [], // Sequência completa gerada pelo computador
    playerInput: [], // Input atual do jogador
    level: 0, // Nível atual (= tamanho da sequência)
    score: 0, // Pontuação (sequências corretas até agora)
    isPlaying: false, // Jogo está em andamento?
    isShowingSequence: false, // Computador está mostrando sequência?
    timerInterval: null, // Intervalo do cronômetro
    timerSeconds: 0, // Segundos decorridos
    attemptsRemaining: 3, // Tentativas restantes hoje
    attemptNumber: 0, // Número da tentativa atual do dia
  };

  // ========================================================================
  // REFERÊNCIAS AO DOM
  // ========================================================================

  /** Pads coloridos do jogo */
  const pads = {
    green: null,
    red: null,
    yellow: null,
    blue: null,
  };

  /** Elementos de UI */
  let ui = {
    startBtn: null, // Botão de iniciar jogo
    timerDisplay: null, // Display do cronômetro (MM:SS)
    scoreDisplay: null, // Display de pontuação
    attemptDisplay: null, // Display de tentativa atual
    statusMessage: null, // Mensagem de status (sua vez, observar, etc)
    gameBoard: null, // Container do tabuleiro do jogo
    gameOverModal: null, // Modal de fim de jogo
    finalScore: null, // Pontuação final no modal
    finalTime: null, // Tempo final no modal
    newRecordBadge: null, // Badge de novo recorde
    rankingBody: null, // Corpo da tabela de ranking
    blockedOverlay: null, // Overlay de jogo bloqueado
    instructions: null, // Bloco de instruções "Como Jogar"
    attemptsRemainingDisplay: null, // Span de tentativas restantes (info jogador)
  };

  // ========================================================================
  // INICIALIZAÇÃO
  // ========================================================================

  /**
   * Inicializa o jogo quando o DOM estiver pronto.
   * Busca referências aos elementos, configura eventos e verifica status do jogador.
   */
  document.addEventListener("DOMContentLoaded", function () {
    // Captura referências aos elementos do DOM
    initDOMReferences();

    // Configura event listeners nos pads
    initPadListeners();

    // Configura botão de iniciar
    if (ui.startBtn) {
      ui.startBtn.addEventListener("click", startGame);
    }

    // Botão de jogar novamente no modal de game over
    var playAgainBtn = document.getElementById("simon-play-again");
    if (playAgainBtn) {
      playAgainBtn.addEventListener("click", function () {
        hideGameOverModal();
        // Verifica se ainda tem tentativas antes de iniciar novo jogo
        if (gameState.attemptsRemaining > 0) {
          startGame();
        }
      });
    }

    // Verifica status do jogador via AJAX
    checkPlayerStatus();
  });

  /**
   * Inicializa referências aos elementos do DOM.
   * Todos os IDs são definidos no template PHP.
   */
  function initDOMReferences() {
    // Pads coloridos
    pads.green = document.getElementById("simon-pad-green");
    pads.red = document.getElementById("simon-pad-red");
    pads.yellow = document.getElementById("simon-pad-yellow");
    pads.blue = document.getElementById("simon-pad-blue");

    // Elementos de UI
    ui.startBtn = document.getElementById("simon-start-btn");
    ui.timerDisplay = document.getElementById("simon-timer");
    ui.scoreDisplay = document.getElementById("simon-score");
    ui.attemptDisplay = document.getElementById("simon-attempt");
    ui.statusMessage = document.getElementById("simon-status-message");
    ui.gameBoard = document.getElementById("simon-game-board");
    ui.gameOverModal = document.getElementById("simon-gameover-modal");
    ui.finalScore = document.getElementById("simon-final-score");
    ui.finalTime = document.getElementById("simon-final-time");
    ui.newRecordBadge = document.getElementById("simon-new-record");
    ui.rankingBody = document.getElementById("simon-ranking-body");
    ui.blockedOverlay = document.getElementById("simon-blocked-overlay");
    ui.instructions = document.getElementById("simon-instructions");
    ui.attemptsRemainingDisplay = document.getElementById("simon-attempts-remaining");
  }

  /**
   * Configura event listeners de click/touch em cada pad colorido.
   * Cada pad dispara handlePadClick quando clicado.
   */
  function initPadListeners() {
    COLORS.forEach(function (color) {
      if (pads[color]) {
        pads[color].addEventListener("click", function () {
          handlePadClick(color);
        });
      }
    });
  }

  // ========================================================================
  // CONTROLE DO JOGO
  // ========================================================================

  /**
   * Inicia uma nova partida.
   * Consome a tentativa no servidor ANTES de começar o jogo, garantindo que
   * um refresh na página não devolva a tentativa ao jogador (anti-trapaça).
   */
  function startGame() {
    // Verifica tentativas restantes (guarda local — confirmação definitiva vem do servidor)
    if (gameState.attemptsRemaining <= 0) {
      showBlockedState();
      return;
    }

    // Bloqueia o botão imediatamente para evitar duplo clique
    if (ui.startBtn) {
      ui.startBtn.disabled = true;
      ui.startBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Iniciando...';
    }
    setStatusMessage("Aguardando servidor...", "info");

    // Consome a tentativa no servidor — se o usuário der refresh, a tentativa
    // já foi debitada e não será devolvida
    var formData = new FormData();
    formData.append("action", "simon_start_game");
    formData.append("nonce", simonSaysData.nonce);

    fetch(simonSaysData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (!result.success) {
          // Tentativas esgotadas ou erro no servidor
          setStatusMessage(
            (result.data && result.data.message) || "Não foi possível iniciar o jogo.",
            "game-over"
          );
          if (ui.startBtn) {
            ui.startBtn.disabled = false;
            ui.startBtn.innerHTML = '<i class="fa-solid fa-play"></i> Iniciar Jogo';
          }
          checkPlayerStatus(); // Sincroniza estado com o servidor
          return;
        }

        // Atualiza estado local com os valores confirmados pelo servidor
        gameState.attemptsRemaining = parseInt(result.data.tentativas_restantes);
        gameState.attemptNumber     = parseInt(result.data.tentativas_usadas);

        // Prossegue com o jogo
        _doStartGame();
      })
      .catch(function (error) {
        console.error("Simon Says: Erro ao iniciar partida:", error);
        setStatusMessage("Erro de conexão. Tente novamente.", "game-over");
        if (ui.startBtn) {
          ui.startBtn.disabled = false;
          ui.startBtn.innerHTML = '<i class="fa-solid fa-play"></i> Iniciar Jogo';
        }
      });
  }

  /**
   * Executa a inicialização real da partida após a tentativa ser confirmada
   * pelo servidor via AJAX em startGame().
   */
  function _doStartGame() {
    // Reseta estado do jogo
    gameState.sequence = [];
    gameState.playerInput = [];
    gameState.level = 0;
    gameState.score = 0;
    gameState.isPlaying = true;
    gameState.timerSeconds = 0;

    // Atualiza UI
    updateScoreDisplay();
    updateTimerDisplay();
    updateAttemptDisplay();
    setStatusMessage("Preparando...", "info");

    // Som de início do jogo
    playStartSound();

    // Esconde botão de iniciar durante o jogo
    if (ui.startBtn) {
      ui.startBtn.disabled = false;
      ui.startBtn.style.display = "none";
    }

    // Oculta as instruções com transição suave
    if (ui.instructions) {
      ui.instructions.style.transition = "opacity 0.4s ease, max-height 0.5s ease";
      ui.instructions.style.opacity = "0";
      setTimeout(function () {
        ui.instructions.style.display = "none";
      }, 400);
    }

    // Habilita tabuleiro
    if (ui.gameBoard) {
      ui.gameBoard.classList.add("simon-active");
    }

    // Inicia cronômetro
    startTimer();

    // Inicia primeira rodada após breve delay
    setTimeout(function () {
      nextRound();
    }, 1000);
  }

  /**
   * Avança para a próxima rodada.
   * Adiciona uma nova cor à sequência e a reproduz para o jogador.
   */
  function nextRound() {
    // Incrementa nível
    gameState.level++;
    gameState.playerInput = [];

    // Gera nova cor aleatória e adiciona à sequência
    var randomColor = COLORS[Math.floor(Math.random() * COLORS.length)];
    gameState.sequence.push(randomColor);

    // Mensagem para o jogador observar
    setStatusMessage("👀 Observe a sequência!", "watching");

    // Reproduz a sequência completa com animação
    playSequence();
  }

  /**
   * Reproduz a sequência de cores com iluminação sequencial.
   * Velocidade aumenta conforme o nível (dificuldade progressiva).
   */
  function playSequence() {
    gameState.isShowingSequence = true;
    disablePads();

    // Calcula velocidade para este nível
    var speed = Math.max(
      MIN_SPEED,
      BASE_SPEED - gameState.level * SPEED_DECREMENT,
    );

    var i = 0;
    var sequenceInterval = setInterval(function () {
      if (i < gameState.sequence.length) {
        flashPad(gameState.sequence[i], speed * 0.5);
        i++;
      } else {
        // Sequência completa — vez do jogador
        clearInterval(sequenceInterval);
        gameState.isShowingSequence = false;
        enablePads();
        setStatusMessage("🎮 Sua vez! Repita a sequência", "your-turn");
      }
    }, speed);
  }

  /**
   * Processa o clique do jogador em um pad.
   * Verifica se a cor clicada está correta na posição atual da sequência.
   *
   * @param {string} color — Cor do pad clicado ('green', 'red', 'yellow', 'blue')
   */
  function handlePadClick(color) {
    // Ignora cliques se não está jogando ou se sequência está sendo exibida
    if (!gameState.isPlaying || gameState.isShowingSequence) return;

    // Feedback visual do clique
    flashPad(color, FLASH_DURATION);

    // Adiciona ao input do jogador
    gameState.playerInput.push(color);

    // Posição atual na sequência
    var currentIndex = gameState.playerInput.length - 1;

    // Verifica se a cor está correta
    if (
      gameState.playerInput[currentIndex] !== gameState.sequence[currentIndex]
    ) {
      // ERROU — fim da partida
      gameOver();
      return;
    }

    // Verifica se completou toda a sequência da rodada
    if (gameState.playerInput.length === gameState.sequence.length) {
      // ACERTOU a sequência completa!
      gameState.score++;
      updateScoreDisplay();
      setStatusMessage("✅ Correto! Próxima rodada...", "correct");

      // Som de acerto
      playCorrectSound();

      // Desabilita pads enquanto prepara próxima rodada
      disablePads();

      // Próxima rodada após breve pausa
      setTimeout(function () {
        nextRound();
      }, 1000);
    }
  }

  /**
   * Finaliza a partida (jogador errou).
   * Para o cronômetro, salva dados via AJAX e mostra modal de game over.
   */
  function gameOver() {
    gameState.isPlaying = false;
    stopTimer();
    disablePads();

    // Som de erro + game over
    playErrorSound();
    setTimeout(function () { playGameOverSound(); }, 600);

    setStatusMessage("❌ Você errou! Fim da partida.", "game-over");

    // Formata tempo para envio
    var tempoFormatado = formatTime(gameState.timerSeconds);

    // Salva resultado via AJAX
    saveGameResult(gameState.score, tempoFormatado);

    // Mostra modal de game over após breve delay
    setTimeout(function () {
      showGameOverModal(gameState.score, tempoFormatado);
    }, 1500);
  }

  // ========================================================================
  // CRONÔMETRO
  // ========================================================================

  /**
   * Inicia o cronômetro do jogo.
   * Atualiza a cada segundo e exibe no formato MM:SS.
   */
  function startTimer() {
    gameState.timerSeconds = 0;
    updateTimerDisplay();
    gameState.timerInterval = setInterval(function () {
      gameState.timerSeconds++;
      updateTimerDisplay();
    }, 1000);
  }

  /**
   * Para o cronômetro do jogo.
   */
  function stopTimer() {
    if (gameState.timerInterval) {
      clearInterval(gameState.timerInterval);
      gameState.timerInterval = null;
    }
  }

  /**
   * Formata segundos em string MM:SS.
   *
   * @param {number} totalSeconds — Total de segundos
   * @returns {string} — Tempo no formato "MM:SS"
   */
  function formatTime(totalSeconds) {
    var min = Math.floor(totalSeconds / 60);
    var sec = totalSeconds % 60;
    return String(min).padStart(2, "0") + ":" + String(sec).padStart(2, "0");
  }

  // ========================================================================
  // FEEDBACK VISUAL
  // ========================================================================

  /**
   * Ilumina (flash) um pad por uma duração determinada.
   * Adiciona a classe CSS 'simon-pad-active' que ativa o efeito de brilho.
   *
   * @param {string} color    — Cor do pad ('green', 'red', 'yellow', 'blue')
   * @param {number} duration — Duração do flash em ms
   */
  function flashPad(color, duration) {
    var pad = pads[color];
    if (!pad) return;

    pad.classList.add("simon-pad-active");

    // Efeito sonoro via Web Audio API (opcional, silencioso se AudioContext não disponível)
    playTone(color);

    setTimeout(function () {
      pad.classList.remove("simon-pad-active");
    }, duration);
  }

  /**
   * Desabilita todos os pads (não respondem a cliques).
   */
  function disablePads() {
    COLORS.forEach(function (color) {
      if (pads[color]) {
        pads[color].classList.add("simon-pad-disabled");
      }
    });
  }

  /**
   * Habilita todos os pads para interação.
   */
  function enablePads() {
    COLORS.forEach(function (color) {
      if (pads[color]) {
        pads[color].classList.remove("simon-pad-disabled");
      }
    });
  }

  // ========================================================================
  // ÁUDIO (Web Audio API) — Sistema completo de efeitos sonoros
  // ========================================================================

  /** Contexto de áudio (lazy — inicializado no primeiro uso) */
  var audioCtx = null;

  /**
   * Retorna o AudioContext, criando-o sob demanda.
   * Necessário para contornar restrição de autoplay dos browsers.
   *
   * @returns {AudioContext|null}
   */
  function getAudioCtx() {
    try {
      if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      // Resume contexto caso esteja suspenso (política de autoplay)
      if (audioCtx.state === "suspended") {
        audioCtx.resume();
      }
      return audioCtx;
    } catch (e) {
      return null;
    }
  }

  /**
   * Utilitário: reproduz uma nota com parâmetros flexíveis.
   *
   * @param {number} frequency — Frequência em Hz
   * @param {number} duration  — Duração em segundos
   * @param {string} type      — Tipo de onda: 'sine', 'square', 'sawtooth', 'triangle'
   * @param {number} volume    — Volume (0 a 1)
   * @param {number} delay     — Delay antes de tocar (segundos)
   */
  function playNote(frequency, duration, type, volume, delay) {
    var ctx = getAudioCtx();
    if (!ctx) return;

    var startTime = ctx.currentTime + (delay || 0);
    var osc = ctx.createOscillator();
    var gain = ctx.createGain();

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.type = type || "sine";
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(volume || 0.3, startTime);
    // Fade out suave para evitar clique
    gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

    osc.start(startTime);
    osc.stop(startTime + duration);
  }

  // ---- Frequências por cor (notas musicais clássicas do Simon) ----
  var PAD_FREQUENCIES = {
    green:  329.63,  // Mi4
    red:    261.63,  // Dó4
    yellow: 220.00,  // Lá3
    blue:   164.81,  // Mi3
  };

  /**
   * Som do pad: tom limpo correspondente à cor.
   * @param {string} color
   */
  function playTone(color) {
    playNote(PAD_FREQUENCIES[color] || 300, 0.25, "sine", 0.35, 0);
  }

  /**
   * Som de início do jogo: arpejo ascendente rápido (Mi3 → Lá3 → Dó4 → Mi4).
   */
  function playStartSound() {
    playNote(164.81, 0.15, "sine", 0.25, 0);      // Mi3
    playNote(220.00, 0.15, "sine", 0.25, 0.12);   // Lá3
    playNote(261.63, 0.15, "sine", 0.25, 0.24);   // Dó4
    playNote(329.63, 0.3,  "sine", 0.30, 0.36);   // Mi4 (sustain)
  }

  /**
   * Som de sequência correta: dois bips agudos rápidos.
   */
  function playCorrectSound() {
    playNote(523.25, 0.12, "sine", 0.25, 0);      // Dó5
    playNote(659.26, 0.20, "sine", 0.30, 0.10);   // Mi5
  }

  /**
   * Som de erro: tom grave distorcido (buzz).
   */
  function playErrorSound() {
    playNote(110, 0.5, "sawtooth", 0.30, 0);       // Lá2 — serra = som áspero
    playNote(82.41, 0.5, "square", 0.15, 0);       // Mi2 — quadrada = buzz
  }

  /**
   * Som de game over: melodia descendente triste (Mi4 → Dó4 → Lá3 → Mi3).
   */
  function playGameOverSound() {
    playNote(329.63, 0.25, "triangle", 0.25, 0);     // Mi4
    playNote(261.63, 0.25, "triangle", 0.22, 0.25);  // Dó4
    playNote(220.00, 0.25, "triangle", 0.20, 0.50);  // Lá3
    playNote(164.81, 0.50, "triangle", 0.18, 0.75);  // Mi3 (sustain)
  }

  // ========================================================================
  // ATUALIZAÇÃO DA UI
  // ========================================================================

  /**
   * Atualiza o display do cronômetro com o tempo atual.
   */
  function updateTimerDisplay() {
    if (ui.timerDisplay) {
      ui.timerDisplay.textContent = formatTime(gameState.timerSeconds);
    }
  }

  /**
   * Atualiza o display de pontuação com o score atual.
   */
  function updateScoreDisplay() {
    if (ui.scoreDisplay) {
      ui.scoreDisplay.textContent = gameState.score;
    }
    // Atualiza também o score no centro do tabuleiro
    var centerScore = document.getElementById("simon-center-score");
    if (centerScore) {
      centerScore.textContent = gameState.score;
    }
  }

  /**
   * Atualiza o display do número da tentativa atual.
   */
  function updateAttemptDisplay() {
    if (ui.attemptDisplay) {
      ui.attemptDisplay.textContent =
        gameState.attemptNumber + " / " + MAX_ATTEMPTS;
    }
  }

  /**
   * Define uma mensagem de status para o jogador.
   * Adiciona classe CSS para estilização diferenciada conforme o tipo.
   *
   * @param {string} message — Texto da mensagem
   * @param {string} type    — Tipo: 'info', 'watching', 'your-turn', 'correct', 'game-over'
   */
  function setStatusMessage(message, type) {
    if (ui.statusMessage) {
      ui.statusMessage.textContent = message;
      ui.statusMessage.className = "simon-status-message simon-status-" + type;
    }
  }

  /**
   * Mostra o modal de game over com pontuação e tempo final.
   *
   * @param {number} score — Pontuação final
   * @param {string} time  — Tempo formatado (MM:SS)
   */
  function showGameOverModal(score, time) {
    if (ui.gameOverModal) {
      ui.gameOverModal.classList.add("simon-modal-visible");
    }
    if (ui.finalScore) {
      ui.finalScore.textContent = score;
    }
    if (ui.finalTime) {
      ui.finalTime.textContent = time;
    }

    // Verifica se ainda pode jogar
    if (gameState.attemptsRemaining <= 0) {
      var playAgainBtn = document.getElementById("simon-play-again");
      if (playAgainBtn) {
        playAgainBtn.style.display = "none";
      }
      var goHomeBtn = document.getElementById("simon-go-home");
      if (goHomeBtn) {
        goHomeBtn.style.display = "inline-block";
      }
    }
  }

  /**
   * Esconde o modal de game over.
   */
  function hideGameOverModal() {
    if (ui.gameOverModal) {
      ui.gameOverModal.classList.remove("simon-modal-visible");
    }
    // Reseta badge de novo recorde
    if (ui.newRecordBadge) {
      ui.newRecordBadge.style.display = "none";
    }

    // Mostra botão de iniciar se ainda tem tentativas
    if (gameState.attemptsRemaining > 0 && ui.startBtn) {
      ui.startBtn.style.display = "inline-block";
    }
  }

  /**
   * Atualiza o display de tentativas restantes (na seção de info do jogador).
   */
  function updateAttemptsRemainingDisplay() {
    if (ui.attemptsRemainingDisplay) {
      ui.attemptsRemainingDisplay.textContent = gameState.attemptsRemaining;
    }
  }

  /**
   * Mostra o overlay de jogo bloqueado (tentativas esgotadas).
   */
  function showBlockedState() {
    if (ui.blockedOverlay) {
      ui.blockedOverlay.style.display = "flex";
    }
    if (ui.startBtn) {
      ui.startBtn.style.display = "none";
    }
  }

  /**
   * Atualiza a tabela de ranking com dados novos.
   *
   * @param {Array} rankingData — Array de objetos com dados do ranking
   */
  function updateRankingTable(rankingData) {
    if (!ui.rankingBody || !rankingData) return;

    // Limpa corpo da tabela
    ui.rankingBody.innerHTML = "";

    // Monta linhas do ranking
    rankingData.forEach(function (player, index) {
      var row = document.createElement("tr");

      // Medalha para top 3
      var positionText = (index + 1).toString();
      if (index === 0) positionText = "🥇";
      if (index === 1) positionText = "🥈";
      if (index === 2) positionText = "🥉";

      row.innerHTML =
        '<td class="simon-rank-pos">' +
        positionText +
        "</td>" +
        "<td>" +
        escapeHtml(player.nome || "") +
        "</td>" +
        "<td>" +
        escapeHtml(player.unidade || "") +
        "</td>" +
        '<td class="simon-rank-score">' +
        (player.pontuacao || 0) +
        "</td>" +
        "<td>" +
        escapeHtml(player.tempo || "00:00") +
        "</td>";

      ui.rankingBody.appendChild(row);
    });

    // Se ranking vazio, mostra mensagem
    if (rankingData.length === 0) {
      var emptyRow = document.createElement("tr");
      emptyRow.innerHTML =
        '<td colspan="5" style="text-align:center;opacity:.6;">Nenhuma pontuação registrada ainda</td>';
      ui.rankingBody.appendChild(emptyRow);
    }
  }

  /**
   * Escapa HTML para prevenir XSS ao inserir dados dinâmicos.
   *
   * @param {string} str — String a ser escapada
   * @returns {string} — String segura para inserção no DOM
   */
  function escapeHtml(str) {
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ========================================================================
  // COMUNICAÇÃO AJAX COM WORDPRESS
  // ========================================================================

  /**
   * Verifica o status atual do jogador via AJAX.
   * Recupera tentativas restantes e atualiza a UI conforme necessário.
   */
  function checkPlayerStatus() {
    // simonSaysData é injetado via wp_localize_script no PHP
    if (typeof simonSaysData === "undefined") {
      console.error("Simon Says: Dados do WordPress não disponíveis.");
      return;
    }

    var formData = new FormData();
    formData.append("action", "simon_get_status");
    formData.append("nonce", simonSaysData.nonce);

    fetch(simonSaysData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          var data = result.data;

          // Atualiza estado do jogo
          gameState.attemptsRemaining = parseInt(data.tentativas_restantes);
          gameState.attemptNumber = parseInt(data.tentativas_usadas);

          // Atualiza displays de tentativa
          updateAttemptDisplay();
          updateAttemptsRemainingDisplay();

          // Se não tem tentativas, bloqueia
          if (gameState.attemptsRemaining <= 0) {
            showBlockedState();
          }
        }
      })
      .catch(function (error) {
        console.error("Simon Says: Erro ao verificar status:", error);
      });

    // Carrega ranking inicial
    loadInitialRanking();
  }

  /**
   * Carrega o ranking inicial da página (dados embutidos pelo PHP).
   */
  function loadInitialRanking() {
    if (typeof simonSaysData !== "undefined" && simonSaysData.ranking) {
      updateRankingTable(simonSaysData.ranking);
    }
  }

  /**
   * Salva o resultado da partida via AJAX.
   * Cria log de partida e atualiza user meta no WordPress.
   *
   * @param {number} score — Pontuação obtida
   * @param {string} tempo — Tempo formatado (MM:SS)
   */
  function saveGameResult(score, tempo) {
    if (typeof simonSaysData === "undefined") {
      console.error("Simon Says: Dados do WordPress não disponíveis.");
      return;
    }

    var formData = new FormData();
    formData.append("action", "simon_save_game");
    formData.append("nonce", simonSaysData.nonce);
    formData.append("pontuacao", score);
    formData.append("tempo", tempo);

    fetch(simonSaysData.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (result.success) {
          var data = result.data;

          // Atualiza tentativas restantes
          gameState.attemptsRemaining = parseInt(data.tentativas_restantes);
          updateAttemptDisplay();
          updateAttemptsRemainingDisplay();

          // Mostra badge de novo recorde se aplicável
          if (data.novo_recorde && ui.newRecordBadge) {
            ui.newRecordBadge.style.display = "block";
          }

          // Atualiza tabela de ranking
          if (data.ranking) {
            updateRankingTable(data.ranking);
          }

          // Se esgotou tentativas
          if (gameState.attemptsRemaining <= 0) {
            showBlockedState();
          }
        } else {
          console.error("Simon Says: Erro ao salvar:", result.data.message);
        }
      })
      .catch(function (error) {
        console.error("Simon Says: Erro de rede ao salvar:", error);
      });
  }
})();
