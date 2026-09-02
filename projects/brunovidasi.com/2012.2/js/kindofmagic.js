function web() {
  /* menicko + offline/online odkazy */
  $('#navi li a, #contact #arrows a').css({backgroundPosition: '0 0'});
  $('#navi li a, #contact #arrows a').hover(function(){
          $(this).stop().animate({backgroundPosition: '0 -16px'}, {queue: false, duration: 150});
          if($(this).parent().hasClass('online'))
          {
           $(this).parent().css({'background-position' : 'right bottom'});
          }
          else
          {
           $(this).parent().css({'background-position' : 'left bottom'});
          }
      },
      function(){
          $(this).stop().animate({backgroundPosition: '0 0'}, {queue: false, duration: 150});
          if($(this).parent().hasClass('online'))
          {
           $(this).parent().css({'background-position' : 'right top'});
          }
          else
          {
           $(this).parent().css({'background-position' : 'left top'});
          }
      }
  );

  /* offline/online odkazy */
  $('#contact #arrows a, #navi li.offline a, #navi li.online a').click(function(){
      var link = $(this).html();
      $('body').load(link +'.html #loadp', function(){
        $('body').attr('id', 'galleryp');
        document.title = 'Mediocore - '+ link.charAt(0).toUpperCase() + link.slice(1);
        gallery();
        web();
      });
      return false;
  });

  /* hlavicka */
  $('h1 a').click(function(){
      $('body').load('index-2.html', function(){
        $('body').attr('id', 'contact');
        web(); 
      });
      return false;
  });

  /* dalsi detail */
  $('#galleryp #arrows .next').click(function(){
      if($('#thumbs ul li a.active').parent().next('li').length>0) {
      
          var thumbsWrap = $('body').width()-57;
          var thumbsPerPage = Math.floor(thumbsWrap/143);
          var thumbsCount = Math.ceil(($('#thumbs ul li a.active').parent().prevAll('li').size()+1)/thumbsPerPage);

          if($('#thumbs ul li a.active').parent().prevAll('li').size()+2 > ((thumbsPerPage)*thumbsCount)) {
              $('#thumbs ul').animate({left: -thumbsPerPage*143*thumbsCount}, {queue: false, duration: 250});
          }

          var url = $('a', $('#thumbs ul li a.active').parent().next('li')).attr('href');
          $('#thumbs ul li a.active span').css({borderWidth: '10px', height: '60px', width: '100px'})
          var last = $('#thumbs ul li a.active').removeClass('active');
          $('span', last).animate({borderWidth: '0', height: '80px', width: '120px'}, {queue: false, duration: 150});
          $('span', $(last).parent().next('li')).animate({borderWidth: '10px', height: '60px', width: '100px'}, {queue: false, duration: 150, complete: function(){
            $(this).parent().addClass('active');
            checkArrows();
          }});

          $('#contentp img').animate({marginLeft: -$('body').width()}, {queue: false, duration: 150, complete: function(){
            galleryDetail(url, 'next');
          }});
      }
      if($('#thumbs ul li a.active').parent().next('li').next('li').length) {
        alert('ted dalsi');
      }
      return false;
  });

  /* predchozi detail */
  $('#galleryp #arrows .prev').click(function(){
      if($('#thumbs ul li a.active').parent().prev('li').length>0) {
  
          var thumbsWrap = $('body').width()-57;
          var thumbsPerPage = Math.floor(thumbsWrap/143);
          var thumbsCount = Math.ceil(($('#thumbs ul li a.active').parent().prevAll('li').size()+1)/thumbsPerPage);
          
          if($('#thumbs ul li a.active').parent().prevAll('li').size() < ((thumbsPerPage*thumbsCount)-(thumbsPerPage-1))) {
              $('#thumbs ul').animate({left: -((thumbsPerPage*143*(thumbsCount-1))-(thumbsPerPage*143))}, {queue: false, duration: 250});
          }

          var url = $('a', $('#thumbs ul li a.active').parent().prev('li')).attr('href');
          $('#thumbs ul li a.active span').css({borderWidth: '10px', height: '60px', width: '100px'})
          var last = $('#thumbs ul li a.active').removeClass('active');
          $('span', last).animate({borderWidth: '0', height: '80px', width: '120px'}, {queue: false, duration: 150});
          $('span', $(last).parent().prev('li')).animate({borderWidth: '10px', height: '60px', width: '100px'}, {queue: false, duration: 150, complete: function(){
            $(this).parent().addClass('active');
            checkArrows();
          }});

          $('#contentp img').animate({marginLeft: $('body').width()}, {queue: false, duration: 150, complete: function(){
            galleryDetail(url, 'prev');
          }});
      }
      return false;
  });

  /* dalsi nahledy */
  $('#thumbs .thumbsRight').click(function(){

      var thumbsWrap = $('body').width()-57;
      var thumbsPerPage = Math.floor(thumbsWrap/143);
      var thumbsCount = Math.ceil(($('#thumbs ul li a.active').parent().prevAll('li').size()+1)/thumbsPerPage);
      $('#thumbs ul').animate({left: -thumbsPerPage*143*thumbsCount}, {queue: false, duration: 250});
      $('#thumbs ul li a.active').removeClass('active');
      $('a', '#thumbs ul li').eq(thumbsPerPage*thumbsCount).addClass('active');
      galleryDetail($('#thumbs ul li a.active').attr('href'), 'next');
      checkArrows();

      return false;
  });

  /* predchozi nahledy */
  $('#thumbs .thumbsLeft').click(function(){

      var thumbsWrap = $('body').width()-57;
      var thumbsPerPage = Math.floor(thumbsWrap/143);
      var thumbsCount = Math.ceil(($('#thumbs ul li a.active').parent().prevAll('li').size()+1)/thumbsPerPage);
      $('#thumbs ul').animate({left: -((thumbsPerPage*143*(thumbsCount-1))-(thumbsPerPage*143))}, {queue: false, duration: 250});
      $('#thumbs ul li a.active').removeClass('active');
      $('a', '#thumbs ul li').eq((thumbsPerPage*(thumbsCount-1))-thumbsPerPage).addClass('active');
      galleryDetail($('#thumbs ul li a.active').attr('href'), 'prev');
      checkArrows();

      return false;
  });

  /* galerie */
  $('#thumbs ul li a span').css({height: '80px', width: '120px'});
  $('#thumbs ul li a').hover(function(){
          $('span', this).stop().animate({borderWidth: '10px', height: '60px', width: '100px'}, {queue: false, duration: 150});
      },
      function(){
          $('span', this).stop().animate({borderWidth: '0', height: '80px', width: '120px'}, {queue: false, duration: 150});
      }
  );
}

