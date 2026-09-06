/**
 * Dashboard de Vídeos - Funções AJAX com suporte a Group Box de Seções
 * Cada vídeo pode ter múltiplas seções com título + upload de arquivo
 */

(function($) {
    'use strict';

    const VideosState = {
        currentPage: 1,
        perPage: 25,
        search: '',
        categoria: 0,
        selectedVideos: new Set(),
        editingVideoId: 0,
        secoes: [] // Array de seções temporárias durante criação/edição
    };

    $(document).ready(function() {
        console.log('Admin Videos JS carregado');
        console.log('videos_vars:', videos_vars);
        initEventListeners();
        populateCategorias();
        loadVideos();
    });

    function initEventListeners() {
        $('#btn-novo-video').on('click', showCreateModal);
        $('#btn-excluir-videos').on('click', deleteSelectedVideos);
        $('#select-all-videos').on('change', toggleSelectAll);
        $('#btn-select-all-videos').on('click', function(){
            const total = $('.video-checkbox').length;
            const checked = $('.video-checkbox:checked').length;
            $('#select-all-videos').prop('checked', checked !== total).trigger('change');
        });

        $('#search-video').on('keyup', debounce(handleSearch, 500));
        $('#filter-categoria').on('change', handleFilterChange);
        $('#per-page-videos').on('change', handlePerPageChange);
        $('#btn-limpar-filtros-videos').on('click', clearFilters);

        $(document).on('change', '.video-checkbox', handleVideoCheckbox);
        $(document).on('click', '.btn-edit-video', handleEditVideo);
        $(document).on('click', '.btn-delete-video', handleDeleteVideo);
        $(document).on('click', '#videos-table-body tr', function(e){
            const $target = $(e.target);
            if ($target.is('input') || $target.is('button') || $target.is('a') || $target.closest('.table-actions').length) return;
            const $cb = $(this).find('.video-checkbox');
            if ($cb.length) {
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            }
        });
    }

    function populateCategorias(){
        console.log('populateCategorias() chamado');
        
        const $select = $('#filter-categoria');
        console.log('Select #filter-categoria encontrado:', $select.length);
        console.log('Opções já existentes no select:', $select.find('option').length);
        
        // Verificar se o select existe
        if ($select.length === 0) {
            console.error('Select #filter-categoria não encontrado no DOM!');
            return;
        }
        
        // Se já há mais de 1 opção (além de "Todas as categorias"), significa que foi populado pelo PHP
        const optionsExistentes = $select.find('option').length;
        if (optionsExistentes > 1) {
            console.log('✓ Select já populado pelo PHP com', optionsExistentes, 'opções (incluindo "Todas as categorias")');
            console.log('Categorias disponíveis:', $select.find('option:not(:first)').map(function(){ return $(this).text(); }).get().join(', '));
            return;
        }
        
        // Se chegou aqui, o PHP não populou - tentar via JavaScript
        console.warn('Select vazio, tentando popular via JavaScript...');
        
        // Verificar se categorias estão disponíveis
        if (typeof videos_vars === 'undefined' || typeof videos_vars.categorias === 'undefined') {
            console.error('videos_vars.categorias não está definido!');
            console.log('videos_vars completo:', typeof videos_vars !== 'undefined' ? videos_vars : 'undefined');
            return;
        }
        
        console.log('Categorias recebidas do JS:', videos_vars.categorias);
        
        // Popular select com todas as categorias
        if (Array.isArray(videos_vars.categorias) && videos_vars.categorias.length > 0) {
            videos_vars.categorias.forEach(function(cat){
                const option = $('<option></option>')
                    .val(cat.id)
                    .text(cat.name);
                $select.append(option);
                console.log(`Adicionada categoria via JS: ${cat.name} (ID: ${cat.id})`);
            });
            console.log(`✓ ${videos_vars.categorias.length} categoria(s) carregada(s) no filtro via JavaScript`);
            console.log('Opções no select após popular:', $select.find('option').length);
        } else {
            console.error('⚠ Nenhuma categoria encontrada em videos_vars.categorias');
            console.log('Tipo de videos_vars.categorias:', typeof videos_vars.categorias);
            console.log('É array?', Array.isArray(videos_vars.categorias));
        }
    }

    function loadVideos() {
        showLoading();

        $.ajax({
            url: videos_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_videos_dashboard',
                page: VideosState.currentPage,
                per_page: VideosState.perPage,
                search: VideosState.search,
                categoria: VideosState.categoria,
                nonce: videos_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderVideosTable(response.data.videos);
                    renderPagination(response.data.total, response.data.pages);
                    updateStats(response.data);
                } else {
                    showError('Erro ao carregar vídeos: ' + response.data.message);
                }
            },
            error: function() {
                showError('Erro de conexão ao carregar vídeos');
            }
        });
    }

    function renderVideosTable(videos) {
        const tbody = $('#videos-table-body');
        
        if (!videos || videos.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6" class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-video" style="font-size:48px; color:#ddd;"></i></div>
                        <h3>Nenhum vídeo encontrado</h3>
                        <p>Tente ajustar os filtros ou criar um novo vídeo</p>
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        videos.forEach(function(video) {
            const isSelected = VideosState.selectedVideos.has(video.id);
            const numSecoes = video.num_secoes || 0;
            html += `
                <tr class="${isSelected ? 'selected' : ''}">
                    <td>
                        <input type="checkbox" class="video-checkbox" 
                               data-video-id="${video.id}" 
                               ${isSelected ? 'checked' : ''}>
                    </td>
                    <td><strong>${escapeHtml(video.title)}</strong></td>
                    <td>${escapeHtml(video.categorias)}</td>
                    <td style="text-align:center;">${numSecoes} seção(ões)</td>
                    <td>${escapeHtml(video.date)}</td>
                    <td class="table-actions" style="text-align: center;">
                        <button type="button" class="btn-icon edit btn-edit-video" 
                                data-video-id="${video.id}" 
                                title="Editar vídeo">
                            <i class="fas fa-edit" style="font-size:16px;" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn-icon delete btn-delete-video" 
                                data-video-id="${video.id}" 
                                title="Excluir vídeo">
                            <i class="fas fa-trash" style="font-size:16px;" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);
        
        // Animação GSAP suave nas linhas da tabela
        if (typeof gsap !== 'undefined') {
            gsap.from('#videos-table-body tr', {
                opacity: 0,
                x: -20,
                duration: 0.4,
                stagger: 0.05,
                ease: 'power2.out',
                clearProps: 'all'
            });
        }
        
        updateSelectedCount();
    }

    function renderPagination(total, pages) {
        const container = $('#pagination-controls-videos');
        const currentPage = VideosState.currentPage;
        
        let html = '';
        html += `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">« Anterior</button>`;
        
        for (let i = 1; i <= pages; i++) {
            if (i === 1 || i === pages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<button class="${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<span style="padding: 0.5rem;">...</span>';
            }
        }
        
        html += `<button ${currentPage === pages ? 'disabled' : ''} data-page="${currentPage + 1}">Próximo »</button>`;
        container.html(html);
        
        container.find('button').on('click', function() {
            const page = parseInt($(this).data('page'));
            if (!isNaN(page) && page !== currentPage) {
                VideosState.currentPage = page;
                loadVideos();
            }
        });
    }

    function updateStats(data) {
        const start = ((VideosState.currentPage - 1) * VideosState.perPage) + 1;
        const end = Math.min(VideosState.currentPage * VideosState.perPage, data.total);
        
        $('#showing-start-videos').text(start);
        $('#showing-end-videos').text(end);
        $('#total-videos').text(data.total);
    }

    function showCreateModal() {
        VideosState.secoes = [];
        VideosState.editingVideoId = 0;
        
        Swal.fire({
            title: 'Novo Vídeo',
            html: getVideoFormHTML(),
            width: '800px',
            showCancelButton: true,
            confirmButtonText: 'Criar Vídeo',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                attachModalEventListeners();
            },
            preConfirm: () => {
                return validateAndGetVideoData();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                createVideo(result.value);
            }
        });
    }

    function getVideoFormHTML() {
        return `
            <div style="text-align: left;">
                <div class="mb-2">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Título do Vídeo</label>
                    <input type="text" id="swal-video-title" class="swal2-input" style="width: 100%;" placeholder="Digite o título">
                </div>
                <div class="mb-2">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Categoria</label>
                    <select id="swal-video-categoria" class="swal2-select" style="width: 100%;">
                        <option value="">Selecione a categoria</option>
                        ${getCategoriasOptions()}
                    </select>
                </div>
                <hr style="margin: 1.5rem 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="margin: 0;">Seções de Vídeo</h4>
                    <button type="button" id="btn-add-secao" class="swal2-confirm swal2-styled" style="margin: 0;">
                        <i class="fas fa-plus"></i> Adicionar Seção
                    </button>
                </div>
                <div id="secoes-container" style="max-height: 300px; overflow-y: auto;">
                    ${renderSecoesHTML()}
                </div>
            </div>
        `;
    }

    function renderSecoesHTML() {
        const isEditing = VideosState.editingVideoId !== null;
        
        if (VideosState.secoes.length === 0) {
            const mensagem = isEditing 
                ? '<p style="text-align: center; color: #999;">Nenhuma nova seção adicionada. As seções atuais do vídeo serão mantidas. Clique em "Adicionar Seção" para adicionar mais.</p>'
                : '<p style="text-align: center; color: #999;">Nenhuma seção adicionada. Clique em "Adicionar Seção" para começar.</p>';
            return mensagem;
        }

        let html = '';
        VideosState.secoes.forEach((secao, index) => {
            // Verificar se é uma seção existente (tem upload e filename)
            const isExistingSection = secao.upload && secao.filename && isEditing;
            const sectionLabel = isExistingSection ? `Seção ${index + 1} (Existente)` : `Seção ${index + 1}`;
            
            html += `
                <div class="secao-item" data-index="${index}" style="border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; background: ${isExistingSection ? '#e8f5e9' : '#f9f9f9'};">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong>${sectionLabel}</strong>
                        <button type="button" class="btn-remove-secao" data-index="${index}" style="background: #e74c3c; color: white; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: block; margin-bottom: 0.3rem; font-size: 0.9rem;">Título da Seção</label>
                        <input type="text" class="secao-titulo" data-index="${index}" value="${escapeHtml(secao.titulo_secao_do_video)}" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.3rem; font-size: 0.9rem;">Arquivo de Vídeo ${isExistingSection ? '(Atual)' : ''}</label>
                        ${isExistingSection ? `
                            <div style="background: #c8e6c9; padding: 10px; border-radius: 4px; margin-bottom: 8px; border-left: 4px solid #4caf50;">
                                <small style="color: #2e7d32;">
                                    <i class="fas fa-video"></i> <strong>Vídeo atual:</strong><br>
                                    ${escapeHtml(secao.filename)}
                                </small>
                            </div>
                        ` : ''}
                        <input type="file" class="secao-upload" data-index="${index}" accept="video/*" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        ${!isExistingSection && secao.filename ? `<small style="color: #27ae60;"><i class="fas fa-check-circle"></i> ${escapeHtml(secao.filename)}</small>` : !isExistingSection ? '<small style="color: #999;">Nenhum arquivo selecionado</small>' : '<small style="color: #1976d2;"><i class="fas fa-info-circle"></i> Fazer upload de novo arquivo substituirá o atual</small>'}
                    </div>
                </div>
            `;
        });
        return html;
    }

    function attachModalEventListeners() {
        $('#btn-add-secao').off('click').on('click', function(e) {
            e.preventDefault();
            VideosState.secoes.push({
                titulo_secao_do_video: '',
                upload: 0,
                filename: ''
            });
            $('#secoes-container').html(renderSecoesHTML());
            attachSecaoEventListeners();
        });
        
        attachSecaoEventListeners();
    }

    function attachSecaoEventListeners() {
        $('.btn-remove-secao').off('click').on('click', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            VideosState.secoes.splice(index, 1);
            $('#secoes-container').html(renderSecoesHTML());
            attachSecaoEventListeners();
        });

        $('.secao-titulo').off('input').on('input', function() {
            const index = $(this).data('index');
            VideosState.secoes[index].titulo_secao_do_video = $(this).val();
        });

        $('.secao-upload').off('change').on('change', function() {
            const index = $(this).data('index');
            const file = this.files[0];
            if (file) {
                uploadVideoFile(file, index);
            }
        });
    }

    function uploadVideoFile(file, secaoIndex) {
        // Validar tamanho do arquivo (avisar se > 100MB)
        const maxSize = 100 * 1024 * 1024; // 100MB
        if (file.size > maxSize) {
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);
            const $secaoItem = $(`.secao-item[data-index="${secaoIndex}"]`);
            $secaoItem.find('small').html(`<span style="color: #ff9800;">⚠ Arquivo grande (${sizeMB} MB). Aguarde o upload...</span>`);
        }
        
        // Mostrar feedback visual na seção específica
        const $secaoItem = $(`.secao-item[data-index="${secaoIndex}"]`);
        const $uploadInput = $secaoItem.find('.secao-upload');
        const originalHtml = $secaoItem.find('div:last').html();
        
        $secaoItem.find('div:last').html(`
            <label style="display: block; margin-bottom: 0.3rem; font-size: 0.9rem;">Arquivo de Vídeo</label>
            <div style="background: #e3f2fd; padding: 10px; border-radius: 4px; text-align: center;">
                <div class="upload-progress">
                    <p style="margin: 5px 0;"><strong>${escapeHtml(file.name)}</strong></p>
                    <div style="background: #bbdefb; border-radius: 10px; height: 20px; margin: 10px 0;">
                        <div class="progress-bar" style="background: #2196f3; width: 0%; height: 100%; border-radius: 10px; transition: width 0.3s;"></div>
                    </div>
                    <p style="margin: 5px 0; color: #1976d2; font-size: 0.9em;">Preparando upload...</p>
                </div>
            </div>
        `);
        
        const formData = new FormData();
        formData.append('video_file', file);
        formData.append('action', 'upload_video_file_dashboard');
        formData.append('nonce', videos_vars.nonce);

        $.ajax({
            url: videos_vars.ajaxurl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 300000, // 5 minutos de timeout para vídeos grandes
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                // Progress bar
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        const loadedMB = (evt.loaded / 1024 / 1024).toFixed(2);
                        const totalMB = (evt.total / 1024 / 1024).toFixed(2);
                        
                        $secaoItem.find('.progress-bar').css('width', percentComplete + '%');
                        $secaoItem.find('.upload-progress p:last').html(
                            `<strong>${percentComplete}%</strong> - ${loadedMB} MB de ${totalMB} MB`
                        );
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                console.log('Upload response:', response);
                
                if (response.success) {
                    // Atualizar estado da seção
                    VideosState.secoes[secaoIndex].upload = response.data.attachment_id;
                    VideosState.secoes[secaoIndex].filename = response.data.filename;
                    
                    // Mostrar feedback de sucesso na seção
                    $secaoItem.find('div:last').html(`
                        <label style="display: block; margin-bottom: 0.3rem; font-size: 0.9rem;">Arquivo de Vídeo</label>
                        <input type="file" class="secao-upload" data-index="${secaoIndex}" accept="video/*" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <div style="background: #d4edda; padding: 8px; border-radius: 4px; margin-top: 5px; border-left: 4px solid #28a745;">
                            <small style="color: #155724;">
                                <i class="fas fa-check-circle"></i> <strong>Upload concluído!</strong><br>
                                ${escapeHtml(response.data.filename)} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                            </small>
                        </div>
                    `);
                    
                    // Reattach event listener para o novo input
                    $secaoItem.find('.secao-upload').off('change').on('change', function() {
                        const newFile = this.files[0];
                        if (newFile) {
                            uploadVideoFile(newFile, secaoIndex);
                        }
                    });
                    
                    // Feedback sonoro (opcional)
                    // new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYHGGS57OihUBELTqXh8Lx0JQUqgc3y2Yk2Bhxdu+3qn1ARC06l4fC8diUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHYlBSt/zfLZiTYGHF277eqfUBELTqXh8Lx2JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBSt/zfLZiTYGHF277eqfUBELTqXh8Lx3JQUrfs3y2Yk2Bhxdu+3qn1ARC06l4fC8dyUFK3/N8tmJNgYcXbvt6p9QEQtOpeHwvHclBQ==').play();
                    
                } else {
                    // Mostrar erro na seção
                    $secaoItem.find('div:last').html(`
                        <label style="display: block; margin-bottom: 0.3rem; font-size: 0.9rem;">Arquivo de Vídeo</label>
                        <input type="file" class="secao-upload" data-index="${secaoIndex}" accept="video/*" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <div style="background: #f8d7da; padding: 8px; border-radius: 4px; margin-top: 5px; border-left: 4px solid #dc3545;">
                            <small style="color: #721c24;">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Erro:</strong><br>
                                ${escapeHtml(response.data.message)}
                            </small>
                        </div>
                    `);
                    
                    // Reattach event listener
                    $secaoItem.find('.secao-upload').off('change').on('change', function() {
                        const newFile = this.files[0];
                        if (newFile) {
                            uploadVideoFile(newFile, secaoIndex);
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', status, error, xhr.responseText);
                
                let errorMsg = 'Erro de conexão no upload';
                if (status === 'timeout') {
                    errorMsg = 'Timeout: O upload demorou muito. Tente um arquivo menor.';
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.data?.message || errorMsg;
                    } catch (e) {
                        errorMsg += ' (Erro ao processar resposta)';
                    }
                }
                
                // Mostrar erro na seção
                $secaoItem.find('div:last').html(`
                    <label style="display: block; margin-bottom: 0.3rem; font-size: 0.9rem;">Arquivo de Vídeo</label>
                    <input type="file" class="secao-upload" data-index="${secaoIndex}" accept="video/*" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    <div style="background: #f8d7da; padding: 8px; border-radius: 4px; margin-top: 5px; border-left: 4px solid #dc3545;">
                        <small style="color: #721c24;">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Erro:</strong><br>
                            ${errorMsg}
                        </small>
                    </div>
                `);
                
                // Reattach event listener
                $secaoItem.find('.secao-upload').off('change').on('change', function() {
                    const newFile = this.files[0];
                    if (newFile) {
                        uploadVideoFile(newFile, secaoIndex);
                    }
                });
            }
        });
    }

    function validateAndGetVideoData() {
        const title = $('#swal-video-title').val();
        const categoria_id = $('#swal-video-categoria').val();
        const isEditing = VideosState.editingVideoId !== null;

        if (!title) {
            Swal.showValidationMessage('Título é obrigatório');
            return false;
        }

        // Atualizar títulos finais das seções
        $('.secao-titulo').each(function() {
            const index = $(this).data('index');
            VideosState.secoes[index].titulo_secao_do_video = $(this).val();
        });

        // Validar seções (todas devem ter título e upload)
        const secoesValidas = VideosState.secoes.filter(s => s.titulo_secao_do_video && s.upload);
        
        // Se estiver CRIANDO, é obrigatório ter pelo menos um arquivo de vídeo anexado
        if (!isEditing) {
            if (VideosState.secoes.length === 0) {
                Swal.showValidationMessage('Adicione pelo menos uma seção com título e vídeo');
                return false;
            }
            
            // Verificar se existe pelo menos um arquivo anexado
            const temArquivoAnexado = VideosState.secoes.some(s => s.upload);
            if (!temArquivoAnexado) {
                Swal.showValidationMessage('É obrigatório anexar pelo menos um arquivo de vídeo');
                return false;
            }
            
            if (secoesValidas.length === 0) {
                Swal.showValidationMessage('Adicione pelo menos uma seção com título e vídeo');
                return false;
            }
        }

        return {
            title,
            categoria_id,
            secoes: secoesValidas
        };
    }

    function createVideo(videoData) {
        Swal.fire({
            title: 'Criando vídeo...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: videos_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'create_video_dashboard',
                title: videoData.title,
                categoria_id: videoData.categoria_id,
                secoes: JSON.stringify(videoData.secoes),
                nonce: videos_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Vídeo criado!',
                        text: response.data.message,
                        timer: 2000
                    });
                    loadVideos();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: response.data.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro de conexão ao criar vídeo'
                });
            }
        });
    }

    function handleEditVideo() {
        const videoId = $(this).data('video-id');
        
        Swal.fire({
            title: 'Carregando dados...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: videos_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_video_data_dashboard',
                post_id: videoId,
                nonce: videos_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showEditVideoModal(response.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao carregar dados do vídeo'
                    });
                }
            }
        });
    }

    function showEditVideoModal(video) {
        VideosState.editingVideoId = video.id;
        VideosState.secoes = video.secoes || [];
        
        Swal.fire({
            title: 'Editar Vídeo',
            html: getEditVideoFormHTML(video),
            width: '800px',
            showCancelButton: true,
            confirmButtonText: 'Atualizar',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                attachModalEventListeners();
            },
            preConfirm: () => {
                return validateAndGetVideoData();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateVideo(result.value);
            }
        });
    }

    function getEditVideoFormHTML(video) {
        return `
            <div style="text-align: left;">
                <div class="mb-2">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Título do Vídeo</label>
                    <input type="text" id="swal-video-title" class="swal2-input" style="width: 100%;" value="${escapeHtml(video.title)}">
                </div>
                <div class="mb-2">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Categoria</label>
                    <select id="swal-video-categoria" class="swal2-select" style="width: 100%;">
                        ${getCategoriasOptions(video.categoria_id)}
                    </select>
                </div>
                <hr style="margin: 1.5rem 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="margin: 0;">Seções de Vídeo</h4>
                    <button type="button" id="btn-add-secao" class="swal2-confirm swal2-styled" style="margin: 0;">
                        <i class="fas fa-plus"></i> Adicionar Seção
                    </button>
                </div>
                <div id="secoes-container" style="max-height: 300px; overflow-y: auto;">
                    ${renderSecoesHTML()}
                </div>
            </div>
        `;
    }

    function updateVideo(videoData) {
        Swal.fire({
            title: 'Atualizando vídeo...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: videos_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'update_video_dashboard',
                post_id: VideosState.editingVideoId,
                title: videoData.title,
                categoria_id: videoData.categoria_id,
                secoes: JSON.stringify(videoData.secoes),
                nonce: videos_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Vídeo atualizado!',
                        text: response.data.message,
                        timer: 2000
                    });
                    loadVideos();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: response.data.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro de conexão ao atualizar vídeo'
                });
            }
        });
    }

    function handleDeleteVideo() {
        const videoId = $(this).data('video-id');
        
        Swal.fire({
            title: 'Tem certeza?',
            text: 'Esta ação não pode ser desfeita!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteVideos([videoId]);
            }
        });
    }

    function deleteSelectedVideos() {
        const videoIds = Array.from(VideosState.selectedVideos);
        
        if (videoIds.length === 0) {
            return;
        }

        Swal.fire({
            title: 'Tem certeza?',
            text: `Você está prestes a excluir ${videoIds.length} vídeo(s). Esta ação não pode ser desfeita!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Sim, excluir todos!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteVideos(videoIds);
            }
        });
    }

    function deleteVideos(videoIds) {
        Swal.fire({
            title: 'Excluindo vídeos...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: videos_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'delete_videos_dashboard',
                post_ids: videoIds,
                nonce: videos_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
                        text: response.data.message,
                        timer: 2000
                    });
                    VideosState.selectedVideos.clear();
                    updateSelectedCount();
                    loadVideos();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: response.data.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro de conexão ao excluir vídeos'
                });
            }
        });
    }

    function toggleSelectAll() {
        const isChecked = $(this).is(':checked');
        $('.video-checkbox').prop('checked', isChecked).trigger('change');
    }

    function handleVideoCheckbox() {
        const videoId = parseInt($(this).data('video-id'));
        const isChecked = $(this).is(':checked');
        
        if (isChecked) {
            VideosState.selectedVideos.add(videoId);
            $(this).closest('tr').addClass('selected');
        } else {
            VideosState.selectedVideos.delete(videoId);
            $(this).closest('tr').removeClass('selected');
        }
        
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const count = VideosState.selectedVideos.size;
        $('#count-selected-videos').text(count);
        
        if (count > 0) {
            $('#btn-excluir-videos').removeClass('hidden');
        } else {
            $('#btn-excluir-videos').addClass('hidden');
        }

        const totalCheckboxes = $('.video-checkbox').length;
        const checkedCheckboxes = $('.video-checkbox:checked').length;
        $('#select-all-videos').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    }

    function handleSearch() {
        VideosState.search = $(this).val();
        VideosState.currentPage = 1;
        loadVideos();
    }

    function handleFilterChange() {
        VideosState.categoria = parseInt($(this).val()) || 0;
        VideosState.currentPage = 1;
        loadVideos();
    }

    function handlePerPageChange() {
        VideosState.perPage = parseInt($(this).val());
        VideosState.currentPage = 1;
        loadVideos();
    }

    function clearFilters() {
        $('#search-video').val('');
        $('#filter-categoria').val('');
        $('#per-page-videos').val('25');
        
        VideosState.search = '';
        VideosState.categoria = 0;
        VideosState.perPage = 25;
        VideosState.currentPage = 1;
        
        loadVideos();
    }

    function showLoading() {
        $('#videos-table-body').html(`
            <tr>
                <td colspan="6" class="loading-spinner">
                    <div class="spinner"></div>
                    <p class="mt-2">Carregando vídeos...</p>
                </td>
            </tr>
        `);
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: message
        });
    }

    function getCategoriasOptions(selectedId = 0) {
        let options = '<option value="">Selecione a categoria</option>';
        
        if (typeof videos_vars !== 'undefined' && typeof videos_vars.categorias !== 'undefined' && Array.isArray(videos_vars.categorias)) {
            videos_vars.categorias.forEach(function(categoria) {
                const selected = categoria.id == selectedId ? 'selected' : '';
                options += `<option value="${categoria.id}" ${selected}>${escapeHtml(categoria.name)}</option>`;
            });
        }
        
        return options;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

})(jQuery);
