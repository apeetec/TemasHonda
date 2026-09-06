<?php
/**
 * Template Name: Resultados dos Usuários - Completo
 * 
 * Exibe TODOS os usuários e TODAS as respostas de TODAS as datas da SIPAT
 * Sistema dinâmico que busca automaticamente todas as taxonomias e perguntas
 */

get_header();

// Verifica permissão de administrador
if (!current_user_can('administrator')) {
    ?>
    <section class="container">
        <div class="card-panel red lighten-4">
            <p class="red-text text-darken-4">
                <strong>Acesso negado:</strong> Você não tem permissão para acessar esta página.
            </p>
        </div>
    </section>
    <?php
    get_footer();
    exit;
}

/**
 * Busca TODAS as datas (taxonomias) e suas perguntas dinamicamente
 */
function get_all_dates_and_questions() {
    $dates_config = [];
    
    // Busca todas as taxonomias 'datas_perguntas'
    $terms = get_terms([
        'taxonomy' => 'datas_perguntas',
        'hide_empty' => false,
        'parent' => 0, // Apenas categorias principais (não subcategorias)
        'orderby' => 'term_id',
        'order' => 'ASC'
    ]);
    
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $term_slug = sanitize_title($term->name);
            $term_name = $term->name;
            
            // Busca todas as perguntas desta data
            $questions = get_posts([
                'post_type' => 'perguntas',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'ID',
                'order' => 'ASC',
                'tax_query' => [
                    [
                        'taxonomy' => 'datas_perguntas',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                        'include_children' => false
                    ]
                ]
            ]);
            
            $questions_array = [];
            $question_counter = 1;
            
            foreach ($questions as $question) {
                $questions_array[] = [
                    'id' => $question->ID,
                    'label' => 'P' . $question_counter,
                    'title' => $question->post_title
                ];
                $question_counter++;
            }
            
            // Busca subcategorias (vídeos adicionais)
            $children = get_term_children($term->term_id, 'datas_perguntas');
            foreach ($children as $child_id) {
                $child_term = get_term($child_id, 'datas_perguntas');
                $child_slug = sanitize_title($child_term->name);
                
                // Busca perguntas da subcategoria
                $child_questions = get_posts([
                    'post_type' => 'perguntas',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'ID',
                    'order' => 'ASC',
                    'tax_query' => [
                        [
                            'taxonomy' => 'datas_perguntas',
                            'field' => 'term_id',
                            'terms' => $child_id,
                            'include_children' => false
                        ]
                    ]
                ]);
                
                foreach ($child_questions as $question) {
                    $questions_array[] = [
                        'id' => $question->ID,
                        'label' => 'P' . $question_counter,
                        'title' => $question->post_title,
                        'subcategory' => $child_term->name
                    ];
                    $question_counter++;
                }
            }
            
            if (!empty($questions_array)) {
                $dates_config[$term_slug] = [
                    'label' => $term_name,
                    'term_id' => $term->term_id,
                    'questions' => $questions_array
                ];
            }
        }
    }
    
    return $dates_config;
}

/**
 * Busca resposta do usuário para uma pergunta específica
 */
function get_user_answer_dynamic($user_id, $date_slug, $question_id) {
    $meta_key = "user_field_{$date_slug}_{$question_id}";
    $answer = get_user_meta($user_id, $meta_key, true);
    return !empty($answer) ? esc_html($answer) : '-';
}

/**
 * Verifica se o usuário respondeu pelo menos uma pergunta
 */
