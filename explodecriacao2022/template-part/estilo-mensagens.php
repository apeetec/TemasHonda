<?php
/**
 * Estilo das caixas de mensagem usadas na home, no fallback e na página 404.
 *
 * Fica em um único lugar para que os três templates compartilhem a mesma aparência.
 * Só um deles é renderizado por requisição, então não há risco de duplicar o CSS.
 */

// Impede o acesso direto ao script fora do ambiente do WordPress
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<style>
  .aviso-etapa {
    max-width: 720px;
    margin: 0 auto;
    padding: 30px 34px;
    border-radius: 12px;
    border: 1px dashed rgba(255, 255, 255, .45);
    background: rgba(255, 255, 255, .07);
    text-align: center;
  }
  .aviso-etapa h2 { margin: 0 0 12px; }
  .aviso-etapa p { margin: 0 0 8px; font-size: 15px; line-height: 1.65; }
  .aviso-etapa .botao-etapa {
    display: inline-block;
    margin-top: 16px;
    padding: 12px 26px;
    border-radius: 8px;
    background: linear-gradient(90deg, #5b9b99, #54c5cf);
    color: #fff;
    font-weight: 700;
    text-decoration: none;
  }
  .aviso-etapa .aviso-etapa-admin {
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid rgba(255, 255, 255, .25);
    font-size: 13px;
    opacity: .9;
  }
  .aviso-etapa .aviso-etapa-admin code {
    padding: 1px 6px;
    border-radius: 4px;
    background: rgba(0, 0, 0, .25);
  }
</style>
