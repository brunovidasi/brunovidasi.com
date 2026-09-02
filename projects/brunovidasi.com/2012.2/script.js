/*
  PREVIEW BUILD — concatenated from (in original <script> load order):
  js/script.js + js/script2.js
  Source: 2012-brunovidasi.com/2012.2 (index.html)

  Removed from the originals for this static preview:
  - Google Analytics snippet (tracking).
  - Deferred loader of twitter widgets.js / Google +1 platform.js (external
    social widgets with no offline value; the container they enhance,
    #social-container, is display:none in the CSS anyway).
  - A call to a "Chirp" Twitter-timeline plugin that was never actually
    loaded by this page (already dead/broken in the original — calling it
    threw and silently skipped the code after it, including the clock and
    search box init). Removing it lets those two features work as intended.
  - An email "decoder" using $.base64, a plugin this page never loads either
    (same kind of dead code) — it also decoded to an unrelated leftover
    template author's email address, not Bruno's.
*/

jQuery(function($) {

  $('#loading').after("<div id='loading-background'></div>");

  // PreLoad

  var amount = 17;
  var loaded = 0;

  $('.preload-image').each(function(index) {
      var img = $(this);
      img.load(function(){
          loaded++;
          if (loaded == amount){

             $('#preload-container').remove();
             $('#loading img#loading-gif').delay(1500).fadeToggle("normal", function(){
               $('#loading p img').fadeToggle("normal", function(){
                 $('#loading').remove();
                 $('#loading-background').fadeToggle("normal", function(){
                   $('#loading-background').remove();
                   $('.text').delay(100).fadeToggle("fast", function(){
                     $('.text').removeClass('hidden');
                     $('.text #home-container img').fadeIn(200, function(){
                       $('.text #home-container p').fadeIn(200, function(){
                         $('#twitter').fadeIn(100, function(){
                           $('#googleplus').fadeIn(100, function(){
                             $('#mail').fadeIn(100, function(){
                               $('#androidweekly').fadeIn(100, function(){
                                 $('#youtube').fadeIn(100, function(){
                                   $('#blogger').fadeIn(100, function(){
                                     $('#vidasi').fadeIn(100, function(){
                                       $('#maps').fadeIn(100, function(){
                                         $('#more').fadeIn(100);
                                       });
                                     });
                                   });
                                 });
                               });
                             });
                           });
                         });
                       });
                     });
                   });
                 });
               });
             });
          }
      });
      img.attr("src", img.attr("src"));
  });



  // Detecção iPad
  if ((navigator.userAgent.indexOf('iPhone') != -1) || (navigator.userAgent.indexOf('iPod') != -1) || (navigator.userAgent.indexOf('iPad') != -1) || (navigator.userAgent.indexOf('Android') != -1)) {
    $('#handy').removeClass('p-effect');
    $('#handy .background').css({
      "bottom" : "-120px",
      "left" : "-750px"
    });
  }

// Posições do Parallax
  var winheight = $(window).height();
  var winwidth = $(window).width();

  var background_left = 150;
  var background_bottom = -190;
  var handy_left = 300;
  var handy_bottom = -100;
  var text_left = -850;

  if (winheight < 720) {
    background_bottom = -220;
    $('#handy .background').css('bottom','-220px');
    handy_bottom = -260;
    $('#handy .device').css('bottom','-260px');
    $('#handy .text').css('bottom', '175px');
  }

  if (winwidth < 1310) {
    background_left = 450;
    $('#handy .background').css('left','-450px');
    handy_left = 680;
    $('#handy .device').css('left','-680px');
    text_left = -390;
    $('#handy .text').css('left','390px');
  }



  $('div#header-parallax.parallax').parallax({
    'elements': [
      // Handy
      {
        // Posição Inicial do Background da Handy
        'selector': 'div#header-parallax  div#handy.p-effect .background',
        'properties': {
          'x': {
            'left': {
              'initial': background_left,
              'multiplier': 0.1,
              'invert': true
            }
          },
          'y': {
            'bottom': {
              'initial': background_bottom,
              'multiplier': 0.1,
              'invert': false
            }
          }
        }
      },
      {
        // Posição Inicial do Device da Handy
        'selector': 'div#header-parallax  div#handy.p-effect .device',
        'properties': {
          'x': {
            'left': {
              'initial': handy_left,
              'multiplier': 0.15,
              'invert': true
            }
          },
          'y': {
            'bottom' : {
              'initial': handy_bottom,
              'multiplier': 0.15,
              'invert': false
            }
          }
        }
      },
        {
          // Posição Inicial do Texto da Handy
          'selector': 'div#header-parallax  div#handy.p-effect .text',
          'properties': {
            'x': {
              'left': {
                'initial': text_left,
                'multiplier': 0.2,
                'invert': true
              }
            }
          }
        }
    ]
  });



  // Navegação de Ícone da Handy
  $('.nav-button').click(function(ev) {
    $('img.help_arrow').fadeToggle("fast", function(){
      $('img.help_arrow').remove();
    });

    var container = $(this).attr('href');

    $('#header-parallax .content').addClass('hidden');
    $('.text').hide();

    $(container).removeClass("hidden");
    $(".text").fadeIn("normal");

    $('.nav-button').removeClass('active');
    $('#nav li h3').removeClass('active');

    $(this).addClass('active');
    $(this).parent().children("h3").addClass('active');

    return false;
  });

  // Android Clock - Relógio da Handy

  $.fn.androClock = function() {
    var days = ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
    var months = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    function getTime() {
      var date = new Date(),
      hour = date.getHours();
      return {
        day: days[date.getDay()],
        date: date.getDate(),
        month: months[date.getMonth()],
        hour: appendZero(hour),
        minute: appendZero(date.getMinutes())
      };
    }
    function appendZero(num) {
      if (num < 10) {
        return "0" + num;
      }
      return num;
    }
    function refreshClock() {
      var now = getTime();
      $('#date').html(now.day + "<br>" + now.date + ' de ' + now.month);
      $('#time').html(now.hour + ":" + now.minute);
      setTimeout(function() {
        refreshClock();
      }, 10000);
    }
    refreshClock();
  };
  $('#andro-clock').androClock();


  // Search - Sistema de Pesquisa da Handy

  var landingInput = $('#search');
  landingInput.keydown(function(e){
    if( e.keyCode === 13) {
      if(($(this).attr("value") != "") && ($(this).attr("value") != " ")) {
        var newSearch = "https://www.google.com/#q=" + ($(this).attr('value').replace(/\s+/g, '+'));
        window.open(newSearch,"_blank");
      }
      return
    }
  });

});

