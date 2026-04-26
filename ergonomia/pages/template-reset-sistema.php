<?php
/**
 * ============================================================================
 * Template: Reset do Sistema
 * ============================================================================
 *
 * Página administrativa para resetar dados CMB2 dos usuários.
 * Processa em lotes com barra de progresso em tempo real.
 *
 * ACESSO: Somente administradores.
 *
 * Arquivo: pages/template-reset-sistema.php
 * ============================================================================
 */

/* Template Name: Reset do Sistema */

get_header();

if (!is_user_logged_in()) {
    wp_redirect(home_url('login'));
    exit;
}

if (!current_user_can('administrator')) {
    echo '<section class="container"><h2>Acesso negado</h2><p>Você não tem permissão para acessar esta página.</p></section>';
    get_footer();
    exit;
}

$reset_nonce = wp_create_nonce('reset_sistema_nonce');
?>

<style>
    .reset-page { max-width: 900px; margin: 40px auto; padding: 0 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .reset-page h1 { font-size: 28px; margin-bottom: 10px; color: #333; }
    .reset-page .subtitle { color: #666; margin-bottom: 30px; font-size: 15px; }

    .reset-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .reset-card h3 { margin: 0 0 8px; font-size: 18px; color: #333; }
    .reset-card p { margin: 0 0 16px; color: #666; font-size: 14px; line-height: 1.5; }
    .reset-card .meta-list { background: #f9f9f9; border-radius: 4px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; color: #555; }
    .reset-card .meta-list code { background: #eee; padding: 2px 6px; border-radius: 3px; font-size: 12px; }

    .btn-reset { display: inline-block; padding: 10px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-reset:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-danger { background: #e74c3c; color: #fff; }
    .btn-danger:hover:not(:disabled) { background: #c0392b; }
    .btn-warning { background: #e67e22; color: #fff; }
    .btn-warning:hover:not(:disabled) { background: #d35400; }
    .btn-critical { background: #8e44ad; color: #fff; }
    .btn-critical:hover:not(:disabled) { background: #6c3483; }

    /* Barra de progresso */
    .progress-wrapper { margin-top: 16px; display: none; }
    .progress-wrapper.active { display: block; }
    .progress-bar-outer { width: 100%; height: 28px; background: #e9ecef; border-radius: 14px; overflow: hidden; position: relative; }
    .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #27ae60, #2ecc71); border-radius: 14px; transition: width 0.3s ease; width: 0%; display: flex; align-items: center; justify-content: center; min-width: 40px; }
    .progress-bar-inner span { font-size: 12px; font-weight: 700; color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.2); }
    .progress-status { margin-top: 8px; font-size: 13px; color: #555; }
    .progress-status .fase { font-weight: 600; color: #2c3e50; }
    .progress-status .detalhe { color: #888; font-size: 12px; }
    .progress-status .tempo { float: right; color: #999; font-size: 12px; }
    .progress-log { margin-top: 10px; max-height: 120px; overflow-y: auto; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 8px 12px; font-size: 12px; font-family: monospace; color: #555; }
    .progress-log div { padding: 2px 0; border-bottom: 1px solid #f0f0f0; }
    .progress-log div:last-child { border-bottom: none; }

    .reset-result { margin-top: 12px; padding: 10px 14px; border-radius: 4px; font-size: 13px; display: none; }
    .reset-result.success { display: block; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .reset-result.error { display: block; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .reset-warning-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 16px 20px; margin-bottom: 30px; }
    .reset-warning-box strong { color: #856404; }
    .reset-warning-box p { margin: 4px 0; color: #856404; font-size: 14px; }

    .confirm-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
    .confirm-overlay.active { display: flex; }
    .confirm-box { background: #fff; border-radius: 10px; padding: 30px; max-width: 450px; width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .confirm-box h3 { margin: 0 0 10px; color: #e74c3c; }
    .confirm-box p { color: #555; margin-bottom: 20px; font-size: 14px; }
    .confirm-box input[type="text"] { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; text-align: center; margin-bottom: 16px; box-sizing: border-box; }
    .confirm-box .btn-group { display: flex; gap: 10px; justify-content: center; }
    .confirm-box .btn-cancel { background: #95a5a6; color: #fff; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .confirm-box .btn-confirm { background: #e74c3c; color: #fff; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .confirm-box .btn-confirm:disabled { opacity: 0.5; cursor: not-allowed; }
</style>

<section class="reset-page">
    <h1>&#9888; Reset do Sistema</h1>
    <p class="subtitle">Painel administrativo para limpar dados dos usuários. Todas as ações são irreversíveis.</p>

    <div class="reset-warning-box">
        <strong>&#9888; ATENÇÃO:</strong>
        <p>Todas as operações abaixo são <strong>irreversíveis</strong>. Os dados apagados não poderão ser recuperados. Certifique-se de ter um backup antes de prosseguir.</p>
    </div>

    <!-- 1. PONTUAÇÕES -->
    <div class="reset-card">
        <h3>1. Resetar Pontuações e Dados de Jogo</h3>
        <p>Remove pontuações, tempos de partida, tentativas, horários, IPs e datas de todos os usuários (subscribers).</p>
        <div class="meta-list">
            Campos: <code>pontuacao_obtida</code> <code>tempo_partida</code> <code>horario</code>
            <code>numero_tentativas</code> <code>data_de_partida</code> <code>ip_usuario</code>
        </div>
        <button class="btn-reset btn-danger" data-action="pontuacao">Resetar Pontuações</button>
        <div class="progress-wrapper" id="progress-pontuacao">
            <div class="progress-bar-outer"><div class="progress-bar-inner"><span>0%</span></div></div>
            <div class="progress-status"></div>
            <div class="progress-log"></div>
        </div>
        <div class="reset-result" id="result-pontuacao"></div>
    </div>

    <!-- 2. SIMON SAYS META -->
    <div class="reset-card">
        <h3>2. Resetar Simon Says (User Meta)</h3>
        <p>Remove dados do jogo Simon Says de todos os usuários: ranking, tentativas diárias e data da última tentativa.</p>
        <div class="meta-list">
            Campos: <code>simon_posicao_ranking</code> <code>simon_tentativas_restantes_hoje</code>
            <code>simon_data_ultima_tentativa</code>
        </div>
        <button class="btn-reset btn-danger" data-action="simon_meta">Resetar Simon Says</button>
        <div class="progress-wrapper" id="progress-simon_meta">
            <div class="progress-bar-outer"><div class="progress-bar-inner"><span>0%</span></div></div>
            <div class="progress-status"></div>
            <div class="progress-log"></div>
        </div>
        <div class="reset-result" id="result-simon_meta"></div>
    </div>

    <!-- 3. LOGS SIMON SAYS -->
    <div class="reset-card">
        <h3>3. Deletar Logs do Simon Says</h3>
        <p>Remove permanentemente todos os posts do tipo <strong>simon_log</strong> (registros de partidas).</p>
        <button class="btn-reset btn-warning" data-action="simon_logs">Deletar Logs</button>
        <div class="progress-wrapper" id="progress-simon_logs">
            <div class="progress-bar-outer"><div class="progress-bar-inner"><span>0%</span></div></div>
            <div class="progress-status"></div>
            <div class="progress-log"></div>
        </div>
        <div class="reset-result" id="result-simon_logs"></div>
    </div>

    <!-- 4. PERGUNTAS -->
    <div class="reset-card">
        <h3>4. Resetar Respostas de Perguntas</h3>
        <p>Remove todas as respostas dos usuários para as perguntas, incluindo flags de presencial, vídeo concluído, acesso e classificação para todas as datas.</p>
        <div class="meta-list">
            Campos dinâmicos por data: <code>user_field_{data}_{id}</code> <code>presencial_{data}</code>
            <code>video_concluido_{data}</code> <code>todas_alternativa_{data}</code>
            <code>acertou_todas_alternativas_{data}</code> <code>acessou_{data}</code>
            <code>horario_da_data_de_{data}</code> <code>classificado_{data}</code>
        </div>
        <button class="btn-reset btn-warning" data-action="perguntas">Resetar Respostas</button>
        <div class="progress-wrapper" id="progress-perguntas">
            <div class="progress-bar-outer"><div class="progress-bar-inner"><span>0%</span></div></div>
            <div class="progress-status"></div>
            <div class="progress-log"></div>
        </div>
        <div class="reset-result" id="result-perguntas"></div>
    </div>

    <!-- 5. TUDO -->
    <div class="reset-card" style="border-color: #e74c3c;">
        <h3 style="color: #e74c3c;">5. RESETAR TUDO</h3>
        <p>Executa <strong>todas</strong> as operações acima de uma vez: pontuações, Simon Says, logs e respostas de perguntas.</p>
        <button class="btn-reset btn-critical" data-action="tudo">RESETAR TUDO</button>
        <div class="progress-wrapper" id="progress-tudo">
            <div class="progress-bar-outer"><div class="progress-bar-inner"><span>0%</span></div></div>
            <div class="progress-status"></div>
            <div class="progress-log"></div>
        </div>
        <div class="reset-result" id="result-tudo"></div>
    </div>
</section>

<!-- MODAL DE CONFIRMAÇÃO -->
<div class="confirm-overlay" id="confirm-overlay">
    <div class="confirm-box">
        <h3 id="confirm-title">Confirmar Reset</h3>
        <p id="confirm-desc">Esta ação é irreversível. Digite <strong>CONFIRMAR</strong> para prosseguir.</p>
        <input type="text" id="confirm-input" placeholder="Digite CONFIRMAR" autocomplete="off">
        <div class="btn-group">
            <button class="btn-cancel" id="btn-cancel">Cancelar</button>
            <button class="btn-confirm" id="confirm-btn" disabled>Confirmar Reset</button>
        </div>
    </div>
</div>

<script>
(function() {
    var ajaxUrl = '<?php echo esc_url(admin_url("admin-ajax.php")); ?>';
    var nonce = '<?php echo esc_js($reset_nonce); ?>';
    var currentAction = '';
    var isProcessing = false;

    // Nomes amigáveis para as fases
    var faseLabels = {
        'pontuacao': 'Pontuações e Dados de Jogo',
        'simon_meta': 'Simon Says (Meta)',
        'simon_logs': 'Logs do Simon Says',
        'perguntas': 'Respostas de Perguntas'
    };

    // Fases por tipo de reset
    var fasesPorTipo = {
        'pontuacao': ['pontuacao'],
        'simon_meta': ['simon_meta'],
        'simon_logs': ['simon_logs'],
        'perguntas': ['perguntas'],
        'tudo': ['pontuacao', 'simon_meta', 'simon_logs', 'perguntas']
    };

    // Bind botões
    var buttons = document.querySelectorAll('.btn-reset');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function() {
            var action = this.getAttribute('data-action');
            openConfirm(action);
        });
    }

    function openConfirm(action) {
        currentAction = action;
        var label = faseLabels[action] || 'TUDO';
        document.getElementById('confirm-title').textContent = 'Resetar: ' + label;
        document.getElementById('confirm-input').value = '';
        document.getElementById('confirm-btn').disabled = true;
        document.getElementById('confirm-overlay').classList.add('active');
        document.getElementById('confirm-input').focus();
    }

    function closeConfirm() {
        document.getElementById('confirm-overlay').classList.remove('active');
    }

    document.getElementById('btn-cancel').addEventListener('click', closeConfirm);

    document.getElementById('confirm-overlay').addEventListener('click', function(e) {
        if (e.target === this) closeConfirm();
    });

    document.getElementById('confirm-input').addEventListener('input', function() {
        document.getElementById('confirm-btn').disabled = (this.value.trim() !== 'CONFIRMAR');
    });

    document.getElementById('confirm-btn').addEventListener('click', function() {
        if (isProcessing) return;
        var action = currentAction;
        closeConfirm();
        startReset(action);
    });

    // ================================================================
    // Motor principal: inicia o reset com barra de progresso
    // ================================================================
    function startReset(action) {
        isProcessing = true;
        disableButtons(true);

        var progressEl = document.getElementById('progress-' + action);
        var resultEl = document.getElementById('result-' + action);
        var barInner = progressEl.querySelector('.progress-bar-inner');
        var barSpan = barInner.querySelector('span');
        var statusEl = progressEl.querySelector('.progress-status');
        var logEl = progressEl.querySelector('.progress-log');

        // Reset UI
        resultEl.className = 'reset-result';
        resultEl.innerHTML = '';
        logEl.innerHTML = '';
        barInner.style.width = '0%';
        barSpan.textContent = '0%';
        statusEl.innerHTML = '<span class="fase">Iniciando...</span>';
        progressEl.classList.add('active');

        var startTime = Date.now();

        // Fase 1: pede info de contagens
        ajaxPost('reset_sistema_info', { tipo_reset: action }, function(data) {
            if (!data.success) {
                showError(resultEl, progressEl, data.data ? data.data.message : 'Erro ao obter informações.');
                return;
            }

            var info = data.data;
            var totalSteps = info.total_steps;
            var fases = fasesPorTipo[action];
            var completedSteps = 0;
            var logMessages = [];

            statusEl.innerHTML = '<span class="fase">Preparando...</span> ' +
                info.total_users + ' usuários encontrados' +
                (info.total_logs > 0 ? ', ' + info.total_logs + ' logs' : '') +
                '<span class="tempo"></span>';

            addLog(logEl, logMessages, 'Iniciado: ' + fases.length + ' fase(s), ' + totalSteps + ' lote(s) estimado(s)');

            // Fase 2: processa fases sequencialmente
            processFases(fases, 0, action, totalSteps, completedSteps, logMessages, barInner, barSpan, statusEl, logEl, resultEl, progressEl, startTime);

        }, function(err) {
            showError(resultEl, progressEl, 'Erro de conexão: ' + err);
        });
    }

    // Processa fases uma a uma
    function processFases(fases, faseIndex, action, totalSteps, completedSteps, logMessages, barInner, barSpan, statusEl, logEl, resultEl, progressEl, startTime) {
        if (faseIndex >= fases.length) {
            // Tudo concluído
            finishReset(barInner, barSpan, statusEl, resultEl, progressEl, logMessages, logEl, startTime, completedSteps);
            return;
        }

        var fase = fases[faseIndex];
        var label = faseLabels[fase];
        addLog(logEl, logMessages, 'Fase ' + (faseIndex + 1) + '/' + fases.length + ': ' + label);
        statusEl.innerHTML = '<span class="fase">' + label + '</span> <span class="detalhe">preparando...</span>' + tempoDecorrido(startTime);

        processBatch(fase, action, 0, totalSteps, completedSteps, logMessages, barInner, barSpan, statusEl, logEl, resultEl, progressEl, startTime, function(newCompleted) {
            completedSteps = newCompleted;
            processFases(fases, faseIndex + 1, action, totalSteps, completedSteps, logMessages, barInner, barSpan, statusEl, logEl, resultEl, progressEl, startTime);
        });
    }

    // Processa lotes de uma fase
    function processBatch(fase, action, offset, totalSteps, completedSteps, logMessages, barInner, barSpan, statusEl, logEl, resultEl, progressEl, startTime, onFaseDone) {
        var label = faseLabels[fase];
        statusEl.innerHTML = '<span class="fase">' + label + '</span> <span class="detalhe">lote a partir de #' + offset + '...</span>' + tempoDecorrido(startTime);

        ajaxPost('reset_sistema_batch', {
            tipo_reset: action,
            fase: fase,
            offset: offset
        }, function(data) {
            if (!data.success) {
                showError(resultEl, progressEl, data.data ? data.data.message : 'Erro no lote.');
                return;
            }

            var result = data.data;
            completedSteps++;

            // Atualiza barra
            var pct = Math.min(100, Math.round((completedSteps / totalSteps) * 100));
            barInner.style.width = pct + '%';
            barSpan.textContent = pct + '%';

            addLog(logEl, logMessages, result.detail);
            statusEl.innerHTML = '<span class="fase">' + label + '</span> <span class="detalhe">' + result.processed + ' processados neste lote</span>' + tempoDecorrido(startTime);

            if (result.done) {
                addLog(logEl, logMessages, label + ' concluído.');
                onFaseDone(completedSteps);
            } else {
                // Próximo lote (offset só incrementa para fases que usam offset real; simon_logs sempre usa 0)
                var nextOffset = (fase === 'simon_logs') ? 0 : offset + result.processed;
                processBatch(fase, action, nextOffset, totalSteps, completedSteps, logMessages, barInner, barSpan, statusEl, logEl, resultEl, progressEl, startTime, onFaseDone);
            }

        }, function(err) {
            showError(resultEl, progressEl, 'Erro de conexão no lote: ' + err);
        });
    }

    // Finaliza
    function finishReset(barInner, barSpan, statusEl, resultEl, progressEl, logMessages, logEl, startTime, totalProcessed) {
        barInner.style.width = '100%';
        barSpan.textContent = '100%';
        barInner.style.background = 'linear-gradient(90deg, #27ae60, #2ecc71)';

        var elapsed = formatTime(Date.now() - startTime);
        statusEl.innerHTML = '<span class="fase" style="color:#27ae60;">Concluído!</span> <span class="tempo">' + elapsed + '</span>';
        addLog(logEl, logMessages, 'Finalizado em ' + elapsed + ' (' + totalProcessed + ' lotes processados)');

        resultEl.className = 'reset-result success';
        resultEl.innerHTML = '&#10004; Reset concluído com sucesso em <strong>' + elapsed + '</strong>. Veja o log acima para detalhes.';

        isProcessing = false;
        disableButtons(false);
    }

    // Helpers
    function showError(resultEl, progressEl, msg) {
        resultEl.className = 'reset-result error';
        resultEl.textContent = msg;
        isProcessing = false;
        disableButtons(false);
    }

    function disableButtons(state) {
        var btns = document.querySelectorAll('.btn-reset');
        for (var i = 0; i < btns.length; i++) btns[i].disabled = state;
    }

    function addLog(logEl, logMessages, msg) {
        var time = new Date().toLocaleTimeString();
        logMessages.push('[' + time + '] ' + msg);
        var div = document.createElement('div');
        div.textContent = '[' + time + '] ' + msg;
        logEl.appendChild(div);
        logEl.scrollTop = logEl.scrollHeight;
    }

    function tempoDecorrido(startTime) {
        return '<span class="tempo">' + formatTime(Date.now() - startTime) + '</span>';
    }

    function formatTime(ms) {
        var s = Math.floor(ms / 1000);
        var m = Math.floor(s / 60);
        s = s % 60;
        if (m > 0) return m + 'min ' + s + 's';
        return s + 's';
    }

    function ajaxPost(action, extraData, onSuccess, onError) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', nonce);
        for (var key in extraData) {
            if (extraData.hasOwnProperty(key)) {
                formData.append(key, extraData[key]);
            }
        }
        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(onSuccess)
        .catch(function(err) { onError(err.message); });
    }

})();
</script>

<?php get_footer(); ?>
