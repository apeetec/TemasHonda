// Aguarda o DOM estar carregado
document.addEventListener('DOMContentLoaded', function() {
    
    // Variáveis globais
    const opcoes = document.querySelectorAll('.escolha input[type="radio"]');
    const videos = document.querySelectorAll('.video-box');
    const boxs_perguntas = document.querySelectorAll('.box-perguntas');

    ////////////////////////////////////////// Opções de presencial ou não
    opcoes.forEach((opcao, i) => {
        opcao.addEventListener('change', function(e) {
            const valor = e.target.value;
            const codigo = document.querySelector('.box-codigo');
            
            if (valor === 'Sim') {
                if (codigo) codigo.style.display = 'block';
                videos.forEach(video => {
                    video.style.display = 'none';
                });
            } else if (valor === 'Não') {
                if (codigo) codigo.style.display = 'none';
                videos.forEach(video => {
                    video.style.display = 'block';
                });
            }
        }, false);
    });


/////////////////////////////////////// Input de código
var inputs_codigo = document.querySelectorAll('.codigo input[type=text]');
var codigo_input = document.querySelector('.input_codigo_oculto');
var codigo_inputVetor = document.querySelectorAll('.input_codigo_oculto');
var vetor = [];
codigo_inputVetor.forEach(elemento => {
    vetor.push(elemento.value); 
});
// console.log(vetor);
inputs_codigo.forEach((input_codigo,i)=> {
    let timeoutId = null;
    
    // Adiciona eventos para foco e desfoque
    input_codigo.addEventListener('focus', function() {
        const dicaCodigo = document.getElementById('dica-codigo');
        if (dicaCodigo && this.value.trim() === '') {
            dicaCodigo.style.display = 'flex';
        }
    });
    
    input_codigo.addEventListener('blur', function() {
        const dicaCodigo = document.getElementById('dica-codigo');
        if (dicaCodigo) {
            setTimeout(function() {
                dicaCodigo.style.display = 'none';
            }, 2000);
        }
    });
    
    input_codigo.addEventListener('keyup', function(e) {
        var valor = e.target.value;
        var codigo = codigo_input.value;
        const avisoErro = document.getElementById('aviso-codigo-errado');
        const avisoSucesso = document.getElementById('aviso-codigo-correto');
        const dicaCodigo = document.getElementById('dica-codigo');
        
        // Remove classes de erro anteriores
        input_codigo.classList.remove('input-codigo-erro', 'input-codigo-sucesso');
        
        // Limpa timeout anterior se existir
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        
        // Se o campo estiver vazio, esconde todos os avisos e mostra a dica
        if (valor.trim() === '') {
            if (avisoErro) avisoErro.style.display = 'none';
            if (avisoSucesso) avisoSucesso.style.display = 'none';
            if (dicaCodigo && document.activeElement === input_codigo) {
                dicaCodigo.style.display = 'flex';
            }
            return;
        }
        
        // Esconde a dica quando começar a digitar
        if (dicaCodigo) {
            dicaCodigo.style.display = 'none';
        }
        
        if (vetor.includes(valor)) {
            // Código válido - esconde aviso de erro e mostra aviso de sucesso
            if (avisoErro) {
                avisoErro.style.display = 'none';
            }
            
            if (avisoSucesso) {
                avisoSucesso.style.display = 'flex';
                // Auto-esconde o aviso de sucesso após 4 segundos
                setTimeout(function() {
                    avisoSucesso.style.display = 'none';
                }, 4000);
            }
            
            // Adiciona classe de sucesso ao input
            input_codigo.classList.add('input-codigo-sucesso');
            setTimeout(function() {
                input_codigo.classList.remove('input-codigo-sucesso');
            }, 4000);
            
            var video = document.getElementById('video_'+valor);
            var bloco_perguntas = document.getElementById('perguntas_'+valor);
            
            // Corrigido: busca pelo campo presencial correto
            var campo_presencial = document.getElementById('presencial_'+valor);
            if (campo_presencial) {
                campo_presencial.setAttribute('value', 'Presencial');
                console.log('✅ Campo presencial atualizado para: Presencial (código: ' + valor + ')');
            } else {
                // Fallback: tenta encontrar qualquer campo presencial da página
                var campos_presenciais = document.querySelectorAll('input[name*="presencial"]');
                if (campos_presenciais.length > 0) {
                    campos_presenciais[0].setAttribute('value', 'Presencial');
                    console.log('✅ Campo presencial atualizado via fallback para: Presencial');
                } else {
                    console.log('❌ Nenhum campo presencial encontrado!');
                }
            }
            
            console.log('Código válido digitado:', valor);
            if(bloco_perguntas){
                bloco_perguntas.style.display = 'block';
            }
            if(video){
                video.style.display = 'block';
            }
        } else {
            // Código inválido - esconde aviso de sucesso e aguarda um tempo antes de mostrar o aviso de erro
            if (avisoSucesso) {
                avisoSucesso.style.display = 'none';
            }
            
            timeoutId = setTimeout(function() {
                if (valor.trim() !== '' && !vetor.includes(valor)) {
                    // Adiciona classe de erro ao input
                    input_codigo.classList.add('input-codigo-erro');
                    
                    // Mostra aviso de erro
                    if (avisoErro) {
                        avisoErro.style.display = 'flex';
                        
                        // Auto-esconde o aviso após 5 segundos
                        setTimeout(function() {
                            avisoErro.style.display = 'none';
                            input_codigo.classList.remove('input-codigo-erro');
                        }, 5000);
                    }
                    
                    console.log('❌ Código inválido digitado:', valor);
                }
            }, 1500); // Aguarda 1.5 segundos antes de mostrar o erro
        }
    }); 
});



///////////////////////////////////////////////////// Videos
document.querySelectorAll('.video-box').forEach(videoBox => {
    const video = videoBox.querySelector('.wp-video video');
    const playButton = videoBox.querySelector('.play');
    const pauseButton = videoBox.querySelector('.pause');
    // Adiciona o evento de play
    playButton.addEventListener('click', () => {
        video.play();
    });
    // Adiciona o evento de pause
    pauseButton.addEventListener('click', () => {
        video.pause();
    });
});
// var videos = document.querySelectorAll('.video-box .wp-video video');
// var plays = document.querySelectorAll('.video-box .play');
// var pauses = document.querySelectorAll('.video-box .pause');

// plays.forEach((play,i) => {
//     play.addEventListener('click', function(e) {
//         videos[i].play();
//     },false);
// });


// pauses.forEach((pause,c) => {
//     pause.addEventListener('click', function(e) {
//         videos[c].pause();
//     },false);
// });






//////////////////////////////////////// Descer as perguntas ao completar 99%
document.querySelectorAll('video').forEach((element,i) => {
    element.addEventListener('timeupdate', function() {
        var currentTime = element.currentTime;
        var duration = element.duration;
        var watchPoint = Math.floor((currentTime/duration) * 100);
        var checar = false;
        console.log(watchPoint);  // Aqui você pode fazer algo com o watchPoint, como mostrar perguntas ou trocar imagens. 100 é a porcentagem máxima que o vídeo pode chegar. 99% é a porcentagem para mostrar as perguntas.
        if(watchPoint >= 99) {
            checar = true;
            let box_perguntas = document.querySelectorAll('.box-perguntas');
            box_perguntas[i].style.display = 'block';
        }   
       if(checar == true){
        // [CRÍTICO-04] Correção: URL dinâmica via wp_localize_script, sem Id_usuario (evita IDOR), com nonce
        const inputValuecategoria = $('#categoria').val();
        $.ajax({
            url: connectrh_vars.progresso_url,
            type: 'POST',
            data: {
                // [CRÍTICO-04c] REMOVIDO Id_usuario — server usa get_current_user_id()
                categoria_video: inputValuecategoria,
                nonce:           connectrh_vars.progresso_nonce  // [CRÍTICO-04b] CSRF
            },
            success: function(data) {
                console.log('Progresso registrado:', data);
            },
            error: function(xhr, status, error) {
                console.error('Erro ao registrar progresso:', error);
            }
        });

       }
       console.log(checar);
    });

 
//   element.addEventListener('ended', () => {
//     if (element.currentTime >= element.duration * 0.99) {
//       let box_perguntas = document.querySelector('.box-perguntas');
//       box_perguntas.style.display = 'block';
//     }
//   });
    });

    // Sistema de feedback para formulários
    const forms = document.querySelectorAll('form[name]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const formName = this.getAttribute('name');
            const submitBtn = document.getElementById('submit-btn-' + formName);
            const loadingIndicator = document.getElementById('loading-' + formName);
            
            if (submitBtn && loadingIndicator) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
                loadingIndicator.style.display = 'block';
            }
        });
    });
    
    // Scroll para mensagem de sucesso se existir
    const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 500);
    }

}); // Fim do DOMContentLoaded