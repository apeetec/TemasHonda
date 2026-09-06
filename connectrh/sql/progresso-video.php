<?php
/**
 * Endpoint: Registrar progresso de vídeo
 * [CRÍTICO-04] Correção: reescrito com autenticação, CSRF e proteção IDOR
 */
$wp_load = preg_replace( '/wp-content(?!.*wp-content).*/', '', __DIR__ ) . 'wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    http_response_code( 500 );
    exit( json_encode( array( 'success' => false ) ) );
}
require_once( $wp_load );

header( 'Content-Type: application/json; charset=utf-8' );

// [CRÍTICO-04a] Correção: autenticação obrigatória
if ( ! is_user_logged_in() ) {
    http_response_code( 401 );
    exit( json_encode( array( 'success' => false, 'message' => 'Não autenticado.' ) ) );
}

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code( 405 );
    exit( json_encode( array( 'success' => false, 'message' => 'Método não permitido.' ) ) );
}

// [CRÍTICO-04b] Correção: verificar nonce CSRF
if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'progresso_video_nonce' ) ) {
    http_response_code( 403 );
    exit( json_encode( array( 'success' => false, 'message' => 'Token de segurança inválido.' ) ) );
}

// [CRÍTICO-04c] Correção: sanitizar input de categoria
$categoria = isset( $_POST['categoria_video'] ) ? sanitize_text_field( $_POST['categoria_video'] ) : '';

if ( empty( $categoria ) ) {
    http_response_code( 400 );
    exit( json_encode( array( 'success' => false, 'message' => 'Dados inválidos.' ) ) );
}

// Validar que a categoria existe como taxonomia
$term = get_term_by( 'slug', $categoria, 'datas_perguntas' );
if ( ! $term ) {
    http_response_code( 400 );
    exit( json_encode( array( 'success' => false, 'message' => 'Categoria inválida.' ) ) );
}

// [CRÍTICO-04c] Correção: anti-IDOR — SEMPRE usar o usuário autenticado — NUNCA aceitar ID do POST
$id_user = get_current_user_id();

update_user_meta( $id_user, 'video_concluido_' . $categoria, 'on' );

// [MÉDIO-06] Log de segurança — progresso de vídeo
if ( function_exists( 'connectrh_security_log' ) ) {
    connectrh_security_log( 'VIDEO_PROGRESS', $id_user, $categoria );
}

exit( json_encode( array( 'success' => true, 'categoria' => esc_html( $categoria ) ) ) );
