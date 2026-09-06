<?php
/**
 * Template Name: Nova senha
 * [CRÍTICO-06] Correção: política de senha forte com histórico e validação completa
 * [ALTO-06] Correção: nonce CSRF adicionado
 * [ALTO-07] Correção: senha atual exigida após primeiro acesso
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url( 'login' ) );
    exit;
}

$sucesso           = false;
$error             = array();
$current_uid       = get_current_user_id();
$current_user      = get_userdata( $current_uid );
$senha_ja_alterada = get_user_meta( $current_uid, 'user_field_senha_alterada', true );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

    // [ALTO-06] Correção: verificar nonce CSRF
    if ( ! isset( $_POST['nova_senha_nonce'] ) || ! wp_verify_nonce( $_POST['nova_senha_nonce'], 'connectrh_nova_senha' ) ) {
        $error[] = 'Requisição inválida. Recarregue a página e tente novamente.';
    } else {
        $nova_senha  = $_POST['password']        ?? '';
        $confirma    = $_POST['confirmPassword'] ?? '';
        $senha_atual = $_POST['currentPassword'] ?? '';

        // [ALTO-07] Correção: exigir senha atual (exceto no primeiro acesso)
        if ( $senha_ja_alterada === 'Sim' ) {
            if ( empty( $senha_atual ) ) {
                $error[] = 'Informe sua senha atual.';
            } elseif ( ! wp_check_password( $senha_atual, $current_user->user_pass, $current_uid ) ) {
                $error[] = 'Senha atual incorreta.';
            }
        }

        if ( empty( $nova_senha ) || empty( $confirma ) ) {
            $error[] = 'Preencha todos os campos.';
        } elseif ( $nova_senha !== $confirma ) {
            $error[] = 'As senhas não coincidem.';
        } else {
            // [CRÍTICO-06a] Correção: tamanho mínimo — 10 para admin, 8 para usuário
            $min = user_can( $current_uid, 'manage_options' ) ? 10 : 8;
            if ( strlen( $nova_senha ) < $min ) {
                $error[] = "A senha deve ter no mínimo {$min} caracteres.";
            }
            // [CRÍTICO-06b] Correção: complexidade obrigatória
            if ( ! preg_match( '/[A-Z]/', $nova_senha ) ) {
                $error[] = 'A senha deve conter ao menos uma letra maiúscula.';
            }
            if ( ! preg_match( '/[a-z]/', $nova_senha ) ) {
                $error[] = 'A senha deve conter ao menos uma letra minúscula.';
            }
            if ( ! preg_match( '/[0-9]/', $nova_senha ) ) {
                $error[] = 'A senha deve conter ao menos um número.';
            }
            if ( ! preg_match( '/[!@#$%^&*()\-_=+\[\]{};:\'",.<>?\/\\\\|`~]/', $nova_senha ) ) {
                $error[] = 'A senha deve conter ao menos um caractere especial (!@#$%...).';
            }
            // [CRÍTICO-06c] Correção: senha não pode ser igual à matrícula
            if ( strtolower( $nova_senha ) === strtolower( $current_user->user_login ) ) {
                $error[] = 'A senha não pode ser igual à matrícula.';
            }
            // [CRÍTICO-06d] Correção: verificar histórico das últimas 5 senhas
            if ( empty( $error ) ) {
                $historico = get_user_meta( $current_uid, 'connectrh_password_history', true );
                if ( is_array( $historico ) ) {
                    foreach ( $historico as $hash_antigo ) {
                        if ( wp_check_password( $nova_senha, $hash_antigo, $current_uid ) ) {
                            $error[] = 'Esta senha já foi usada recentemente. Escolha uma senha diferente das últimas 5.';
                            break;
                        }
                    }
                }
            }
        }

        // Se sem erros, salvar
        if ( empty( $error ) ) {
            // Atualizar histórico antes de trocar (armazena hash atual)
            $historico = get_user_meta( $current_uid, 'connectrh_password_history', true );
            if ( ! is_array( $historico ) ) $historico = array();
            $historico[] = $current_user->user_pass; // hash bcrypt existente
            $historico   = array_slice( $historico, -5 ); // manter apenas últimas 5
            update_user_meta( $current_uid, 'connectrh_password_history', $historico );

            // Registrar data da troca (controle de expiração 90 dias)
            update_user_meta( $current_uid, 'connectrh_password_changed_at', current_time( 'mysql' ) );

            // Alterar a senha
            wp_set_password( $nova_senha, $current_uid );
            update_user_meta( $current_uid, 'user_field_senha_alterada', 'Sim' );

            // [MÉDIO-06] Log de segurança — senha trocada
            connectrh_security_log( 'PASSWORD_CHANGED', $current_uid );

            $sucesso = true;
        }
    }
}

get_header();
?>
<section class="page-login">
    <article class="container">               
        <form action="" class="loading" method="POST">
            <?php wp_nonce_field( 'connectrh_nova_senha', 'nova_senha_nonce' ); // [ALTO-06] CSRF nonce ?>
            <div class="content">            
                <?php if( $sucesso == false ) { ?>
                <p class="center white-text"><b>Sucesso!</b> <br>Para seu primeiro acesso e por motivos de segurança, será necessário alterar sua senha.</p>

                <?php if(!empty($error)) { ?>
                <p class="erro-main">
                    <?php foreach ($error as $erro) { ?>
                        <span><?php echo esc_html( $erro ); ?></span>
                    <?php } ?>
                </p>
                <?php } ?>

                <?php if ( $senha_ja_alterada === 'Sim' ) : // [ALTO-07] Senha atual só após primeiro acesso ?>
                <div class="line">
                    <label class="white-text">Senha atual</label>
                    <input type="password" name="currentPassword" maxlength="50" autocomplete="current-password">
                </div>
                <?php endif; ?>

                <div class="line">
                    <label class="white-text">Nova senha</label>
                    <!-- [BAIXO-02] Correção: autocomplete="new-password" adicionado -->
                    <input type="password" name="password" id="nova_senha_input" maxlength="50" autocomplete="new-password">
                    <div id="password-strength-bar" style="height:4px;border-radius:2px;margin-top:6px;transition:all .3s;"></div>
                    <small id="password-strength-label" class="white-text" style="font-size:11px;"></small>
                </div>
                <div class="line">
                    <label class="white-text">Confirme a nova senha</label>
                    <!-- [BAIXO-02] Correção: autocomplete="new-password" adicionado -->
                    <input type="password" name="confirmPassword" maxlength="50" autocomplete="new-password">
                </div>

                <div class="line">
                    <small class="white-text">
                        Mínimo <?php echo user_can( $current_uid, 'manage_options' ) ? 10 : 8; ?> caracteres,
                        com maiúsculas, minúsculas, números e um caractere especial (!@#$%...).
                    </small>
                </div>

                <div class="line">
                    <input type="submit" value="Alterar senha">
                </div>

                <?php } elseif( $sucesso == true ) { echo '<p class="sucesso">Sua senha foi alterada com sucesso! <a href="'.esc_url( get_site_url() ).'">Clique aqui</a> para efetuar o login novamente.</p>'; } ?>
            </div>
        </form>
    </article>
</section>

<!-- [BAIXO-01] Indicador visual de força de senha -->
<script>
document.getElementById('nova_senha_input')?.addEventListener('input', function () {
    var s = this.value, score = 0;
    if (s.length >= 8)  score++;
    if (/[A-Z]/.test(s)) score++;
    if (/[a-z]/.test(s)) score++;
    if (/[0-9]/.test(s)) score++;
    if (/[!@#$%^&*()\-_=+\[\]{};:'",.<?\/|`~]/.test(s)) score++;
    var cores  = ['','#e53935','#fb8c00','#fdd835','#7cb342','#2e7d32'];
    var labels = ['','Muito fraca','Fraca','Razoável','Forte','Muito forte'];
    var bar   = document.getElementById('password-strength-bar');
    var label = document.getElementById('password-strength-label');
    bar.style.width      = (score * 20) + '%';
    bar.style.background = cores[score]  || 'transparent';
    label.textContent    = labels[score] || '';
    label.style.color    = cores[score]  || '';
});
</script>

<?php get_footer(); ?>