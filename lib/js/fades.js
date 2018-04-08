function fadeIn(id) {
    document.getElementById(id).style.visibility = "visible";
    document.getElementById(id).classList.remove("fadeOut");
    document.getElementById(id).classList.add("fadeIn");
}
function fadeOut(id) {
    document.getElementById(id).classList.remove("fadeIn");
    document.getElementById(id).classList.add("fadeOut");
}