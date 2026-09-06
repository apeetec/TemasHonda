/**
 * Dashboard de Usuários - Funções AJAX
 * Gerenciamento completo de usuários com SweetAlert2
 */

(function($) {
    'use strict';

    // Estado global do dashboard
    const DashboardState = {
        currentPage: 1,
        perPage: 25,
        search: '',
        unidade: '',
        sortBy: 'data',
        sortOrder: 'DESC',
        selectedUsers: new Set()
    };

    /**
     * Inicialização do dashboard
     */
    $(document).ready(function() {
        initEventListeners();
        loadUsers();
    });

    /**
     * Configura todos os event listeners
     */
    function initEventListeners() {
        // Botão novo usuário
        $('#btn-novo-usuario').on('click', showCreateUserModal);

        // Botão excluir selecionados
        $('#btn-excluir-selecionados').on('click', deleteSelectedUsers);

        // Select all checkbox
        $('#select-all').on('change', toggleSelectAll);

        // Filtros
        $('#search-usuario').on('keyup', debounce(handleSearch, 500));
        $('#filter-unidade').on('change', handleFilterChange);
        $('#per-page').on('change', handlePerPageChange);
        $('#btn-limpar-filtros').on('click', clearFilters);

        // Ordenação
        $('.sortable').on('click', handleSort);

        // Delegação de eventos para tabela dinâmica
        $(document).on('change', '.user-checkbox', handleUserCheckbox);
        $(document).on('click', '.btn-edit-user', handleEditUser);
        $(document).on('click', '.btn-delete-user', handleDeleteUser);

        // Clique na linha da tabela seleciona / desmarca o checkbox (ignora cliques em botões/links/inputs)
        $(document).on('click', '#users-table-body tr', function(e) {
            const $target = $(e.target);
            if ($target.is('input') || $target.is('button') || $target.is('a') || $target.is('select') || $target.is('label') || $target.closest('.table-actions').length) {
                return;
            }

            const $checkbox = $(this).find('.user-checkbox');
            if ($checkbox.length) {
                $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            }
        });

        // Botão selecionar todos (toggle): seleciona ou desmarca todos na página
        $('#btn-select-all').on('click', function() {
            const total = $('.user-checkbox').length;
            const checked = $('.user-checkbox:checked').length;

            if (total === 0) return;

            // Se não todos selecionados, marque todos; caso contrário, desmarque todos
            const shouldCheck = checked !== total;
            $('#select-all').prop('checked', shouldCheck).trigger('change');
        });
    }

    /**
     * Carrega usuários via AJAX
     */
    function loadUsers() {
        showLoading();

        $.ajax({
            url: dashboard_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_users_dashboard',
                page: DashboardState.currentPage,
                per_page: DashboardState.perPage,
                search: DashboardState.search,
                unidade: DashboardState.unidade,
                sort_by: DashboardState.sortBy,
                sort_order: DashboardState.sortOrder,
                nonce: dashboard_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderUsersTable(response.data.users);
                    renderPagination(response.data.total, response.data.pages);
                    updateStats(response.data);
                } else {
                    // showError('Erro ao carregar usuários: ' + response.data.message);
                    console.log("Erro ao carregar usuários: " + response.data.message);
                }
            },
            error: function() {
                showError('Erro de conexão ao carregar usuários');
            }
        });
    }

    /**
     * Renderiza tabela de usuários
     */
    function renderUsersTable(users) {
        const tbody = $('#users-table-body');
        
        if (!users || users.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-users" style="font-size:48px; color:#ddd;"></i></div>
                        <h3>Nenhum usuário encontrado</h3>
                        <p>Tente ajustar os filtros ou criar um novo usuário</p>
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        users.forEach(function(user) {
            const isSelected = DashboardState.selectedUsers.has(user.id);
            html += `
                <tr class="${isSelected ? 'selected' : ''}">
                    <td>
                        <input type="checkbox" class="user-checkbox" 
                               data-user-id="${user.id}" 
                               ${isSelected ? 'checked' : ''}>
                    </td>
                    <td><strong>${escapeHtml(user.matricula)}</strong></td>
                    <td>${escapeHtml(user.nome)}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td><span class="badge badge-info">${escapeHtml(user.unidade)}</span></td>
                    <td>${escapeHtml(user.data_cadastro)}</td>
                    <td class="table-actions" style="text-align: center;">
                        <button type="button" class="btn-icon edit btn-edit-user" 
                                data-user-id="${user.id}" 
                                title="Editar usuário">
                            <i class="fas fa-edit" style="font-size:16px;" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn-icon delete btn-delete-user" 
                                data-user-id="${user.id}" 
                                title="Excluir usuário">
                            <i class="fas fa-trash" style="font-size:16px;" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);
        
        // Animação GSAP suave nas linhas da tabela
        if (typeof gsap !== 'undefined') {
            gsap.from('#users-table-body tr', {
                opacity: 0,
                x: -20,
                duration: 0.4,
                stagger: 0.05,
                ease: 'power2.out',
                clearProps: 'all'
            });
        }

        // Atualiza contador/estado de seleção após renderizar a tabela
        updateSelectedCount();
    }

    /**
     * Renderiza controles de paginação
     */
    function renderPagination(total, pages) {
        const container = $('#pagination-controls');
        const currentPage = DashboardState.currentPage;
        
        let html = '';
        
        // Botão anterior
        html += `<button ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">« Anterior</button>`;
        
        // Páginas
        for (let i = 1; i <= pages; i++) {
            if (i === 1 || i === pages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<button class="${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += '<span style="padding: 0.5rem;">...</span>';
            }
        }
        
        // Botão próximo
        html += `<button ${currentPage === pages ? 'disabled' : ''} data-page="${currentPage + 1}">Próximo »</button>`;
        
        container.html(html);
        
        // Event listener para paginação
        container.find('button').on('click', function() {
            const page = parseInt($(this).data('page'));
            if (!isNaN(page) && page !== currentPage) {
                DashboardState.currentPage = page;
                loadUsers();
            }
        });
    }

    /**
     * Atualiza estatísticas
     */
    function updateStats(data) {
        const start = ((DashboardState.currentPage - 1) * DashboardState.perPage) + 1;
        const end = Math.min(DashboardState.currentPage * DashboardState.perPage, data.total);
        
        $('#showing-start').text(start);
        $('#showing-end').text(end);
        $('#total-users').text(data.total);
    }

    /**
     * Modal de criar usuário
     */
    function showCreateUserModal() {
        Swal.fire({
            title: 'Novo Usuário',
            html: `
                <div style="text-align: left;">
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nome Completo</label>
                        <input type="text" id="swal-nome" class="swal2-input" style="width: 100%;" placeholder="Digite o nome completo">
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email</label>
                        <input type="email" id="swal-email" class="swal2-input" style="width: 100%;" placeholder="email@exemplo.com">
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Matrícula</label>
                        <input type="text" id="swal-matricula" class="swal2-input" style="width: 100%;" placeholder="Digite a matrícula">
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Unidade</label>
                        <select id="swal-unidade" class="swal2-select" style="width: 100%;">
                            <option value="">Selecione a unidade</option>
                            ${getUnidadesOptions()}
                        </select>
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Senha</label>
                        <input type="password" id="swal-senha" class="swal2-input" style="width: 100%;" placeholder="Senha (mínimo 6 caracteres)">
                    </div>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Criar Usuário',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const nome = $('#swal-nome').val();
                const email = $('#swal-email').val();
                const matricula = $('#swal-matricula').val();
                const unidade = $('#swal-unidade').val();
                const senha = $('#swal-senha').val();

                if (!nome || !email || !matricula || !unidade || !senha) {
                    Swal.showValidationMessage('Todos os campos são obrigatórios');
                    return false;
                }

                if (senha.length < 6) {
                    Swal.showValidationMessage('A senha deve ter no mínimo 6 caracteres');
                    return false;
                }

                return { nome, email, matricula, unidade, senha };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                createUser(result.value);
            }
        });
    }

    /**
     * Criar usuário via AJAX
     */
    function createUser(userData) {
        Swal.fire({
            title: 'Criando usuário...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: dashboard_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'create_user_dashboard',
                ...userData,
                nonce: dashboard_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuário criado!',
                        text: response.data.message,
                        timer: 2000
                    });
                    loadUsers();
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
                    text: 'Erro de conexão ao criar usuário'
                });
            }
        });
    }

    /**
     * Editar usuário
     */
    function handleEditUser(e) {
        const userId = $(this).data('user-id');
        
        // Buscar dados do usuário
        $.ajax({
            url: dashboard_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'get_user_data_dashboard',
                user_id: userId,
                nonce: dashboard_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    showEditUserModal(response.data);
                } else {
                    showError('Erro ao carregar dados do usuário');
                }
            }
        });
    }

    /**
     * Modal de editar usuário
     */
    function showEditUserModal(user) {
        Swal.fire({
            title: 'Editar Usuário',
            html: `
                <div style="text-align: left;">
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nome Completo</label>
                        <input type="text" id="swal-nome" class="swal2-input" style="width: 100%;" value="${escapeHtml(user.nome)}">
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email</label>
                        <input type="email" id="swal-email" class="swal2-input" style="width: 100%;" value="${escapeHtml(user.email)}">
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #999;">Matrícula (não editável)</label>
                        <input type="text" class="swal2-input" style="width: 100%; background: #f5f5f5;" value="${escapeHtml(user.matricula)}" disabled>
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Unidade</label>
                        <select id="swal-unidade" class="swal2-select" style="width: 100%;">
                            ${getUnidadesOptions(user.unidade)}
                        </select>
                    </div>
                    <div class="mb-2">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nova Senha (deixe em branco para não alterar)</label>
                        <input type="password" id="swal-senha" class="swal2-input" style="width: 100%;" placeholder="Nova senha (opcional)">
                    </div>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Atualizar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const nome = $('#swal-nome').val();
                const email = $('#swal-email').val();
                const unidade = $('#swal-unidade').val();
                const senha = $('#swal-senha').val();

                if (!nome || !email || !unidade) {
                    Swal.showValidationMessage('Nome, email e unidade são obrigatórios');
                    return false;
                }

                if (senha && senha.length < 6) {
                    Swal.showValidationMessage('A senha deve ter no mínimo 6 caracteres');
                    return false;
                }

                return { user_id: user.id, nome, email, unidade, senha };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateUser(result.value);
            }
        });
    }

    /**
     * Atualizar usuário via AJAX
     */
    function updateUser(userData) {
        Swal.fire({
            title: 'Atualizando usuário...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: dashboard_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'update_user_dashboard',
                ...userData,
                nonce: dashboard_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Usuário atualizado!',
                        text: response.data.message,
                        timer: 2000
                    });
                    loadUsers();
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
                    text: 'Erro de conexão ao atualizar usuário'
                });
            }
        });
    }

    /**
     * Excluir usuário
     */
    function handleDeleteUser() {
        const userId = $(this).data('user-id');
        
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
                deleteUsers([userId]);
            }
        });
    }

    /**
     * Excluir usuários selecionados
     */
    function deleteSelectedUsers() {
        const userIds = Array.from(DashboardState.selectedUsers);
        
        if (userIds.length === 0) {
            return;
        }

        Swal.fire({
            title: 'Tem certeza?',
            text: `Você está prestes a excluir ${userIds.length} usuário(s). Esta ação não pode ser desfeita!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Sim, excluir todos!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteUsers(userIds);
            }
        });
    }

    /**
     * Excluir usuários via AJAX
     */
    function deleteUsers(userIds) {
        Swal.fire({
            title: 'Excluindo usuários...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: dashboard_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'delete_users_dashboard',
                user_ids: userIds,
                nonce: dashboard_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
                        text: response.data.message,
                        timer: 2000
                    });
                    DashboardState.selectedUsers.clear();
                    updateSelectedCount();
                    loadUsers();
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
                    text: 'Erro de conexão ao excluir usuários'
                });
            }
        });
    }

    /**
     * Handlers de eventos
     */
    function toggleSelectAll() {
        const isChecked = $(this).is(':checked');
        $('.user-checkbox').prop('checked', isChecked).trigger('change');
    }

    function handleUserCheckbox() {
        const userId = parseInt($(this).data('user-id'));
        const isChecked = $(this).is(':checked');
        
        if (isChecked) {
            DashboardState.selectedUsers.add(userId);
            $(this).closest('tr').addClass('selected');
        } else {
            DashboardState.selectedUsers.delete(userId);
            $(this).closest('tr').removeClass('selected');
        }
        
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const count = DashboardState.selectedUsers.size;
        $('#count-selected').text(count);
        
        if (count > 0) {
            $('#btn-excluir-selecionados').removeClass('hidden');
        } else {
            $('#btn-excluir-selecionados').addClass('hidden');
        }

        // Atualizar estado do select-all
        const totalCheckboxes = $('.user-checkbox').length;
        const checkedCheckboxes = $('.user-checkbox:checked').length;
        $('#select-all').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
    }

    function handleSearch() {
        DashboardState.search = $(this).val();
        DashboardState.currentPage = 1;
        loadUsers();
    }

    function handleFilterChange() {
        DashboardState.unidade = $(this).val();
        DashboardState.currentPage = 1;
        loadUsers();
    }

    function handlePerPageChange() {
        DashboardState.perPage = parseInt($(this).val());
        DashboardState.currentPage = 1;
        loadUsers();
    }

    function clearFilters() {
        $('#search-usuario').val('');
        $('#filter-unidade').val('');
        $('#per-page').val('25');
        
        DashboardState.search = '';
        DashboardState.unidade = '';
        DashboardState.perPage = 25;
        DashboardState.currentPage = 1;
        
        loadUsers();
    }

    function handleSort() {
        const sortBy = $(this).data('sort');
        
        if (DashboardState.sortBy === sortBy) {
            DashboardState.sortOrder = DashboardState.sortOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            DashboardState.sortBy = sortBy;
            DashboardState.sortOrder = 'ASC';
        }
        
        loadUsers();
    }

    /**
     * Funções auxiliares
     */
    function showLoading() {
        $('#users-table-body').html(`
            <tr>
                <td colspan="7" class="loading-spinner">
                    <div class="spinner"></div>
                    <p class="mt-2">Carregando usuários...</p>
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

    function getUnidadesOptions(selectedSlug = '') {
        let options = '<option value="">Selecione a unidade</option>';
        if (typeof dashboard_vars.unidades !== 'undefined') {
            dashboard_vars.unidades.forEach(function(unidade) {
                const selected = unidade.slug === selectedSlug ? 'selected' : '';
                options += `<option value="${unidade.slug}" ${selected}>${escapeHtml(unidade.name)}</option>`;
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
