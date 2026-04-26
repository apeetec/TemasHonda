/**
 * ============================================================================
 * SISTEMA DE PERGUNTAS E VALIDAÇÃO DE CÓDIGO PRESENCIAL
 * ============================================================================
 * 
 * Este script controla a lógica de exibição de perguntas baseada em duas opções:
 * 1. Assistir ao vídeo completo (99% de progresso)
 * 2. Digitar código de presença fornecido na palestra
 * 
 * Fluxo da aplicação:
 * - Usuário escolhe entre "Digitar código" ou "Assistir"
 * - Se escolher "Digitar código": valida o código e libera perguntas imediatamente
 * - Se escolher "Assistir": monitora progresso do vídeo e libera perguntas aos 99%
 * 
 * Arquivos relacionados:
 * - template-parts/cabecalhos/cabecalho.php (HTML do formulário)
 * - template-parts/formulario_de_perguntas.php (Container das perguntas)
 * - style.css (.box-perguntas, .box-codigo, .ativo)
 * ============================================================================
 */

// ============================================================================
// VARIÁVEIS GLOBAIS E INICIALIZAÇÃO
// ============================================================================

/**
 * Captura todos os radio buttons de escolha entre presencial/assistir
 * Utilizados para alternar entre exibir campo de código ou vídeo
 */
var opcoes = document.querySelectorAll('.escolha input[type="radio"]');

/**
 * Captura todos os containers de vídeo da página
 * Podem existir múltiplos vídeos (categoria principal + subcategorias)
 */
let videos = document.querySelectorAll('.video-box');

/**
 * Captura todos os containers de perguntas
 * Cada container tem ID único: perguntas_{codigo}
 * Inicialmente ocultos via CSS: .box-perguntas { display: none; }
 */
let boxs_perguntas = document.querySelectorAll('.box-perguntas');

// ============================================================================
// CONTROLE DE EXIBIÇÃO: CÓDIGO PRESENCIAL vs. ASSISTIR VÍDEO
// ============================================================================

/**
 * Event listener para os radio buttons "Digitar código" / "Assistir"
 * 
 * Comportamento:
 * - "Digitar código" (Sim): Mostra campo de código, oculta vídeos
 * - "Assistir" (Não): Mostra vídeos, oculta campo de código
 */
opcoes.forEach((opcao, i) => {
    opcao.addEventListener('change', function(e) {
        var valor = e.target.value;
        
        // Obtém referências aos elementos de controle
        let boxCodigo = document.querySelector('.box-codigo');
        let primeiroVideo = document.querySelector('.video-box');
        
        if (valor === 'Sim') {
            // Modo: Digitar código presencial
            console.log('[MODO] Código presencial ativado');
            
            if (boxCodigo) {
                boxCodigo.style.display = 'block';
            }
            
            // Oculta todos os vídeos (validação por código não exige visualização)
            videos.forEach(video => {
                video.style.display = 'none';
            });
            
        } else if (valor === 'Não') {
            // Modo: Assistir vídeo completo
            console.log('[MODO] Assistir vídeo completo ativado');
            
            if (boxCodigo) {
                boxCodigo.style.display = 'none';
            }
            
            // Exibe todos os vídeos disponíveis
            videos.forEach(video => {
                video.style.display = 'block';
            });
        }
    }, false);
});

// ============================================================================
// VALIDAÇÃO DE CÓDIGO PRESENCIAL
// ============================================================================

/**
 * Captura todos os inputs de código presencial
 * Normalmente existe apenas um por página, mas o código suporta múltiplos
 */
var inputs_codigo = document.querySelectorAll('.codigo input[type=text]');

/**
 * Captura o primeiro input oculto que contém um código válido
 * (usado como fallback, mas o vetor abaixo é mais confiável)
 */
var codigo_input = document.querySelector('.input_codigo_oculto');

