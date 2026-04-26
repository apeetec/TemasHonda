<?php
/**
 * Sistema Avançado de Importação de Usuários
 * Versão 2.0 - Refatorada e Otimizada
 * 
 * @author ESG Theme
 * @version 2.0
 */

// Carrega WordPress
$wp_load_paths = [
    preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__) . 'wp-load.php',
    dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php',
    '../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    wp_die('Erro: Não foi possível carregar o WordPress.');
}

/**
 * Classe principal para importação de usuários
 */
class UserImporter {
    
    private $batch_size = 25;
    private $max_execution_time = 300; // 5 minutos
    private $allowed_mime_types = ['text/csv', 'application/csv', 'text/plain'];
    private $max_file_size = 10 * 1024 * 1024; // 10MB
    
    private $stats = [
        'created' => [],
        'updated' => [],
        'errors' => [],
        'total_processed' => 0,
        'start_time' => null,
        'end_time' => null
    ];
    
    public function __construct() {
        $this->stats['start_time'] = microtime(true);
        
        // Configurações PHP
        ini_set('max_execution_time', $this->max_execution_time);
        ini_set('memory_limit', '256M');
        
        // Headers para JSON
        header('Content-Type: application/json; charset=utf-8');
    }
    
    /**
     * Processa a requisição principal
     */
    public function process_request() {
        try {
            // Verifica permissões
            if (!current_user_can('manage_options')) {
                throw new Exception('Permissão negada. Apenas administradores podem importar usuários.');
            }
            
            // Verifica se é POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método de requisição inválido.');
            }

            // [ALTO-01] Correção: verificar nonce CSRF
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'user_import_nonce' ) ) {
                throw new Exception( 'Token de segurança inválido. Recarregue a página e tente novamente.' );
            }
            
            // Processa o arquivo
            $file_data = $this->process_uploaded_file();
            $this->import_users($file_data);
            
            $this->stats['end_time'] = microtime(true);
            $execution_time = round($this->stats['end_time'] - $this->stats['start_time'], 2);
            
            $this->send_success_response([
                'created' => $this->stats['created'],
                'updated' => $this->stats['updated'], 
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
     * Processa o arquivo enviado
     */
    private function process_uploaded_file() {
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo.');
        }
        
        $file = $_FILES['csvFile'];
        
        // Validações de arquivo
        $this->validate_file($file);
        
        // Lê e processa o CSV
        return $this->parse_csv_file($file['tmp_name']);
    }
    
    /**
     * Valida o arquivo enviado
     */
    private function validate_file($file) {
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
        
        // Verifica extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            throw new Exception('Extensão de arquivo inválida. Apenas .csv é aceito.');
        }
    }
    
    /**
     * Faz parse do arquivo CSV
     */
    private function parse_csv_file($file_path) {
        if (!is_readable($file_path)) {
            throw new Exception('Não foi possível ler o arquivo.');
        }
        
        $csv_data = [];
        $headers = null;
        $line_number = 0;
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                $line_number++;
                
                // Converte encoding
                $data = array_map(function($field) {
                    return mb_convert_encoding($field, 'UTF-8', 'auto');
                }, $data);
                
                if ($line_number === 1) {
                    $headers = array_map('trim', $data);
                    $this->validate_csv_headers($headers);
                } else {
                    if (count($data) === count($headers)) {
                        $csv_data[] = array_combine($headers, array_map('trim', $data));
                    } else {
                        $this->stats['errors'][] = "Linha {$line_number}: Número incorreto de colunas";
                    }
                }
            }
            fclose($handle);
        } else {
            throw new Exception('Erro ao abrir o arquivo CSV.');
        }
        
        if (empty($csv_data)) {
            throw new Exception('Arquivo CSV vazio ou sem dados válidos.');
        }
        
        return $csv_data;
    }
    
    /**
     * Valida os cabeçalhos do CSV
     */
    private function validate_csv_headers($headers) {
        $required_fields = ['user_login', 'user_email', 'user_pass'];
        
        foreach ($required_fields as $field) {
            if (!in_array($field, $headers)) {
                throw new Exception("Campo obrigatório ausente: {$field}");
            }
        }
    }
    
    /**
     * Importa usuários em lotes
     */
    private function import_users($csv_data) {
        $batches = array_chunk($csv_data, $this->batch_size);
        
        foreach ($batches as $batch_index => $batch) {
            $this->process_user_batch($batch, $batch_index + 1, count($batches));
        }
    }
    
    /**
     * Processa um lote de usuários
     */
    private function process_user_batch($batch, $current_batch, $total_batches) {
        global $wpdb;
        
        // Inicia transação
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($batch as $user_data) {
                $this->process_single_user($user_data);
                $this->stats['total_processed']++;
            }
            
            // Confirma transação se tudo deu certo
            $wpdb->query('COMMIT');
            
        } catch (Exception $e) {
            // Desfaz transação em caso de erro
            $wpdb->query('ROLLBACK');
            $this->stats['errors'][] = "Erro no lote {$current_batch}: " . $e->getMessage();
        }
    }
    
    /**
     * Processa um único usuário
     */
    private function process_single_user($user_data) {
        // Validação básica
        $validation_result = $this->validate_user_data($user_data);
        if (!$validation_result['valid']) {
            $this->stats['errors'][] = "Usuário {$user_data['user_login']}: " . $validation_result['error'];
            return;
        }
        
        // Sanitiza dados
        $clean_data = $this->sanitize_user_data($user_data);
        
        // Verifica se usuário já existe
        $existing_user = $this->get_existing_user($clean_data);
        
        if ($existing_user) {
            $this->update_existing_user($existing_user, $clean_data);
        } else {
            $this->create_new_user($clean_data);
        }
    }
    
    /**
     * Valida dados do usuário
     */
    private function validate_user_data($user_data) {
        // Email válido
        if (empty($user_data['user_email']) || !is_email($user_data['user_email'])) {
            return ['valid' => false, 'error' => 'Email inválido ou ausente'];
        }
        
        // Login válido
        if (empty($user_data['user_login']) || !validate_username($user_data['user_login'])) {
            return ['valid' => false, 'error' => 'Nome de usuário inválido ou ausente'];
        }
        
        // Senha não vazia
        if (empty($user_data['user_pass'])) {
            return ['valid' => false, 'error' => 'Senha é obrigatória'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Sanitiza dados do usuário
     */
    private function sanitize_user_data($user_data) {
        $clean_data = [];
        
        // Campos básicos do WordPress
        $wp_fields = ['user_login', 'user_email', 'user_pass', 'first_name', 'last_name', 
                     'display_name', 'user_nicename', 'user_url', 'description'];
        
        foreach ($wp_fields as $field) {
            if (isset($user_data[$field])) {
                $clean_data[$field] = sanitize_text_field($user_data[$field]);
            }
        }
        
        // Meta fields personalizados
        foreach ($user_data as $key => $value) {
            if (!in_array($key, $wp_fields)) {
                $clean_data['meta'][$key] = sanitize_text_field($value);
            }
        }
        
        return $clean_data;
    }
    
    /**
     * Procura usuário existente
     */
    private function get_existing_user($user_data) {
        // Primeiro por email (mais confiável)
        $user = get_user_by('email', $user_data['user_email']);
        if ($user) return $user;
        
        // Depois por login
        $user = get_user_by('login', $user_data['user_login']);
        return $user ? $user : false;
    }
    
    /**
     * Atualiza usuário existente
     */
    private function update_existing_user($existing_user, $clean_data) {
        $user_id = $existing_user->ID;
        
        // Atualiza dados básicos
        $update_data = array_merge($clean_data, ['ID' => $user_id]);
        unset($update_data['meta']); // Remove meta para não conflitar
        
        $result = wp_update_user($update_data);
        
        if (is_wp_error($result)) {
            $this->stats['errors'][] = "Erro ao atualizar {$clean_data['user_login']}: " . $result->get_error_message();
            return;
        }
        
        // Atualiza senha se fornecida
        if (!empty($clean_data['user_pass'])) {
            wp_set_password($clean_data['user_pass'], $user_id);
        }
        
        // Atualiza meta fields
        if (isset($clean_data['meta'])) {
            foreach ($clean_data['meta'] as $meta_key => $meta_value) {
                if ($meta_key === 'user_infos_empresas') {
                    $meta_value = $this->process_taxonomy_term($meta_value, 'unidades');
                }
                update_user_meta($user_id, $meta_key, $meta_value);
            }
        }
        
        $this->stats['updated'][] = $clean_data['user_login'];
    }
    
    /**
     * Cria novo usuário
     */
    private function create_new_user($clean_data) {
        // Remove meta fields dos dados principais
        $user_data = $clean_data;
        $meta_data = isset($clean_data['meta']) ? $clean_data['meta'] : [];
        unset($user_data['meta']);
        
        // Cria usuário
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            $this->stats['errors'][] = "Erro ao criar {$clean_data['user_login']}: " . $user_id->get_error_message();
            return;
        }
        
        // Define senha
        wp_set_password($clean_data['user_pass'], $user_id);
        
        // Adiciona meta fields
        foreach ($meta_data as $meta_key => $meta_value) {
            if ($meta_key === 'user_infos_empresas') {
                $meta_value = $this->process_taxonomy_term($meta_value, 'unidades');
            }
            update_user_meta($user_id, $meta_key, $meta_value);
        }
        
        $this->stats['created'][] = $clean_data['user_login'];
    }
    
    /**
     * Processa termos de taxonomia (cria se não existir)
     */
    private function process_taxonomy_term($term_name, $taxonomy) {
        if (empty($term_name)) return '';
        
        $term = get_term_by('name', $term_name, $taxonomy);
        
        if ($term) {
            return $term->slug;
        }
        
        // Cria termo se não existir
        $result = wp_insert_term($term_name, $taxonomy);
        
        if (is_wp_error($result)) {
            return '';
        }
        
        $new_term = get_term_by('id', $result['term_id'], $taxonomy);
        return $new_term ? $new_term->slug : '';
    }
    
    /**
     * Formata bytes para leitura humana
     */
    private function format_bytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Envia resposta de sucesso
     */
    private function send_success_response($data) {
        // [MÉDIO-05] Correção: wp_send_json_success define Content-Type + charset corretamente e chama wp_die()
        wp_send_json_success( array_merge(
            array( 'message' => 'Importação concluída com sucesso!' ),
            $data
        ) );
    }
    
    /**
     * Envia resposta de erro
     */
    private function send_error_response($message) {
        // [MÉDIO-05] Correção: wp_send_json_error padroniza o formato de erro
        http_response_code(400);
        wp_send_json_error( array( 'message' => $message ) );
    }
}

// Executa o importador
try {
    $importer = new UserImporter();
    $importer->process_request();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
?>
