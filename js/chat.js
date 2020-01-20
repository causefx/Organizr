/*jslint browser: true*/
/*global $, jQuery, alert*/

$(document).ready(function () {

    "use strict";

    $('.chat-left-inner > .chatonline').slimScroll({
        height: '100%',
        position: 'right',
        size: "0px",
        color: '#dcdcdc'

    });
    $(function () {
        $(window).on("load", function () { // On load
            $('.chat-list').css({
                'height': (($(window).height()) - 470) + 'px'
            });
        });
        $(window).on("resize", function () { // On resize
            $('.chat-list').css({
                'height': (($(window).height()) - 470) + 'px'
            });
        });
    });

    // this is for the left-aside-fix in content area with scroll

    $(function () {
        $(window).on("load", function () { // On load
            $('.chat-left-inner').css({
                'height': (($(window).height()) - 240) + 'px'
            });
        });
        $(window).on("resize", function () { // On resize
            $('.chat-left-inner').css({
                'height': (($(window).height()) - 240) + 'px'
            });
        });
    });


    $(".open-panel").on("click", function () {
        $(".chat-left-aside").toggleClass("open-pnl");
        $(".open-panel i").toggleClass("ti-angle-left");
    });

});
