<?php
/**
 * ============================================================================
 * Template: Relatório de Usuários
 * ============================================================================
 *
 * Exibe respostas e pontuações de todos os subscribers em tabelas
 * com paginação, busca e exportação para Excel (via SheetJS).
 *
 * Abas:
 * 1. Respostas — respostas de perguntas por data (dinâmico)
 * 2. Pontuações — Simon Says (pontuação, tempo, ranking, tentativas)
 *
 * ACESSO: Somente administradores.
 * Arquivo: pages/template-relatorio.php
 * ============================================================================
 */

/* Template Name: Relatorio de Usuarios */

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

// ============================================================================
// DADOS: Termos (datas) e Perguntas para montar colunas dinâmicas
// ============================================================================
$terms = get_terms(array(
    'taxonomy' => 'datas_perguntas',
    'hide_empty' => false,
));

$datas_info = array();
foreach ($terms as $term) {
    $slug = $term->slug;
    $nome = $term->name;

    // Perguntas dessa data
    $perguntas = get_posts(array(
        'post_type' => 'perguntas',
        'posts_per_page' => -1,
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'datas_perguntas',
                'field' => 'slug',
                'terms' => $slug,
            )
        )
    ));

    $pergs = array();
    foreach ($perguntas as $p) {
        $pergs[] = array('id' => $p->ID, 'titulo' => $p->post_title);
    }

    $datas_info[] = array(
        'slug' => $slug,
        'nome' => $nome,
        'perguntas' => $pergs,
    );
}

// ============================================================================
// DADOS: Todos os subscribers
// ============================================================================
$users = get_users(array('role' => 'subscriber', 'orderby' => 'display_name', 'order' => 'ASC'));
$total_users = count($users);
?>

