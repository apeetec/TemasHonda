// document.addEventListener('DOMContentLoaded', function() {
//     var carousel = document.querySelectorAll('.carousel');
//     var instance_carousel = M.Carousel.init(carousel, {
//         fullWidth:true
//     });
//     var panel = document.getElementById('panel');
//     var bts = document.querySelectorAll('.bt');
//     var instance = M.Carousel.getInstance(panel);
//     console.log(instance);
//     bts.forEach((bt,indice) => {
//         bt.addEventListener("click", function(){
//             console.log(indice);
//             instance.set(indice);
//         });
//     });

// });
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.sidenav');
    var instances = M.Sidenav.init(elems, {
      // specify options here
    });
  });

  document.addEventListener('DOMContentLoaded', function() {
    const elems = document.querySelectorAll('select');
    const instances = M.FormSelect.init(elems, {
      // specify options here
    });
  });
  document.addEventListener('DOMContentLoaded', function () {
    const popover = document.getElementById("modal1");
    popover.addEventListener("toggle", (event) => {
        if (event.newState === "open") {
            console.log("🔄 Modal aberto");
            $('.zoom').magnify({
              magnifiedWidth: 2200,
              magnifiedHeight:2200
            });
        } 

    });
  });

// Modal
//   document.addEventListener('DOMContentLoaded', function () {
//     // Seleciona todos os elementos que possuem a classe 'modal'
//     var elems = document.querySelectorAll('.modal');
    
//     // Inicializa o modal com as opções desejadas
//     var instances = M.Modal.init(elems, {
//       onOpenEnd:function(){

      //   function toggleMute() {
      //   var video = document.getElementById("videoId");
      //   if(video.muted){
      //     video.muted = false;
      //     video.play();
      //   } else {
      //     debugger;
      //     video.muted = true;
      //     video.play();
      //   }
      // }
      // $(document).ready(function(){
      //   setTimeout(toggleMute,3000);
      // });


//       },
//       onCloseEnd:function(){
//         var video=document.getElementById("videoId");
//         video.pause();
//       },
//       dismissible:false
//     });
  
    // Abre o primeiro modal automaticamente ao carregar a página
//     if (instances.length > 0) {
//       instances[0].open();
//     }
//   });
  

//   document.addEventListener('DOMContentLoaded', function () {
//     var video=document.getElementById("videoId");
//     // Inicia a reprodução ao passar o mouse
//     video.addEventListener('mouseenter', function () {
//       video.play();
//     });
  
//     // Pausa o vídeo ao tirar o mouse

//   });


document.addEventListener('DOMContentLoaded', function() {
  const elems = document.querySelectorAll('.dropdown-trigger');
  const instances = M.Dropdown.init(elems, {
    coverTrigger:false,
    constrainWidth:false
  });
});
  