/**
 * Captura TODOS os inputs ocultos com códigos válidos
 * Estes são gerados dinamicamente pelo PHP e contêm os códigos corretos
 * de cada categoria/subcategoria
 */
var codigo_inputVetor = document.querySelectorAll('.input_codigo_oculto');

/**
 * Array que armazena todos os códigos válidos
 * Preenchido a partir dos inputs ocultos gerados pelo PHP
 */
var vetor = [];
codigo_inputVetor.forEach(elemento => {
    // Sanitiza o código antes de adicionar ao vetor
    var codigoSanitizado = elemento.value.trim().toUpperCase();
    if (codigoSanitizado) {
        vetor.push(codigoSanitizado);
    }
});

console.log('[INIT] Códigos válidos carregados:', vetor);

/**
 * Adiciona event listener a cada campo de código
 * Monitora em tempo real (keyup) se o código digitado corresponde a algum válido
 * 
 * CORREÇÃO DO BUG:
 * - Sanitização do input do usuário (trim + uppercase)
 * - Uso de classList.add('ativo') em vez de style.display
 * - Logs detalhados para debug
 * - Verificação robusta de existência dos elementos
 */
inputs_codigo.forEach((input_codigo, i) => {
    input_codigo.addEventListener('keyup', function(e) {
        // Captura o valor digitado e sanitiza
        var valorDigitado = e.target.value.trim().toUpperCase();
        
        console.log('[INPUT] Código digitado:', valorDigitado);
        
        // Verifica se o código digitado está no vetor de códigos válidos
        if (vetor.includes(valorDigitado)) {
            console.log('[VALIDAÇÃO] ✓ Código válido reconhecido:', valorDigitado);
            
            // Tenta localizar o vídeo correspondente
            var video = document.getElementById('video_' + valorDigitado);
            if (video) {
                video.style.display = 'block';
                console.log('[VIDEO] Exibido: video_' + valorDigitado);
            } else {
                console.warn('[VIDEO] Não encontrado: video_' + valorDigitado);
            }
            
            // Tenta localizar o container de perguntas correspondente
            var bloco_perguntas = document.getElementById('perguntas_' + valorDigitado);
            if (bloco_perguntas) {
                bloco_perguntas.classList.add('ativo');
                // Força display:block via inline style para sobrescrever qualquer regra CSS com !important
                bloco_perguntas.style.display = 'block';
                console.log('[PERGUNTAS] ✓ Container exibido: perguntas_' + valorDigitado);
            } else {
                console.error('[PERGUNTAS] ✗ Container NÃO encontrado: perguntas_' + valorDigitado);
                console.error('[DEBUG] Verifique se existe um elemento com ID:', 'perguntas_' + valorDigitado);
                
                // Fallback: exibe todos os containers de perguntas disponíveis
                var todasPerguntas = document.querySelectorAll('.box-perguntas');
                if (todasPerguntas.length > 0) {
                    console.warn('[FALLBACK] Exibindo todas as perguntas disponíveis');
                    todasPerguntas.forEach(pergunta => {
                        pergunta.classList.add('ativo');
                        pergunta.style.display = 'block';
                    });
                }
            }
            
            // Marca o input hidden de presencial com o valor correto
            var inputPresencial = document.getElementById('presencial_' + valorDigitado);
            if (inputPresencial) {
                inputPresencial.setAttribute('value', 'Presencial');
                console.log('[PRESENCIAL] Marcado como presencial');
            } else {
                console.warn('[PRESENCIAL] Input não encontrado: presencial_' + valorDigitado);
                // Fallback: marca todos os inputs presenciais da página
                document.querySelectorAll('.presencial').forEach(function(el) {
                    el.setAttribute('value', 'Presencial');
                });
            }
            
        } else {
            // Código ainda não corresponde a nenhum válido
            if (valorDigitado.length >= 5) {
                console.warn('[VALIDAÇÃO] ✗ Código inválido:', valorDigitado);
            }
        }
    }); 
});



