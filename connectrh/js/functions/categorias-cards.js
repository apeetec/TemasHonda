(function($){
    'use strict';

    const CardsState = {
        categories: [],
        selected: new Set(),
        filter: ''
    };

    $(document).ready(function(){
        init();
        loadCards();
    });

    function init(){
        $('#search-categorias').on('keyup', debounce(function(){
            CardsState.filter = $(this).val().trim();
            loadCards();
        }, 300));

        // Delegation for dynamic content
        // REMOVIDO: clique no card inteiro (agora apenas o botão "Ver Vídeos" é clicável)

        $(document).on('click', '.btn-edit-cat', function(e){
            e.stopPropagation();
            const termId = $(this).closest('.categoria-card').data('id');
            openEditModal(termId);
        });

        $(document).on('click', '.btn-delete-cat', function(e){
            e.stopPropagation();
            const termId = $(this).closest('.categoria-card').data('id');
            deleteCategories([termId]);
        });

        // Handler para mudanças nos checkboxes (usa 'change' para funcionar com .trigger('change'))
        $(document).on('change', '.card-select-checkbox', function(e){
            const termId = parseInt($(this).closest('.categoria-card').data('id'));
            const checked = $(this).is(':checked');
            
            if (checked) {
                CardsState.selected.add(termId);
                $(this).closest('.categoria-card').addClass('selected');
            } else {
                CardsState.selected.delete(termId);
                $(this).closest('.categoria-card').removeClass('selected');
            }
            
            updateSelectedControls();
        });

        // Botão "Ver Vídeos" (anchor tag) segue naturalmente seu href
    }

    function loadCards(){
        $('#categorias-cards-grid').html('<div style="grid-column:1/-1; text-align:center; padding:2rem;"><div class="spinner"></div><p class="mt-2">Carregando categorias...</p></div>');

        $.post(categorias_cards_vars.ajaxurl, { action: 'get_categorias_videos_public', search: CardsState.filter }, function(response){
            if (response.success) {
                CardsState.categories = response.data.categories || [];
                renderCards();
            } else {
                $('#categorias-cards-grid').html('<div style="grid-column:1/-1;">Erro ao carregar categorias</div>');
            }
        }, 'json').fail(function(){
            $('#categorias-cards-grid').html('<div style="grid-column:1/-1;">Erro de conexão</div>');
        });
    }

    function renderCards(){
        const container = $('#categorias-cards-grid');
        if (!CardsState.categories || CardsState.categories.length === 0) {
            container.html('<div style="grid-column:1/-1; text-align:center; padding:2rem;" class="empty-state"><div class="empty-state-icon"><i class="fas fa-folder-open" style="font-size:64px; color:#ddd;"></i></div><h3>Nenhuma categoria encontrada</h3><p style="color:#999; margin-top:8px;">Tente ajustar sua busca ou criar uma nova categoria</p></div>');
            updateSelectedControls();
            return;
        }

        // Atualizar contador
        $('#categorias-count').text(CardsState.categories.length);

        let html = '';
        CardsState.categories.forEach(function(cat){
            const selected = CardsState.selected.has(cat.id) ? 'selected' : '';
            const checked = CardsState.selected.has(cat.id) ? 'checked' : '';
            const iconClass = getIconForCategory(cat.name);
            html += `
                <div class="categoria-card ${selected}" data-id="${cat.id}" data-slug="${cat.slug}">
                    <div>
                        <div class="categoria-name-wrapper">
                            <div class="categoria-icon">
                                <i class="${iconClass}" aria-hidden="true"></i>
                            </div>
                            <div class="categoria-title">${escapeHtml(cat.name)}</div>
                        </div>
                        ${categorias_cards_vars.is_admin ? `<div class="card-actions"><input type="checkbox" class="card-select-checkbox" ${checked} title="Selecionar categoria"><button class="btn-icon btn-edit-cat" title="Editar categoria"><i class="fas fa-edit"></i></button><button class="btn-icon btn-delete-cat" title="Excluir categoria"><i class="fas fa-trash"></i></button></div>` : ''}
                    </div>
                    <div class="categoria-description">${escapeHtml(cat.description) || 'Sem descrição disponível'}</div>
                    <a class="btn-view-cat" href="${escapeHtml(cat.link)}" title="Ver vídeos desta categoria">
                        <i class="fas fa-play-circle" aria-hidden="true"></i> Ver Vídeos
                    </a>
                    <div class="categoria-meta">
                        <i class="fas fa-video" aria-hidden="true"></i>
                        <span>${cat.count} ${cat.count === 1 ? 'vídeo' : 'vídeos'}</span>
                    </div>
                </div>
            `;
        });

        container.html(html);
        
        // Animação GSAP suave nos cards (fade + slide up)
        if (typeof gsap !== 'undefined') {
            gsap.from('.categoria-card', {
                opacity: 0,
                y: 30,
                duration: 0.5,
                stagger: 0.08,
                ease: 'power2.out',
                clearProps: 'all'
            });
        }

        // If admin, show batch controls
        renderAdminBatchBar();
    }

    // Helper: retorna ícone baseado no nome da categoria
    function getIconForCategory(name){
        const nameLower = (name || '').toLowerCase();
        if (nameLower.includes('segurança') || nameLower.includes('seguranca')) return 'fas fa-shield-alt';
        if (nameLower.includes('saúde') || nameLower.includes('saude')) return 'fas fa-heartbeat';
        if (nameLower.includes('treinamento')) return 'fas fa-graduation-cap';
        if (nameLower.includes('tutorial')) return 'fas fa-book-open';
        if (nameLower.includes('procedimento')) return 'fas fa-clipboard-list';
        if (nameLower.includes('equipamento')) return 'fas fa-tools';
        return 'fas fa-folder';
    }

    function renderAdminBatchBar(){
        if (!categorias_cards_vars.is_admin) return;
        
        // Ensure event handlers are attached once
        if (!window.batchControlsInitialized) {
            $(document).on('click', '#btn-select-all-cards', function(){
                $('.card-select-checkbox').prop('checked', true).trigger('change');
            });
            
            $(document).on('click', '#btn-deselect-all-cards', function(){
                $('.card-select-checkbox').prop('checked', false).trigger('change');
            });
            
            $(document).on('click', '#btn-delete-selected-cards', function(){
                const ids = Array.from(CardsState.selected);
                if (ids.length === 0) return;
                deleteCategories(ids);
            });
            
            window.batchControlsInitialized = true;
        }
        
        updateSelectedControls();
    }

    function updateSelectedControls(){
        const count = CardsState.selected.size;
        const total = $('.card-select-checkbox').length;
        
        $('#count-selected-cards').text(count);
        
        // Mostrar/ocultar barra de controles
        if (total > 0 && categorias_cards_vars.is_admin) {
            $('#batch-controls-bar').removeClass('hidden');
        } else {
            $('#batch-controls-bar').addClass('hidden');
        }
        
        // Controlar visibilidade dos botões
        if (count > 0) {
            $('#btn-delete-selected-cards').removeClass('hidden');
            $('#btn-deselect-all-cards').removeClass('hidden');
        } else {
            $('#btn-delete-selected-cards').addClass('hidden');
            $('#btn-deselect-all-cards').addClass('hidden');
        }
        
        // Toggle botão "Selecionar Todos"
        if (count === total && total > 0) {
            $('#btn-select-all-cards').addClass('hidden');
        } else {
            $('#btn-select-all-cards').removeClass('hidden');
        }
    }

    function openEditModal(termId){
        // find category in state
        const cat = CardsState.categories.find(c=>c.id==termId);
        if (!cat) return;

        Swal.fire({
            title: 'Editar Categoria',
            html: `
                <input id="swal-cat-name" class="swal2-input" value="${escapeHtml(cat.name)}">
                <input id="swal-cat-slug" class="swal2-input" value="${escapeHtml(cat.slug)}">
                <textarea id="swal-cat-desc" class="swal2-textarea">${escapeHtml(cat.description)}</textarea>
            `,
            showCancelButton:true,
            confirmButtonText:'Atualizar',
            preConfirm: () => {
                const name = $('#swal-cat-name').val();
                const slug = $('#swal-cat-slug').val();
                const description = $('#swal-cat-desc').val();
                if (!name) { Swal.showValidationMessage('Nome é obrigatório'); return false; }
                return { term_id: termId, name, slug, description };
            }
        }).then((res)=>{
            if (res.isConfirmed) {
                // send update via admin AJAX
                $.post(categorias_cards_vars.ajaxurl, { action: 'update_categoria_video', nonce: categorias_cards_vars.nonce, ...res.value }, function(response){
                    if (response.success) {
                        Swal.fire({icon:'success', title:'Atualizada', timer:1000});
                        loadCards();
                    } else {
                        Swal.fire({icon:'error', title:'Erro', text: response.data.message || 'Erro ao atualizar'});
                    }
                }, 'json').fail(function(){ Swal.fire({icon:'error', title:'Erro', text:'Erro de conexão'}); });
            }
        });
    }

    function deleteCategories(ids){
        Swal.fire({title:'Tem certeza?', text:`Excluir ${ids.length} categoria(s)? Essa ação é irreversível.`, icon:'warning', showCancelButton:true, confirmButtonText:'Sim, excluir'}).then((res)=>{
            if (!res.isConfirmed) return;
            // admin-only AJAX
            $.post(categorias_cards_vars.ajaxurl, { action: 'delete_categorias_videos', nonce: categorias_cards_vars.nonce, term_ids: ids }, function(response){
                if (response.success) {
                    Swal.fire({icon:'success', title:'Excluído', text: response.data.message, timer:1200});
                    CardsState.selected.clear();
                    loadCards();
                } else {
                    Swal.fire({icon:'error', title:'Erro', text: response.data.message || 'Erro ao excluir'});
                }
            }, 'json').fail(function(){ Swal.fire({icon:'error', title:'Erro', text:'Erro de conexão'}); });
        });
    }

    function escapeHtml(text){ if (!text) return ''; return String(text).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]); }); }
    function debounce(fn,wait){ let t; return function(...a){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,a),wait); }; }

})(jQuery);
