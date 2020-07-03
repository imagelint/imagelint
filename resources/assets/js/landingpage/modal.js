// Sticky Header
(function () {
    function closeModal() {
        document.getElementById('english-info').classList.add('hidden');
        document.getElementById('backdrop').classList.add('hidden');
    } 
    if((navigator.languages ? navigator.languages[0] : (navigator.language || navigator.userLanguage)) !== 'de') {
        document.body.classList.add('en');
        document.getElementsByClassName('close')[0].addEventListener('click', closeModal);
        document.getElementById('backdrop').addEventListener('click', closeModal);
    }
})();
