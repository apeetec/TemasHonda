
    var links = [];
    var titles = [];

    // Clique nas miniaturas
    $('.box_img').on('click', '.thumb', function () {
      $(this).toggleClass('thumbChecked');
      $(this).css("border", $(this).hasClass('thumbChecked') ? "2px solid #c32032" : "none");

      if ($(this).hasClass('thumbChecked')) {
        links.push($(this).attr('src'));
        titles.push($(this).next('h2').text()); // Assume que o título está logo após a imagem
      } else {
        links = links.filter(link => link !== $(this).attr('src'));
        titles = titles.filter(title => title !== $(this).next('h2').text());
      }

      updateDownloadButton();
    });

    // Selecionar todas as miniaturas
    $('.selectAll').on('click', function () {
      $('.thumb').addClass('thumbChecked').css("border", "2px solid #c32032");
      links = $('.thumb').map(function() { return $(this).attr('src'); }).get();
      titles = $('.box_img').find('h2').map(function() { return $(this).text(); }).get();

      updateDownloadButton();
    });

    // Desmarcar todas as miniaturas
    $('.deselectAll').on('click', function () {
      $('.thumb').removeClass('thumbChecked').css("border", "none");
      links = [];  // Limpa os arrays
      titles = [];
      updateDownloadButton();
    });

    // Função para mostrar/ocultar botão de download
    function updateDownloadButton() {
      if (links.length !== 0) {
        $('.download').css("display", "block");
      } else {
        $('.download').css("display", "none");
      }
    }

    // Remover seleção
    $('.box_img').on('click', '.thumbChecked', function () {
      $(this).removeClass('thumbChecked').addClass('thumb');
      $(this).css("border", "none");
      var itemtoRemove = $(this).attr('src');
      links.splice($.inArray(itemtoRemove, links), 1);
      titles.splice($.inArray($(this).next('h2').text(), titles), 1);
      console.log(links, titles);

      updateDownloadButton();
    });

    // Função para gerar ZIP
    function generateZIP(links, titles) {
      var zip = new JSZip();
      var zipFilename = "Pictures.zip";

      // Mapeia os links em promessas para controle assíncrono
      var promises = links.map(function (url, i) {
        return new Promise(function (resolve, reject) {
          JSZipUtils.getBinaryContent(url, function (err, data) {
            if (err) {
              console.error("Erro ao carregar o arquivo: ", err);
              reject(err);
            } else {
              var title = titles[i];
              var filename = title + '.jpg';
              filename = filename.replace(/[\/\*\|\:\<\>\?\"\\]/gi, '').replace(/^https?:\/\/i\.imgur\.com\//, '');
              zip.file(filename, data, { binary: true });
              resolve();
            }
          });
        });
      });

      // Aguarda que todas as promessas sejam concluídas
      Promise.all(promises).then(function () {
        zip.generateAsync({ type: 'blob' }).then(function (content) {
          saveAs(content, zipFilename);
        });
      }).catch(function (err) {
        console.error("Erro ao gerar o ZIP: ", err);
      });
    }

    // Ação ao clicar no botão de download
    $('.download').on('click', function () {
      generateZIP(links, titles);
    });