function user_has_answers_dynamic($user_id, $dates_config) {
    foreach ($dates_config as $date_slug => $date_data) {
        foreach ($date_data['questions'] as $question) {
            $meta_key = "user_field_{$date_slug}_{$question['id']}";
            $answer = get_user_meta($user_id, $meta_key, true);
            if (!empty($answer)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Conta total de perguntas
 */
function count_total_questions($dates_config) {
    $total = 0;
    foreach ($dates_config as $date_data) {
        $total += count($date_data['questions']);
    }
    return $total;
}

// Busca configuração dinâmica
$dates_config = get_all_dates_and_questions();

// Busca todos os usuários com papel 'subscriber'
$users = get_users(['role' => 'subscriber', 'orderby' => 'display_name', 'order' => 'ASC']);
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">

<style>
    .results-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: 8px;
        color: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .results-header h1 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
    }
    
    .results-stats {
        display: flex;
        gap: 2rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    
    .stat-item {
        background: rgba(255,255,255,0.2);
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
    }
    
    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
        display: block;
    }
    
    .stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    .table-container {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow-x: auto;
    }
    
    table.dataTable {
        font-size: 0.85rem;
    }
    
    table.dataTable thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 10px 6px;
        font-size: 0.8rem;
        white-space: nowrap;
    }
    
    table.dataTable tbody td {
        padding: 8px 6px;
        vertical-align: middle;
    }
    
    .day-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        font-weight: 700;
        font-size: 0.9rem;
        text-align: center !important;
    }
    
    .question-header {
        background-color: #e9ecef;
        font-weight: 600;
        text-align: center !important;
    }
    
    .user-info {
        background-color: #f8f9fa !important;
        font-weight: 600;
    }
    
    .no-answer {
        color: #6c757d;
        font-style: italic;
    }
    
    .has-answer {
        color: #28a745;
        font-weight: 500;
    }
    
    .dt-buttons {
        margin-bottom: 1rem;
    }
    
    .dt-button {
        background: #667eea !important;
        color: white !important;
        border: none !important;
        padding: 8px 16px !important;
        margin-right: 8px !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        transition: background 0.3s ease !important;
    }
    
    .dt-button:hover {
        background: #5568d3 !important;
    }
    
    .dates-info {
        background: #e7f3ff;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }
    
    .dates-list {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
    }
    
    .date-badge {
        background: white;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        font-weight: 600;
    }
    
    .filter-controls {
        background: white;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 0.6rem 1.5rem;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }
    
    .filter-btn:hover {
        background: #f0f4ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.2);
    }
    
    .filter-btn.active {
        background: #667eea;
        color: white;
    }
    
    .filter-label {
        font-weight: 600;
        color: #333;
    }
</style>

<section class="container">
    <div class="results-header">
        <h1>📊 Resultados Completos SIPAT - Todas as Datas e Perguntas</h1>
        <div class="results-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($users); ?></span>
                <span class="stat-label">Total de Usuários</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php 
                    $users_with_answers = 0;
                    foreach ($users as $user) {
                        if (user_has_answers_dynamic($user->ID, $dates_config)) {
                            $users_with_answers++;
                        }
                    }
                    echo $users_with_answers;
                    ?>
                </span>
                <span class="stat-label">Com Respostas</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count($dates_config); ?></span>
                <span class="stat-label">Datas Diferentes</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count_total_questions($dates_config); ?></span>
                <span class="stat-label">Total de Perguntas</span>
            </div>
        </div>
    </div>

    <?php if (!empty($dates_config)): ?>
    <div class="dates-info">
        <strong>📅 Datas Incluídas:</strong>
        <div class="dates-list">
            <?php foreach ($dates_config as $date_data): ?>
                <div class="date-badge">
                    <?php echo esc_html($date_data['label']); ?> 
                    <span style="color: #667eea;">(<?php echo count($date_data['questions']); ?> perguntas)</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="filter-controls">
        <span class="filter-label">🔍 Filtrar Usuários:</span>
        <button class="filter-btn active" data-filter="todos" onclick="filtrarUsuarios('todos')">
            📋 Todos os Usuários
        </button>
        <button class="filter-btn" data-filter="com-respostas" onclick="filtrarUsuarios('com-respostas')">
            ✅ Apenas com Respostas
        </button>
        <button class="filter-btn" data-filter="sem-respostas" onclick="filtrarUsuarios('sem-respostas')">
            ❌ Apenas sem Respostas
        </button>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table id="consultar_usuarios" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th rowspan="2" class="user-info">Matrícula</th>
                    <th rowspan="2" class="user-info">Nome</th>
                    <?php foreach ($dates_config as $date_slug => $date_data): ?>
                        <th colspan="<?php echo count($date_data['questions']); ?>" class="day-header">
                            <?php echo esc_html($date_data['label']); ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php foreach ($dates_config as $date_slug => $date_data): ?>
                        <?php foreach ($date_data['questions'] as $question): ?>
                            <th class="question-header" title="<?php echo esc_attr($question['title']); ?>">
                                <?php echo esc_html($question['label']); ?>
                            </th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php 
                    // Verifica se o usuário tem respostas
                    $user_has_answer = user_has_answers_dynamic($user->ID, $dates_config);
                    $row_class = $user_has_answer ? 'user-with-answers' : 'user-without-answers';
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="user-info"><?php echo esc_html(str_replace('.', '_', $user->user_login)); ?></td>
                        <td class="user-info"><?php echo esc_html(get_user_meta($user->ID, 'first_name', true) ?: $user->display_name); ?></td>
                        
                        <?php foreach ($dates_config as $date_slug => $date_data): ?>
                            <?php foreach ($date_data['questions'] as $question): ?>
                                <td>
                                    <?php 
                                    $answer = get_user_answer_dynamic($user->ID, $date_slug, $question['id']);
                                    if ($answer === '-') {
                                        echo '<span class="no-answer">-</span>';
                                    } else {
                                        echo '<span class="has-answer">' . $answer . '</span>';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php get_footer(); ?>

<!-- DataTables e extensões -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>

<script>
jQuery(document).ready(function($) {
    // Configuração do DataTable
    var table = $('#consultar_usuarios').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: '📋 Copiar',
                title: 'Resultados_SIPAT_Completo_' + new Date().toLocaleDateString('pt-BR'),
                exportOptions: {
                    orthogonal: 'export',
                    columns: ':visible'
                }
            },
            {
                extend: 'excel',
                text: '📊 Excel',
                title: 'Resultados_SIPAT_Completo_' + new Date().toISOString().split('T')[0],
                filename: 'SIPAT_Resultados_Completo_' + new Date().toISOString().split('T')[0],
                exportOptions: {
                    orthogonal: 'export',
                    columns: ':visible'
                }
            },
            {
                extend: 'csv',
                text: '📄 CSV',
                title: 'Resultados_SIPAT_Completo_' + new Date().toISOString().split('T')[0],
                filename: 'SIPAT_Resultados_Completo_' + new Date().toISOString().split('T')[0],
                charset: 'utf-8',
                bom: true,
                exportOptions: {
                    orthogonal: 'export',
                    columns: ':visible'
                }
            },
            {
                extend: 'pdf',
                text: '📕 PDF',
                title: 'Resultados Completos SIPAT',
                orientation: 'landscape',
                pageSize: 'A2', // Aumentado para A2 devido ao número de colunas
                exportOptions: {
                    orthogonal: 'export',
                    columns: ':visible'
                },
                customize: function(doc) {
                    doc.defaultStyle.fontSize = 7;
                    doc.styles.tableHeader.fontSize = 8;
                    doc.styles.tableHeader.bold = true;
                    doc.styles.tableHeader.fillColor = '#667eea';
                    doc.styles.title.fontSize = 14;
                    doc.styles.title.bold = true;
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('auto');
                }
            },
            {
                extend: 'print',
                text: '🖨️ Imprimir',
                title: 'Resultados Completos SIPAT - ' + new Date().toLocaleDateString('pt-BR'),
                exportOptions: {
                    columns: ':visible'
                }
            }
        ],
        scrollX: true,
        scrollCollapse: true,
        fixedColumns: {
            leftColumns: 2 // Fixa as colunas de Matrícula e Nome
        },
        responsive: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json',
            buttons: {
                copyTitle: 'Copiado!',
                copySuccess: {
                    _: '%d linhas copiadas',
                    1: '1 linha copiada'
                }
            }
        },
        lengthMenu: [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, 'Todos']],
        pageLength: 50,
        order: [[1, 'asc']], // Ordena por nome
        columnDefs: [
            {
                targets: '_all',
                className: 'dt-center'
            },
            {
                targets: [0, 1], // Matrícula e Nome
                className: 'dt-left'
            }
        ],
        initComplete: function() {
            console.log('✅ DataTable inicializada com sucesso');
            console.log('📊 Total de registros: ' + this.api().data().length);
            console.log('📋 Total de colunas: ' + this.api().columns().count());
            
            // Adiciona informação sobre colunas fixas
            $('.table-container').prepend(
                '<div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 0.9rem;">' +
                '<strong>💡 Dica:</strong> As colunas de Matrícula e Nome ficam fixas ao rolar horizontalmente para facilitar a visualização.' +
                '</div>'
            );
        }
    });
    
    // Função de filtro global
    window.filtrarUsuarios = function(filtro) {
        // Atualiza aparência dos botões
        $('.filter-btn').removeClass('active');
        $('.filter-btn[data-filter="' + filtro + '"]').addClass('active');
        
        if (filtro === 'todos') {
            // Mostra todos
            table.search('').columns().search('').draw();
            
        } else if (filtro === 'com-respostas') {
            // Usa filtro customizado para mostrar apenas com respostas
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var row = table.row(dataIndex).node();
                    return $(row).hasClass('user-with-answers');
                }
            );
            table.draw();
            $.fn.dataTable.ext.search.pop();
            
        } else if (filtro === 'sem-respostas') {
            // Usa filtro customizado para mostrar apenas sem respostas
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var row = table.row(dataIndex).node();
                    return $(row).hasClass('user-without-answers');
                }
            );
            table.draw();
            $.fn.dataTable.ext.search.pop();
        }
        
        // Mostra todos os resultados na mesma página quando filtrado
        if (filtro !== 'todos') {
            var pageInfo = table.page.info();
            var filteredCount = pageInfo.recordsDisplay;
            
            // Se tiver menos de 500 registros filtrados, mostra todos
            if (filteredCount <= 500) {
                table.page.len(-1).draw('page');
            } else {
                table.page.len(100).draw('page');
            }
            
            console.log('Filtro aplicado: ' + filtro + ' - Total de registros: ' + filteredCount);
        } else {
            // Volta para exibição de 50 registros por página
            table.page.len(50).draw('page');
        }
    };
    
    // Adiciona contador de respostas
    var totalUsers = table.rows().count();
    console.log('Total de usuários: ' + totalUsers);
});
</script>