/* Kontrola sipek */
function checkArrows() {
    if($('#thumbs ul li a.active').parent().prev().length == '0') {
      $('#arrows .prev, #thumbs .thumbsLeft').hide();
    }
    else
    {
      $('#arrows .prev, #thumbs .thumbsLeft').show();
    }
  
    if($('#thumbs ul li a.active').parent().next().length == '0') {
      $('#arrows .next, #thumbs .thumbsRight').hide();
    }
    else
    {
      $('#arrows .next, #thumbs .thumbsRight').show();
    }
    var thumbsWrap = $('body').width()-57;
    var thumbsPerPage = Math.floor(thumbsWrap/143);
    var thumbsCount = Math.ceil(($('#thumbs ul li a.active').parent().prevAll('li').size()+1)/thumbsPerPage);
    if((thumbsCount) == Math.ceil($('#thumbs ul li').size()/thumbsPerPage)) {
      $('#thumbs .thumbsRight').hide();
    }
}

function galleryDetail(src, side) {
    $('#loadp').addClass('loading');
    var img = new Image();
    img.src = src;
    img.onload = function() {
        $('#contentp').html(this);
        $('#contentp').css({marginTop: -this.height/2-30, marginLeft: -this.width/2});

        if(side=='prev')
        {
          $('#contentp img').css({'margin-left' : -$('body').width()});
          $('#contentp img').animate({marginLeft: 0}, {queue: false, duration: 150, complete: function(){
          }});
        }
        else if (side=='next')
        {
          $('#contentp img').css({'margin-left' : $('body').width(), 'top' : '0'});
          $('#contentp img').animate({marginLeft: 0}, {queue: false, duration: 150, complete: function(){
          }});
        }
        detailMove();
        $('#loadp').removeClass('loading');
        
        if($('#thumbs ul li a.active').parent().prev().length) {
          var leftPreImg = new Image();
          leftPreImg.src = $('a', $('#thumbs ul li a.active').parent().prev()).attr('href');
        }
        if($('#thumbs ul li a.active').parent().next().length) {
          var rightPreImg = new Image();
          rightPreImg.src = $('a', $('#thumbs ul li a.active').parent().next()).attr('href');
        }        
    };
}

function detailMove() {
    $('body').mousemove(function(e){
      if($('#contentp img').height() > $('body').height()) {
        my = -Math.ceil((e.pageY/($('body').height()/($('#contentp img').height()-($('body').height()-390))))-(($('#contentp img').height()-$('body').height())/2));
        
        $('#contentp img').css({'margin-top' : '180px', 'top' : my});
      }      
    })
}

function gallery() {
    $('#thumbs ul li a').click(function(){

        $('#thumbs ul li a.active span').css({borderWidth: '10px', height: '60px', width: '100px'})
        var last = $('#thumbs ul li a.active').removeClass('active');
        $('span', last).animate({borderWidth: '0', height: '80px', width: '120px'}, {queue: false, duration: 150});
        $('span', this).animate({borderWidth: '10px', height: '60px', width: '100px'}, {queue: false, duration: 150, complete: function(){
          $(this).parent().addClass('active');
          checkArrows();
        }});
        var url = $(this).attr('href');

        $('#contentp img').animate({marginLeft: -$('body').width()}, {queue: false, duration: 150, complete: function(){
          galleryDetail(url, 'next');
        }});

        return false;
    });

    if($('body').attr('id')!='contact') {
        $('#thumbs').animate({bottom: '0'}, {queue: false, duration: 250});
  
        $('#thumbs ul li:first a').addClass('active');
        checkArrows();
        galleryDetail($('#thumbs ul li:first a').attr('href'));
    }
}

$(document).ready(function(){
  web();

  gallery();
})