<?php
/**
 * Processamento de requisições do formulário de perguntas
 * Arquivo responsável por salvar as respostas dos usuários
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

    // Log básico para monitoramento
if (!empty($_POST) && defined('WP_DEBUG') && WP_DEBUG) {
    error_log('ESG Form: Processando formulário para ' . (isset($sanitiza_term_name) ? $sanitiza_term_name : 'categoria não identificada'));
}// Verifica se há dados POST e se o usuário está logado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) && is_user_logged_in()) {
    
    // Verifica se o formulário foi submetido corretamente
    if (isset($_POST[$sanitiza_term_name]) && isset($_POST['slug_id'])) {
        
        error_log('Processando formulário para: ' . $sanitiza_term_name);
        
        // Sanitiza e valida os dados de entrada
        $slug_id = intval($_POST['slug_id']);
        
        if ($slug_id <= 0) {
            error_log('Erro: slug_id inválido - ' . $slug_id);
            return;
        }
        
        // Busca as perguntas da categoria
        $args = [
            'post_type' => 'perguntas',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'datas_perguntas',
                    'field' => 'term_id',
                    'terms' => $slug_id,
                    'include_children' => false,
                ],
            ],
        ];
        
        $post_questoes = get_posts($args);
        error_log('Perguntas encontradas: ' . count($post_questoes));
        
        if (empty($post_questoes)) {
            error_log('Nenhuma pergunta encontrada para categoria: ' . $slug_id);
            return;
        }
        
        $respostas_salvas = 0;
        
        // Processa cada pergunta
        foreach ($post_questoes as $questao) {
            $post_id = $questao->ID;
            
            // Processa resposta de múltipla escolha
            if (isset($_POST['resp_video'][$post_id])) {
                $resposta = sanitize_text_field(trim($_POST['resp_video'][$post_id]));
                if (!empty($resposta)) {
                    $meta_key = 'user_field_' . $sanitiza_term_name . '_' . $post_id;
                    $resultado = update_user_meta($id_user, $meta_key, $resposta);
                    if ($resultado !== false) {
                        $respostas_salvas++;
                    }
                }
            }
            
            // Processa sugestão
            $chave_sugestao = 'sugestao_' . $post_id;
            
            if (isset($_POST[$chave_sugestao])) {
                $sugestao = sanitize_textarea_field(trim($_POST[$chave_sugestao]));
                
                if (!empty($sugestao)) {
                    $meta_key_sugestao = 'sugestao_pergunta_' . $sanitiza_term_name . '_' . $post_id;
                    update_user_meta($id_user, $meta_key_sugestao, $sugestao);
                }
            }
        }
        
        // Marca que o usuário respondeu se houver pelo menos uma resposta
        if ($respostas_salvas > 0) {
            update_user_meta($id_user, 'todas_alternativa_' . $sanitiza_term_name, 'on');
            update_user_meta($id_user, 'data_resposta_' . $sanitiza_term_name, current_time('mysql'));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ESG Form: {$respostas_salvas} respostas salvas para usuário {$id_user}, categoria {$sanitiza_term_name}");
            }
        }
        
        // Processa informação de presencial
        if (isset($_POST['presencial']) && is_array($_POST['presencial'])) {
            if (isset($_POST['presencial'][$sanitiza_term_name])) {
                $presencial = sanitize_text_field($_POST['presencial'][$sanitiza_term_name]);
                $meta_key = 'presencial_' . $sanitiza_term_name;
                $resultado = update_user_meta($id_user, $meta_key, $presencial);
                
                if (defined('WP_DEBUG') && WP_DEBUG && $resultado !== false) {
                    error_log("ESG Form: Presencial salvo - {$presencial} para usuário {$id_user}");
                }
            }
        }
        
        // Calcula pontuação e acertos
        $total_perguntas = 0;
        $acertos = 0;
        
        foreach ($post_questoes as $questao) {
            $post_id = $questao->ID;
            
            // Busca as alternativas da pergunta
            $grupo_alternativas = get_post_meta($post_id, 'grupo_de_respostas', true);
            
            if (!is_array($grupo_alternativas) || empty($grupo_alternativas)) {
                continue;
            }
            
            // Encontra a resposta correta
            $resposta_correta = '';
            foreach ($grupo_alternativas as $entrada) {
                if (!empty($entrada['alternativa_correta']) && !empty($entrada['alternativa'])) {
                    $resposta_correta = trim($entrada['alternativa']);
                    break;
                }
            }
            
            if (!empty($resposta_correta)) {
                $total_perguntas++;
                
                // Busca a resposta do usuário
                $resposta_usuario = get_user_meta($id_user, 'user_field_' . $sanitiza_term_name . '_' . $post_id, true);
                
                if (trim($resposta_usuario) === $resposta_correta) {
                    $acertos++;
                }
            }
        }
        
        // Salva estatísticas de pontuação
        if ($total_perguntas > 0) {
            $percentual = round(($acertos / $total_perguntas) * 100, 2);
            
            update_user_meta($id_user, 'pontuacao_' . $sanitiza_term_name, $acertos . '/' . $total_perguntas);
            update_user_meta($id_user, 'percentual_' . $sanitiza_term_name, $percentual);
            
            // Se acertou 100%, marca como todas corretas
            if ($percentual == 100) {
                update_user_meta($id_user, 'acertou_todas_alternativas_' . $sanitiza_term_name, 'on');
            } else {
                delete_user_meta($id_user, 'acertou_todas_alternativas_' . $sanitiza_term_name);
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ESG Form: Pontuação calculada - {$acertos}/{$total_perguntas} ({$percentual}%) para usuário {$id_user}");
            }
        }
        
        // Salva flag de sucesso com pontuação
        $mensagem_sucesso = "Suas respostas foram salvas com sucesso!";
        if ($total_perguntas > 0) {
            $mensagem_sucesso .= " Você acertou {$acertos} de {$total_perguntas} perguntas.";
        }
        
        set_transient('form_success_' . $id_user . '_' . $sanitiza_term_name, $mensagem_sucesso, 300);
        
        // Define variável global para indicar sucesso
        global $form_submission_success;
        $form_submission_success = true;
        
        error_log("Processamento concluído com sucesso: {$respostas_salvas} respostas salvas, {$acertos}/{$total_perguntas} acertos");
    } else {
        error_log('Formulário não enviado corretamente - chaves não encontradas');
        error_log('POST keys: ' . implode(', ', array_keys($_POST)));
        error_log('Procurando por chave: ' . (isset($sanitiza_term_name) ? $sanitiza_term_name : 'INDEFINIDO'));
        error_log('Tem slug_id? ' . (isset($_POST['slug_id']) ? 'SIM' : 'NÃO'));
        
        // Tenta processar mesmo assim se há dados
        if (isset($_POST['slug_id']) && !empty($_POST['resp_video'])) {
            error_log('TENTANDO PROCESSAR MESMO SEM A CHAVE PRINCIPAL...');
            
            $slug_id = intval($_POST['slug_id']);
            $respostas_salvas = 0;
            
            foreach ($_POST['resp_video'] as $post_id => $resposta) {
                $post_id = intval($post_id);
                $resposta = sanitize_text_field(trim($resposta));
                
                if (!empty($resposta)) {
                    $meta_key = 'user_field_' . $sanitiza_term_name . '_' . $post_id;
                    $resultado = update_user_meta($id_user, $meta_key, $resposta);
                    if ($resultado !== false) {
                        $respostas_salvas++;
                        error_log("FALLBACK - Resposta salva: {$meta_key} = {$resposta}");
                    }
                }
            }
            
            // FALLBACK - Processa sugestões também
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'sugestao_') === 0) {
                    $post_id = str_replace('sugestao_', '', $key);
                    $sugestao = sanitize_textarea_field(trim($value));
                    
                    if (!empty($sugestao)) {
                        $meta_key_sugestao = 'sugestao_pergunta_' . $sanitiza_term_name . '_' . $post_id;
                        $resultado = update_user_meta($id_user, $meta_key_sugestao, $sugestao);
                        error_log("FALLBACK - Sugestão salva: {$meta_key_sugestao} = {$sugestao}");
                    }
                }
            }
            
            // FALLBACK - Processa presencial também
            if (isset($_POST['presencial']) && is_array($_POST['presencial'])) {
                foreach ($_POST['presencial'] as $key => $value) {
                    $presencial = sanitize_text_field($value);
                    $meta_key = 'presencial_' . $key;
                    update_user_meta($id_user, $meta_key, $presencial);
                    error_log("FALLBACK - Presencial salvo: {$meta_key} = {$presencial}");
                }
            }
            
            if ($respostas_salvas > 0) {
                update_user_meta($id_user, 'todas_alternativa_' . $sanitiza_term_name, 'on');
                
                // FALLBACK - Calcula pontuação também
                $args_fallback = [
                    'post_type' => 'perguntas',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'tax_query' => [
                        [
                            'taxonomy' => 'datas_perguntas',
                            'field' => 'term_id',
                            'terms' => $slug_id,
                            'include_children' => false,
                        ],
                    ],
                ];
                
                $perguntas_fallback = get_posts($args_fallback);
                $total_perguntas_fb = 0;
                $acertos_fb = 0;
                
                foreach ($perguntas_fallback as $questao_fb) {
                    $post_id_fb = $questao_fb->ID;
                    $grupo_alternativas_fb = get_post_meta($post_id_fb, 'grupo_de_respostas', true);
                    
                    if (!is_array($grupo_alternativas_fb) || empty($grupo_alternativas_fb)) {
                        continue;
                    }
                    
                    // Encontra a resposta correta
                    $resposta_correta_fb = '';
                    foreach ($grupo_alternativas_fb as $entrada_fb) {
                        if (!empty($entrada_fb['alternativa_correta']) && !empty($entrada_fb['alternativa'])) {
                            $resposta_correta_fb = trim($entrada_fb['alternativa']);
                            break;
                        }
                    }
                    
                    if (!empty($resposta_correta_fb)) {
                        $total_perguntas_fb++;
                        $resposta_usuario_fb = get_user_meta($id_user, 'user_field_' . $sanitiza_term_name . '_' . $post_id_fb, true);
                        
                        if (trim($resposta_usuario_fb) === $resposta_correta_fb) {
                            $acertos_fb++;
                        }
                    }
                }
                
                // Salva estatísticas
                if ($total_perguntas_fb > 0) {
                    $percentual_fb = round(($acertos_fb / $total_perguntas_fb) * 100, 2);
                    update_user_meta($id_user, 'pontuacao_' . $sanitiza_term_name, $acertos_fb . '/' . $total_perguntas_fb);
                    update_user_meta($id_user, 'percentual_' . $sanitiza_term_name, $percentual_fb);
                    
                    if ($percentual_fb == 100) {
                        update_user_meta($id_user, 'acertou_todas_alternativas_' . $sanitiza_term_name, 'on');
                    }
                }
                
                $mensagem_fallback = "Respostas salvas: {$respostas_salvas}";
                if ($total_perguntas_fb > 0) {
                    $mensagem_fallback .= " | Acertos: {$acertos_fb}/{$total_perguntas_fb}";
                }
                
                set_transient('form_success_' . $id_user . '_' . $sanitiza_term_name, $mensagem_fallback, 300);
                global $form_submission_success;
                $form_submission_success = true;
                error_log("FALLBACK SUCESSO: {$respostas_salvas} respostas salvas, {$acertos_fb}/{$total_perguntas_fb} acertos");
            }
        }
    }
} else {
    if (!empty($_POST)) {
        error_log('Usuário não está logado ou método não é POST');
    }
}
?>