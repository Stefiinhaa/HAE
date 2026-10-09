<?php
// ==============================================================================
// LISTA DE VÍDEOS DE AJUDA DO SISTEMA
// Para colocar os seus vídeos reais: Vá no YouTube > Compartilhar > Incorporar 
// E copie apenas a URL que fica dentro do src="..." (Ex: https://www.youtube.com/embed/XXXX)
// ==============================================================================
$videos_ajuda = [
    [
        'titulo' => 'Primeiros passos no sistema',
        'thumb'  => 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
        'url'    => 'a'
    ],
    [
        'titulo' => 'Como enviar uma Nova Solicitação',
        'thumb'  => 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
        'url'    => 'a'
    ],
    [
        'titulo' => 'Como enviar o Relatório Mensal',
        'thumb'  => 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg',
        'url'    => 'a'
    ],
];
?>

<!-- O BOTÃO E O DROPDOWN -->
<div class="help-menu-container">
    <button class="btn-help-icon" id="btnHelpToggle" title="Central de Ajuda e Tutoriais">
        <i class="fa-solid fa-circle-question"></i>
    </button>
    
    <div class="help-dropdown" id="helpDropdown">
        <div class="help-header">
            <h3>Vídeos Explicativos</h3>
        </div>
        <div class="help-body">
            <?php foreach($videos_ajuda as $video): ?>
                <div class="video-item" onclick="abrirVideo('<?php echo $video['url']; ?>', '<?php echo addslashes($video['titulo']); ?>')">
                    <div class="video-thumb">
                        <img src="<?php echo $video['thumb']; ?>" alt="Thumbnail">
                        <i class="fa-solid fa-play video-play-icon"></i>
                    </div>
                    <div class="video-title"><?php echo htmlspecialchars($video['titulo']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- O MODAL INVISÍVEL DO REPRODUTOR DE VÍDEO -->
<div class="video-modal-overlay" id="videoModalOverlay" onclick="fecharVideo()">
    <div class="video-modal-box" onclick="event.stopPropagation();">
        <div class="video-modal-header">
            <h3 id="videoModalTitle">Tutorial HAE</h3>
            <button class="btn-close-video" onclick="fecharVideo()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="video-wrapper">
            <iframe id="videoIframe" src="" allow="autoplay; encrypted-media; fullscreen" allowfullscreen></iframe>
        </div>
    </div>
</div>

<!-- O MOTOR JAVASCRIPT DOS VÍDEOS -->
<script>
    const btnHelp = document.getElementById('btnHelpToggle');
    const dropdownHelp = document.getElementById('helpDropdown');
    if(btnHelp && dropdownHelp) {
        btnHelp.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownHelp.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
            if (!dropdownHelp.contains(e.target) && !btnHelp.contains(e.target)) {
                dropdownHelp.classList.remove('active');
            }
        });
    }

    const modalVideo = document.getElementById('videoModalOverlay');
    const iframe = document.getElementById('videoIframe');
    const title = document.getElementById('videoModalTitle');

    function abrirVideo(url, tituloVideo) {
        if(dropdownHelp) dropdownHelp.classList.remove('active'); 
        title.innerText = tituloVideo;
        iframe.src = url + "?autoplay=1"; // Força rodar sozinho
        modalVideo.style.display = 'flex';
    }

    function fecharVideo() {
        modalVideo.style.display = 'none';
        iframe.src = ""; // Corta o iframe pra mutar o som imediatamente
    }
</script>