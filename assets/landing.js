const menuButton = document.querySelector(".landing-menu-button");
const menu = document.querySelector(".landing-menu");

if (menuButton && menu) {
    menuButton.addEventListener("click", function () {
        const open = menu.classList.toggle("open");
        menuButton.setAttribute("aria-expanded", open ? "true" : "false");
        menuButton.textContent = open ? "×" : "☰";
    });

    menu.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            menu.classList.remove("open");
            menuButton.setAttribute("aria-expanded", "false");
            menuButton.textContent = "☰";
        });
    });
}
