(function() {
    'use strict';

    // Mapeamento de sub-opções por função
    var subOpcoes = {
        producao: {
            operacional:    'OPERACIONAL',
            especializados: 'ESPECIALIZADOS',
            lideres:        'LÍDERES'
        },
        manutencao: {
            operacional:    'OPERACIONAL',
            especializados: 'ESPECIALIZADOS',
            lideres:        'LÍDERES'
        },
        qualidade: {
            especializados:  'ESPECIALIZADOS',
            lideres:         'LÍDERES'
        },
        grupo_tecnico: {
            especializados:  'ESPECIALIZADOS',
            lideres:         'LÍDERES',
            chefe_analistas: 'CHEFE / ANALISTAS'
        }
    };

    // Mapeamento de meses
    var mesesLabels = {
        janeiro: 'Janeiro', fevereiro: 'Fevereiro', marco: 'Março', abril: 'Abril',
        maio: 'Maio', junho: 'Junho', julho: 'Julho', agosto: 'Agosto',
        setembro: 'Setembro', outubro: 'Outubro', novembro: 'Novembro', dezembro: 'Dezembro'
    };

    // Elementos
    var selectFuncao        = document.getElementById('funcao_colaborador');
    var subFuncaoBox        = document.getElementById('sub-funcao-box');
    var subFuncaoOpcoes     = document.getElementById('sub-funcao-opcoes');
    var selectDepartamento  = document.getElementById('departamento_colaborador');
    var moduloBox           = document.getElementById('modulo-treinamento-box');
    var selectTreinamento   = document.getElementById('modulo_treinamento');
    var mesBox              = document.getElementById('mes-treinamento-box');
    var selectMes           = document.getElementById('mes_treinamento');
    var infoBox             = document.getElementById('treinamento-info-box');
    var vagasEl             = document.getElementById('treinamento-vagas');
    var formInscricao       = document.getElementById('form-inscricao-treinamento');
    var btnCadastros        = document.getElementById('btn-meus-cadastros');
    var modalOverlay        = document.getElementById('modal-meus-cadastros');
    var fecharModal         = document.getElementById('fechar-modal');
    var msgEl               = document.getElementById('inscricao-mensagem');
    var painelSucesso       = document.getElementById('painel-sucesso');
    var formWrapper         = document.querySelector('.inscricao-form-wrapper');
    var btnCadastrosSucesso = document.getElementById('btn-meus-cadastros-sucesso');

    // ----- FILTRAR TREINAMENTOS com base em TODAS as opções marcadas -----
    function filtrarTreinamentos() {
        var funcao = selectFuncao.value;
        var nivelRadio = document.querySelector('input[name="nivel_funcao"]:checked');
        var nivel = nivelRadio ? nivelRadio.value : '';
        var depto = selectDepartamento.value;

        // Reset treinamento, mês e info
        selectTreinamento.innerHTML = '<option value="" disabled selected>Selecione o treinamento</option>';
        mesBox.style.display = 'none';
        selectMes.innerHTML = '';
        infoBox.style.display = 'none';

        // Só mostra treinamentos quando TODAS as 3 opções estiverem preenchidas
        if (!funcao || !nivel || !depto) {
            moduloBox.style.display = 'none';
            return;
        }

        var encontrou = false;
        for (var id in treinamentosData) {
            if (!treinamentosData.hasOwnProperty(id)) continue;
            var t = treinamentosData[id];
            if (t.vagas <= 0) continue;

            // Pular treinamentos em que o usuário já está inscrito
            if (inscricoesUsuario.indexOf(parseInt(id)) !== -1) continue;

            // Verificar departamento
            if (!t.departamentos || t.departamentos.indexOf(depto) === -1) continue;

            // Verificar se o treinamento tem a função+nível selecionada
            var funcaoData = t[funcao]; // ex: t.producao, t.manutencao, t.qualidade
            if (!funcaoData || funcaoData.indexOf(nivel) === -1) continue;

            // Passou em TODOS os filtros
            var opt = document.createElement('option');
            opt.value = id;
            opt.textContent = t.titulo;
            selectTreinamento.appendChild(opt);
            encontrou = true;
        }

        if (encontrou) {
            moduloBox.style.display = 'block';
        } else {
            moduloBox.style.display = 'block';
            selectTreinamento.innerHTML = '<option value="" disabled selected>Nenhum treinamento disponível para os critérios selecionados</option>';
        }
    }

    // ----- FUNÇÃO: mostrar sub-opções -----
    selectFuncao.addEventListener('change', function() {
        var funcao = this.value;
        subFuncaoOpcoes.innerHTML = '';

        if (funcao && subOpcoes[funcao]) {
            var opcoes = subOpcoes[funcao];
            for (var key in opcoes) {
                if (opcoes.hasOwnProperty(key)) {
                    var label = document.createElement('label');
                    var radio = document.createElement('input');
                    radio.type  = 'radio';
                    radio.name  = 'nivel_funcao';
                    radio.value = key;

                    // Evento para highlight visual + filtrar
                    radio.addEventListener('change', function() {
                        var labels = subFuncaoOpcoes.querySelectorAll('label');
                        for (var i = 0; i < labels.length; i++) {
                            labels[i].classList.remove('checked-label');
                        }
                        this.parentElement.classList.add('checked-label');
                        filtrarTreinamentos();
                    });

                    label.appendChild(radio);
                    label.appendChild(document.createTextNode(' ' + opcoes[key]));
                    subFuncaoOpcoes.appendChild(label);
                }
            }
            subFuncaoBox.style.display = 'block';
        } else {
            subFuncaoBox.style.display = 'none';
        }
        filtrarTreinamentos();
    });

    // ----- DEPARTAMENTO: filtrar treinamentos -----
    selectDepartamento.addEventListener('change', function() {
        filtrarTreinamentos();
    });

    // ----- TREINAMENTO: mostrar meses disponíveis e vagas -----
    selectTreinamento.addEventListener('change', function() {
        var treinamentoId = parseInt(this.value);

        if (treinamentoId && treinamentosData[treinamentoId]) {
            var data = treinamentosData[treinamentoId];

            // Mês - select
            selectMes.innerHTML = '<option value="" disabled selected>Selecione o mês</option>';
            if (data.agenda && data.agenda.length > 0) {
                for (var i = 0; i < data.agenda.length; i++) {
                    var mesKey = data.agenda[i];
                    var opt = document.createElement('option');
                    opt.value = mesKey;
                    opt.textContent = mesesLabels[mesKey] || mesKey;
                    selectMes.appendChild(opt);
                }
                mesBox.style.display = 'block';
            } else {
                mesBox.style.display = 'none';
            }

            // Vagas
            vagasEl.textContent = data.vagas;

            infoBox.style.display = 'block';
        } else {
            mesBox.style.display = 'none';
            infoBox.style.display = 'none';
        }
    });

    // ----- FORM SUBMIT (INSCRIÇÃO) -----
    formInscricao.addEventListener('submit', function(e) {
        e.preventDefault();

        var funcao         = selectFuncao.value;
        var treinamentoId  = selectTreinamento.value;
        var nivelRadio     = document.querySelector('input[name="nivel_funcao"]:checked');
        var departamento   = selectDepartamento.value;
        var mesSelecionado = selectMes.value;

        // Validações
        if (!funcao) {
            showMsg('Selecione uma função.', 'erro');
            return;
        }
        if (!nivelRadio) {
            showMsg('Selecione um nível/categoria.', 'erro');
            return;
        }
        if (!departamento) {
            showMsg('Selecione um departamento.', 'erro');
            return;
        }
        if (!treinamentoId) {
            showMsg('Selecione um módulo de treinamento.', 'erro');
            return;
        }
        if (!mesSelecionado) {
            showMsg('Selecione o mês desejado.', 'erro');
            return;
        }

        var nivel = nivelRadio.value;
        var btnConfirmar = document.getElementById('btn-confirmar');
        var btnOrigText  = btnConfirmar.innerHTML;
        btnConfirmar.disabled  = true;
        btnConfirmar.innerHTML = '<span class="inscricao-loading"></span> Processando...';

        // AJAX
        var formData = new FormData();
        formData.append('action', 'inscricao_treinamento');
        formData.append('nonce', inscricaoAjax.nonce);
        formData.append('treinamento_id', treinamentoId);
        formData.append('funcao', funcao);
        formData.append('nivel', nivel);
        formData.append('departamento', departamento);
        formData.append('mes', mesSelecionado);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', inscricaoAjax.url, true);
        xhr.onload = function() {
            btnConfirmar.disabled  = false;
            btnConfirmar.innerHTML = btnOrigText;

            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    showMsg(resp.data.message, 'sucesso');

                    // Atualizar vagas no JS local
                    if (treinamentosData[treinamentoId]) {
                        treinamentosData[treinamentoId].vagas = resp.data.novas_vagas;
                        vagasEl.textContent = resp.data.novas_vagas;
                    }

                    // Adicionar treinamento à lista de inscrições do usuário
                    inscricoesUsuario.push(parseInt(treinamentoId));

                    // Se vagas acabaram, remover da lista
                    if (resp.data.novas_vagas <= 0) {
                        var optToRemove = selectTreinamento.querySelector('option[value="' + treinamentoId + '"]');
                        if (optToRemove) optToRemove.remove();
                        infoBox.style.display = 'none';
                        selectTreinamento.value = '';
                    }

                    // Esconder formulário e mostrar painel de sucesso
                    formWrapper.style.display = 'none';
                    msgEl.style.display = 'none';
                    painelSucesso.style.display = 'flex';

                    // Reset interno do form
                    selectFuncao.value = '';
                    subFuncaoBox.style.display = 'none';
                    subFuncaoOpcoes.innerHTML = '';
                    selectDepartamento.value = '';
                    moduloBox.style.display = 'none';
                    selectTreinamento.value = '';
                    selectTreinamento.innerHTML = '<option value="" disabled selected>Selecione o treinamento</option>';
                    mesBox.style.display = 'none';
                    selectMes.innerHTML = '';
                    infoBox.style.display = 'none';
                } else {
                    showMsg(resp.data.message, 'erro');
                }
            } else {
                showMsg('Erro ao processar a solicitação.', 'erro');
            }
        };
        xhr.onerror = function() {
            btnConfirmar.disabled  = false;
            btnConfirmar.innerHTML = btnOrigText;
            showMsg('Erro de conexão. Tente novamente.', 'erro');
        };
        xhr.send(formData);
    });

    // ----- MODAL: ABRIR -----
    btnCadastros.addEventListener('click', function() {
        carregarInscricoes();
        modalOverlay.style.display = 'flex';
    });

    // Modal via botão do painel de sucesso
    btnCadastrosSucesso.addEventListener('click', function() {
        carregarInscricoes();
        modalOverlay.style.display = 'flex';
    });

    // ----- MODAL: FECHAR -----
    fecharModal.addEventListener('click', function() {
        modalOverlay.style.display = 'none';
        // Se veio do painel de sucesso, voltar para o form
        if (painelSucesso.style.display !== 'none') {
            painelSucesso.style.display = 'none';
            formWrapper.style.display = '';
        }
    });
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.style.display = 'none';
            if (painelSucesso.style.display !== 'none') {
                painelSucesso.style.display = 'none';
                formWrapper.style.display = '';
            }
        }
    });

    // ----- CARREGAR INSCRIÇÕES NO MODAL -----
    function carregarInscricoes() {
        var lista = document.getElementById('lista-inscricoes');
        lista.innerHTML = '<p style="text-align:center;padding:20px;"><span class="inscricao-loading" style="border-top-color:#0a328b;border-color:rgba(10,50,139,0.2);border-top-color:#0a328b;"></span> Carregando...</p>';

        var formData = new FormData();
        formData.append('action', 'buscar_inscricoes_treinamento');
        formData.append('nonce', inscricaoAjax.nonce);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', inscricaoAjax.url, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = JSON.parse(xhr.responseText);
                if (resp.success && resp.data.length > 0) {
                    var html = '<table class="inscricao-tabela">';
                    html += '<thead><tr>';
                    html += '<th>Treinamento</th><th>Depto</th><th>Função</th><th>Nível</th><th>Mês</th><th>Ação</th>';
                    html += '</tr></thead><tbody>';
                    for (var i = 0; i < resp.data.length; i++) {
                        var item = resp.data[i];
                        html += '<tr>';
                        html += '<td data-label="Treinamento">' + escHtml(item.treinamento) + '</td>';
                        html += '<td data-label="Depto">' + escHtml(item.departamento) + '</td>';
                        html += '<td data-label="Função">' + escHtml(item.funcao) + '</td>';
                        html += '<td data-label="Nível">' + escHtml(item.nivel) + '</td>';
                        html += '<td data-label="Mês">' + escHtml(item.mes) + '</td>';
                        html += '<td data-label="Ação"><button type="button" class="btn-cancelar-inscricao" data-index="' + item.index + '" data-treinamento="' + item.treinamento_id + '"><i class="fa-solid fa-trash"></i> Cancelar</button></td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';
                    lista.innerHTML = html;
                    bindCancelar();
                } else {
                    lista.innerHTML = '<p class="sem-inscricoes">Você ainda não está inscrito em nenhum treinamento.</p>';
                }
            }
        };
        xhr.send(formData);
    }

    // ----- BIND CANCELAR -----
    function bindCancelar() {
        var btns = document.querySelectorAll('.btn-cancelar-inscricao');
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener('click', function() {
                if (!confirm('Deseja realmente cancelar esta inscrição?')) return;

                var btn           = this;
                var index         = btn.getAttribute('data-index');
                var treinamentoId = btn.getAttribute('data-treinamento');

                btn.disabled  = true;
                btn.innerHTML = '<span class="inscricao-loading"></span>';

                var formData = new FormData();
                formData.append('action', 'cancelar_inscricao_treinamento');
                formData.append('nonce', inscricaoAjax.nonce);
                formData.append('index', index);
                formData.append('treinamento_id', treinamentoId);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', inscricaoAjax.url, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.success) {
                            showMsg(resp.data.message, 'sucesso');

                            // Atualizar vagas local se o treinamento ainda estiver no data
                            // Remover treinamento da lista de inscrições do usuário
                            var idxUser = inscricoesUsuario.indexOf(parseInt(treinamentoId));
                            if (idxUser !== -1) {
                                inscricoesUsuario.splice(idxUser, 1);
                            }

                            if (treinamentosData[treinamentoId]) {
                                treinamentosData[treinamentoId].vagas++;
                            }

                            // Re-filtrar para mostrar treinamento liberado
                            filtrarTreinamentos();
                            carregarInscricoes();
                        } else {
                            showMsg(resp.data.message, 'erro');
                            btn.disabled  = false;
                            btn.innerHTML = '<i class="fa-solid fa-trash"></i> Cancelar';
                        }
                    }
                };
                xhr.send(formData);
            });
        }
    }

    // Bind cancelar buttons that already exist on page load
    bindCancelar();

    // ----- SHOW MSG -----
    function showMsg(text, type) {
        msgEl.textContent = text;
        msgEl.className   = 'inscricao-msg ' + type;
        msgEl.style.display = 'block';
        // Scroll to message
        msgEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Auto-hide after 5s
        setTimeout(function() {
            msgEl.style.display = 'none';
        }, 5000);
    }

    // ----- ESCAPE HTML -----
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})();
