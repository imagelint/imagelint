// Sticky Header
(function() {
    var fixed = false;
    var header = document.getElementsByTagName('header')[0]
    window.addEventListener('scroll', function(e) {
        if (document.scrollingElement.scrollTop >= 5) {
            if(!fixed) {
                fixed = true;
                header.classList.add('fixed');
            }
        }
        else {
            if(fixed) {
                fixed = false;
                header.classList.remove('fixed');
            }
        }
    });
})();
