document.addEventListener("DOMContentLoaded", function() {
    const mobileToggle = document.getElementById("mobile-toggle");
    const collapseBtn = document.getElementById("collapse-btn");
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.querySelector(".main-content");
    
    // Cria a camada escura (overlay) para o menu no celular
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Lógica para CELULAR (Abre/Fecha a barra inteira)
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener("click", function() {
            sidebar.classList.add("active");
            overlay.classList.add("active");
        });

        // Clicar fora do menu (na parte escura) fecha ele
        overlay.addEventListener("click", function() {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });
    }

    // Lógica para DESKTOP (Minimiza/Maximiza deixando só os ícones)
    if (collapseBtn && sidebar && mainContent) {
        collapseBtn.addEventListener("click", function(e) {
            e.preventDefault(); // Evita recarregar a página
            sidebar.classList.toggle("collapsed");
            mainContent.classList.toggle("expanded");
        });
    }
});