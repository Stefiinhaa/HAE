<?php
// ==============================================================================
// LISTA DE VÍDEOS DE AJUDA DO SISTEMA
// ==============================================================================
$videos_ajuda = [
    [
        'titulo' => 'Primeiros passos e configuração de perfil',
        'thumb'  => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png',
        'url'    => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png'
    ],
    [
        'titulo' => 'Como enviar uma Nova Solicitação',
        'thumb'  => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png',
        'url'    => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png'
    ],
    [
        'titulo' => 'Consultando Meus Projetos',
        'thumb'  => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png',
        'url'    => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png'
    ],
    [
        'titulo' => 'Como enviar o Relatório Mensal',
        'thumb'  => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png',
        'url'    => 'https://sites.diretasistemas.com.br/wp-content/uploads/sites/5/2019/11/11150435/em-breve-1.png'
    ]
];
?>

<!-- ESTILOS BLINDADOS CONTRA CACHE (Exclusivos deste componente) -->
<style>
    .help-menu-container { position: relative; margin-left: 15px; display: flex; align-items: center; }
    .btn-help-icon { background: #f1f3f5; color: #555; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #ddd; cursor: pointer; transition: 0.2s; }
    .btn-help-icon:hover { background: var(--fatec-red, #b20000); color: white; border-color: var(--fatec-red, #b20000); transform: scale(1.05); }
    
    .help-dropdown { display: none; position: absolute; top: 50px; right: 0; width: 320px; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #e9ecef; z-index: 2000; overflow: hidden; animation: slideDown 0.2s ease-out; text-align: left; }
    .help-dropdown.active { display: block; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    
    .help-header { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; }
    .help-header h3 { margin: 0; font-size: 14px; color: #333; text-align: center; font-weight: 700; }
    
    .help-body { max-height: 400px; overflow-y: auto; padding: 10px 0; }
    .help-body::-webkit-scrollbar { width: 6px; }
    .help-body::-webkit-scrollbar-track { background: #f1f1f1; }
    .help-body::-webkit-scrollbar-thumb { background: #bbb; border-radius: 4px; }
    .help-body::-webkit-scrollbar-thumb:hover { background: #999; }
    
    .video-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; cursor: pointer; transition: 0.2s; border-bottom: 1px solid #f5f5f5; }
    .video-item:last-child { border-bottom: none; }
    .video-item:hover { background: #fdf2f2; }
    
    .video-thumb { width: 80px; height: 50px; background: #eee; border-radius: 4px; overflow: hidden; flex-shrink: 0; position: relative; }
    .video-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .video-play-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: rgba(255,255,255,0.9); font-size: 16px; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
    .video-title { font-size: 13px; color: #444; line-height: 1.3; font-weight: 500; margin: 0; }
    
    .video-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 3000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .video-modal-box { width: 90%; max-width: 800px; background: #fff; border-radius: 8px; overflow: hidden; position: relative; animation: popIn 0.3s ease-out; }
    @keyframes popIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    
    .video-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #2c3e50; color: white; }
    .video-modal-header h3 { margin: 0; font-size: 15px; font-weight: 600; }
    .btn-close-video { background: none; border: none; color: white; font-size: 20px; cursor: pointer; opacity: 0.8; transition: 0.2s; }
    .btn-close-video:hover { opacity: 1; }
    
    .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; }
    .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
</style>

<!-- ESTRUTURA HTML DO BOTÃO E MODAL -->
<div class="help-menu-container">
    <button class="btn-help-icon" id="btnHelpToggle" title="Central de Ajuda e Tutoriais" type="button">
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

<div class="video-modal-overlay" id="videoModalOverlay" onclick="fecharVideo()">
    <div class="video-modal-box" onclick="event.stopPropagation();">
        <div class="video-modal-header">
            <h3 id="videoModalTitle">Tutorial HAE</h3>
            <button type="button" class="btn-close-video" onclick="fecharVideo()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="video-wrapper">
            <iframe id="videoIframe" src="" allow="autoplay; encrypted-media; fullscreen" allowfullscreen></iframe>
        </div>
    </div>
</div>

<!-- LÓGICA DE FUNCIONAMENTO (JAVASCRIPT) -->
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
        iframe.src = url + "?autoplay=1"; 
        modalVideo.style.display = 'flex';
    }

    function fecharVideo() {
        modalVideo.style.display = 'none';
        iframe.src = ""; 
    }
</script>