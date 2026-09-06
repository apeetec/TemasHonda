(function($){
    'use strict';

    const CategoriasState = {
        selected: new Set(),
        filter: ''
    };

    $(document).ready(function(){
        init();
        loadCategories();
    });

    function init(){
        $(document).on('click', '#btn-nova-categoria', showCreateModal);
        $(document).on('click', '#btn-recarregar-categorias', loadCategories);
        $(document).on('change', '#select-all-categorias', toggleSelectAll);
        $(document).on('change', '.categoria-checkbox', handleCheckbox);
        $(document).on('click', '.btn-edit-categoria', handleEdit);
        $(document).on('click', '.btn-delete-categoria', handleDelete);
        $(document).on('click', '#btn-excluir-categorias', deleteSelectedCategories);
        $(document).on('click', '#categorias-table-body tr', function(e){
            const $target = $(e.target);
            if ($target.is('input') || $target.is('button') || $target.is('a') || $target.closest('.table-actions').length) return;
            const $cb = $(this).find('.categoria-checkbox');
            if ($cb.length) {
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            }
        });

        $('#categoria-busca').on('keyup', debounce(function(){
            CategoriasState.filter = $(this).val().toLowerCase();
            renderFilter();
        }, 300));
    }

    function loadCategories(){
        const tbody = $('#categorias-table-body');
        tbody.html(`<tr><td colspan="5" class="loading-spinner"><div class="spinner"></div><p class="mt-2">Carregando categorias...</p></td></tr>`);

        $.post(categorias_vars.ajaxurl, { action: 'get_categorias_videos', nonce: categorias_vars.nonce }, function(response){
            if (response.success) {
                renderCategories(response.data.categories || []);
            } else {
                tbody.html(`<tr><td colspan="5">Erro ao carregar categorias</td></tr>`);
            }
        }, 'json').fail(function(){
            tbody.html(`<tr><td colspan="5">Erro de conexão ao carregar categorias</td></tr>`);
        });
    }

    function renderCategories(items){
        const tbody = $('#categorias-table-body');
        if (!items || items.length === 0) {
            tbody.html(`<tr><td colspan="5" class="empty-state"><div class="empty-state-icon"><i class="fas fa-folder-open" style="font-size:48px; color:#ddd;"></i></div><h3>Nenhuma categoria encontrada</h3><p style="color:#999;">Crie sua primeira categoria para começar</p></td></tr>`);
            return;
        }

        let html = '';
        items.forEach(function(cat){
            const isSelected = CategoriasState.selected.has(cat.id);
            html += `
                <tr class="${isSelected ? 'selected' : ''}">
                    <td><input type="checkbox" class="categoria-checkbox" data-term-id="${cat.id}" ${isSelected ? 'checked' : ''}></td>
                    <td>${escapeHtml(cat.name)}</td>
                    <td>${escapeHtml(cat.slug)}</td>
                    <td>${escapeHtml(cat.description)}</td>
                    <td class="table-actions" style="text-align:center;">
                        <button type="button" class="btn-icon edit btn-edit-categoria" data-term-id="${cat.id}" title="Editar"><i class="fas fa-edit" aria-hidden="true"></i></button>
                        <button type="button" class="btn-icon delete btn-delete-categoria" data-term-id="${cat.id}" title="Excluir"><i class="fas fa-trash" aria-hidden="true"></i></button>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);
        
        // Animação GSAP suave nas linhas da tabela
        if (typeof gsap !== 'undefined') {
            gsap.from('#categorias-table-body tr', {
                opacity: 0,
                x: -20,
                duration: 0.4,
                stagger: 0.05,
                ease: 'power2.out',
                clearProps: 'all'
            });
        }
        
        updateSelectedCount();
        renderFilter();
    }

    function showCreateModal(){
        Swal.fire({
            title: 'Nova Categoria',
            html: `
                <input id="swal-cat-name" class="swal2-input" placeholder="Nome">
                <input id="swal-cat-slug" class="swal2-input" placeholder="Slug (opcional)">
                <textarea id="swal-cat-desc" class="swal2-textarea" placeholder="Descrição (opcional)"></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'Criar',
            preConfirm: () => {
                const name = $('#swal-cat-name').val();
                const slug = $('#swal-cat-slug').val();
                const desc = $('#swal-cat-desc').val();
                if (!name) { Swal.showValidationMessage('Nome é obrigatório'); return false; }
                return { name, slug, description: desc };
            }
        }).then((res)=>{
            if (res.isConfirmed) {
                createCategory(res.value);
            }
        });
    }

    function createCategory(data){
        Swal.fire({title:'Criando...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
        $.post(categorias_vars.ajaxurl, { action: 'create_categoria_video', nonce: categorias_vars.nonce, ...data }, function(response){
            if (response.success) {
                Swal.fire({icon:'success', title:'Criada', timer:1200});
                CategoriasState.selected.clear();
                loadCategories();
            } else {
                Swal.fire({icon:'error', title:'Erro', text: response.data.message || 'Erro ao criar categoria'});
            }
        }, 'json').fail(function(){
            Swal.fire({icon:'error', title:'Erro', text:'Erro de conexão'});
        });
    }

    function handleEdit(){
        const termId = $(this).data('term-id');
        // fetch current values from row
        const $row = $(this).closest('tr');
        const name = $row.find('td').eq(1).text().trim();
        const slug = $row.find('td').eq(2).text().trim();
        const desc = $row.find('td').eq(3).text().trim();

        Swal.fire({
            title: 'Editar Categoria',
            html: `
                <input id="swal-cat-name" class="swal2-input" value="${escapeHtml(name)}">
                <input id="swal-cat-slug" class="swal2-input" value="${escapeHtml(slug)}">
                <textarea id="swal-cat-desc" class="swal2-textarea">${escapeHtml(desc)}</textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'Atualizar',
            preConfirm: () => {
                const name = $('#swal-cat-name').val();
                const slug = $('#swal-cat-slug').val();
                const description = $('#swal-cat-desc').val();
                if (!name) { Swal.showValidationMessage('Nome é obrigatório'); return false; }
                return { term_id: termId, name, slug, description };
            }
        }).then((res)=>{
            if (res.isConfirmed) {
                updateCategory(res.value);
            }
        });
    }

    function updateCategory(data){
        Swal.fire({title:'Atualizando...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
        $.post(categorias_vars.ajaxurl, { action: 'update_categoria_video', nonce: categorias_vars.nonce, ...data }, function(response){
            if (response.success) {
                Swal.fire({icon:'success', title:'Atualizada', timer:1000});
                loadCategories();
            } else {
                Swal.fire({icon:'error', title:'Erro', text: response.data.message || 'Erro ao atualizar'});
            }
        }, 'json').fail(function(){
            Swal.fire({icon:'error', title:'Erro', text:'Erro de conexão'});
        });
    }

    function handleDelete(){
        const termId = $(this).data('term-id');
        Swal.fire({
            title:'Tem certeza?', text:'Excluir essa categoria é irreversível', icon:'warning', showCancelButton:true, confirmButtonText:'Sim, excluir'
        }).then((res)=>{
            if (res.isConfirmed) deleteCategories([termId]);
        });
    }

    function deleteSelectedCategories(){
        const ids = Array.from(CategoriasState.selected);
        if (ids.length === 0) return;
        Swal.fire({
            title:'Tem certeza?', text:`Excluir ${ids.length} categoria(s)?`, icon:'warning', showCancelButton:true, confirmButtonText:'Sim, excluir'
        }).then((res)=>{
            if (res.isConfirmed) deleteCategories(ids);
        });
    }

    function deleteCategories(ids){
        Swal.fire({title:'Excluindo...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
        $.post(categorias_vars.ajaxurl, { action: 'delete_categorias_videos', nonce: categorias_vars.nonce, term_ids: ids }, function(response){
            if (response.success) {
                Swal.fire({icon:'success', title:'Excluído', text: response.data.message, timer:1200});
                CategoriasState.selected.clear();
                loadCategories();
            } else {
                Swal.fire({icon:'error', title:'Erro', text: response.data.message || 'Erro ao excluir'});
            }
        }, 'json').fail(function(){
            Swal.fire({icon:'error', title:'Erro', text:'Erro de conexão'});
        });
    }

    function toggleSelectAll(){
        const isChecked = $(this).is(':checked');
        $('.categoria-checkbox').prop('checked', isChecked).trigger('change');
    }

    function handleCheckbox(){
        const id = parseInt($(this).data('term-id'));
        const checked = $(this).is(':checked');
        if (checked) {
            CategoriasState.selected.add(id);
            $(this).closest('tr').addClass('selected');
        } else {
            CategoriasState.selected.delete(id);
            $(this).closest('tr').removeClass('selected');
        }
        updateSelectedCount();
    }

    function updateSelectedCount(){
        const count = CategoriasState.selected.size;
        $('#count-selected-categorias').text(count);
        if (count > 0) {
            $('#btn-excluir-categorias').removeClass('hidden');
        } else {
            $('#btn-excluir-categorias').addClass('hidden');
        }
        const total = $('.categoria-checkbox').length;
        const checked = $('.categoria-checkbox:checked').length;
        $('#select-all-categorias').prop('checked', total > 0 && total === checked);
    }

    function renderFilter(){
        const filter = CategoriasState.filter || '';
        $('#categorias-table-body tr').each(function(){
            const name = $(this).find('td').eq(1).text().toLowerCase();
            const slug = $(this).find('td').eq(2).text().toLowerCase();
            if (name.indexOf(filter) === -1 && slug.indexOf(filter) === -1 && filter !== '') {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    }

    function escapeHtml(text){
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]); });
    }

    function debounce(func, wait){
        let timeout;
        return function(...args){ clearTimeout(timeout); timeout = setTimeout(()=>func.apply(this,args), wait); };
    }

})(jQuery);
