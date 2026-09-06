/**
 * Dashboard Central - Funções AJAX
 * Carrega KPIs, atividade recente, alertas e gráficos
 */

(function($) {
    'use strict';

    /**
     * Inicialização do dashboard
     */
    $(document).ready(function() {
        loadKPIs();
        loadAlerts();
        loadRecentActivity();
        loadChart();
    });

    /**
     * Carrega KPIs via AJAX
     */
    function loadKPIs() {
        $.ajax({
            url: dashboard_central_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_dashboard_kpis',
                nonce: dashboard_central_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderKPIs(response.data);
                } else {
                    showKPIError();
                }
            },
            error: function() {
                showKPIError();
            }
        });
    }

    /**
     * Renderiza os KPI cards
     */
    function renderKPIs(data) {
        const container = $('#dashboard-kpis-grid');
        
        let html = '';
        
        // KPI 1: Total de Usuários
        html += `
            <div class="kpi-card kpi-usuarios">
                <div class="kpi-icon">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-value">${data.total_users || 0}</div>
                    <div class="kpi-label">Total de Usuários</div>
                </div>
            </div>
        `;

        // KPI 2: Total de Vídeos
        html += `
            <div class="kpi-card kpi-videos">
                <div class="kpi-icon">
                    <i class="fas fa-video" aria-hidden="true"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-value">${data.total_videos || 0}</div>
                    <div class="kpi-label">Total de Vídeos</div>
                </div>
            </div>
        `;

        // KPI 3: Total de Categorias
        html += `
            <div class="kpi-card kpi-categorias">
                <div class="kpi-icon">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-value">${data.total_categorias || 0}</div>
                    <div class="kpi-label">Total de Categorias</div>
                </div>
            </div>
        `;

        // KPI 4: Unidades (Top 3)
        const unidadesHtml = data.top_unidades && data.top_unidades.length > 0
            ? data.top_unidades.map(u => `<div class="kpi-unidade-item"><span class="unidade-name">${escapeHtml(u.name)}</span> <span class="unidade-count">${u.count}</span></div>`).join('')
            : '<div class="kpi-unidade-item"><span class="text-muted">Nenhuma unidade</span></div>';
        
        html += `
            <div class="kpi-card kpi-unidades">
                <div class="kpi-icon">
                    <i class="fas fa-building" aria-hidden="true"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Usuários por Unidade</div>
                    <div class="kpi-unidades-list">
                        ${unidadesHtml}
                    </div>
                </div>
            </div>
        `;

        container.html(html);
        
        // Animação GSAP suave nos cards - com delay para garantir renderização
        if (typeof gsap !== 'undefined') {
            setTimeout(function() {
                gsap.from('.kpi-card', {
                    duration: 0.6,
                    y: 30,
                    opacity: 0,
                    stagger: 0.1,
                    ease: 'power2.out',
                    clearProps: 'all' // Limpa propriedades após animação
                });
            }, 50);
        }
    }

    /**
     * Carrega alertas do sistema
     */
    function loadAlerts() {
        $.ajax({
            url: dashboard_central_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_dashboard_alerts',
                nonce: dashboard_central_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderAlerts(response.data);
                } else {
                    showAlertsError();
                }
            },
            error: function() {
                showAlertsError();
            }
        });
    }

    /**
     * Renderiza alertas
     */
    function renderAlerts(data) {
        const container = $('#dashboard-alerts-grid');
        let html = '';

        // Vídeos sem categoria
        const videosClass = data.videos_sem_categoria > 0 ? 'alert-warning' : 'alert-success';
        const videosIcon = data.videos_sem_categoria > 0 ? 'fa-exclamation-circle' : 'fa-check-circle';
        html += `
            <div class="alert-card ${videosClass} alert-clickable" data-alert-type="videos_sem_categoria">
                <i class="fas ${videosIcon}" aria-hidden="true"></i>
                <div class="alert-content">
                    <strong>${data.videos_sem_categoria || 0}</strong>
                    <span>Vídeo(s) sem categoria</span>
                </div>
                ${data.videos_sem_categoria > 0 ? '<i class="fas fa-chevron-right alert-arrow" aria-hidden="true"></i>' : ''}
            </div>
        `;

        // Categorias vazias
        const catClass = data.categorias_vazias > 0 ? 'alert-info' : 'alert-success';
        const catIcon = data.categorias_vazias > 0 ? 'fa-folder-open' : 'fa-check-circle';
        html += `
            <div class="alert-card ${catClass} alert-clickable" data-alert-type="categorias_vazias">
                <i class="fas ${catIcon}" aria-hidden="true"></i>
                <div class="alert-content">
                    <strong>${data.categorias_vazias || 0}</strong>
                    <span>Categoria(s) vazia(s)</span>
                </div>
                ${data.categorias_vazias > 0 ? '<i class="fas fa-chevron-right alert-arrow" aria-hidden="true"></i>' : ''}
            </div>
        `;

        // Usuários sem unidade
        const userClass = data.usuarios_sem_unidade > 0 ? 'alert-warning' : 'alert-success';
        const userIcon = data.usuarios_sem_unidade > 0 ? 'fa-user-times' : 'fa-check-circle';
        html += `
            <div class="alert-card ${userClass} alert-clickable" data-alert-type="usuarios_sem_unidade">
                <i class="fas ${userIcon}" aria-hidden="true"></i>
                <div class="alert-content">
                    <strong>${data.usuarios_sem_unidade || 0}</strong>
                    <span>Usuário(s) sem unidade</span>
                </div>
                ${data.usuarios_sem_unidade > 0 ? '<i class="fas fa-chevron-right alert-arrow" aria-hidden="true"></i>' : ''}
            </div>
        `;

        container.html(html);

        // Adicionar eventos de clique nos alertas
        $('.alert-clickable').on('click', function() {
            const alertType = $(this).data('alert-type');
            const count = parseInt($(this).find('strong').text());
            
            if (count > 0) {
                showAlertDetails(alertType);
            }
        });

        // Animação GSAP - com delay para garantir renderização
        if (typeof gsap !== 'undefined') {
            setTimeout(function() {
                gsap.from('.alert-card', {
                    duration: 0.5,
                    scale: 0.9,
                    opacity: 0,
                    stagger: 0.1,
                    ease: 'back.out(1.7)',
                    clearProps: 'all'
                });
            }, 50);
        }
    }

    /**
     * Exibe detalhes do alerta em SweetAlert
     */
    function showAlertDetails(alertType) {
        // Mostrar loading
        Swal.fire({
            title: 'Carregando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: dashboard_central_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_alert_details',
                nonce: dashboard_central_vars.nonce,
                alert_type: alertType
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayAlertModal(alertType, response.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: response.data?.message || 'Erro ao carregar detalhes'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro de conexão ao carregar detalhes'
                });
            }
        });
    }

    /**
     * Exibe modal com detalhes formatados
     */
    function displayAlertModal(alertType, data) {
        let title = '';
        let icon = 'info';
        let html = '';

        switch(alertType) {
            case 'videos_sem_categoria':
                title = 'Vídeos sem Categoria';
                icon = 'warning';
                html = '<div class="alert-details-list">';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(video) {
                        html += `
                            <div class="alert-detail-item">
                                <div class="alert-detail-icon">
                                    <i class="fas fa-video"></i>
                                </div>
                                <div class="alert-detail-content">
                                    <strong>${escapeHtml(video.title)}</strong>
                                    <span>ID: ${video.id} | Publicado em: ${escapeHtml(video.date)}</span>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html += '<p class="text-muted">Nenhum vídeo encontrado</p>';
                }
                html += '</div>';
                break;

            case 'categorias_vazias':
                title = 'Categorias Vazias';
                icon = 'info';
                html = '<div class="alert-details-list">';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(cat) {
                        html += `
                            <div class="alert-detail-item">
                                <div class="alert-detail-icon">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="alert-detail-content">
                                    <strong>${escapeHtml(cat.name)}</strong>
                                    <span>${escapeHtml(cat.description || 'Sem descrição')}</span>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html += '<p class="text-muted">Nenhuma categoria encontrada</p>';
                }
                html += '</div>';
                break;

            case 'usuarios_sem_unidade':
                title = 'Usuários sem Unidade';
                icon = 'warning';
                html = '<div class="alert-details-list">';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(user) {
                        html += `
                            <div class="alert-detail-item">
                                <div class="alert-detail-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="alert-detail-content">
                                    <strong>${escapeHtml(user.name)}</strong>
                                    <span>Login: ${escapeHtml(user.login)} | Email: ${escapeHtml(user.email)}</span>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html += '<p class="text-muted">Nenhum usuário encontrado</p>';
                }
                html += '</div>';
                break;
        }

        Swal.fire({
            title: title,
            icon: icon,
            html: html,
            width: '700px',
            confirmButtonText: 'Fechar',
            customClass: {
                container: 'alert-details-modal',
                popup: 'alert-details-popup',
                htmlContainer: 'alert-details-html'
            }
        });
    }

    /**
     * Carrega atividade recente
     */
    function loadRecentActivity() {
        $.ajax({
            url: dashboard_central_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_dashboard_activity',
                nonce: dashboard_central_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderRecentUsers(response.data.users);
                    renderRecentVideos(response.data.videos);
                    renderRecentCategorias(response.data.categorias);
                } else {
                    showActivityError();
                }
            },
            error: function() {
                showActivityError();
            }
        });
    }

    /**
     * Renderiza últimos usuários
     */
    function renderRecentUsers(users) {
        const container = $('#recent-users-list');
        
        if (!users || users.length === 0) {
            container.html('<div class="activity-empty"><i class="fas fa-inbox"></i><p>Nenhum usuário recente</p></div>');
            return;
        }

        let html = '';
        users.forEach(function(user) {
            html += `
                <div class="activity-item">
                    <div class="activity-item-icon">
                        <i class="fas fa-user" aria-hidden="true"></i>
                    </div>
                    <div class="activity-item-content">
                        <div class="activity-item-title">${escapeHtml(user.name)}</div>
                        <div class="activity-item-meta">
                            ${user.unidade ? '<span class="activity-tag">' + escapeHtml(user.unidade) + '</span>' : ''}
                            <span class="activity-date">${escapeHtml(user.date)}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        container.html(html);
    }

    /**
     * Renderiza últimos vídeos
     */
    function renderRecentVideos(videos) {
        const container = $('#recent-videos-list');
        
        if (!videos || videos.length === 0) {
            container.html('<div class="activity-empty"><i class="fas fa-inbox"></i><p>Nenhum vídeo recente</p></div>');
            return;
        }

        let html = '';
        videos.forEach(function(video) {
            html += `
                <div class="activity-item">
                    <div class="activity-item-icon">
                        <i class="fas fa-video" aria-hidden="true"></i>
                    </div>
                    <div class="activity-item-content">
                        <div class="activity-item-title">${escapeHtml(video.title)}</div>
                        <div class="activity-item-meta">
                            ${video.categoria ? '<span class="activity-tag">' + escapeHtml(video.categoria) + '</span>' : ''}
                            <span class="activity-date">${escapeHtml(video.date)}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        container.html(html);
    }

    /**
     * Renderiza últimas categorias
     */
    function renderRecentCategorias(categorias) {
        const container = $('#recent-categorias-list');
        
        if (!categorias || categorias.length === 0) {
            container.html('<div class="activity-empty"><i class="fas fa-inbox"></i><p>Nenhuma categoria recente</p></div>');
            return;
        }

        let html = '';
        categorias.forEach(function(cat) {
            html += `
                <div class="activity-item">
                    <div class="activity-item-icon">
                        <i class="fas fa-folder" aria-hidden="true"></i>
                    </div>
                    <div class="activity-item-content">
                        <div class="activity-item-title">${escapeHtml(cat.name)}</div>
                        <div class="activity-item-meta">
                            <span class="activity-count">${cat.count} vídeo(s)</span>
                        </div>
                    </div>
                </div>
            `;
        });

        container.html(html);
    }

    /**
     * Carrega gráfico de distribuição
     */
    function loadChart() {
        $.ajax({
            url: dashboard_central_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_dashboard_chart',
                nonce: dashboard_central_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderChart(response.data);
                } else {
                    showChartError();
                }
            },
            error: function() {
                showChartError();
            }
        });
    }

    /**
     * Renderiza gráfico de barras horizontal
     */
    function renderChart(data) {
        const container = $('#chart-container');
        
        if (!data || data.length === 0) {
            container.html('<div class="chart-empty"><i class="fas fa-chart-bar"></i><p>Nenhum dado para exibir</p></div>');
            return;
        }

        // Calcular valor máximo para escala
        const maxValue = Math.max(...data.map(item => item.count));

        let html = '<div class="chart-bars">';
        data.forEach(function(item) {
            const percentage = maxValue > 0 ? (item.count / maxValue) * 100 : 0;
            html += `
                <div class="chart-bar-item">
                    <div class="chart-bar-label">${escapeHtml(item.name)}</div>
                    <div class="chart-bar-wrapper">
                        <div class="chart-bar-fill" style="width: ${percentage}%">
                            <span class="chart-bar-value">${item.count}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.html(html);

        // Animação GSAP nas barras - com delay
        if (typeof gsap !== 'undefined') {
            setTimeout(function() {
                gsap.from('.chart-bar-fill', {
                    duration: 1,
                    width: 0,
                    stagger: 0.1,
                    ease: 'power2.out',
                    clearProps: 'all'
                });
            }, 50);
        }
    }

    /**
     * Exibe mensagem de erro nos KPIs
     */
    function showKPIError() {
        const container = $('#dashboard-kpis-grid');
        container.html(`
            <div class="kpi-error">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <p>Erro ao carregar estatísticas</p>
            </div>
        `);
    }

    /**
     * Exibe erro nos alertas
     */
    function showAlertsError() {
        $('#dashboard-alerts-grid').html('<div class="alert-card alert-error"><i class="fas fa-times-circle"></i><div class="alert-content"><span>Erro ao carregar alertas</span></div></div>');
    }

    /**
     * Exibe erro na atividade
     */
    function showActivityError() {
        $('.activity-list').html('<div class="activity-empty"><i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar</p></div>');
    }

    /**
     * Exibe erro no gráfico
     */
    function showChartError() {
        $('#chart-container').html('<div class="chart-empty"><i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar gráfico</p></div>');
    }

    /**
     * Função auxiliar: escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

})(jQuery);
