<?php
/**
 * Sistema de Debug e Logs para o formulário de perguntas
 * Arquivo para facilitar a manutenção e resolução de problemas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe para gerenciar logs e debug
 */
class ESG_Debug {
    
    private static $debug_enabled = null;
    
    /**
     * Verifica se o debug está habilitado
     */
    public static function is_debug_enabled() {
        if (self::$debug_enabled === null) {
            self::$debug_enabled = defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
        }
        return self::$debug_enabled;
    }
    
    /**
     * Log personalizado para o sistema de perguntas
     */
    public static function log($message, $data = null, $level = 'INFO') {
        if (!self::is_debug_enabled()) {
            return;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] ESG_FORM: {$message}";
        
        if ($data !== null) {
            $log_entry .= " | Data: " . print_r($data, true);
        }
        
        error_log($log_entry);
    }
    
    /**
     * Log de erro
     */
    public static function error($message, $data = null) {
        self::log($message, $data, 'ERROR');
    }
    
    /**
     * Log de aviso
     */
    public static function warning($message, $data = null) {
        self::log($message, $data, 'WARNING');
    }
    
    /**
     * Log de informação
     */
    public static function info($message, $data = null) {
        self::log($message, $data, 'INFO');
    }
    
    /**
     * Exibe informações de debug para administradores
     */
    public static function show_debug_info($user_id, $term_name, $term_id) {
        if (!current_user_can('administrator') || !self::is_debug_enabled()) {
            return;
        }
        
        echo '<div class="debug-info" style="background: #f0f0f0; padding: 15px; margin: 20px 0; border-left: 4px solid #007cba; font-family: monospace; font-size: 12px;">';
        echo '<h4>🔧 Informações de Debug (visível apenas para administradores)</h4>';
        
        // Informações do usuário
        echo '<strong>Usuário:</strong> ID=' . $user_id . ', Login=' . get_userdata($user_id)->user_login . '<br>';
        
        // Informações da categoria
        $term = get_term($term_id);
        echo '<strong>Categoria:</strong> ID=' . $term_id . ', Name=' . $term_name . ', Slug=' . $term->slug . '<br>';
        
        // Verifica se há respostas salvas
        $respostas = get_user_meta($user_id, 'user_field_' . $term_name . '_', false);
        echo '<strong>Respostas Salvas:</strong> ' . count($respostas) . '<br>';
        
        // Status do questionário
        $completou = get_user_meta($user_id, 'todas_alternativa_' . $term_name, true);
        echo '<strong>Status:</strong> ' . ($completou ? 'Completado' : 'Não completado') . '<br>';
        
        // Última atualização
        $data_resposta = get_user_meta($user_id, 'data_resposta_' . $term_name, true);
        if ($data_resposta) {
            echo '<strong>Última Resposta:</strong> ' . $data_resposta . '<br>';
        }
        
        // Informações de presencial
        $presencial = get_user_meta($user_id, 'presencial_' . $term_name, true);
        if ($presencial) {
            echo '<strong>Modalidade:</strong> ' . $presencial . '<br>';
        }
        
        echo '</div>';
    }
    
    /**
     * Valida a integridade dos dados do formulário
     */
    public static function validate_form_integrity($term_id) {
        $issues = [];
        
        // Verifica se a categoria existe
        $term = get_term($term_id);
        if (is_wp_error($term) || !$term) {
            $issues[] = "Categoria {$term_id} não encontrada";
        }
        
        // Busca perguntas da categoria
        $perguntas = buscar_perguntas_categoria($term_id);
        
        if (empty($perguntas)) {
            $issues[] = "Nenhuma pergunta encontrada para a categoria {$term_id}";
        } else {
            foreach ($perguntas as $pergunta) {
                $alternativas = get_post_meta($pergunta->ID, 'grupo_de_respostas', true);
                
                if (!is_array($alternativas) || empty($alternativas)) {
                    $issues[] = "Pergunta {$pergunta->ID} não possui alternativas válidas";
                    continue;
                }
                
                $tem_correta = false;
                $tem_alternativa = false;
                
                foreach ($alternativas as $alt) {
                    if (!empty($alt['alternativa'])) {
                        $tem_alternativa = true;
                    }
                    if (!empty($alt['alternativa_correta'])) {
                        $tem_correta = true;
                    }
                }
                
                if (!$tem_alternativa) {
                    $issues[] = "Pergunta {$pergunta->ID} não possui alternativas com texto";
                }
                
                if (!$tem_correta) {
                    $issues[] = "Pergunta {$pergunta->ID} não possui resposta correta marcada";
                }
            }
        }
        
        if (!empty($issues) && current_user_can('administrator')) {
            echo '<div class="notice notice-error"><p><strong>⚠️ Problemas detectados no formulário:</strong><br>';
            foreach ($issues as $issue) {
                echo '• ' . esc_html($issue) . '<br>';
            }
            echo '</p></div>';
        }
        
        self::log('Validação de integridade', ['term_id' => $term_id, 'issues' => $issues]);
        
        return empty($issues);
    }
}

/**
 * Função auxiliar para facilitar o uso
 */
function esg_log($message, $data = null, $level = 'INFO') {
    ESG_Debug::log($message, $data, $level);
}