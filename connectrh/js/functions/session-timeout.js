/* [MÉDIO-01] Session timeout — auto-logout por inatividade */
(function ($) {
    'use strict';
    var cfg         = session_cfg;
    var warnTimer   = null;
    var logoutTimer = null;

    function resetTimers() {
        clearTimeout(warnTimer);
        clearTimeout(logoutTimer);
        warnTimer   = setTimeout(showWarning, cfg.warning_ms);
        logoutTimer = setTimeout(doLogout,    cfg.timeout_ms);
    }

    function showWarning() {
        if (confirm('Sua sessão expirará em breve por inatividade.\nDeseja continuar conectado?')) {
            $.post(cfg.ajaxurl, { action: 'session_keepalive', nonce: cfg.keepalive_nonce },
                function (res) { if (res.success) resetTimers(); else doLogout(); }
            ).fail(doLogout);
        }
    }

    function doLogout() { window.location.href = cfg.logout_url; }

    $(document).on('mousemove keydown click scroll touchstart', resetTimers);
    resetTimers();

}(jQuery));
