/* assets/js/painel.js */
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const collapseBtn = document.getElementById('collapse-btn');
    const mobileToggle = document.getElementById('mobile-toggle');

    // Botão de minimizar/maximizar no Computador
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Botão de Hambúrguer no Celular
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    // Fecha o menu se clicar fora dele no celular
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(event.target) && event.target !== mobileToggle) {
                sidebar.classList.remove('open');
            }
        }
    });
});