// "Eu sou o Bruno e..."

var iam = [
  'Tenho uma namorada linda &lt;3',
  'Desenvolvo aplicativos em Java e PHP',
  'Sei programar também em C/C++',
  'Quer desenvolver seu site comigo?',
  'Estudo Sistemas de Informação',
  'Sou Desenvolvedor Web na Vidasi Consultoria',
  'Sou viciado em tecnologia',
  'Gosto de programar sites com JavaScript',
  'Acho HTML5 & CSS3 incrível!!',
  'Sou um programador do Rio de Janeiro'];
var index = 0;

var anim = function() {
  if (index >= iam.length) {index = 0;}
  var claim = iam[index];
  index = index + 1;
  setTimeout(function() {
    $('#home-container p span').parent("p").fadeOut("fast", function(){
      $('#home-container p span').html(claim);
      $('#home-container p span').parent("p").fadeIn("normal", anim);
    });
  }, 4200);
}
setTimeout(anim, 3500);


// Intervalos do Awards (fora de uso no momento)
var awards = [

  ];
var indexx = 0;

var animation = function() {
  if (indexx >= awards.length) {indexx = 0;}
  var claimawards = awards[indexx];
  indexx = indexx + 1;
  setTimeout(function() {
    $('#awwwards').fadeOut("fast", function(){
      $('#awwwards').html(claimawards);
      $('#awwwards').fadeIn("slow", animation);
    });
  }, 10000);
}
animation();

/*
SCRIPT2.JS DO SITE BRUNOVIDASI
*/

