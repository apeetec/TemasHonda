<?php
/*
Template Name: Login
*/

// [CRÍTICO-03] Correção: processamento seguro com nonce CSRF
$error    = array();
$username = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

    // [CRÍTICO-03] Correção: nonce CSRF adicionado — verificar antes de qualquer processamento
    if ( ! isset( $_POST['login_nonce'] ) || ! wp_verify_nonce( $_POST['login_nonce'], 'connectrh_login' ) ) {
        $error[] = 'Requisição inválida. Recarregue a página e tente novamente.';
    } else {

        // [BAIXO-06] Correção: $_POST em vez de $_REQUEST para credenciais
        // [ALTO-08] Correção: sanitize_text_field em vez de esc_sql para input de formulário
        $username = sanitize_text_field( $_POST['matricula'] ?? '' );
        $password = $_POST['password'] ?? ''; // senha não recebe sanitize_text_field — usada diretamente no wp_signon

        if ( empty( $username ) || empty( $password ) ) {
            $error[] = 'Preencha todos os campos.';
        } else {
            // [ALTO-03] Correção: verificar bloqueio por tentativas antes do wp_signon
            if ( connectrh_check_login_attempts( $username ) >= 5 ) {
                $error[] = 'Muitas tentativas incorretas. Aguarde 15 minutos e tente novamente.';
                // [MÉDIO-06] Log de segurança — conta bloqueada
                connectrh_security_log( 'LOGIN_BLOCKED', 0, $username );
            } else {
                $login_data = array(
                    'user_login'    => $username,
                    'user_password' => $password,
                    'remember'      => true,
                );

                $user_verify = wp_signon( $login_data, false );

                if ( is_wp_error( $user_verify ) ) {
                    // [ALTO-03] Correção: incrementar contador de tentativas falhas
                    connectrh_increment_login_fail( $username );
                    // [ALTO-08] Correção: mensagem genérica — não revela qual campo está errado
                    $error[] = 'Matrícula ou senha inválidos.';
                    // [MÉDIO-06] Log de segurança — login falho
                    connectrh_security_log( 'LOGIN_FAILED', 0, $username );
                } else {
                    // [ALTO-03] Correção: limpar contador de tentativas ao logar com sucesso
                    connectrh_clear_login_attempts( $username );

                    wp_set_auth_cookie( $user_verify->ID, true, true );
                    wp_set_current_user( $user_verify->ID );

                    // [MÉDIO-02] Correção: regenerar session ID após autenticação — proteção contra Session Fixation
                    if ( function_exists( 'session_id' ) && session_id() !== '' ) {
                        session_regenerate_id( true );
                    }

                    // [MÉDIO-06] Log de segurança — login OK
                    connectrh_security_log( 'LOGIN_SUCCESS', $user_verify->ID, $username );

                    if ( user_can( $user_verify, 'administrator' ) ) {
                        wp_redirect( home_url() );
                    } else {
                        wp_redirect( home_url( 'categorias-dos-videos' ) );
                    }
                    exit;
                }
            }
        }
    }
}
get_header();
?>
    <section class="page-login">
        <article class="container">
            <div class="row center">
                <!-- Header da Página Login -->
                <div class="col s12 m12 l12 login-header-wrapper">
                    <div class="login-header-icon">
						<img width="100%" src="https://hondaconectarh.com.br/wp-content/uploads/2026/02/ho_25_4319_1_logo_id_visual_logos_v7-cor-scaled.png" alt="">
                    </div>
                    <h1 class="login-title">Acesso ao Sistema</h1>
                    <p class="login-subtitle">Entre com suas credenciais para continuar</p>
                </div>
                
                <form class="col s12 m12 l12 login-form" action="" method="POST">
                    <?php wp_nonce_field( 'connectrh_login', 'login_nonce' ); // [CRÍTICO-03] CSRF nonce ?>
                    <div class="content">
                        <div class="row">
                            <div class="col s12 m12 l12 loading">
                                <?php if(!empty($error)) { ?>
                                    <div class="erro-main">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <div>
                                            <?php foreach ($error as $erro) { ?>
                                            <span><?php echo esc_html( $erro ); ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="input-field col s12 m12 l12">
                                <i class="fas fa-user prefix"></i>
                                <!-- [BAIXO-02] Correção: autocomplete="username" adicionado -->
                                <input type="text" name="matricula" maxlength="10" autocomplete="username" value="<?php echo esc_attr( $username ?? '' ); ?>" placeholder=" ">  
                                <label for="icon_prefix">Matrícula</label>
                            </div>
                            <div class="input-field col s12 m12 l12">
                                <i class="fas fa-lock prefix"></i>
                                <!-- [ALTO-02] Correção: SEM value="" — nunca reenviar senha no HTML -->
                                <!-- [BAIXO-02] Correção: autocomplete="current-password" adicionado -->
                                <input type="password" name="password" maxlength="50" autocomplete="current-password" placeholder=" ">
                                <label for="icon_telephone">Senha</label>
                            </div>
                            <div class="col s12 m12 l12 line">
                                <button type="submit" class="btn-login-submit">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>Entrar</span>
                                </button>
                            </div>
                            <!-- [CRÍTICO-05] Correção: bloco info-card-login com dica de senha padrão REMOVIDO -->
                        </div>
                    </div>
                </form>
            </div>
        </article>
    </section>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
    // Animações GSAP para página de login
    if (typeof gsap !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function() {
            gsap.from('.login-header-wrapper', {
                opacity: 0,
                y: -30,
                duration: 0.6,
                ease: 'power2.out'
            });
            gsap.from('.login-form', {
                opacity: 0,
                y: 30,
                duration: 0.6,
                delay: 0.2,
                ease: 'power2.out'
            });
        });
    }
    </script>


<?php
 get_footer();
?>


