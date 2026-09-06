<?php
/**
 * Funções auxiliares para o formulário de perguntas
 * Arquivo com funções de validação e processamento
 */

if (!defined('ABSPATH')) {
    exit; // Previne acesso direto
}

/**
 * Valida e sanitiza as respostas do formulário
 */
function validar_respostas_formulario($post_data, $user_id, $term_name) {
    $erros = [];
    $dados_limpos = [];
    
    // Validação básica
    if (empty($user_id) || !is_numeric($user_id)) {
        $erros[] = 'ID do usuário inválido';
        return ['erros' => $erros, 'dados' => $dados_limpos];
    }
    
    if (empty($term_name) || !is_string($term_name)) {
        $erros[] = 'Nome da categoria inválido';
        return ['erros' => $erros, 'dados' => $dados_limpos];
    }
    
    // Sanitiza e valida as respostas
    if (isset($post_data['resp_video']) && is_array($post_data['resp_video'])) {
        foreach ($post_data['resp_video'] as $post_id => $resposta) {
            $post_id = intval($post_id);
            if ($post_id > 0 && !empty(trim($resposta))) {
                $dados_limpos['respostas'][$post_id] = sanitize_text_field(trim($resposta));
            }
        }
    }
    
    // Sanitiza e valida as sugestões
    foreach ($post_data as $key => $value) {
        if (strpos($key, 'sugestao_') === 0) {
            $post_id = intval(str_replace('sugestao_', '', $key));
            if ($post_id > 0 && !empty(trim($value))) {
                $dados_limpos['sugestoes'][$post_id] = sanitize_textarea_field(trim($value));
            }
        }
    }
    
    // Valida informação de presencial
    if (isset($post_data['presencial'][$term_name])) {
        $presencial = sanitize_text_field($post_data['presencial'][$term_name]);
        if (in_array($presencial, ['Presencial', 'Não presencial'])) {
            $dados_limpos['presencial'] = $presencial;
        }
    }
    
    return ['erros' => $erros, 'dados' => $dados_limpos];
}

/**
 * Salva as respostas do usuário no banco de dados
 */
function salvar_respostas_usuario($dados, $user_id, $term_name, $slug_id) {
    $sucesso = true;
    
    try {
        // Salva respostas de múltipla escolha
        if (isset($dados['respostas']) && is_array($dados['respostas'])) {
            foreach ($dados['respostas'] as $post_id => $resposta) {
                $meta_key = 'user_field_' . $term_name . '_' . $post_id;
                $resultado = update_user_meta($user_id, $meta_key, $resposta);
                
                if ($resultado === false) {
                    error_log("Erro ao salvar resposta para usuário {$user_id}, pergunta {$post_id}");
                    $sucesso = false;
                }
            }
            
            // Marca que o usuário respondeu
            if (!empty($dados['respostas'])) {
                update_user_meta($user_id, 'todas_alternativa_' . $term_name, 'on');
                update_user_meta($user_id, 'data_resposta_' . $term_name, current_time('mysql'));
            }
        }
        
        // Salva sugestões
        if (isset($dados['sugestoes']) && is_array($dados['sugestoes'])) {
            foreach ($dados['sugestoes'] as $post_id => $sugestao) {
                $meta_key = 'sugestao_pergunta_' . $term_name . '_' . $post_id;
                $resultado = update_user_meta($user_id, $meta_key, $sugestao);
                
                if ($resultado === false) {
                    error_log("Erro ao salvar sugestão para usuário {$user_id}, pergunta {$post_id}");
                }
            }
        }
        
        // Salva informação de presencial
        if (isset($dados['presencial'])) {
            update_user_meta($user_id, 'presencial_' . $term_name, $dados['presencial']);
        }
        
        // Log de sucesso
        if ($sucesso) {
            $total_respostas = isset($dados['respostas']) ? count($dados['respostas']) : 0;
            error_log("Sucesso: {$total_respostas} respostas salvas para usuário {$user_id}, categoria {$term_name}");
        }
        
    } catch (Exception $e) {
        error_log('Erro ao salvar respostas: ' . $e->getMessage());
        $sucesso = false;
    }
    
    return $sucesso;
}

/**
 * Verifica se o usuário tem permissão para responder o questionário
 */
function verificar_permissao_questionario($user_id, $term_id) {
    if (!is_user_logged_in() || get_current_user_id() != $user_id) {
        return false;
    }
    
    // Verifica se o termo existe
    $term = get_term($term_id, 'datas_perguntas');
    if (is_wp_error($term) || !$term) {
        return false;
    }
    
    return true;
}

/**
 * Busca as perguntas de uma categoria específica
 */
function buscar_perguntas_categoria($term_id) {
    $args = [
        'post_type' => 'perguntas',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'tax_query' => [
            [
                'taxonomy' => 'datas_perguntas',
                'field' => 'term_id',
                'terms' => $term_id,
                'include_children' => false,
            ],
        ],
    ];
    
    return get_posts($args);
}

/**
 * Calcula a pontuação do usuário
 */
function calcular_pontuacao_usuario($user_id, $term_name, $perguntas) {
    $acertos = 0;
    $total = 0;
    
    foreach ($perguntas as $pergunta) {
        $post_id = $pergunta->ID;
        $grupo_alternativas = get_post_meta($post_id, 'grupo_de_respostas', true);
        
        if (!is_array($grupo_alternativas)) {
            continue;
        }
        
        // Busca a resposta correta
        $resposta_correta = '';
        foreach ($grupo_alternativas as $entrada) {
            if (!empty($entrada['alternativa_correta']) && !empty($entrada['alternativa'])) {
                $resposta_correta = $entrada['alternativa'];
                break;
            }
        }
        
        if (!empty($resposta_correta)) {
            $total++;
            
            // Busca a resposta do usuário
            $resposta_usuario = get_user_meta($user_id, 'user_field_' . $term_name . '_' . $post_id, true);
            
            if ($resposta_usuario === $resposta_correta) {
                $acertos++;
            }
        }
    }
    
    return [
        'acertos' => $acertos,
        'total' => $total,
        'percentual' => $total > 0 ? round(($acertos / $total) * 100, 2) : 0
    ];
}