$(function(){

	/* GERAL */

/* JS enabled user
   -------------------------------------------------------------------------- */
	$('html').removeClass('no-js').addClass('has-js');


/* Touch ou mouse ?
   -------------------------------------------------------------------------- */
	var ua = navigator.userAgent;
    function is_touch_device() {
        try {
            document.createEvent("TouchEvent");
            return true;
        } catch (e) {
            return false;
        }
    }

    if ((is_touch_device()) || ua.match(/(iPhone|iPod|iPad)/)
    || ua.match(/BlackBerry/) || ua.match(/Android/)) {
        $('body').addClass('touch');
    } else {
        $('body').addClass('mouse');
		initCustomScroll();
    }


/* Sumir MiniMenu Copyright e Social-Container depois de 400 px
   -------------------------------------------------------------------------- */

   $(document).ready(function(){
   var tam = $(window).width();

   if (tam >=400 ){
     $("#minimenu").show();
	 $("#copyright").show();
	 $("#social-container").show();
	 $(".device").show();
   }else{
     $("#minimenu").hide();
	 $("#copyright").hide();
	 $("#social-container").hide();
	 $(".device").hide();
   }

});


/* Redimensionar dispatcher
   -------------------------------------------------------------------------- */
	$(window).on('resize', function() {
		resizeBackground();
		initSlideshow();
		iphoneSlideshowResize();
		initIscroll();
		iscrollResize();
	});


/* Ajustar imagem ao passar o mouse
   -------------------------------------------------------------------------- */
	$('body').on('hover', '.hover-img', function(e) {
		if ( !$(this).hasClass('current') ) {
			var img = $(this).find('img');
			if (img.length == 0)
				img = $(this);

			var src = img.attr('src');
			if (src) {
				if ( e.type == 'mouseenter' ) {
					var newSrc = src.replace(new RegExp("(\.png|\.jpg)", "i"), "_active$1");
				} else if ( e.type == 'mouseleave' ) {
					var newSrc = src.replace('_active','');
				}
				img.attr('src',newSrc);
			}
		}
	});


/* Emulação para navegadores antigos
   -------------------------------------------------------------------------- */
	var formElements = $('input:text, input:password, textarea');
	formElements.on('focus', function() {
		$('label[for='+$(this).attr('id')+']').hide();
	}).on('blur', function() {
		if ($(this).attr('value') == '' || $(this).attr('value') == $('label[for='+$(this).attr('id')+']').text()) {
			$('label[for='+$(this).attr('id')+']').show();
		}
	}).on('refresh', function() {
		if ($(this).attr('value') == '' || $(this).attr('value') == $('label[for='+$(this).attr('id')+']').text()) {
			$('label[for='+$(this).attr('id')+']').show();
		} else {
			$('label[for='+$(this).attr('id')+']').hide();
		}
	}).trigger('refresh');



	/* BACKGROUND */

/* Redimensiona Background
   -------------------------------------------------------------------------- */
	var backgroundRatio = 16/9; // 2560x1440

	function resizeBackground() {
		var background = $('img.page-background');

		if ($(window).width() > 1000) {
			background.width($(window).width());
			background.height($(window).width()/backgroundRatio);

			if (background.height() < $(window).height()) {
				background.height($(window).height());
				background.width($(window).height()*backgroundRatio);
			}
		} else {
			background.width(background.parent().height()*backgroundRatio);
			background.height(background.parent().height());
			if (background.height() < $(document).height()) {
				background.height($(document).height());
				background.width($(document).height()*backgroundRatio);
			}
			if (background.width() < $(window).width()) {
				background.width($(window).width());
				background.height($(window).width()/backgroundRatio);
			}
		}
	}
	resizeBackground();



	/* LOADER */

/* Site loading
   -------------------------------------------------------------------------- */
	$(window).load(function() {
		siteLoaded();
	});



	/* NAV */


/* Nav page
   -------------------------------------------------------------------------- */
	var pageAnimated = false;

	$('#nav-page a').on('click', function(e) {
		if (pageAnimated === false) {
			if($(this).hasClass('next'))
				changePage(true, true);
			else
				changePage(false, true);
		}

		e.preventDefault();
	})

	function changePage(next, animate) {
		if (pageAnimated === false) {
			pageAnimated = true;

			var currentPage = $('.page-current');
			var currentPageIndex = currentPage.index('.page');
			var nextPage = '';
			if (next === true) {
				nextPage = currentPage.next('.page');
			} else if (next === false) {
				nextPage = currentPage.prev('.page');
			} else {
				nextPage = next;
			}
			var nextPageIndex = nextPage.index('.page');

			if (nextPage.length == 1 && !nextPage.hasClass('page-current') && !nextPage.hasClass('page-text')) {

				var container = $('<div></div>');
				container.css({overflow:'hidden',position:'absolute', top:0, left:0, width:0, height:'100%', zIndex:2})

				nextPage.css({width:$(window).width()});
				container.insertAfter(nextPage);
				nextPage.appendTo(container);

				if (nextPageIndex > currentPageIndex) {
					container.css({right:0, left:'auto'});
					nextPage.css({right:0, left:'auto'});
				} else {
					nextPage.css({left:0});
				}

				var animationDuration = 1000;
				if (animate === false)
					animationDuration = 0;

				container.animate({width:$(window).width()}, {duration:animationDuration, easing:'easeInOutExpo', complete:function() {
					currentPage.removeClass('page-current').attr('style','');
					nextPage.addClass('page-current').attr('style','').insertAfter(container);
					container.remove();
					checkNavPage();
					pageAnimated = false;
				}});

				if (nextPage.is('#projetos'))
					$('body').addClass('dark');
				else
					$('body').removeClass('dark');
			} else {
				pageAnimated = false;
			}
		}
	}

	function checkNavPage() {
		var currentPage = $('.page-current');
		var id = currentPage.attr('id');
		window.location.hash = '#'+id;

		if (currentPage.is('.page:last') || currentPage.next('.page').hasClass('page-text'))
			$('#nav-page .next').fadeOut(200, 'linear');
		else
			$('#nav-page .next').fadeIn(200, 'linear');

		if (currentPage.is('.page:first'))
			$('#nav-page .previous').fadeOut(200, 'linear');
		else
			$('#nav-page .previous').fadeIn(200, 'linear');

		$('#nav-main .current').removeClass('current');
		$('#nav-main a[data-target="#'+id+'"]').closest('li').addClass('current');
	}


/* Nav mobile
   -------------------------------------------------------------------------- */
	$('#nav-mobile .menu-link').on('click', function(e) {
		var container = $('#nav-mobile .container');

		$('#nav-mobile').addClass('opened');

		container.css({left:-$(window).width()});
		container.animate({left:0}, {duration:500, easing:'easeInOutCubic'});

		e.preventDefault();
	});

	$('#nav-mobile ul a').on('click', function(e) {
		var container = $('#nav-mobile .container');

		container.animate({left:-$(window).width()}, {duration:500, easing:'easeInOutCubic', complete:function() {
			$('#nav-mobile').removeClass('opened');
			$(this).attr('style','');
		}});

		if ($(this).attr('data-target')) {
			var id = $(this).attr('data-target');
			var page = $(id);

			if (pageAnimated === false && !page.hasClass('page-current')) {
				changePage($(id), false);
			}

			e.preventDefault();
		}
	});




	/* GALERIA */

/* Ao clicar na Imagem
   -------------------------------------------------------------------------- */
	$('#gallery a').on('click', function(e) {

		imagePopin($(this).find('.image'));

		e.preventDefault();
	});


/* POPIN da Imagem - Página 2
   -------------------------------------------------------------------------- */
	function imagePopin(image) {
		var newImage = image.clone();

		var src = newImage.attr('src');
		if (src) {
			var newSrc = src.replace('-mobile','');
			newImage.attr('src',newSrc);
		}

		newImage.attr('class', '').attr('style','');
		var popin = $('<div id="popin"></div>');
		newImage.appendTo(popin);
		popin.prependTo('body');

		var origWidth = newImage.width();
		var origHeight = newImage.height();

		popin.hide();

		var imageRatio = origWidth/origHeight;
		var newWidth = origWidth;
		var newHeight = origHeight;

		if ($(window).width() > 1000) {
			if (newWidth>$(window).width()-60) {
				newWidth = $(window).width()-60;
				newHeight = newWidth/imageRatio;
			}
			if (newHeight>$(window).height()-60) {
				newHeight = $(window).height()-60
				newWidth = newHeight*imageRatio;
			}

			var newMarginLeft = -(newWidth/2+15);
			var newMarginTop = -(newHeight/2+15);

		} else {
			if (newWidth>$(window).width()) {
				newWidth = $(window).width();
				newHeight = newWidth/imageRatio;
			}
			if (newHeight>$(window).height()) {
				newHeight = $(window).height();
				newWidth = newHeight*imageRatio;
			}

			var newMarginLeft = -(newWidth/2);
			var newMarginTop = -(newHeight/2);
		}

		newImage.css({marginTop:newMarginTop, marginLeft:newMarginLeft, position:'absolute', top:'50%', left:'50%', width:newWidth, height:newHeight})

		popin.fadeIn(300, 'linear');

	}


/* Fechar POPIN
   -------------------------------------------------------------------------- */
	$('body').on('click', '#popin', function() {
		$(this).fadeOut(300, 'linear', function() {
			$(this).remove();
		})
	});



	/* TEXT PAGE */

/* Abrir Página
   -------------------------------------------------------------------------- */
	$('.page-text-link').on('click', function(e) {
		var page = $($(this).attr('data-page'));

		page.css({display:'none', left:0});
		page.fadeIn(500, 'linear');

		e.preventDefault();
	});


/* Fechar Termos
   -------------------------------------------------------------------------- */
	$('.page-text .page-background, .page-text .back-link').on('click', function(e) {
		$(this).closest('.page-text').fadeOut(500, 'linear', function() {
			$(this).attr('style','');
		});
	});


/* Custom scroll
   -------------------------------------------------------------------------- */
	function initCustomScroll() {
		$('.page-text .page-content').mCustomScrollbar();
	}



	/* MUDANÇA DE ENDEREÇO */

	var windowHash = window.location.hash;
	var checkAddressTimeout = setInterval(checkAddress, 100);


/* Checar Endereço
   -------------------------------------------------------------------------- */
	function checkAddress() {
		if (window.location.hash != windowHash) {
			windowHash = window.location.hash;
			if (windowHash == '')
				windowHash = '#inicio';

			var id = windowHash
			var page = $(id);

			if (pageAnimated === false && !page.hasClass('page-current')) {
				changePage($(id), true);
			}
		}
	}



	/* INIT */

	var hash = window.location.hash;
	var firstPage = $('.page').filter(hash);
	if (firstPage.length == 1) {
		$('.page-current').removeClass('page-current');
		firstPage.addClass('page-current');
		checkNavPage();
	}


/* Init site depois do loading
   -------------------------------------------------------------------------- */
	function siteLoaded() {
		$('#site-loader').fadeTo(1000, 0, 'linear', function() {
			$('#inicio').addClass('loaded');
			$(this).remove();
		});

		$(window).trigger('resize');
	}


/* Init iscroll
   -------------------------------------------------------------------------- */
	var iScrolls = null;

	function initIscroll() {

		if (iScrolls != null) {
			for(var i = 0; i <= iScrolls.length; i++) {
				var temp = iScrolls[i];
				if (temp)
					temp.destroy();
			}
			$('.iscroll-barV').remove();
			$('.iscroll, .iscroll > div').attr('style','');
		}
		iScrolls = null;
		var elements = null;

		if ($(window).width() <= 1000) {
			elements = $('.iscroll');
			if ($(window).height() >= 600) {
				elements = elements.filter('.tablet');
			}
		} else if ($('body').hasClass('touch')) {
			elements = $('.page-text .iscroll');
		}

		if (elements != null && elements.length > 0) {
			iScrolls = new Array();
			elements.each(function() {
				var temp = new iScroll(this, {
					hScroll: false,
					vScroll: true,
					hScrollbar: false,
					vScrollbar: true,
					hideScrollbar: true,
					scrollbarClass: 'iscroll-bar'
				});

				iScrolls.push(temp);
			});
		}
	}
	initIscroll();


/* Redimensiona Iscroll
   -------------------------------------------------------------------------- */
	function iscrollResize() {
		var pageContent = $('.page-content');
			pageContent.width($(window).width());
	}
	iscrollResize();

});
