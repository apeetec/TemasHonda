<article class="container space-top">
    <div class="atracao">
        <p>Atração: <?php echo $atracao;?></p>
        <p><?php echo $tema;?></p>
    </div>
    <div class="video-box" id="video_<?php echo $codigo;?>">
        <?php   
            echo $tag_video;
        ?>
        <div class="progress-bar"><div class="line"></div></div>
        <div class="buttons-play-and-pause">
            <p class="play video-button <?php echo $sanitiza_term_name;?>" id="play">
                <i class="fas fa-play"></i>
            </p>
            <p class="pause video-button <?php echo $sanitiza_term_name;?>" id="pause">
                <i class="fas fa-pause"></i>
            </p>
        </div>
    </div>
</article>