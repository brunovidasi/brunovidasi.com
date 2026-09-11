/*

SCRIPT2.JS DO SITE BRUNOVIDASI

Customizado por Bruno Vieira 
ID brunovidasi
Créditos gerais para Starmatic

*/

$(function(){

//$("#home-container").hide();
	
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
	 $("#social-container").show();
	 $(".device").show();	 
	 $(".versites").show();	 
	 $(".verjava").show();	 
   }else{
	 $("#social-container").hide();
	 $(".device").hide();
   } 
   
   if (tam < 1310){
	$('#minimenu').hide();
   }

});


/* Trocar a Home & Contato para Versão Mobile
   -------------------------------------------------------------------------- */
   
   $(document).ready(function(){
   var tam = $(window).width();
 
	if (tam >=400 ){
		$("#iniciopc").show();
		$("#iniciomobile").hide();
		$("#bg-normal").show();
		$("#bg-mobile").hide();
		$(".copyright").show();
	}else{
		$("#iniciopc").hide();
		$("#iniciomobile").show();
		$("#bg-normal").hide();
		$("#bg-mobile").show();
		$(".copyright").hide();
	} 

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
	$('.gallery a, .gallery1 a').on('click', function(e) {
		
		imagePopin($(this).find('.image, .image0'));
		
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
		var pageContent = $('.page');
			pageContent.width($(window).width());
	}
	iscrollResize();
	
});