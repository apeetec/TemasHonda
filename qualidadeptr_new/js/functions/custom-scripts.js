// Variáveis globais
var opcoes = document.querySelectorAll('.escolha input[type="radio"]');
let videos = document.querySelectorAll('.video-box');
let boxs_perguntas = document.querySelectorAll('.box-perguntas');

////////////////////////////////////////// Opções de presencial ou não
opcoes.forEach((opcao,i)=> {
    opcao.addEventListener('change',function(e) {
        var valor = e.target.value;
        // console.log(valor);
        if(valor === 'Sim'){
            let codigo = document.querySelector('.box-codigo');
            let video = document.querySelector('.video-box');
            codigo.style.display = 'block';
           
            videos.forEach(video => {
                video.style.display = 'none';
            });
        }
        else if(valor === 'Não'){
            let codigo = document.querySelector('.box-codigo');
            let video = document.querySelector('.video-box');
            codigo.style.display = 'none';
            videos.forEach(video => {
                video.style.display = 'block';
            });
        }
    },false);
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
    input_codigo.addEventListener('keyup', function(e) {
        var valor = e.target.value;
        var codigo = codigo_input.value;
        
        // var codigo_tamanho = codigo.length;       
        // if(valor === codigo){
        //     // alert('Código válido');      
        //     videos[i].style.display = 'block';
        //     boxs_perguntas[i].style.display = 'block';
        //     console.log("Existe");
        // }   

        if (vetor.includes(valor)) {
            var video = document.getElementById('video_'+valor);
            var bloco_perguntas = document.getElementById('perguntas_'+valor);
            document.getElementById('presencial_'+valor).setAttribute('value', 'Presencial');
            console.log(valor);
            if(bloco_perguntas){
                bloco_perguntas.style.display = 'block';
              
                // Ajax para enviar a confirmação do presencial
                $.ajax({
                    url: 'https://qualidadeptr.com.br/wp-content/themes/tema_em_uso/new_theme_qualidade_ptr/sql/confirmacaoPresencial.php',
                    type: 'POST',
                    data: {
                        confirmacao: 'Presencial',
            
                    },
                    success: function(data) {
                        // Manipula a resposta do servidor
                        console.log('Resposta do servidor:', data);
                    },
                    error: function(xhr, status, error) {
                        // Manipula erros da requisição
                        console.error('Erro:', error);
                    }
                });



            }
            if(video){
                video.style.display = 'block';
            }
            // document.getElementById('confirmar_presencial_segundo_video').setAttribute('value', 'Presencial');
            // console.log("Existe");
            // console.log(video);
        }
    }); 
});



///////////////////////////////////////////////////// Videos
document.querySelectorAll('.video-box').forEach(videoBox => {
    const video = videoBox.querySelector('.wp-video video');
    const playButton = videoBox.querySelector('.play');
    const pauseButton = videoBox.querySelector('.pause');
    const fullScreen = videoBox.querySelector('.fullScreen');
    // Adiciona o evento de play
    playButton.addEventListener('click', () => {
        video.play();
    });
    // Adiciona o evento de pause
    pauseButton.addEventListener('click', () => {
        video.pause();
    });
    fullScreen.addEventListener('click',()=>{
        video.requestFullscreen();
    })
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
        const inputValue = $('#id_usuario').val();
        const inputValuecategoria = $('#categoria').val();
        console.log(id_usuario);
        $.ajax({
            url: 'https://qualidadeptr.com.br/wp-content/themes/tema_em_uso/new_theme_qualidade_ptr/sql/progresso-video.php',
            type: 'POST',
            data: {
                Id_usuario: inputValue,
                categoria_video: inputValuecategoria
    
            },
            success: function(data) {
                // Manipula a resposta do servidor
                console.log('Resposta do servidor:', data);
            },
            error: function(xhr, status, error) {
                // Manipula erros da requisição
                console.error('Erro:', error);
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