<style>
    .relatorio-page { max-width: 100%; margin: 30px auto; padding: 0 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .relatorio-page h1 { font-size: 26px; margin-bottom: 6px; color: #333; }
    .relatorio-page .subtitle { color: #666; margin-bottom: 20px; font-size: 14px; }

    /* Abas */
    .tab-nav { display: flex; gap: 0; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
    .tab-nav button { padding: 12px 24px; border: none; background: #f5f5f5; cursor: pointer; font-size: 14px; font-weight: 600; color: #666; border-radius: 6px 6px 0 0; transition: all 0.2s; }
    .tab-nav button.active { background: #fff; color: #2c3e50; border: 2px solid #ddd; border-bottom: 2px solid #fff; margin-bottom: -2px; }
    .tab-nav button:hover:not(.active) { background: #eee; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* Toolbar */
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    .toolbar .info { font-size: 13px; color: #888; }
    .btn-export { padding: 8px 20px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; background: #27ae60; color: #fff; transition: all 0.2s; }
    .btn-export:hover { background: #219a52; }
    .btn-export i { margin-right: 6px; }

    /* Tabelas */
    .table-wrapper { overflow-x: auto; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
    table.relatorio { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.relatorio thead { background: #2c3e50; color: #fff; position: sticky; top: 0; z-index: 10; }
    table.relatorio thead th { padding: 10px 12px; text-align: left; white-space: nowrap; font-weight: 600; font-size: 12px; }
    table.relatorio tbody tr { border-bottom: 1px solid #eee; }
    table.relatorio tbody tr:hover { background: #f0f8ff; }
    table.relatorio tbody td { padding: 8px 12px; white-space: nowrap; color: #444; }
    table.relatorio tbody td.acertou { background: #d4edda; color: #155724; }
    table.relatorio tbody td.errou { background: #f8d7da; color: #721c24; }
    table.relatorio tbody td.vazio { color: #ccc; }
    table.relatorio tbody td.sim { color: #27ae60; font-weight: 600; }
    table.relatorio tbody td.nao { color: #999; }

    /* Grupo de colunas */
    table.relatorio thead th.grupo-header { background: #34495e; text-align: center; border-left: 2px solid #2c3e50; }
    table.relatorio thead th:first-child { border-radius: 8px 0 0 0; }
    table.relatorio thead th:last-child { border-radius: 0 8px 0 0; }

    /* Paginação */
    .pagination-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; flex-wrap: wrap; gap: 10px; }
    .pagination-info { font-size: 13px; color: #666; }
    .pagination-controls { display: flex; gap: 4px; }
    .pagination-controls button { padding: 6px 12px; border: 1px solid #ddd; background: #fff; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.15s; }
    .pagination-controls button:hover:not(:disabled) { background: #2c3e50; color: #fff; }
    .pagination-controls button.active { background: #2c3e50; color: #fff; border-color: #2c3e50; }
    .pagination-controls button:disabled { opacity: 0.4; cursor: not-allowed; }

    .per-page-select { padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }

    /* Busca */
    .search-box { padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; width: 260px; }
    .search-box:focus { outline: none; border-color: #2c3e50; }

    /* Loading */
    .loading-overlay { text-align: center; padding: 60px; color: #999; font-size: 15px; }
</style>

<section class="relatorio-page">
    <h1><i class="fa-solid fa-chart-bar"></i> Relatório de Usuários</h1>
    <p class="subtitle"><?php echo $total_users; ?> usuários cadastrados (subscribers)</p>

    <!-- ABAS -->
    <div class="tab-nav">
        <button class="active" data-tab="tab-respostas">Respostas de Perguntas</button>
        <button data-tab="tab-pontuacoes">Pontuações (Simon Says)</button>
    </div>

    <!-- ================================================================ -->
    <!-- ABA 1: RESPOSTAS DE PERGUNTAS                                    -->
    <!-- ================================================================ -->
    <div class="tab-content active" id="tab-respostas">
        <div class="toolbar">
            <div>
                <input type="text" class="search-box" id="search-respostas" placeholder="Buscar por nome, matrícula ou unidade...">
                <select class="per-page-select" id="perpage-respostas">
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                    <option value="all">Todos</option>
                </select>
            </div>
            <button class="btn-export" onclick="exportToExcel('respostas')">
                <i class="fa-solid fa-file-excel"></i> Exportar Excel
            </button>
        </div>
        <div class="table-wrapper" style="max-height: 70vh; overflow-y: auto;">
            <table class="relatorio" id="table-respostas">
                <thead>
                    <tr>
                        <th rowspan="2">Matrícula</th>
                        <th rowspan="2">Nome</th>
                        <th rowspan="2">Unidade</th>
                        <?php foreach ($datas_info as $data): ?>
                            <?php
                            $colspan = count($data['perguntas']) + 4; // perguntas + presencial + video + respondeu + acertou
                            ?>
                            <th class="grupo-header" colspan="<?php echo $colspan; ?>"><?php echo esc_html($data['nome']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($datas_info as $data): ?>
                            <th>Presencial</th>
                            <th>Vídeo</th>
                            <th>Respondeu</th>
                            <th>Acertou tudo</th>
                            <?php foreach ($data['perguntas'] as $perg): ?>
                                <th title="<?php echo esc_attr($perg['titulo']); ?>">
                                    <?php echo esc_html(mb_strimwidth($perg['titulo'], 0, 30, '...')); ?>
                                </th>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody id="tbody-respostas">
                    <tr><td colspan="999" class="loading-overlay">Carregando dados...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper" id="pagination-respostas"></div>
    </div>

    <!-- ================================================================ -->
    <!-- ABA 2: PONTUAÇÕES (SIMON SAYS)                                   -->
    <!-- ================================================================ -->
    <div class="tab-content" id="tab-pontuacoes">
        <div class="toolbar">
            <div>
                <input type="text" class="search-box" id="search-pontuacoes" placeholder="Buscar por nome, matrícula ou unidade...">
                <select class="per-page-select" id="perpage-pontuacoes">
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                    <option value="all">Todos</option>
                </select>
            </div>
            <button class="btn-export" onclick="exportToExcel('pontuacoes')">
                <i class="fa-solid fa-file-excel"></i> Exportar Excel
            </button>
        </div>
        <div class="table-wrapper" style="max-height: 70vh; overflow-y: auto;">
            <table class="relatorio" id="table-pontuacoes">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nome</th>
                        <th>Unidade</th>
                        <th>Pontuação</th>
                        <th>Tempo</th>
                        <th>Ranking</th>
                        <th>Tentativas Hoje</th>
                        <th>Tentativas Usadas</th>
                        <th>Última Tentativa</th>
                        <th>Data Partida</th>
                        <th>Horário</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody id="tbody-pontuacoes">
                    <tr><td colspan="12" class="loading-overlay">Carregando dados...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper" id="pagination-pontuacoes"></div>
    </div>
</section>

<!-- SheetJS para exportação Excel -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<script>
(function() {
    var ajaxUrl = '<?php echo esc_url(admin_url("admin-ajax.php")); ?>';
    var nonce = '<?php echo wp_create_nonce("relatorio_usuarios_nonce"); ?>';

    // ================================================================
    // SISTEMA DE ABAS
    // ================================================================
    var tabButtons = document.querySelectorAll('.tab-nav button');
    for (var i = 0; i < tabButtons.length; i++) {
        tabButtons[i].addEventListener('click', function() {
            for (var j = 0; j < tabButtons.length; j++) tabButtons[j].classList.remove('active');
            this.classList.add('active');
            var tabs = document.querySelectorAll('.tab-content');
            for (var j = 0; j < tabs.length; j++) tabs[j].classList.remove('active');
            document.getElementById(this.getAttribute('data-tab')).classList.add('active');
        });
    }

    // ================================================================
    // ESTADO DAS TABELAS
    // ================================================================
    var state = {
        respostas: { data: null, page: 1, perPage: 25, search: '' },
        pontuacoes: { data: null, page: 1, perPage: 25, search: '' }
    };

    // ================================================================
    // CARREGAMENTO INICIAL
    // ================================================================
    loadData('respostas');
    loadData('pontuacoes');

    // ================================================================
    // BUSCA E POR-PÁGINA
    // ================================================================
    var searchTimer;
    document.getElementById('search-respostas').addEventListener('input', function() {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function() {
            state.respostas.search = val.toLowerCase();
            state.respostas.page = 1;
            renderTable('respostas');
        }, 300);
    });
    document.getElementById('search-pontuacoes').addEventListener('input', function() {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function() {
            state.pontuacoes.search = val.toLowerCase();
            state.pontuacoes.page = 1;
            renderTable('pontuacoes');
        }, 300);
    });
    document.getElementById('perpage-respostas').addEventListener('change', function() {
        state.respostas.perPage = this.value === 'all' ? 999999 : parseInt(this.value);
        state.respostas.page = 1;
        renderTable('respostas');
    });
    document.getElementById('perpage-pontuacoes').addEventListener('change', function() {
        state.pontuacoes.perPage = this.value === 'all' ? 999999 : parseInt(this.value);
        state.pontuacoes.page = 1;
        renderTable('pontuacoes');
    });

    // ================================================================
    // CARREGAR DADOS VIA AJAX
    // ================================================================
    function loadData(tipo) {
        var formData = new FormData();
        formData.append('action', 'relatorio_get_data');
        formData.append('nonce', nonce);
        formData.append('tipo', tipo);

        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    state[tipo].data = data.data.rows;
                    renderTable(tipo);
                } else {
                    document.getElementById('tbody-' + tipo).innerHTML =
                        '<tr><td colspan="999" style="text-align:center;color:#e74c3c;">Erro: ' + (data.data ? data.data.message : 'desconhecido') + '</td></tr>';
                }
            })
            .catch(function(err) {
                document.getElementById('tbody-' + tipo).innerHTML =
                    '<tr><td colspan="999" style="text-align:center;color:#e74c3c;">Erro de conexão: ' + err.message + '</td></tr>';
            });
    }

    // ================================================================
    // FILTRO
    // ================================================================
    function filterData(tipo) {
        var rows = state[tipo].data || [];
        var search = state[tipo].search;
        if (!search) return rows;
        return rows.filter(function(r) {
            var searchable = (r.matricula + ' ' + r.nome + ' ' + r.unidade).toLowerCase();
            return searchable.indexOf(search) !== -1;
        });
    }

    // ================================================================
    // RENDERIZAR TABELA
    // ================================================================
    function renderTable(tipo) {
        var filtered = filterData(tipo);
        var perPage = state[tipo].perPage;
        var page = state[tipo].page;
        var total = filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / perPage));
        if (page > totalPages) page = totalPages;
        state[tipo].page = page;

        var start = (page - 1) * perPage;
        var pageRows = filtered.slice(start, start + perPage);

        var tbody = document.getElementById('tbody-' + tipo);
        var html = '';

        if (pageRows.length === 0) {
            var cols = tipo === 'pontuacoes' ? 12 : 999;
            html = '<tr><td colspan="' + cols + '" style="text-align:center;padding:40px;color:#999;">Nenhum registro encontrado.</td></tr>';
        } else if (tipo === 'respostas') {
            for (var i = 0; i < pageRows.length; i++) {
                var r = pageRows[i];
                html += '<tr>';
                html += '<td>' + esc(r.matricula) + '</td>';
                html += '<td>' + esc(r.nome) + '</td>';
                html += '<td>' + esc(r.unidade) + '</td>';
                for (var d = 0; d < r.datas.length; d++) {
                    var dt = r.datas[d];
                    html += '<td class="' + (dt.presencial === 'Presencial' ? 'sim' : 'nao') + '">' + esc(dt.presencial || '-') + '</td>';
                    html += '<td class="' + (dt.video ? 'sim' : 'nao') + '">' + (dt.video ? 'Sim' : 'Não') + '</td>';
                    html += '<td class="' + (dt.respondeu ? 'sim' : 'nao') + '">' + (dt.respondeu ? 'Sim' : 'Não') + '</td>';
                    html += '<td class="' + (dt.acertou ? 'acertou' : (dt.respondeu ? 'errou' : 'nao')) + '">' + (dt.acertou ? 'Sim' : (dt.respondeu ? 'Não' : '-')) + '</td>';
                    for (var p = 0; p < dt.respostas.length; p++) {
                        var resp = dt.respostas[p];
                        var cls = resp ? '' : 'vazio';
                        html += '<td class="' + cls + '">' + esc(resp || '-') + '</td>';
                    }
                }
                html += '</tr>';
            }
        } else {
            for (var i = 0; i < pageRows.length; i++) {
                var r = pageRows[i];
                html += '<tr>';
                html += '<td>' + esc(r.matricula) + '</td>';
                html += '<td>' + esc(r.nome) + '</td>';
                html += '<td>' + esc(r.unidade) + '</td>';
                html += '<td><strong>' + esc(r.pontuacao || '-') + '</strong></td>';
                html += '<td>' + esc(r.tempo || '-') + '</td>';
                html += '<td>' + esc(r.ranking || '-') + '</td>';
                html += '<td>' + esc(r.tentativas_rest || '-') + '</td>';
                html += '<td>' + esc(r.tentativas_usadas || '-') + '</td>';
                html += '<td>' + esc(r.ultima_tentativa || '-') + '</td>';
                html += '<td>' + esc(r.data_partida || '-') + '</td>';
                html += '<td>' + esc(r.horario || '-') + '</td>';
                html += '<td>' + esc(r.ip || '-') + '</td>';
                html += '</tr>';
            }
        }

        tbody.innerHTML = html;
        renderPagination(tipo, total, totalPages, page);
    }

    // ================================================================
    // PAGINAÇÃO
    // ================================================================
    function renderPagination(tipo, total, totalPages, page) {
        var wrapper = document.getElementById('pagination-' + tipo);
        var perPage = state[tipo].perPage;
        var start = (page - 1) * perPage + 1;
        var end = Math.min(page * perPage, total);

        var html = '<div class="pagination-info">Mostrando ' + start + '-' + end + ' de ' + total + '</div>';
        html += '<div class="pagination-controls">';
        html += '<button ' + (page <= 1 ? 'disabled' : '') + ' data-tipo="' + tipo + '" data-page="1">&laquo;</button>';
        html += '<button ' + (page <= 1 ? 'disabled' : '') + ' data-tipo="' + tipo + '" data-page="' + (page - 1) + '">&lsaquo;</button>';

        var startPage = Math.max(1, page - 2);
        var endPage = Math.min(totalPages, page + 2);
        for (var p = startPage; p <= endPage; p++) {
            html += '<button class="' + (p === page ? 'active' : '') + '" data-tipo="' + tipo + '" data-page="' + p + '">' + p + '</button>';
        }

        html += '<button ' + (page >= totalPages ? 'disabled' : '') + ' data-tipo="' + tipo + '" data-page="' + (page + 1) + '">&rsaquo;</button>';
        html += '<button ' + (page >= totalPages ? 'disabled' : '') + ' data-tipo="' + tipo + '" data-page="' + totalPages + '">&raquo;</button>';
        html += '</div>';

        wrapper.innerHTML = html;

        // Bind pagination clicks
        var btns = wrapper.querySelectorAll('button');
        for (var i = 0; i < btns.length; i++) {
            btns[i].addEventListener('click', function() {
                if (this.disabled) return;
                var t = this.getAttribute('data-tipo');
                var pg = parseInt(this.getAttribute('data-page'));
                state[t].page = pg;
                renderTable(t);
                // Scroll to top of table
                document.getElementById('table-' + t).scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    // ================================================================
    // EXPORTAR EXCEL (SheetJS)
    // ================================================================
    window.exportToExcel = function(tipo) {
        var filtered = filterData(tipo);
        if (!filtered || filtered.length === 0) {
            alert('Nenhum dado para exportar.');
            return;
        }

        var wsData = [];

        if (tipo === 'pontuacoes') {
            wsData.push(['Matrícula', 'Nome', 'Unidade', 'Pontuação', 'Tempo', 'Ranking', 'Tentativas Restantes', 'Tentativas Usadas', 'Última Tentativa', 'Data Partida', 'Horário', 'IP']);
            for (var i = 0; i < filtered.length; i++) {
                var r = filtered[i];
                wsData.push([r.matricula, r.nome, r.unidade, r.pontuacao || '', r.tempo || '', r.ranking || '', r.tentativas_rest || '', r.tentativas_usadas || '', r.ultima_tentativa || '', r.data_partida || '', r.horario || '', r.ip || '']);
            }
        } else {
            // Monta header dinâmico
            var header1 = ['Matrícula', 'Nome', 'Unidade'];
            var datasInfo = <?php echo json_encode($datas_info); ?>;
            for (var d = 0; d < datasInfo.length; d++) {
                header1.push(datasInfo[d].nome + ' - Presencial');
                header1.push(datasInfo[d].nome + ' - Vídeo');
                header1.push(datasInfo[d].nome + ' - Respondeu');
                header1.push(datasInfo[d].nome + ' - Acertou tudo');
                for (var p = 0; p < datasInfo[d].perguntas.length; p++) {
                    header1.push(datasInfo[d].nome + ' - ' + datasInfo[d].perguntas[p].titulo);
                }
            }
            wsData.push(header1);

            for (var i = 0; i < filtered.length; i++) {
                var r = filtered[i];
                var row = [r.matricula, r.nome, r.unidade];
                for (var d = 0; d < r.datas.length; d++) {
                    var dt = r.datas[d];
                    row.push(dt.presencial || '');
                    row.push(dt.video ? 'Sim' : 'Não');
                    row.push(dt.respondeu ? 'Sim' : 'Não');
                    row.push(dt.acertou ? 'Sim' : 'Não');
                    for (var p = 0; p < dt.respostas.length; p++) {
                        row.push(dt.respostas[p] || '');
                    }
                }
                wsData.push(row);
            }
        }

        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.aoa_to_sheet(wsData);

        // Auto-width columns
        var colWidths = [];
        for (var c = 0; c < wsData[0].length; c++) {
            var maxLen = 10;
            for (var r = 0; r < Math.min(wsData.length, 100); r++) {
                var cell = wsData[r][c];
                if (cell && String(cell).length > maxLen) maxLen = String(cell).length;
            }
            colWidths.push({ wch: Math.min(maxLen + 2, 50) });
        }
        ws['!cols'] = colWidths;

        var sheetName = tipo === 'pontuacoes' ? 'Pontuações' : 'Respostas';
        XLSX.utils.book_append_sheet(wb, ws, sheetName);

        var filename = 'relatorio-' + tipo + '-' + new Date().toISOString().slice(0, 10) + '.xlsx';
        XLSX.writeFile(wb, filename);
    };

    // Escape HTML
    function esc(s) {
        if (!s && s !== 0) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML;
    }

})();
</script>

<?php get_footer(); ?>
