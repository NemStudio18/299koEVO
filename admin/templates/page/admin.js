document.addEventListener("DOMContentLoaded", function() {
    if (document.querySelector('.page-admin table')) {
        document.querySelector('.page-admin tr:first-child .up').style.display = 'none';
        document.querySelector('.page-admin tr:last-child .down').style.display = 'none';
    }
});