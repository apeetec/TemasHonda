<?php
/**
 * Sistema de Exclusão em Massa de Usuários
 * 
 * Arquivo responsável por processar a exclusão de usuários em lote
 * baseado em arquivo CSV com user_login ou user_email.
 * 
 * @package WordPress
 * @subpackage ESG_Theme
 * @version 2.0
 */

// Impede acesso direto e carrega WordPress
if (!defined('ABSPATH')) {
    // Possíveis caminhos para wp-load.php
    $wp_load_paths = [
        dirname(__FILE__) . '/../../../../wp-load.php',
        dirname(__FILE__) . '/../../../wp-load.php', 
        dirname(__FILE__) . '/../../wp-load.php',
        dirname(__FILE__) . '/../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $wp_load_path) {
        if (file_exists($wp_load_path)) {
            require_once($wp_load_path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => 'WordPress não encontrado']));
    }
}

/**
 * Classe para Exclusão em Massa de Usuários
 */
class UserDeleter {
    
    private $batch_size = 25;
    private $max_execution_time = 300; // 5 minutos
    private $allowed_mime_types = ['text/csv', 'application/csv', 'text/plain'];
    private $max_file_size = 10 * 1024 * 1024; // 10MB
    
    private $stats = [
        'deleted' => [],
        'not_found' => [],
        'errors' => [],
        'total_processed' => 0,
        'start_time' => null,
        'end_time' => null
    ];
    
    public function __construct() {
        $this->stats['start_time'] = microtime(true);
        
        // Aumenta limite de tempo e memória
        set_time_limit($this->max_execution_time);
        ini_set('memory_limit', '512M');
    }
    
    /**
     * Processa a exclusão de usuários
     */
    public function process_deletion() {
        try {
            // Verifica se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            // Verifica autenticação
            if (!is_user_logged_in()) {
                throw new Exception('Usuário não autenticado');
            }
            
            // Verifica nonce de segurança
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'user_import_nonce')) {
                throw new Exception('Token de segurança inválido');
            }
            
            // Verifica permissões
            if (!current_user_can('delete_users')) {
                throw new Exception('Permissão insuficiente para excluir usuários');
            }
            // Valida e processa arquivo
            $file_data = $this->validate_file();
            $csv_data = $this->parse_csv($file_data);
            $this->delete_users_in_batches($csv_data);
            
            // Calcula tempo de execução
            $this->stats['end_time'] = microtime(true);
            $execution_time = round($this->stats['end_time'] - $this->stats['start_time'], 2);
            
