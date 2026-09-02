(function($) {
  "use strict"; // Start of use strict

  // Smooth scrolling using jQuery easing
  $('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function() {
    if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
      if (target.length) {
        $('html, body').animate({
          scrollTop: (target.offset().top)
        }, 1000, "easeInOutExpo");
        return false;
      }
    }
  });

  // Closes responsive menu when a scroll trigger link is clicked
  $('.js-scroll-trigger').click(function() {
    $('.navbar-collapse').collapse('hide');
  });

  // Activate scrollspy to add active class to navbar items on scroll
  $('body').scrollspy({
    target: '#sideNav'
  });

})(jQuery); // End of use strict

// Inline page script (moved from index.php)
$(".line_read_more").on("click", function() {
  $(this).parent().find('.line-clamp').removeClass('line-clamp-2');
  $(this).hide();
  $(this).parent().find('.line_read_less').show();
})

$(".line_read_less").on("click", function() {
  $(this).parent().find('.line-clamp').addClass('line-clamp-2');
  $(this).hide();
  $(this).parent().find('.line_read_more').show();
})
