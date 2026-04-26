
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