            $this->send_success_response([
                'deleted' => $this->stats['deleted'],
                'not_found' => $this->stats['not_found'],
                'errors' => $this->stats['errors'],
                'stats' => [
                    'total_processed' => $this->stats['total_processed'],
                    'execution_time' => $execution_time . 's',
                    'memory_used' => $this->format_bytes(memory_get_peak_usage(true))
                ]
            ]);
            
        } catch (Exception $e) {
            $this->send_error_response($e->getMessage());
        }
    }
    
    /**
     * Valida o arquivo enviado
     */
    private function validate_file() {
        if (!isset($_FILES['csvFile'])) {
            throw new Exception('Nenhum arquivo foi enviado');
        }
        
        $file = $_FILES['csvFile'];
        
        // Verifica erros de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload: ' . $this->get_upload_error_message($file['error']));
        }
        
        // Verifica tamanho
        if ($file['size'] > $this->max_file_size) {
            throw new Exception('Arquivo muito grande. Máximo permitido: ' . $this->format_bytes($this->max_file_size));
        }
        
        // Verifica tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $this->allowed_mime_types)) {
            throw new Exception('Tipo de arquivo inválido. Apenas arquivos CSV são aceitos.');
        }
        
        // Lê conteúdo do arquivo
        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            throw new Exception('Erro ao ler o arquivo');
        }
        
        return $content;
    }
    
    /**
     * Faz parse do CSV
     */
    private function parse_csv($content) {
        $lines = explode("\n", $content);
        $data = [];
        
        if (empty($lines)) {
            throw new Exception('Arquivo CSV vazio');
        }
        
        // Processa cabeçalho
        $header_line = trim($lines[0]);
        $headers = array_map('trim', explode(';', $header_line));
        
        // Valida se tem pelo menos uma das colunas necessárias
        $has_login = in_array('user_login', $headers);
        $has_email = in_array('user_email', $headers);
        
        if (!$has_login && !$has_email) {
            throw new Exception('O CSV deve conter pelo menos uma das colunas: user_login ou user_email');
        }
        
        // Processa dados
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line) || substr($line, 0, 2) === ';;') continue; // Pula linhas vazias e comentários
            
            $values = array_map('trim', explode(';', $line));
            $line_number = $i + 1;
            
            if (count($values) < count($headers)) {
                // Preenche valores vazios se necessário
                $values = array_pad($values, count($headers), '');
            }
            
            $row_data = array_combine($headers, $values);
            
            // Valida se tem pelo menos um identificador
            $user_login = !empty($row_data['user_login']) ? $row_data['user_login'] : '';
            $user_email = !empty($row_data['user_email']) ? $row_data['user_email'] : '';
            
            if (empty($user_login) && empty($user_email)) {
                $this->stats['errors'][] = "Linha {$line_number}: Deve ter user_login ou user_email preenchido";
                continue;
            }
            
            $data[] = [
                'user_login' => $user_login,
                'user_email' => $user_email,
                'line_number' => $line_number
            ];
        }
        
        if (empty($data)) {
            throw new Exception('Nenhum dado válido encontrado no CSV');
        }
        
        return $data;
    }
    
    /**
     * Exclui usuários em lotes
     */
    private function delete_users_in_batches($data) {
        $batches = array_chunk($data, $this->batch_size);
        $current_batch = 0;
        
        foreach ($batches as $batch) {
            $current_batch++;
            
            try {
                foreach ($batch as $user_data) {
                    $this->delete_single_user($user_data);
                    $this->stats['total_processed']++;
                }
            } catch (Exception $e) {
                $this->stats['errors'][] = "Erro no lote {$current_batch}: " . $e->getMessage();
            }
        }
    }
    
    /**
     * Exclui um usuário específico
     */
    private function delete_single_user($user_data) {
        $user = null;
        $identifier = '';
        
        // Tenta encontrar o usuário por login ou email
        if (!empty($user_data['user_login'])) {
            $user = get_user_by('login', $user_data['user_login']);
            $identifier = $user_data['user_login'];
        }
        
        if (!$user && !empty($user_data['user_email'])) {
            $user = get_user_by('email', $user_data['user_email']);
            $identifier = $user_data['user_email'];
        }
        
        if (!$user) {
            $this->stats['not_found'][] = "Usuário não encontrado: {$identifier}";
            return;
        }
        
        // Verifica se não é o usuário atual (segurança)
        if ($user->ID == get_current_user_id()) {
            $this->stats['errors'][] = "Não é possível excluir seu próprio usuário: {$identifier}";
            return;
        }
        
        // Verifica se não é admin (segurança adicional)
        if (user_can($user->ID, 'administrator') && $user->ID == 1) {
            $this->stats['errors'][] = "Não é possível excluir o usuário administrador principal: {$identifier}";
            return;
        }
        
        // Inclui arquivos necessários para exclusão
        if (!function_exists('wp_delete_user')) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
        }
        
        // Realiza a exclusão
        $result = wp_delete_user($user->ID);
        
        if ($result) {
            $this->stats['deleted'][] = "Usuário excluído: {$user->user_login} ({$user->user_email})";
        } else {
            $this->stats['errors'][] = "Erro ao excluir usuário: {$identifier}";
        }
    }
    
    /**
     * Formata bytes em formato legível
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Retorna mensagem de erro de upload
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Arquivo muito grande';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload incompleto';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo selecionado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Diretório temporário não encontrado';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Erro de escrita no disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload bloqueado por extensão';
            default:
                return 'Erro desconhecido no upload';
        }
    }
    
    /**
     * Envia resposta de sucesso
     */
    private function send_success_response($data) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Envia resposta de erro
     */
    private function send_error_response($message) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}

// Processa a requisição apenas se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $deleter = new UserDeleter();
        $deleter->process_deletion();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
?>