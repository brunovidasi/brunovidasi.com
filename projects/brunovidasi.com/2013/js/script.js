/*

SCRIPT.JS DO SITE BRUNOVIDASI

Customizado por Bruno Vieira
ID brunovidasi

*/

jQuery(function($) {

  $('#loading').after("<div id='loading-background'></div>");

  // PreLoad

  var amount = 1;
  var loaded = 0;

  $('.preload-image').each(function(index) {
      var img = $(this);
      img.load(function(){
          loaded++;
          if (loaded == amount){

             $('#preload-container').remove();
             $('#loading img#loading-gif').delay(1000).fadeToggle("fast", function(){
               $('#loading p img').fadeToggle("normal", function(){
                 $('#loading').remove();
                 $('#loading-background').fadeToggle("normal", function(){
                   $('#loading-background').remove();
                   $('.text').delay(100).fadeToggle("fast", function(){
                     $('.text').removeClass('hidden');
                     $('.text #home-container img').fadeIn(200, function(){
                       $('.text #home-container h6').fadeIn(200, function(){
                         $('#twitter').fadeIn(100, function(){
                           $('#googleplus').fadeIn(100, function(){
                             $('#mail').fadeIn(100, function(){
                               $('#facebook').fadeIn(100, function(){
                                 $('#youtube').fadeIn(100, function(){
                                   $('#linkedin').fadeIn(100, function(){
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

  // Decodificador
      var m = $.base64.decode('bWFpbHRvOmJydW5vQGJydW5vdmlkYXNpLmNvbQ==');
  // Parse 
    $('a.email').each(function() {
       this.href = m;
    });

  // Chirp - Twitter
    /*Chirp({
      target: 'tweet',
      user: 'brunovidasi',
      max: 1,
      count: 100,
      retweets: false,
      replies: false,
      cacheExpire: 1000 * 60 * 10
    });*/


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


(function() {
  jQuery(function($) {
    var $characters, $claim, $claimCursor, claims, createElements, delay, drawFrame, frame, index, mode, pos;

    claims = [
	'Sou um programador do Rio de Janeiro',
    'Utilizo os conceitos de Orientação a Objetos',
    'Desenvolvo aplicativos em Java Standard Edition',
    'Comecei a programar em C e C++',
    'Quer desenvolver seu site comigo?',
    'Estudo Sistemas de Informação',
    'Programo websites em PHP e JavaScript',
    'HTML5 & CSS3 é incrível!',
	'Utilizo o modelo MVC e o framework CodeIgniter',
    'Sou 100% a favor do Design Responsivo!',
    'Minimalismo... Menos é mais'];
	
    index = 0;
    frame = 0;
    pos = 0;
    mode = 0;
    delay = 250;
    $claim = $('#claim');
    $claimCursor = null;
    $characters = null;
    createElements = function() {
      var c, _i, _len, _ref;

      _ref = claims[index].split('');
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        c = _ref[_i];
        $claim.append("<span class='off'>" + c + "</span>");
      }
      $claimCursor = $('<span id="claim_cursor" class="off"> _</span>');
      $claim.append($claimCursor);
      return $characters = $claim.children();
    };
    createElements();
    drawFrame = function() {
      var $character;

      $character = $characters.eq(pos);
      if ($character.hasClass('off')) {
        $character.removeClass('off');
      } else {
        $character.addClass('off');
      }
      if (pos < claims[index].length) {
        $claimCursor.addClass('off');
      } else {
        if (Math.floor(frame / 10) % 2 === 0) {
          $claimCursor.addClass('off');
        } else {
          $claimCursor.removeClass('off');
        }
      }
      if (mode === 0) {
        if (pos < claims[index].length) {
          pos++;
        }
      } else {
        if (pos > 0) {
          pos--;
        } else {
          mode = 1 - mode;
          index++;
          index %= claims.length;
          $claim.empty();
          createElements();
        }
      }
      frame++;
      if (frame % delay === 0) {
        mode = 1 - mode;
      }
    };
    return window.setInterval(drawFrame, 1000 / 25);
  });

}).call(this);


/*
valor = -1; 

var frases = new Array(); 

// frases para troca | seguir a ordem 
frases[0] = "Utilizo os conceitos de Orientação a Objetos"; 
frases[1] = "Desenvolvo aplicativos em Java e PHP"; 
frases[2] = "Comecei a programar em C e C++"; 
frases[3] = "Quer desenvolver seu site comigo?"; 
frases[4] = "Estudo Sistemas de Informação"; 
frases[5] = "Sou Desenvolvedor Web na Vidasi Consultoria"; 
frases[6] = "Gosto de usar JQuery e JavaScript."; 
frases[7] = "Sou 100% a favor do Design Responsivo!"; 
frases[8] = "Sou viciado em tecnologia <3"; 
frases[9] = "Sou um programador do Rio de Janeiro"; 
frases[10] = "Sempre penso no minimalismo."; 

function aumenta() { 
valor = valor+1; 

if(valor > frases.length-1) { 
valor = 0; 
} 

setTimeout("aumenta()", 4200); 

document.getElementById('frases').innerHTML = frases[valor]; 

} 



var iam = [
  'Utilizo os conceitos de Orientação a Objetos',
  'Desenvolvo aplicativos em Java e PHP',
  'Comecei a programar em C e C++',
  'Quer desenvolver seu site comigo?',
  'Estudo Sistemas de Informação',
  'Sou Desenvolvedor Web na Vidasi Consultoria',
  'Gosto de usar JQuery e JavaScript.',
  'Sou 100% a favor do Design Responsivo!',
  'Sou viciado em tecnologia <3',
  'Sou um programador do Rio de Janeiro',
  'Sempre penso no minimalismo.'];
var index = 0; 

var anim = function() {
  if (index >= iam.length) {index = 0;}
  var claim = iam[index];
  index = index + 1;
  setTimeout(function() {
    $('#home-container p span').parent("p").fadeOut("slow", function(){
      $('#home-container p span').html(claim);
      $('#home-container p span').parent("p").fadeIn("slow", anim);
    });
  }, 4200);
}
setTimeout(anim, 4500);

*/
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


// Analytics
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-15307288-8']);
_gaq.push(['_trackPageview']);
(function() {var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);})();

setTimeout(function() {
// Twitter
!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");

// G+
(function() {var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;po.src = 'https://apis.google.com/js/plusone.js';var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);})();
}, 5000);