if('serviceWorker' in navigator) {
  navigator.serviceWorker.register('service-worker.js');
}

$(document).ready(function() {

  $('html').css({'background-image':'url(img/backgrounds/img_' + Math.floor(Math.random()*62) + '.jpg)'})

  $('.default').hide();
  $('.country').hide();

  var cookie_country = Cookies.get('country'),
      cookie_region = Cookies.get('region');

  if(cookie_country && cookie_region) {
    set_info(cookie_country, cookie_region);
  } else if(cookie_country) {
    set_info(cookie_country, "");
  } else {
    $.get("https://ipinfo.io", function(response) {
      set_info(response.country, response.region);
      Cookies.set("country", response.country, { expires: 30 });
      Cookies.set("region", response.region, { expires: 30 });
    }, "jsonp");
  }

  function set_info(country, region) {

    $.getJSON( "./country.json", function(data) {

      if(country in data) {

        var info;

        if(country == "CA"){
          if(region in data[country]) {
            info = data[country][region];
          }
        } else {
          info = data[country];
        }

        if (info != null){

          $('.default').hide();

          var if_danger = info["if_danger"] + ' <a class="tel-danger" href="tel:'+ info["tel_danger"] +'">'+ info["tel_danger"] +'</a>';

          $('.country .support-title').html(info["support_title"]);
          $('.country .tel-company').html(info["tel_company"]);
          $('.country .tel-no span').html(info["tel_no"]);
          $('.country .tel-no').attr('href', 'tel:' + info["tel_no"]);
          $('.country .module-flag-icon').attr('src', info["flag_icon"]);
          $('.country .if-danger').html(if_danger);

          $('.country').show();

        } else {
          get_default();
        }

      } else {
        get_default();
      }

    })
    .error(function() {
      get_default();
    });
  }

  function get_default() {

    $('.default').show();
    $('.country').hide();

  }

});
