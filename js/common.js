/*
 *
 *   ADVANTAGE - Responsive Admin Theme
 *   version 1.1
 *
*/

var datetime = null,
        date = null;

var update = function () {
    date = moment(new Date())
    datetime.html(date.format('h:mm A'));
};

$(window).on('load', function(){
    //Preloader
    setTimeout(function(){
        $('.preloader').fadeOut(400);
    }, 600);
    //Scroll for menu
    $(".gn-scroller").mCustomScrollbar({
      theme:"minimal",
      scrollInertia: 300
    });
});

$(function () {
  //Menu
  new gnMenu( document.getElementById( 'gn-menu' ) );

  //Choose Language
  [].slice.call( document.querySelectorAll( 'select.cs-select' ) ).forEach( function(el) {
        new SelectFx(el);
  } );

  //Refresh button
  $('.repeat-btn').click(function(e){
    var refreshBox = $(this).closest('div.content-box');
    $("<div class='refresh-preloader'><div class='la-timer la-dark'><div></div></div></div>").appendTo(refreshBox).fadeIn(300);

    setTimeout(function(){
      var refreshPreloader = refreshBox.find('.refresh-preloader'),
          deletedRefreshBox = refreshPreloader.fadeOut(300, function(){
          refreshPreloader.remove();
      });
    },1500);

    e.preventDefault();
  });



  //MetisMenu
  $('.metismenu').metisMenu();

  //Menu width on mobile devices
  function mobileMenuWidth() {
    $(".gn-menu-main ul.gn-menu").css("width", $(window).width() + "px");
  }

  if($(window).width() <= 422) {
    mobileMenuWidth();
  }

  //Smooth scrolling for body
  /*$("body").niceScroll({
    railpadding: {top:56,right:0,left:0,bottom:0},
    scrollspeed: 30,
    mousescrollstep: 60
  });*/


  //Waves effect on buttons
  Waves.attach('.waves', ['waves-float']);
  Waves.init();

  //Current Date
  var dt = new Date(),
      year = dt.getFullYear(),
      months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      month = dt.getMonth(),
      days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
      day = dt.getDay(),
      date = dt.getDate();

  $('.year').append(year);
  $('.month').append(months[month]);
  $('.weekday').append(days[day]);
  $('.date').append(date);

  //Current Time
  datetime = $('.current-time')
  update();
  setInterval(update, 60000);

  //Close Content Box
  $('.close-btn').click(function(e){
    var closedBox = $(this).closest('div.content-box').remove();
    e.preventDefault();
  });

  //Fullscreen mode
  function toggleFullScreen() {
    if (!document.fullscreenElement &&    // alternative standard method
        !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement ) {  // current working methods
      if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen();
      } else if (document.documentElement.msRequestFullscreen) {
        document.documentElement.msRequestFullscreen();
      } else if (document.documentElement.mozRequestFullScreen) {
        document.documentElement.mozRequestFullScreen();
      } else if (document.documentElement.webkitRequestFullscreen) {
        document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.msExitFullscreen) {
        document.msExitFullscreen();
      } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
    }
  }

  $('.fullscreen').click(function(e){
    toggleFullScreen();
    $('.fullscreen i').toggleClass("mdi-fullscreen mdi-fullscreen-exit");
    e.preventDefault();
  });

  // Full height of content
  //$('#content').css("height", $(window).height() - 56 + "px" );
  //$("div").find(".iframe").css("height", $(window).height() - 56 + "px" );
  //Fixed gn-menu
  $('.fix-nav').click(function(e){
    if($(window).width() > 422) {
      if($(this).hasClass("selected")){
        $('.gn-menu-wrapper').removeClass("gn-open-fixed");
        $('.gn-menu-wrapper .fix-nav i').removeClass("mdi-pin-off").addClass("mdi-pin");
        $('.gn-menu-wrapper .fix-nav').removeClass("selected");
        $('body').removeClass("mini-nav");
          if (localStorageSupport) {
                localStorage.setItem("fixNav",'off');
          }
      } else{
        $('.gn-menu-wrapper').addClass("gn-open-fixed");
        $('.gn-menu-wrapper .fix-nav i').removeClass("mdi-pin").addClass("mdi-pin-off");
        $('.gn-menu-wrapper .fix-nav').addClass("selected");
        $('body').addClass("mini-nav");
          if (localStorageSupport) {
                localStorage.setItem("fixNav",'on');
          }
      }
    }
    e.preventDefault();
  });


  $(window).resize(function () {
    if($(window).width() <= 422) {
      mobileMenuWidth();
      $('.gn-menu-wrapper').removeClass("gn-open-fixed");
      $('body').removeClass("mini-nav");
      if (localStorageSupport) {
            localStorage.setItem("fixNav",'off');
      }
    }
    else{
      $(".gn-menu-main ul.gn-menu").css("width", 270 + "px");
      if($(".fix-nav").hasClass("selected")){
        $('.gn-menu-wrapper').addClass("gn-open-fixed");
        $('body').addClass("mini-nav");
        if (localStorageSupport) {
              localStorage.setItem("fixNav",'on');
        }
      }
    }
  });

  if($(window).width() > 422) {
    if (localStorageSupport) {

        var fixNav = localStorage.getItem("fixNav");

        if (fixNav == 'on') {
          $('.gn-menu-wrapper').addClass("gn-open-fixed");
          $('.gn-menu-wrapper .fix-nav i').removeClass("mdi-pin").addClass("mdi-pin-off");
          $('.gn-menu-wrapper .fix-nav').addClass("selected");
          $('body').addClass("mini-nav");
        }

        if (fixNav == 'off') {
          $('.gn-menu-wrapper').removeClass("gn-open-fixed");
          $('.gn-menu-wrapper .fix-nav i').removeClass("mdi-pin-off").addClass("mdi-pin");
          $('.gn-menu-wrapper .fix-nav').removeClass("selected");
          $('body').removeClass("mini-nav");
        }
    }
  }

});


// check if browser support HTML5 local storage
function localStorageSupport() {
    return (('localStorage' in window) && window['localStorage'] !== null)
}