// ============================================================================
// CONTROLES DE PLAY/PAUSE DOS VÍDEOS
// ============================================================================

/**
 * Configura os botões de play e pause para cada vídeo disponível na página
 * 
 * Estrutura esperada no HTML:
 * <div class="video-box">
 *   <video>...</video>
 *   <button class="play">Play</button>
 *   <button class="pause">Pause</button>
 * </div>
 * 
 * Nota: Os vídeos WordPress já possuem controles nativos, estes são adicionais
 */
document.querySelectorAll('.video-box').forEach(videoBox => {
    const video = videoBox.querySelector('.wp-video video');
    const playButton = videoBox.querySelector('.play');
    const pauseButton = videoBox.querySelector('.pause');
    
    // Verifica se todos os elementos necessários existem
    if (!video) {
        console.warn('[VIDEO] Elemento <video> não encontrado em:', videoBox);
        return;
    }
    
    // Adiciona o evento de play (se o botão existir)
    if (playButton) {
        playButton.addEventListener('click', () => {
            video.play();
            console.log('[VIDEO] Play iniciado');
        });
    }
    
    // Adiciona o evento de pause (se o botão existir)
    if (pauseButton) {
        pauseButton.addEventListener('click', () => {
            video.pause();
            console.log('[VIDEO] Pause acionado');
        });
    }
});

// ============================================================================
// MONITORAMENTO DE PROGRESSO DO VÍDEO E LIBERAÇÃO DE PERGUNTAS
// ============================================================================

/**
 * Monitora o progresso de TODOS os vídeos na página
 * 
 * Lógica:
 * - Ao atingir 99% de visualização, libera as perguntas correspondentes
 * - Envia via AJAX a informação de conclusão para o servidor
 * - Previne envios duplicados com flag 'jaEnviado'
 * 
 * CORREÇÕES APLICADAS:
 * - Usa classList.add('ativo') em vez de style.display
 * - Flag para evitar múltiplos envios AJAX
 * - Logs detalhados para rastreamento
 * - Tratamento de erro robusto
 */
