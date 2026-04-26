<?php
/*
Template Name: Inserir Usuários
*/

// Verifica se o usuário tem permissão
if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.'));
}

get_header();
?>

<style>
/* Estilos para a página de inserção de usuários */
.user-import-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.user-import-header h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 10px;
    text-align: center;
}

.user-import-header .subtitle {
    font-size: 1.1rem;
    color: #7f8c8d;
    text-align: center;
    margin-bottom: 30px;
}

.divider {
    height: 2px;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    margin: 30px 0;
    border: none;
}

/* Sistema de Abas */
.tabs-container {
    margin: 20px 0;
}

.tabs {
    display: flex;
    justify-content: center;
    gap: 0;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 4px;
    max-width: 500px;
    margin: 0 auto;
}

.tab-button {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background: transparent;
    color: #6c757d;
    cursor: pointer;
    border-radius: 8px;
    font-weight: 500;
    font-size: 16px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-button:hover {
    color: #495057;
    background: #e9ecef;
}

.tab-button.active {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    box-shadow: 0 2px 10px rgba(52, 152, 219, 0.3);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.delete-warning {
    background: #ffe6e6;
    border: 2px solid #ff9999;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    color: #c0392b;
}

.delete-warning h3 {
    color: #c0392b;
    margin: 0 0 15px 0;
}

.info-card {
    background: #fff;
    border-left: 4px solid #f39c12;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 25px;
    margin: 20px 0;
    border-radius: 5px;
}

.info-card h3 {
    color: #e67e22;
    margin-bottom: 15px;
    font-size: 1.3rem;
}

.info-card ul {
    margin: 15px 0;
    padding-left: 25px;
}

.info-card li {
    margin: 8px 0;
    color: #34495e;
    line-height: 1.6;
}

.download-section {
    background: #ecf0f1;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    margin: 30px 0;
}

.btn-download {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    padding: 15px 30px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
}

.btn-download:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
    color: white;
    text-decoration: none;
}

.upload-section {
    background: #fff;
    border: 2px dashed #bdc3c7;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    margin: 30px 0;
    transition: all 0.3s ease;
}

.upload-section.dragover {
    border-color: #3498db;
    background: #f8f9fa;
    transform: scale(1.02);
}

.file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.file-input-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.btn-file-select {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #27ae60;
    color: white;
    padding: 12px 25px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-weight: 500;
}

.btn-file-select:hover {
    background: #229954;
    transform: translateY(-1px);
}

.btn-upload {
    background: linear-gradient(45deg, #e74c3c, #c0392b);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 15px;
}

.btn-upload:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.btn-upload:disabled {
    background: #95a5a6;
    cursor: not-allowed;
}

.btn-delete {
    background: linear-gradient(45deg, #e74c3c, #c0392b);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 20px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 15px;
    position: relative;
    overflow: hidden;
}

.btn-delete:hover:not(:disabled) {
    background: linear-gradient(45deg, #c0392b, #a93226);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
}

.btn-delete:disabled {
    background: #bdc3c7;
    cursor: not-allowed;
    transform: none;
}

.btn-delete::before {
    content: '⚠️';
    position: absolute;
    left: -30px;
    top: 50%;
    transform: translateY(-50%);
    transition: left 0.3s ease;
    font-size: 18px;
}

.btn-delete:hover:not(:disabled)::before {
    left: 15px;
}

.btn-delete:hover:not(:disabled) {
    padding-left: 45px;
}

.progress-container {
    display: none;
    margin: 20px 0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #ecf0f1;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(45deg, #2ecc71, #27ae60);
    width: 0%;
    transition: width 0.3s ease;
    border-radius: 10px;
}

.progress-text {
    text-align: center;
    margin-top: 10px;
    font-weight: 500;
    color: #2c3e50;
}

.results-section {
    margin: 40px 0;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.result-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.result-card.success {
    border-top: 4px solid #2ecc71;
}

.result-card.warning {
    border-top: 4px solid #f39c12;
}

.result-card.error {
    border-top: 4px solid #e74c3c;
}

.result-header {
    padding: 15px 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.result-header.success {
    background: rgba(46, 204, 113, 0.1);
    color: #27ae60;
}

.result-header.warning {
    background: rgba(243, 156, 18, 0.1);
    color: #e67e22;
}

.result-header.error {
    background: rgba(231, 76, 60, 0.1);
    color: #c0392b;
}

.result-body {
    max-height: 300px;
    overflow-y: auto;
    padding: 0 20px 20px;
}

.result-item {
    padding: 8px 0;
    border-bottom: 1px solid #ecf0f1;
    color: #34495e;
}

.result-item:last-child {
    border-bottom: none;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}

.alert.error {
    background: #ffe6e6;
    border: 1px solid #ff9999;
    color: #c0392b;
}

.alert.success {
    background: #e8f5e8;
    border: 1px solid #4caf50;
    color: #27ae60;
}

aside { display: none !important; }
.container { width: 100% !important; max-width: none !important; }
.row { display: block !important; }

@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .user-import-header h1 {
        font-size: 2rem;
    }
    
    .info-card, .upload-section, .download-section {
        padding: 20px;
    }
}

/* Estilos das Estatísticas */
.stats-container {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stats-title {
    color: #2c3e50;
    margin: 0 0 20px 0;
    font-size: 1.5rem;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #ddd;
}

.stat-card.success {
    border-color: #27ae60;
}

.stat-card.created {
    border-color: #3498db;
}

.stat-card.updated {
    border-color: #f39c12;
}

.stat-card.error {
    border-color: #e74c3c;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-card.success .stat-number {
    color: #27ae60;
}

.stat-card.created .stat-number {
    color: #3498db;
}

.stat-card.updated .stat-number {
    color: #f39c12;
}

.stat-card.error .stat-number {
    color: #e74c3c;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stats-details {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}

.stat-detail {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stat-detail-label {
    color: #34495e;
    font-weight: 500;
}

.stat-detail-value {
    color: #27ae60;
    font-weight: bold;
    background: #e8f5e8;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-details {
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }
}
</style>
<div class="user-import-container">
    <!-- Cabeçalho -->
    <header class="user-import-header">
        <h1>🚀 Sistema de Gestão de Usuários</h1>
        <p class="subtitle">Ferramenta avançada para importação e exclusão em massa de usuários no WordPress</p>
    </header>

    <!-- Sistema de Abas -->
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-button active" data-tab="import">
                📥 Importar Usuários
            </button>
            <button class="tab-button" data-tab="delete">
                🗑️ Excluir Usuários
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Conteúdo da Aba de Importação -->
    <div id="import-tab" class="tab-content active">
        <!-- Card de Informações -->
        <div class="info-card">
        <h3>⚠️ Informações Importantes</h3>
        <ul>
            <li><strong>Backup Obrigatório:</strong> Faça backup completo do site e banco de dados antes de prosseguir</li>
            <li><strong>Processo Irreversível:</strong> A importação não pode ser desfeita automaticamente</li>
            <li><strong>Usuários Existentes:</strong> Dados serão atualizados se o email já existir</li>
            <li><strong>Novos Usuários:</strong> Serão criados automaticamente com os dados fornecidos</li>
            <li><strong>Unidades/Empresas:</strong> Taxonomias são criadas automaticamente se não existirem</li>
        </ul>
    </div>

    <!-- Seção de Download do Template -->
    <div class="download-section">
        <h3>📋 Template CSV</h3>
        <p>Baixe o template CSV com os campos corretos para importação:</p>
        <a href="#" id="btnDownloadTemplate" class="btn-download">
            <span>📥</span> Download Template CSV
        </a>
    </div>

    <!-- Seção de Upload -->
    <div class="upload-section" id="uploadSection">
        <h3>📤 Upload do Arquivo CSV</h3>
        <p style="margin-bottom: 20px;">Arraste e solte o arquivo CSV ou clique para selecionar</p>
        
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="file-input-wrapper">
                <input type="file" name="csvFile" id="csvFile" accept=".csv" required>
                <label for="csvFile" class="btn-file-select">
                    <span>📁</span> Selecionar Arquivo CSV
                </label>
            </div>
            
            <div id="fileInfo" style="margin: 15px 0; display: none;">
                <p><strong>Arquivo selecionado:</strong> <span id="fileName"></span></p>
                <p><strong>Tamanho:</strong> <span id="fileSize"></span></p>
            </div>
            
            <button type="button" id="btnUpload" class="btn-upload" disabled>
                <span>🚀</span> Iniciar Importação
            </button>
        </form>

        <!-- Barra de Progresso -->
        <div class="progress-container" id="progressContainer">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text" id="progressText">Preparando importação...</div>
        </div>
    </div>

        <!-- Seção de Resultados -->
        <div class="results-section" id="resultsSection" style="display: none;">
            <h3>📊 Resultados da Importação</h3>
            <div class="results-grid" id="resultsGrid">
                <!-- Resultados serão inseridos aqui via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Conteúdo da Aba de Exclusão -->
    <div id="delete-tab" class="tab-content">
        <!-- Aviso de Exclusão -->
        <div class="delete-warning">
            <h3>⚠️ ATENÇÃO - EXCLUSÃO DE USUÁRIOS</h3>
            <ul>
                <li><strong>🚨 OPERAÇÃO IRREVERSÍVEL:</strong> Usuários excluídos NÃO podem ser recuperados</li>
                <li><strong>💾 BACKUP OBRIGATÓRIO:</strong> Faça backup completo antes de continuar</li>
                <li><strong>📝 DADOS PERMANENTES:</strong> Todos os dados dos usuários serão perdidos</li>
                <li><strong>🔍 VALIDAÇÃO:</strong> Apenas usuários existentes serão processados</li>
                <li><strong>📊 RELATÓRIO:</strong> Relatório detalhado será gerado após exclusão</li>
            </ul>
        </div>

        <!-- Card de Informações de Exclusão -->
        <div class="info-card">
            <h3>📋 Como usar a Exclusão em Massa</h3>
            <ol>
                <li><strong>Prepare o CSV:</strong> Use apenas as colunas 'user_login' ou 'user_email'</li>
                <li><strong>Formate correto:</strong> Separador ponto-vírgula (;), uma linha por usuário</li>
                <li><strong>Valide dados:</strong> Certifique-se que os usuários existem no sistema</li>
                <li><strong>Execute exclusão:</strong> Processo será executado em lotes para performance</li>
            </ol>
        </div>

        <!-- Download Template de Exclusão -->
        <div class="download-section">
            <h3>📋 Template CSV para Exclusão</h3>
            <p>Baixe o template CSV para exclusão de usuários:</p>
            <a href="#" id="btnDownloadDeleteTemplate" class="btn-download">
                📥 Download Template de Exclusão
            </a>
        </div>

        <!-- Upload de Arquivo de Exclusão -->
        <div class="upload-section" id="deleteUploadSection">
            <h3>📤 Upload do Arquivo de Exclusão</h3>
            <form id="deleteUploadForm" enctype="multipart/form-data">
                <label for="deleteCsvFile" class="file-label">
                    <input type="file" name="deleteCsvFile" id="deleteCsvFile" accept=".csv" required>
                    <span class="btn-file-select">🔍 Selecionar Arquivo CSV</span>
                </label>

            <div id="deleteFileInfo" style="margin: 15px 0; display: none;">
                <p><strong>Arquivo selecionado:</strong> <span id="deleteFileName"></span></p>
                <p><strong>Tamanho:</strong> <span id="deleteFileSize"></span></p>
            </div>

            <button type="button" id="btnDelete" class="btn-delete" disabled>
                🗑️ EXCLUIR USUÁRIOS
            </button>
            </form>
        </div>

        <!-- Progress de Exclusão -->
        <div class="progress-container" id="deleteProgressContainer" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill" id="deleteProgressFill"></div>
            </div>
            <div class="progress-text" id="deleteProgressText">Preparando exclusão...</div>
        </div>

        <!-- Resultados da Exclusão -->
        <div class="results-section" id="deleteResultsSection" style="display: none;">
            <h3>📊 Resultados da Exclusão</h3>
            <div class="results-grid" id="deleteResultsGrid">
                <!-- Resultados da exclusão serão inseridos aqui via JavaScript -->
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>

<script>
class UserImporter {
    constructor() {
        this.init();
        this.setupEventListeners();
    }

    init() {
        this.templateUrl = '<?php echo get_template_directory_uri(); ?>';
        this.ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        this.nonce = '<?php echo wp_create_nonce('user_import_nonce'); ?>';
        this.isUploading = false;
        this.selectedFile = null;
        this.selectedDeleteFile = null;
        this.currentTab = 'import';
    }

    setupEventListeners() {
        // Sistema de Abas
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => this.switchTab(e.target.dataset.tab));
        });

        // Importação de usuários
        const fileInput = document.getElementById('csvFile');
        const uploadBtn = document.getElementById('btnUpload');
        const uploadSection = document.getElementById('uploadSection');
        const downloadBtn = document.getElementById('btnDownloadTemplate');

        if (fileInput) fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        if (uploadBtn) uploadBtn.addEventListener('click', () => this.startImport());
        if (downloadBtn) downloadBtn.addEventListener('click', (e) => this.downloadTemplate(e));

        // Drag and drop para importação
        if (uploadSection) {
            uploadSection.addEventListener('dragover', (e) => this.handleDragOver(e));
            uploadSection.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            uploadSection.addEventListener('drop', (e) => this.handleDrop(e));
        }

        // Exclusão de usuários
        const deleteFileInput = document.getElementById('deleteCsvFile');
        const deleteBtn = document.getElementById('btnDelete');
        const deleteUploadSection = document.getElementById('deleteUploadSection');
        const deleteDownloadBtn = document.getElementById('btnDownloadDeleteTemplate');

        if (deleteFileInput) deleteFileInput.addEventListener('change', (e) => this.handleDeleteFileSelect(e));
        if (deleteBtn) deleteBtn.addEventListener('click', () => this.startDelete());
        if (deleteDownloadBtn) deleteDownloadBtn.addEventListener('click', (e) => this.downloadDeleteTemplate(e));

        // Drag and drop para exclusão
        if (deleteUploadSection) {
            deleteUploadSection.addEventListener('dragover', (e) => this.handleDeleteDragOver(e));
            deleteUploadSection.addEventListener('dragleave', (e) => this.handleDeleteDragLeave(e));
            deleteUploadSection.addEventListener('drop', (e) => this.handleDeleteDrop(e));
        }
    }

    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.validateAndShowFile(file);
        }
    }

    handleDragOver(event) {
        event.preventDefault();
        event.target.closest('.upload-section').classList.add('dragover');
    }

    handleDragLeave(event) {
        event.preventDefault();
        event.target.closest('.upload-section').classList.remove('dragover');
    }

    handleDrop(event) {
        event.preventDefault();
        const uploadSection = event.target.closest('.upload-section');
        uploadSection.classList.remove('dragover');
        
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            document.getElementById('csvFile').files = files;
            this.validateAndShowFile(file);
        }
    }

    validateAndShowFile(file) {
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadBtn = document.getElementById('btnUpload');

        // Validações
        if (!file.name.toLowerCase().endsWith('.csv')) {
            this.showAlert('Apenas arquivos CSV são aceitos!', 'error');
            return;
        }

        if (file.size > 10 * 1024 * 1024) { // 10MB
            this.showAlert('Arquivo muito grande! Máximo 10MB permitido.', 'error');
            return;
        }

        // Mostra informações do arquivo
        fileName.textContent = file.name;
        fileSize.textContent = this.formatFileSize(file.size);
        fileInfo.style.display = 'block';
        uploadBtn.disabled = false;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    async startImport() {
        if (this.isUploading) return;
        
        const fileInput = document.getElementById('csvFile');
        if (!fileInput.files[0]) {
            this.showAlert('Selecione um arquivo CSV primeiro!', 'error');
            return;
        }

        this.isUploading = true;
        this.showProgress(true);
        this.updateProgress(0, 'Iniciando importação...');

        const formData = new FormData();
        formData.append('csvFile', fileInput.files[0]);
        formData.append('action', 'import_users');
        formData.append('nonce', this.nonce);

        // Inicia animação de progresso simulado
        const progressInterval = this.simulateProgress();

        try {
            const response = await fetch(`${this.templateUrl}/sql/inserir-usuarios.php`, {
                method: 'POST',
                body: formData
            });

            // Para a animação de progresso
            clearInterval(progressInterval);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            this.handleImportResult(result);

        } catch (error) {
            console.error('Erro na importação:', error);
            clearInterval(progressInterval);
            this.showAlert('Erro durante a importação: ' + error.message, 'error');
        } finally {
            this.isUploading = false;
            this.showProgress(false);
        }
    }

    simulateProgress() {
        let progress = 5;
        const messages = [
            'Lendo arquivo CSV...',
            'Validando dados...',
            'Processando usuários...',
            'Criando contas...',
            'Atualizando informações...',
            'Salvando alterações...',
            'Finalizando importação...'
        ];
        let messageIndex = 0;

        return setInterval(() => {
            if (progress < 90) {
                // Progresso mais rápido no início, mais lento perto do fim
                const increment = progress < 30 ? 8 : progress < 60 ? 4 : 2;
                progress += increment;
                
                // Atualiza mensagem a cada 20%
                if (progress % 20 < increment) {
                    messageIndex = Math.min(messageIndex + 1, messages.length - 1);
                }
                
                this.updateProgress(progress, messages[messageIndex]);
            }
        }, 500); // Atualiza a cada 500ms
    }

    handleImportResult(result) {
        if (result.success) {
            this.updateProgress(100, 'Importação concluída!');
            this.showResults(result.data);
        } else {
            this.showAlert('Erro: ' + (result.message || 'Falha na importação'), 'error');
        }
    }

    showResults(data) {
        const resultsSection = document.getElementById('resultsSection');
        const resultsGrid = document.getElementById('resultsGrid');
        
        resultsGrid.innerHTML = '';

        // Card de usuários criados
        if (data.created && data.created.length > 0) {
            resultsGrid.appendChild(this.createResultCard(
                'success', 
                `✅ Usuários Criados (${data.created.length})`,
                data.created
            ));
        }

        // Card de usuários atualizados
        if (data.updated && data.updated.length > 0) {
            resultsGrid.appendChild(this.createResultCard(
                'warning', 
                `🔄 Usuários Atualizados (${data.updated.length})`,
                data.updated
            ));
        }

        // Card de erros
        if (data.errors && data.errors.length > 0) {
            resultsGrid.appendChild(this.createResultCard(
                'error', 
                `❌ Erros (${data.errors.length})`,
                data.errors
            ));
        }

        // Mostra estatísticas gerais
        if (data.stats) {
            this.showStats(data.stats);
        }

        resultsSection.style.display = 'block';
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    }

    createResultCard(type, title, items) {
        const card = document.createElement('div');
        card.className = `result-card ${type}`;

        const header = document.createElement('div');
        header.className = `result-header ${type}`;
        header.textContent = title;

        const body = document.createElement('div');
        body.className = 'result-body';

        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'result-item';
            div.textContent = typeof item === 'string' ? item : JSON.stringify(item);
            body.appendChild(div);
        });

        card.appendChild(header);
        card.appendChild(body);
        
        return card;
    }

    showStats(stats) {
        const statsContainer = document.getElementById('statsContainer') || this.createStatsContainer();
        
        const totalCreated = stats.total_processed || 0;
        const createdCount = Array.isArray(stats.created) ? stats.created.length : 0;
        const updatedCount = Array.isArray(stats.updated) ? stats.updated.length : 0;
        const errorCount = Array.isArray(stats.errors) ? stats.errors.length : 0;
        
        statsContainer.innerHTML = `
            <h3 class="stats-title">📊 Estatísticas da Importação</h3>
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-number">${totalCreated}</div>
                    <div class="stat-label">Total Processado</div>
                </div>
                <div class="stat-card created">
                    <div class="stat-number">${createdCount}</div>
                    <div class="stat-label">Usuários Criados</div>
                </div>
                <div class="stat-card updated">
                    <div class="stat-number">${updatedCount}</div>
                    <div class="stat-label">Usuários Atualizados</div>
                </div>
                <div class="stat-card error">
                    <div class="stat-number">${errorCount}</div>
                    <div class="stat-label">Erros</div>
                </div>
            </div>
            <div class="stats-details">
                <div class="stat-detail">
                    <span class="stat-detail-label">⏱️ Tempo de Execução:</span>
                    <span class="stat-detail-value">${stats.execution_time || 'N/A'}</span>
                </div>
                <div class="stat-detail">
                    <span class="stat-detail-label">💾 Memória Utilizada:</span>
                    <span class="stat-detail-value">${stats.memory_used || 'N/A'}</span>
                </div>
            </div>
        `;
        
        statsContainer.style.display = 'block';
    }

    createStatsContainer() {
        const container = document.createElement('div');
        container.id = 'statsContainer';
        container.className = 'stats-container';
        container.style.display = 'none';
        
        // Tenta encontrar a seção de resultados
        const resultsSection = document.getElementById('resultsSection');
        
        if (resultsSection) {
            // Se existe, insere como primeiro filho
            if (resultsSection.firstChild) {
                resultsSection.insertBefore(container, resultsSection.firstChild);
            } else {
                resultsSection.appendChild(container);
            }
        } else {
            // Se não existe, anexa ao body como fallback
            console.warn('Seção de resultados não encontrada, anexando ao body');
            document.body.appendChild(container);
        }
        
        return container;
    }

    showProgress(show) {
        const progressContainer = document.getElementById('progressContainer');
        const uploadBtn = document.getElementById('btnUpload');
        
        progressContainer.style.display = show ? 'block' : 'none';
        uploadBtn.disabled = show;
    }

    updateProgress(percent, text) {
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        progressFill.style.width = percent + '%';
        progressText.textContent = text;
    }

    showAlert(message, type) {
        // Remove alertas existentes
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Cria novo alerta
        const alert = document.createElement('div');
        alert.className = `alert ${type}`;
        alert.textContent = message;

        // Insere após o cabeçalho
        const header = document.querySelector('.user-import-header');
        header.parentNode.insertBefore(alert, header.nextSibling);

        // Remove automaticamente após 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    downloadTemplate(event) {
        event.preventDefault();
        
        // URL do template CSV
        const templateUrl = `${this.templateUrl}/template-usuarios.csv`;
        
        // Cria link de download
        const link = document.createElement('a');
        link.href = templateUrl;
        link.download = 'template-importacao-usuarios.csv';
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Mostra notificação de sucesso
        this.showAlert('Template CSV baixado com sucesso! Preencha os dados e faça o upload.', 'success');
    }

    // ========== SISTEMA DE ABAS ==========

    switchTab(tabName) {
        // Remove active das abas
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        // Ativa a aba selecionada
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById(`${tabName}-tab`).classList.add('active');

        this.currentTab = tabName;
    }

    // ========== FUNCIONALIDADES DE EXCLUSÃO ==========

    handleDeleteFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.validateAndShowDeleteFile(file);
        }
    }

    handleDeleteDragOver(event) {
        event.preventDefault();
        event.target.closest('.upload-section').classList.add('drag-over');
    }

    handleDeleteDragLeave(event) {
        event.preventDefault();
        event.target.closest('.upload-section').classList.remove('drag-over');
    }

    handleDeleteDrop(event) {
        event.preventDefault();
        event.target.closest('.upload-section').classList.remove('drag-over');
        
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.validateAndShowDeleteFile(files[0]);
        }
    }

    validateAndShowDeleteFile(file) {
        // Valida tipo de arquivo
        if (!file.name.toLowerCase().endsWith('.csv')) {
            this.showAlert('Apenas arquivos CSV são aceitos!', 'error');
            return;
        }

        // Valida tamanho
        if (file.size > 10 * 1024 * 1024) { // 10MB
            this.showAlert('Arquivo muito grande! Máximo 10MB permitido.', 'error');
            return;
        }

        this.selectedDeleteFile = file;

        // Mostra informações do arquivo
        const fileInfo = document.getElementById('deleteFileInfo');
        const fileName = document.getElementById('deleteFileName');
        const fileSize = document.getElementById('deleteFileSize');

        fileName.textContent = file.name;
        fileSize.textContent = this.formatFileSize(file.size);
        fileInfo.style.display = 'block';

        // Habilita botão de exclusão
        document.getElementById('btnDelete').disabled = false;
    }

    downloadDeleteTemplate(event) {
        event.preventDefault();
        
        // Cria conteúdo do template de exclusão
        const templateContent = [
            'user_login;user_email',
            'exemplo.usuario1;usuario1@empresa.com',
            'exemplo.usuario2;usuario2@empresa.com',
            ';; // Você pode usar apenas user_login OU user_email',
            'joao.silva;',
            ';maria@empresa.com'
        ].join('\n');
        
        // Cria e baixa o arquivo
        const blob = new Blob([templateContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'template-exclusao-usuarios.csv';
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.showAlert('Template de exclusão baixado com sucesso!', 'success');
    }

    async startDelete() {
        if (!this.selectedDeleteFile) {
            this.showAlert('Selecione um arquivo CSV primeiro!', 'error');
            return;
        }

        // Confirmação dupla
        if (!confirm('⚠️ ATENÇÃO: Esta operação irá EXCLUIR permanentemente os usuários listados no arquivo CSV.\n\n✅ Você fez backup do banco de dados?\n\nDeseja continuar?')) {
            return;
        }

        if (!confirm('🚨 ÚLTIMA CONFIRMAÇÃO: Os usuários serão EXCLUÍDOS PERMANENTEMENTE.\n\nTem CERTEZA que deseja continuar?')) {
            return;
        }

        this.showDeleteProgress(true);
        this.updateDeleteProgress(0, 'Iniciando exclusão...');

        const formData = new FormData();
        formData.append('action', 'delete_users_bulk');
        formData.append('csvFile', this.selectedDeleteFile);
        formData.append('nonce', this.nonce);

        // Inicia animação de progresso simulado para exclusão
        const progressInterval = this.simulateDeleteProgress();

        try {
            const response = await fetch(this.templateUrl + '/sql/excluir-usuarios.php', {
                method: 'POST',
                body: formData
            });

            // Para a animação de progresso
            clearInterval(progressInterval);

            const result = await response.json();
            
            if (result.success) {
                this.updateDeleteProgress(100, 'Exclusão concluída!');
                this.handleDeleteResult(result);
            } else {
                throw new Error(result.message || 'Erro desconhecido na exclusão');
            }

        } catch (error) {
            console.error('Erro na exclusão:', error);
            clearInterval(progressInterval);
            this.showAlert('Erro durante a exclusão: ' + error.message, 'error');
        } finally {
            this.showDeleteProgress(false);
        }
    }

    simulateDeleteProgress() {
        let progress = 5;
        const messages = [
            'Lendo arquivo CSV...',
            'Validando usuários...',
            'Preparando exclusão...',
            'Removendo usuários...',
            'Limpando dados...',
            'Finalizando exclusão...'
        ];
        let messageIndex = 0;

        return setInterval(() => {
            if (progress < 90) {
                const increment = progress < 30 ? 8 : progress < 60 ? 4 : 2;
                progress += increment;
                
                if (progress % 20 < increment) {
                    messageIndex = Math.min(messageIndex + 1, messages.length - 1);
                }
                
                this.updateDeleteProgress(progress, messages[messageIndex]);
            }
        }, 500);
    }

    handleDeleteResult(result) {
        this.showDeleteResults(result.data);
        this.showAlert('Exclusão processada com sucesso!', 'success');
    }

    showDeleteResults(data) {
        const resultsSection = document.getElementById('deleteResultsSection');
        const resultsGrid = document.getElementById('deleteResultsGrid');
        
        resultsGrid.innerHTML = '';

        // Card de usuários excluídos
        if (data.deleted && data.deleted.length > 0) {
            resultsGrid.appendChild(this.createResultCard(
                'success', 
                `✅ Usuários Excluídos (${data.deleted.length})`,
                data.deleted
            ));
        }

        // Card de usuários não encontrados
        if (data.not_found && data.not_found.length > 0) {
            resultsGrid.appendChild(this.createResultCard(
                'warning', 
                `⚠️ Usuários Não Encontrados (${data.not_found.length})`,
                data.not_found
            ));
        }

        // Card de erros
        if (data.errors && data.errors.length > 0) {
            resultsGrid.appendChild(this.createResultCard(
                'error', 
                `❌ Erros (${data.errors.length})`,
                data.errors
            ));
        }

        // Mostra estatísticas da exclusão
        if (data.stats) {
            this.showDeleteStats(data.stats);
        }

        resultsSection.style.display = 'block';
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    }

    showDeleteStats(stats) {
        const statsContainer = document.getElementById('deleteStatsContainer') || this.createDeleteStatsContainer();
        
        const totalProcessed = stats.total_processed || 0;
        const deletedCount = Array.isArray(stats.deleted) ? stats.deleted.length : 0;
        const notFoundCount = Array.isArray(stats.not_found) ? stats.not_found.length : 0;
        const errorCount = Array.isArray(stats.errors) ? stats.errors.length : 0;
        
        statsContainer.innerHTML = `
            <h3 class="stats-title">📊 Estatísticas da Exclusão</h3>
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-number">${totalProcessed}</div>
                    <div class="stat-label">Total Processado</div>
                </div>
                <div class="stat-card error">
                    <div class="stat-number">${deletedCount}</div>
                    <div class="stat-label">Usuários Excluídos</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-number">${notFoundCount}</div>
                    <div class="stat-label">Não Encontrados</div>
                </div>
                <div class="stat-card error">
                    <div class="stat-number">${errorCount}</div>
                    <div class="stat-label">Erros</div>
                </div>
            </div>
            <div class="stats-details">
                <div class="stat-detail">
                    <span class="stat-detail-label">⏱️ Tempo de Execução:</span>
                    <span class="stat-detail-value">${stats.execution_time || 'N/A'}</span>
                </div>
                <div class="stat-detail">
                    <span class="stat-detail-label">💾 Memória Utilizada:</span>
                    <span class="stat-detail-value">${stats.memory_used || 'N/A'}</span>
                </div>
            </div>
        `;
        
        statsContainer.style.display = 'block';
    }

    createDeleteStatsContainer() {
        const container = document.createElement('div');
        container.id = 'deleteStatsContainer';
        container.className = 'stats-container';
        container.style.display = 'none';
        
        const resultsSection = document.getElementById('deleteResultsSection');
        
        if (resultsSection) {
            if (resultsSection.firstChild) {
                resultsSection.insertBefore(container, resultsSection.firstChild);
            } else {
                resultsSection.appendChild(container);
            }
        } else {
            console.warn('Seção de resultados de exclusão não encontrada');
            document.body.appendChild(container);
        }
        
        return container;
    }

    showDeleteProgress(show) {
        const progressContainer = document.getElementById('deleteProgressContainer');
        const deleteBtn = document.getElementById('btnDelete');
        
        if (progressContainer) progressContainer.style.display = show ? 'block' : 'none';
        if (deleteBtn) deleteBtn.disabled = show;
    }

    updateDeleteProgress(percent, text) {
        const progressFill = document.getElementById('deleteProgressFill');
        const progressText = document.getElementById('deleteProgressText');
        
        if (progressFill) progressFill.style.width = percent + '%';
        if (progressText) progressText.textContent = text;
    }
}

// Inicializa quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    new UserImporter();
});
</script>