$(function() {
  //$('a[href*=#]:not([href=#])').click(function() {
  $('footer .footerMenu a').click(function() {
    if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
      if (target.length) {
        $('html,body').animate({
          scrollTop: target.offset().top
        }, 1000);
        return false;
      }
    }
  });
});


$(function() {
    $(window).scroll(function() {
        if($(this).scrollTop() != 0) {
            $('#back-to-top').fadeIn();    
        } else {
            $('#back-to-top').fadeOut();
        }
    });
 
    $('#back-to-top').click(function() {
        $('body,html').animate({scrollTop:0},1500);
    });    
});