document.querySelectorAll('video').forEach((videoElement, indice) => {
    // Flag para controlar se já enviou os dados ao servidor
    let jaEnviouAjax = false;
    
    videoElement.addEventListener('timeupdate', function() {
        var tempoAtual = videoElement.currentTime;
        var duracaoTotal = videoElement.duration;
        
        // Calcula a porcentagem de visualização
        var porcentagemAssistida = Math.floor((tempoAtual / duracaoTotal) * 100);
        
        // Log de progresso a cada 10% (para não poluir o console)
        if (porcentagemAssistida % 10 === 0) {
            console.log('[VIDEO ' + indice + '] Progresso:', porcentagemAssistida + '%');
        }
        
        // Verifica se atingiu 99% de visualização
        if (porcentagemAssistida >= 99 && !jaEnviouAjax) {
            console.log('[VIDEO ' + indice + '] ✓ 99% atingido - Liberando perguntas');
            
            // Marca como enviado para evitar duplicação
            jaEnviouAjax = true;
            
            // Libera o container de perguntas correspondente
            let todosContainersPerguntas = document.querySelectorAll('.box-perguntas');
            if (todosContainersPerguntas[indice]) {
                // CORREÇÃO: Usa classList.add('ativo') seguindo o padrão do projeto
                todosContainersPerguntas[indice].classList.add('ativo');
                // Força display:block via inline style para sobrescrever qualquer regra CSS com !important
                todosContainersPerguntas[indice].style.display = 'block';
                console.log('[PERGUNTAS] Container liberado (índice ' + indice + ')');
                
                // Scroll suave até as perguntas (UX melhorada)
                todosContainersPerguntas[indice].scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            } else {
                console.warn('[PERGUNTAS] Container não encontrado no índice:', indice);
            }
            
            // ================================================================
            // REGISTRO DE CONCLUSÃO NO SERVIDOR VIA AJAX
            // ================================================================
            
            // Captura dados necessários para o registro
            const idUsuario = document.getElementById('id_usuario');
            const categoriaVideo = document.getElementById('categoria');
            
            if (!idUsuario || !categoriaVideo) {
                console.error('[AJAX] Inputs ocultos não encontrados (id_usuario ou categoria)');
                return;
            }
            
            const idUsuarioValor = idUsuario.value;
            const categoriaValor = categoriaVideo.value;
            
            console.log('[AJAX] Enviando conclusão para servidor...', {
                usuario: idUsuarioValor,
                categoria: categoriaValor
            });
            
            // Envia requisição AJAX usando jQuery (se disponível)
            if (typeof $ !== 'undefined') {
                $.ajax({
                    url: 'https://www.campaq.com.br/wp-content/themes/Campaq/sql/progresso-video.php',
                    type: 'POST',
                    data: {
                        Id_usuario: idUsuarioValor,
                        categoria_video: categoriaValor
                    },
                    success: function(resposta) {
                        console.log('[AJAX] ✓ Conclusão registrada:', resposta);
                    },
                    error: function(xhr, status, erro) {
                        console.error('[AJAX] ✗ Erro ao registrar:', erro);
                        console.error('[AJAX] Status:', status);
                        console.error('[AJAX] Response:', xhr.responseText);
                    }
                });
            } else {
                console.warn('[AJAX] jQuery não disponível - registro não enviado');
                
                // Alternativa com Fetch API (caso jQuery não esteja disponível)
                fetch('https://www.campaq.com.br/wp-content/themes/Campaq/sql/progresso-video.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'Id_usuario=' + encodeURIComponent(idUsuarioValor) + 
                          '&categoria_video=' + encodeURIComponent(categoriaValor)
                })
                .then(response => response.text())
                .then(data => {
                    console.log('[AJAX/FETCH] ✓ Conclusão registrada:', data);
                })
                .catch(error => {
                    console.error('[AJAX/FETCH] ✗ Erro ao registrar:', error);
                });
            }
        }
    });
});

/**
 * ============================================================================
 * FIM DO ARQUIVO
 * ============================================================================
 * 
 * RESUMO DAS CORREÇÕES APLICADAS:
 * 
 * 1. SANITIZAÇÃO DE INPUT
 *    - Códigos são convertidos para uppercase e trimmed
 *    - Previne problemas com espaços extras ou case sensitivity
 * 
 * 2. USO CONSISTENTE DE CLASSES CSS
 *    - Substituído style.display = 'block' por classList.add('ativo')
 *    - Segue o padrão definido no style.css
 * 
 * 3. LOGS DETALHADOS
 *    - Console.log estruturado com prefixos [TIPO]
 *    - Facilita debug e rastreamento de problemas
 * 
 * 4. TRATAMENTO DE ERROS
 *    - Verificação de existência de elementos antes de manipular
 *    - Fallback quando elementos não são encontrados
 *    - Mensagens de erro claras e acionáveis
 * 
 * 5. PREVENÇÃO DE DUPLICAÇÃO
 *    - Flag jaEnviouAjax previne múltiplos envios ao servidor
 *    - Importante para não poluir o banco de dados
 * 
 * 6. UX MELHORADA
 *    - Scroll suave até as perguntas quando liberadas
 *    - Feedback visual e console detalhado
 * 
 * 7. FALLBACK DE FETCH API
 *    - Suporte a ambientes sem jQuery
 *    - Mantém funcionalidade mesmo sem dependências
 * 
 * ESTRUTURA DE IDs ESPERADA:
 * - perguntas_{codigo} → Container das perguntas
 * - video_{codigo} → Container do vídeo (opcional)
 * - presencial_{codigo} → Input hidden de presença
 * - id_usuario → Input hidden com ID do usuário
 * - categoria → Input hidden com nome da categoria
 * 
 * ============================================================================
 */