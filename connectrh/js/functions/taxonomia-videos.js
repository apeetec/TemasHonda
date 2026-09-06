(function($){
    var termId = (typeof taxonomy_videos_vars !== 'undefined') ? taxonomy_videos_vars.term_id : 0;
    var ajaxurl = (typeof taxonomy_videos_vars !== 'undefined') ? taxonomy_videos_vars.ajaxurl : '/wp-admin/admin-ajax.php';
    var isAdmin = (typeof taxonomy_videos_vars !== 'undefined') ? taxonomy_videos_vars.is_admin : false;
    var dashboardNonce = (typeof taxonomy_videos_vars !== 'undefined') ? taxonomy_videos_vars.dashboard_nonce : '';

    var selected = new Set();
    var selectionMode = false;
    var debounceTimer = null;

    function fetchVideos(search){
        $.post(ajaxurl, { action: 'get_videos_by_taxonomy', term_id: termId, search: search || '' }, function(resp){
            if (!resp || !resp.success) return;
            renderGrid(resp.data.videos);
        });
    }

    function renderGrid(videos){
        var $grid = $('#videos-cards-grid');
        if (!$grid.length) return;
        $grid.empty();

        videos.forEach(function(v){

            var thumb = v.thumbnail ? '<img src="'+v.thumbnail+'" alt="'+escapeHtml(v.title)+'">' : '<div class="card-placeholder"><i class="fa fa-video-camera" aria-hidden="true"></i></div>';
            var adminButtons = '';
            if (isAdmin) {
                adminButtons = '<div class="card-admin-actions" style="position:absolute; right:8px; top:8px; z-index:10; display:flex; gap:6px;">' +
                                '<button class="btn-icon edit-video" data-id="'+v.id+'" title="Editar"><i class="fa fa-edit"></i></button>' +
                                '<button class="btn-icon delete-video" data-id="'+v.id+'" title="Excluir"><i class="fa fa-trash"></i></button>' +
                                '</div>';
            }

            var checkboxInput = isAdmin ? '<input type="checkbox" class="video-checkbox-hidden" data-id="'+v.id+'" style="display:none;">' : '';

            var $card = $('\n                <div class="card-wrapper" data-id="'+v.id+'" style="position:relative">\n                    '+checkboxInput+'\n                    '+adminButtons+'\n                    <div class="select-overlay" style="display:none;">\n                        <i class="fa fa-check" aria-hidden="true"></i>\n                    </div>\n                    <a class="card-link" href="'+v.permalink+'">\n                        <div class="card">\n                            <div class="card-image">'+thumb+'<span class="card-play"><i class="fa fa-play"></i></span></div>\n                            <div class="card-content">\n                                <h3 class="card-title">'+escapeHtml(v.title)+'</h3>\n                                <p>'+escapeHtml(v.excerpt)+'</p>\n                                <div class="meta">'+escapeHtml(v.date)+'</div>\n                            </div>\n                        </div>\n                    </a>\n                </div>\n            ');

            $grid.append($card);
        });

        // attach events
        // clicking card when in selection mode toggles selection
        $(document).off('click', '.card-link').on('click', '.card-link', function(e){
            if (!selectionMode) return; // default behavior
            e.preventDefault(); e.stopPropagation();
            var $wrapper = $(this).closest('.card-wrapper');
            var id = parseInt($wrapper.data('id'));
            toggleSelection($wrapper, id);
        });

        // hidden checkbox support for accessibility (not visible)
        $('.video-checkbox-hidden').off('change').on('change', function(){
            var id = $(this).data('id');
            var $wrapper = $(this).closest('.card-wrapper');
            if (this.checked) { selected.add(id); $wrapper.addClass('selected'); $wrapper.find('.select-overlay').show(); }
            else { selected.delete(id); $wrapper.removeClass('selected'); $wrapper.find('.select-overlay').hide(); }
        });

        $('#select-all-action').text('Selecionar todos');

        $('.delete-video').off('click').on('click', function(e){
            e.preventDefault(); e.stopPropagation();
            var id = $(this).data('id');
            deleteVideos([id]);
        });

        $('.edit-video').off('click').on('click', function(e){
            e.preventDefault(); e.stopPropagation();
            var id = $(this).data('id');
            openEditModal(id);
        });
    }

    function toggleSelection($wrapper, id){
        if (!$wrapper || !id) return;
        if ($wrapper.hasClass('selected')) {
            $wrapper.removeClass('selected');
            $wrapper.find('.select-overlay').hide();
            selected.delete(id);
            $wrapper.find('.video-checkbox-hidden').prop('checked', false);
        } else {
            $wrapper.addClass('selected');
            $wrapper.find('.select-overlay').show();
            selected.add(id);
            $wrapper.find('.video-checkbox-hidden').prop('checked', true);
        }
        // update select-all-action text
        updateSelectAllText();
    }

    function updateSelectAllText(){
        var $btn = $('#select-all-action');
        var $grid = $('#videos-cards-grid');
        var total = $grid.find('.card-wrapper').length;
        if (selected.size === total && total > 0) {
            $btn.text('Desmarcar todos');
        } else {
            $btn.text('Selecionar todos');
        }
    }

    function deleteVideos(ids){
        if (!Array.isArray(ids) || ids.length === 0) return;
        if (!confirm('Confirmar exclusão de '+ids.length+' vídeo(s)?')) return;
        $.post(ajaxurl, { action: 'delete_videos_dashboard', post_ids: ids, nonce: dashboardNonce }, function(resp){
            if (!resp) return alert('Erro desconhecido');
            if (resp.success) {
                fetchVideos($('#search-videos').val());
            } else {
                alert(resp.data && resp.data.message ? resp.data.message : 'Erro ao excluir');
            }
        });
    }

    // Toggle selection mode and select-all action
    $(document).on('click', '#toggle-selection-mode', function(e){
        e.preventDefault();
        selectionMode = !selectionMode;
        var $btn = $(this);
        if (selectionMode) {
            $btn.text('Cancelar');
            // show overlays on all wrappers (unselected hidden)
            $('.card-wrapper .select-overlay').show();
        } else {
            $btn.text('Selecionar');
            // clear selection visual
            $('.card-wrapper').removeClass('selected');
            $('.card-wrapper .select-overlay').hide();
            selected.clear();
            $('.video-checkbox-hidden').prop('checked', false);
        }
        updateSelectAllText();
    });

    $(document).on('click', '#select-all-action', function(e){
        e.preventDefault();
        var $grid = $('#videos-cards-grid');
        var wrappers = $grid.find('.card-wrapper');
        var ids = [];
        if ($(this).text().indexOf('Desmarcar') !== -1) {
            // clear all
            wrappers.each(function(){
                var $w = $(this); var id = parseInt($w.data('id'));
                $w.removeClass('selected'); $w.find('.select-overlay').hide(); $w.find('.video-checkbox-hidden').prop('checked', false);
            });
            selected.clear();
            $(this).text('Selecionar todos');
            return;
        }
        // select all
        wrappers.each(function(){ var $w=$(this); var id=parseInt($w.data('id')); if(id){ $w.addClass('selected'); $w.find('.select-overlay').show(); $w.find('.video-checkbox-hidden').prop('checked', true); selected.add(id);} });
        updateSelectAllText();
    });

    function openEditModal(postId){
        // Buscar dados
        $.post(ajaxurl, { action: 'get_video_data_dashboard', post_id: postId, nonce: dashboardNonce }, function(resp){
            if (!resp || !resp.success) return alert('Erro ao carregar vídeo');
            var data = resp.data;
            var origSections = data.secoes || [];
            var html = '<input id="swal-title" class="swal2-input" placeholder="Título" value="'+escapeHtml(data.title)+'">';
            html += '<div id="sections-container" style="text-align:left; margin-top:8px;">';
            origSections.forEach(function(s,i){
                html += '<label style="font-weight:600; font-size:0.9rem;">Seção '+(i+1)+'</label>';
                html += '<input data-index="'+i+'" class="swal2-input section-title" placeholder="Título da seção" value="'+escapeHtml(s.titulo_secao_do_video || '')+'">';
                html += '<div style="margin-bottom:8px;"><small>Arquivo atual: '+(s.filename || '—')+'</small><br><input type="file" class="section-file" data-index="'+i+'" accept="video/*"></div>';
            });
            html += '</div>';

            Swal.fire({
                title: 'Editar Vídeo',
                html: html,
                showCancelButton: true,
                focusConfirm: false,
                preConfirm: () => {
                    return new Promise(function(resolve){
                        resolve();
                    });
                }
            }).then(function(result){
                if (!result.isConfirmed) return;
                // Gather updated data
                var newTitle = $('#swal-title').val();
                // initialize sections with existing data to preserve upload IDs
                var sections = origSections.map(function(s){ return { titulo_secao_do_video: s.titulo_secao_do_video || '', upload: s.upload || 0 }; });
                var filesToUpload = [];

                $('#sections-container .section-title').each(function(){
                    var idx = $(this).data('index');
                    var title = $(this).val();
                    sections[idx] = sections[idx] || {};
                    sections[idx].titulo_secao_do_video = title;
                });

                $('#sections-container .section-file').each(function(){
                    var idx = $(this).data('index');
                    var file = this.files[0];
                    if (file) {
                        filesToUpload.push({index: idx, file: file});
                    }
                });

                // If files to upload, upload them first
                var uploadPromises = filesToUpload.map(function(item){
                    var fd = new FormData();
                    fd.append('action','upload_video_file_dashboard');
                    fd.append('video_file', item.file);
                    fd.append('nonce', dashboardNonce);
                    return $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false
                    }).then(function(r){
                        if (!r || !r.success) return $.Deferred().reject(r);
                        return { index: item.index, attachment_id: r.data.attachment_id };
                    });
                });

                $.when.apply($, uploadPromises).done(function(){
                    var results = Array.prototype.slice.call(arguments);
                    // results may be array of objects
                    results.forEach(function(res){
                        sections[res.index].upload = res.attachment_id;
                    });

                    // For sections without upload set existing attachment IDs by fetching again
                    // Build final sections array
                    var finalSections = [];
                    for (var i=0;i<sections.length;i++){
                        var s = sections[i] || {};
                        if (!s.upload) {
                            // If not replaced, we need to fetch original upload id via API again
                            // For simplicity, keep original filename only (do not change upload)
                            // We'll request server to preserve original by not including upload
                        }
                        finalSections.push(s);
                    }

                    // Send update request: we will include only sections that were modified (title and upload)
                    $.post(ajaxurl, { action: 'update_video_dashboard', post_id: postId, title: newTitle, categoria_id: termId, secoes: JSON.stringify(finalSections), nonce: dashboardNonce }, function(resp){
                        if (!resp) return alert('Erro ao atualizar');
                        if (resp.success) {
                            fetchVideos($('#search-videos').val());
                        } else {
                            alert(resp.data && resp.data.message ? resp.data.message : 'Erro ao atualizar');
                        }
                    });

                }).fail(function(err){
                    alert('Erro no upload: ' + (err && err.data && err.data.message ? err.data.message : 'falha'));
                });

                // If no uploads, just send update
                if (uploadPromises.length === 0) {
                    var finalSections = sections; // may include only titles
                    $.post(ajaxurl, { action: 'update_video_dashboard', post_id: postId, title: newTitle, categoria_id: termId, secoes: JSON.stringify(finalSections), nonce: dashboardNonce }, function(resp){
                        if (!resp) return alert('Erro ao atualizar');
                        if (resp.success) {
                            fetchVideos($('#search-videos').val());
                        } else {
                            alert(resp.data && resp.data.message ? resp.data.message : 'Erro ao atualizar');
                        }
                    });
                }
            });
        });
    }

    function escapeHtml(text){
        if (!text) return '';
        return $('<div>').text(text).html();
    }

    $(function(){
        // initial fetch
        fetchVideos('');

        $('#search-videos').on('input', function(){
            var q = $(this).val();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function(){ fetchVideos(q); }, 300);
        });

        // Select all handling
        $(document).on('change', '#select-all-videos-tax', function(){
            var checked = this.checked;
            $('.video-checkbox').prop('checked', checked).trigger('change');
        });

        // Delete selected
        $(document).on('click', '#delete-selected-videos', function(e){
            e.preventDefault();
            if (selected.size === 0) return alert('Nenhum vídeo selecionado');
            deleteVideos(Array.from(selected));
        });
    });

})(jQuery);
