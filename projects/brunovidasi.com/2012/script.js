jQuery(function($) {

  $('#loading').after("<div id='loading-background'></div>");

  //PreLoad
  // Anzahl Bilder
  var amount = 17;
  // Anzahl geladener Bilder
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
                                     $('#elements').fadeIn(100, function(){
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



  // iPad Detection
  if ((navigator.userAgent.indexOf('iPhone') != -1) || (navigator.userAgent.indexOf('iPod') != -1) || (navigator.userAgent.indexOf('iPad') != -1) || (navigator.userAgent.indexOf('Android') != -1)) {
    $('#handy').removeClass('p-effect');
    $('#handy .background').css({
      "bottom" : "-120px",
      "left" : "-750px"
    });
  }

// Parallax positions
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
        // initialposition Handy Background
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
        // initialposition Handy Device
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
          // initialposition Handy Text
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



  // Handy Icon Navigation
  $('.nav-button').click(function(ev) {
    // alle Pfeile werden gelöscht
    $('img.help_arrow').fadeToggle("fast", function(){
      $('img.help_arrow').remove();
    });

    var container = $(this).attr('href');
    // alle Content Container werden ausgeblendet
    $('#header-parallax .content').addClass('hidden');
    $('.text').hide();
    // der richtige Content Container wird eingeblendet
    $(container).removeClass("hidden");
    $(".text").fadeIn("normal");
    // Allen Links wird die Classe active genommen
    $('.nav-button').removeClass('active');
    $('#nav li h3').removeClass('active');
    // Der gedrückte Link kriegt Classe active
    $(this).addClass('active');
    $(this).parent().children("h3").addClass('active');
    // Link wird deaktiviert
    return false;
  });

  // Email Decoder
      var m = $.base64.decode('bWFpbHRvOm1hcnRpbi5nYXVlckA5ZWxlbWVudHMuY29t');
  // Email Parser
    $('a.email').each(function() {
       this.href = m;
    });

  // Chirp
    Chirp({
      target: 'tweet',
      user: 'brunovidasi',
      max: 1,
      count: 100,
      retweets: false,
      replies: false,
      cacheExpire: 1000 * 60 * 10
    });

  // Social Container einfaden
  $('#social-container').delay(5000).fadeIn("normal");

  // Android Clock
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


  // Search
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

// Claim interval
var iam = [
  'Tenho uma namorada linda &lt;3',
  'Desenvolvo aplicativos em Java e PHP',
  'Sei programar também em C/C++',
  'Quer desenvolver seu site comigo?',
  'Estudo Sistemas de Informação',
  'Eu sou um programador do Rio de Janeiro',
  'Sou viciado em tecnologia',
  'Entre em contato comigo!',
  'Acho HTML5 & CSS3 incrível!!',
  'Sou desenvolvedor na Vidasi Consultoria'];
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


// Award interval
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

