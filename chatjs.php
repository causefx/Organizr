<?php
require_once("user.php");
$USER = new User("registration_callback");
$userpic = md5( strtolower( trim( $USER->email ) ) );
header("Content-type: application/javascript");
?>
function dblDigit(d) {
    if (d < 10) { d = "0" + d;}
    return d;
}
function formatAMPM(date) {
  var hours = date.getHours();
  var minutes = date.getMinutes();
  var month = dblDigit(date.getMonth() + 1);
  var day = dblDigit(date.getDate());
  var ampm = hours >= 12 ? 'P' : 'A';
  hours = hours % 12;
  hours = hours ? hours : 12; // the hour '0' should be '12'
  hours = dblDigit(hours);
  minutes = minutes < 10 ? '0'+minutes : minutes;
  var strTime = month + '-' + day + ' ' + hours + ':' + minutes + '' + ampm;
  return strTime;
}
var isMobile = false; //initiate as false
var d = new Date();
var timezone = d.getTimezoneOffset();
// device detection
if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) isMobile = true;
/*
 * JavaScript MD5
 * https://github.com/blueimp/JavaScript-MD5
 *
 * Copyright 2011, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 *
 * Based on
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */

/* global define */

;(function ($) {
  'use strict'

  /*
  * Add integers, wrapping at 2^32. This uses 16-bit operations internally
  * to work around bugs in some JS interpreters.
  */
  function safeAdd (x, y) {
    var lsw = (x & 0xFFFF) + (y & 0xFFFF)
    var msw = (x >> 16) + (y >> 16) + (lsw >> 16)
    return (msw << 16) | (lsw & 0xFFFF)
  }

  /*
  * Bitwise rotate a 32-bit number to the left.
  */
  function bitRotateLeft (num, cnt) {
    return (num << cnt) | (num >>> (32 - cnt))
  }

  /*
  * These functions implement the four basic operations the algorithm uses.
  */
  function md5cmn (q, a, b, x, s, t) {
    return safeAdd(bitRotateLeft(safeAdd(safeAdd(a, q), safeAdd(x, t)), s), b)
  }
  function md5ff (a, b, c, d, x, s, t) {
    return md5cmn((b & c) | ((~b) & d), a, b, x, s, t)
  }
  function md5gg (a, b, c, d, x, s, t) {
    return md5cmn((b & d) | (c & (~d)), a, b, x, s, t)
  }
  function md5hh (a, b, c, d, x, s, t) {
    return md5cmn(b ^ c ^ d, a, b, x, s, t)
  }
  function md5ii (a, b, c, d, x, s, t) {
    return md5cmn(c ^ (b | (~d)), a, b, x, s, t)
  }

  /*
  * Calculate the MD5 of an array of little-endian words, and a bit length.
  */
  function binlMD5 (x, len) {
    /* append padding */
    x[len >> 5] |= 0x80 << (len % 32)
    x[(((len + 64) >>> 9) << 4) + 14] = len

    var i
    var olda
    var oldb
    var oldc
    var oldd
    var a = 1732584193
    var b = -271733879
    var c = -1732584194
    var d = 271733878

    for (i = 0; i < x.length; i += 16) {
      olda = a
      oldb = b
      oldc = c
      oldd = d

      a = md5ff(a, b, c, d, x[i], 7, -680876936)
      d = md5ff(d, a, b, c, x[i + 1], 12, -389564586)
      c = md5ff(c, d, a, b, x[i + 2], 17, 606105819)
      b = md5ff(b, c, d, a, x[i + 3], 22, -1044525330)
      a = md5ff(a, b, c, d, x[i + 4], 7, -176418897)
      d = md5ff(d, a, b, c, x[i + 5], 12, 1200080426)
      c = md5ff(c, d, a, b, x[i + 6], 17, -1473231341)
      b = md5ff(b, c, d, a, x[i + 7], 22, -45705983)
      a = md5ff(a, b, c, d, x[i + 8], 7, 1770035416)
      d = md5ff(d, a, b, c, x[i + 9], 12, -1958414417)
      c = md5ff(c, d, a, b, x[i + 10], 17, -42063)
      b = md5ff(b, c, d, a, x[i + 11], 22, -1990404162)
      a = md5ff(a, b, c, d, x[i + 12], 7, 1804603682)
      d = md5ff(d, a, b, c, x[i + 13], 12, -40341101)
      c = md5ff(c, d, a, b, x[i + 14], 17, -1502002290)
      b = md5ff(b, c, d, a, x[i + 15], 22, 1236535329)

      a = md5gg(a, b, c, d, x[i + 1], 5, -165796510)
      d = md5gg(d, a, b, c, x[i + 6], 9, -1069501632)
      c = md5gg(c, d, a, b, x[i + 11], 14, 643717713)
      b = md5gg(b, c, d, a, x[i], 20, -373897302)
      a = md5gg(a, b, c, d, x[i + 5], 5, -701558691)
      d = md5gg(d, a, b, c, x[i + 10], 9, 38016083)
      c = md5gg(c, d, a, b, x[i + 15], 14, -660478335)
      b = md5gg(b, c, d, a, x[i + 4], 20, -405537848)
      a = md5gg(a, b, c, d, x[i + 9], 5, 568446438)
      d = md5gg(d, a, b, c, x[i + 14], 9, -1019803690)
      c = md5gg(c, d, a, b, x[i + 3], 14, -187363961)
      b = md5gg(b, c, d, a, x[i + 8], 20, 1163531501)
      a = md5gg(a, b, c, d, x[i + 13], 5, -1444681467)
      d = md5gg(d, a, b, c, x[i + 2], 9, -51403784)
      c = md5gg(c, d, a, b, x[i + 7], 14, 1735328473)
      b = md5gg(b, c, d, a, x[i + 12], 20, -1926607734)

      a = md5hh(a, b, c, d, x[i + 5], 4, -378558)
      d = md5hh(d, a, b, c, x[i + 8], 11, -2022574463)
      c = md5hh(c, d, a, b, x[i + 11], 16, 1839030562)
      b = md5hh(b, c, d, a, x[i + 14], 23, -35309556)
      a = md5hh(a, b, c, d, x[i + 1], 4, -1530992060)
      d = md5hh(d, a, b, c, x[i + 4], 11, 1272893353)
      c = md5hh(c, d, a, b, x[i + 7], 16, -155497632)
      b = md5hh(b, c, d, a, x[i + 10], 23, -1094730640)
      a = md5hh(a, b, c, d, x[i + 13], 4, 681279174)
      d = md5hh(d, a, b, c, x[i], 11, -358537222)
      c = md5hh(c, d, a, b, x[i + 3], 16, -722521979)
      b = md5hh(b, c, d, a, x[i + 6], 23, 76029189)
      a = md5hh(a, b, c, d, x[i + 9], 4, -640364487)
      d = md5hh(d, a, b, c, x[i + 12], 11, -421815835)
      c = md5hh(c, d, a, b, x[i + 15], 16, 530742520)
      b = md5hh(b, c, d, a, x[i + 2], 23, -995338651)

      a = md5ii(a, b, c, d, x[i], 6, -198630844)
      d = md5ii(d, a, b, c, x[i + 7], 10, 1126891415)
      c = md5ii(c, d, a, b, x[i + 14], 15, -1416354905)
      b = md5ii(b, c, d, a, x[i + 5], 21, -57434055)
      a = md5ii(a, b, c, d, x[i + 12], 6, 1700485571)
      d = md5ii(d, a, b, c, x[i + 3], 10, -1894986606)
      c = md5ii(c, d, a, b, x[i + 10], 15, -1051523)
      b = md5ii(b, c, d, a, x[i + 1], 21, -2054922799)
      a = md5ii(a, b, c, d, x[i + 8], 6, 1873313359)
      d = md5ii(d, a, b, c, x[i + 15], 10, -30611744)
      c = md5ii(c, d, a, b, x[i + 6], 15, -1560198380)
      b = md5ii(b, c, d, a, x[i + 13], 21, 1309151649)
      a = md5ii(a, b, c, d, x[i + 4], 6, -145523070)
      d = md5ii(d, a, b, c, x[i + 11], 10, -1120210379)
      c = md5ii(c, d, a, b, x[i + 2], 15, 718787259)
      b = md5ii(b, c, d, a, x[i + 9], 21, -343485551)

      a = safeAdd(a, olda)
      b = safeAdd(b, oldb)
      c = safeAdd(c, oldc)
      d = safeAdd(d, oldd)
    }
    return [a, b, c, d]
  }

  /*
  * Convert an array of little-endian words to a string
  */
  function binl2rstr (input) {
    var i
    var output = ''
    var length32 = input.length * 32
    for (i = 0; i < length32; i += 8) {
      output += String.fromCharCode((input[i >> 5] >>> (i % 32)) & 0xFF)
    }
    return output
  }

  /*
  * Convert a raw string to an array of little-endian words
  * Characters >255 have their high-byte silently ignored.
  */
  function rstr2binl (input) {
    var i
    var output = []
    output[(input.length >> 2) - 1] = undefined
    for (i = 0; i < output.length; i += 1) {
      output[i] = 0
    }
    var length8 = input.length * 8
    for (i = 0; i < length8; i += 8) {
      output[i >> 5] |= (input.charCodeAt(i / 8) & 0xFF) << (i % 32)
    }
    return output
  }

  /*
  * Calculate the MD5 of a raw string
  */
  function rstrMD5 (s) {
    return binl2rstr(binlMD5(rstr2binl(s), s.length * 8))
  }

  /*
  * Calculate the HMAC-MD5, of a key and some data (raw strings)
  */
  function rstrHMACMD5 (key, data) {
    var i
    var bkey = rstr2binl(key)
    var ipad = []
    var opad = []
    var hash
    ipad[15] = opad[15] = undefined
    if (bkey.length > 16) {
      bkey = binlMD5(bkey, key.length * 8)
    }
    for (i = 0; i < 16; i += 1) {
      ipad[i] = bkey[i] ^ 0x36363636
      opad[i] = bkey[i] ^ 0x5C5C5C5C
    }
    hash = binlMD5(ipad.concat(rstr2binl(data)), 512 + data.length * 8)
    return binl2rstr(binlMD5(opad.concat(hash), 512 + 128))
  }

  /*
  * Convert a raw string to a hex string
  */
  function rstr2hex (input) {
    var hexTab = '0123456789abcdef'
    var output = ''
    var x
    var i
    for (i = 0; i < input.length; i += 1) {
      x = input.charCodeAt(i)
      output += hexTab.charAt((x >>> 4) & 0x0F) +
      hexTab.charAt(x & 0x0F)
    }
    return output
  }

  /*
  * Encode a string as utf-8
  */
  function str2rstrUTF8 (input) {
    return unescape(encodeURIComponent(input))
  }

  /*
  * Take string arguments and return either raw or hex encoded strings
  */
  function rawMD5 (s) {
    return rstrMD5(str2rstrUTF8(s))
  }
  function hexMD5 (s) {
    return rstr2hex(rawMD5(s))
  }
  function rawHMACMD5 (k, d) {
    return rstrHMACMD5(str2rstrUTF8(k), str2rstrUTF8(d))
  }
  function hexHMACMD5 (k, d) {
    return rstr2hex(rawHMACMD5(k, d))
  }

  function md5 (string, key, raw) {
    if (!key) {
      if (!raw) {
        return hexMD5(string)
      }
      return rawMD5(string)
    }
    if (!raw) {
      return hexHMACMD5(key, string)
    }
    return rawHMACMD5(key, string)
  }

  if (typeof define === 'function' && define.amd) {
    define(function () {
      return md5
    })
  } else if (typeof module === 'object' && module.exports) {
    module.exports = md5
  } else {
    $.md5 = md5
  }
}(this));

$(document).ready(function()
{
    // init

    $(window).focus();
    var tabinfocus = true;
    $("#chat").hide();


    // allowed characters in username
	/*
    $("#username").keyup(function()
    {
        var text = $(this).val();
        $(this).val(text.replace(/[^a-zA-Z0-9 ]/g, ""));
    });
	*/
    // enter username

    var user = "";

    user = "<?php echo $USER->username; ?>";

    // choose avatar, check username, start chat

    var avatar = "";
    avatar = "https://www.gravatar.com/avatar/" + md5('<?php echo $USER->email; ?>') + "?d=mm";

    startchat();

    // start chat

    function startchat()
    {
        // zoom chat to fit viewport

        if( $(window).innerWidth() > 630 )  // if not mobile
        {
            var ratio = $(window).innerHeight() / 660;  // browser viewport by chat height

            //$("#chat").css("zoom", ratio);
            //$("#chat").css("-moz-transform-origin", "0 0");
            //$("#chat").css("-moz-transform", "scale(" + ratio + ")");
        }

        // update favicon to user avatar

        //$("#favicon").remove();
        //var userfavicon = "<link id=\"favicon\" type=\"image/x-icon\"" +
                          " rel=\"shortcut icon\" href=\"img/" + avatar + ".ico\">";
        //$(userfavicon).appendTo("head");

        // start chat
        $("#chat").show();
        $("#message").focus();

        refresh();
    }

    // allowed characters in message



    // log message

    $("#message").focus().keypress(function(event)
    {
        if( event.keyCode === 13 )
        {
            event.preventDefault();

            if( $("#content").is(":visible") )
            {
                var message = $("#message").val();
                message = encodeURIComponent(message);
                var data = "messagedata=" + message + "###" + user + "###" + avatar;
                $(this).val("");

                $.ajax
                ({
                    type: "POST",
                    url: "chat/logmessage.php",
                    data: data,
                    cache: false
                });
            }
        }
    });

    // refresh content

    var content = $("#messages");
    var newcontent = content.html();

    function refresh()
    {
        setTimeout(function()
        {
            var data = "user=" + user;

            $.ajax
            ({
                type: "POST",
                url: "chat/refreshmessages.php",
                data: data,
                cache: false,
                success: function(result)
                {
                    // check who is still online

                    var datetoday = new Date();
                    var timenow = datetoday.getTime() / 1000;

                    $.ajax
                ({
                    url: "chat/getonline.php",
                    cache: false,
                    success: function(result)
                    {
                        var onlineusers = JSON.parse(result);
                        var oldonlineusers = $("#onlineusers").html();
                        var newonlineusers = '';
                        var onlinecount = 0;

                        if( onlineusers.length <= 0 )  // no user typing
                        {
                            newonlineusers += "No Users Online";
                            onlinecount = 0;

                        }
                        else
                        {
                            if( onlineusers.length >= 1 )  // one user typing
                            {
                                jQuery.each( onlineusers, function( i, val ) {
                                    var timecheck = val[1];
                                    var status = "";
                                    var color = "";
                                    if( timecheck < timenow - 1800 )
                                    {
                                        status = '<span style="min-height: 14px;margin-top: 10px;" class="pull-right badge badge-danger"> </span>';
                                        color = "red";
                                    }else{
                                        status = '<span style="min-height: 14px;margin-top: 10px;" class="pull-right badge badge-success"> </span>';
                                        color = "blue";
                                    }
                                    if( timecheck < timenow - 3600 )
                                    {
                                        newonlineusers += '';
                                    }else{
                                        newonlineusers += '<div class="member-info"><img style="height:40px" src="'+val[2]+'" alt="admin" class="img-circle"><span class="member-name" style="position: absolute;margin-top: 10px;">'+val[0]+'</span>'+status+'</div>';
                                        i++;
                                        onlinecount++;
                                    }

                                    $("img[alt^='"+val[0]+"']").each(function()
                                    {
                                        var timestamp = val[1];

                                        // set user offline avatar

                                        if( timestamp < timenow - 1800 )
                                        {
                                            $(this).addClass("offline");
                                            $(this).removeClass("online");

                                        }
                                        else  // set user online avatar
                                        {

                                            $(this).addClass("online");
                                            $(this).removeClass("offline");

                                        }
                                    });

                                });
                            }
                        }
                        if(onlinecount > 9 ){ onlinecount = "9-plus"; }
                        if(newonlineusers === ''){ newonlineusers = "No Users Online"; onlinecount = 0;}
                        if( newonlineusers != oldonlineusers )
                        {
                            $("#onlineusers").html(newonlineusers);
                            $("#online-count").attr("class", "mdi mdi-numeric-"+onlinecount+"-box");
                        }
                    }
                });



                    // new messages

                    var newmessages = result.split("###endofmessage###");

                    for( var i=0; i<newmessages.length; i++ )
                    {

                        var message = newmessages[i];

                        if( message != "" )
                        {
                            var messagekeypos = message.indexOf("avatarandtext");
                            var messagekey = message.substr(messagekeypos + 19, 32);
                            var contentdom = document.getElementById("messages");
                            var messagedom = document.getElementById(messagekey);

                            if( $.contains(contentdom, messagedom) == false )
                            {
                                // append new message

                                content.append(message).promise().done(function()
                                {
                                    // scroll to bottom

                                    if( newcontent != content.html() )
                                    {
                                        var toscroll = document.getElementById("messages");
                                        toscroll.scrollTop = toscroll.scrollHeight;
                                        $(".box").animate({ scrollTop: $('.box').prop("scrollHeight")}, 0);
                                        newcontent = content.html();
                                        $(function(){
                                            $('.chat-timestamp').each(function(){
                                                var $this = $(this).attr('time');
                                                if(isMobile === true){
                                                    newdate = moment($this+'Z').format('lll');
                                                }else {
                                                    newdate = moment(new Date($this+'Z')).format('lll');
                                                }
                                                $(this).text(newdate);
                                            });
                                        });
                                    }

                                    // new message tab alert

                                    var userwriting = user + "writing";

                                    if( message.lastIndexOf(userwriting) == -1 )
                                    {
                                        if ($('.chat-box').hasClass('email-active')){
                                            console.log("supress message");
                                        }else{
                                            newmessagealert(message);
                                        }
                                    }

                                });
                            }
                        }
                    }



                    // hide intro

                    if( result != "" )
                    {
                        $("#intro").hide();
                    }

                    // loop

                    refresh();
                }
            });

            // update if current user is typing

            if( $("#message").val() != "" )
            {
                istyping(user, 1);
            }
            else if( $("#message").val() == "" )
            {
                istyping(user, 0);
            }

        }, 1250);
    }

    // tab focus
    window.onload = function()
    {
        tabinfocus = true;
        window.chatLoaded = false;
        console.log("loading chat");
        setTimeout(function() {
            if ($('.chat-box').hasClass('email-active')){
                tabinfocus = true;
                $(".mdi-forum").removeClass("tada loop-animation new-message");//SET MESSAGE TO ZERO
                console.log("in focus");
            }else{
                tabinfocus = false;
                console.log("not in focus");
            }
            window.chatLoaded = true;
            console.log("chat started");
        }, 5000);

    };

    window.onfocus = function()
    {
        if(window.chatLoaded === true){
            if ($('.chat-box').hasClass('email-active')){
                tabinfocus = true;
                $(".mdi-forum").removeClass("tada loop-animation new-message");//SET MESSAGE TO ZERO
                console.log("in focus");
            }else{
                tabinfocus = false;
                console.log("not in focus");
            }
        }

    };

    window.onblur = function()
    {
        if(window.chatLoaded === true){
            tabinfocus = false;
            console.log("not in focus");
        }
    };

    // new message tab alert

    function newmessagealert(message)
    {


        if( !tabinfocus )
        {
            i = parseInt(parent.document.title);
            if(isNaN(i)){
                i = 1;
             }else{
                i++
             }

            console.log("new message");
            $(".mdi-forum").addClass("tada loop-animation new-message");
            var $jQueryObject = $($.parseHTML(message));
            var alertMessage = $jQueryObject.find(".chat-body").html();
            var alertUsername = $jQueryObject.find("h4[class^='pull-left zero-m']").html();
            var alertIcon = $jQueryObject.find("img").attr("src");;
            if(isMobile === false){
                parent.Push.create(alertUsername, {
                    body: alertMessage,
                    icon: alertIcon,
                    timeout: 4000,
                    onClick: function () {
                        window.parent.focus();
                        this.close();
                    }
                });
            }

            // sound

            var audio = $("#tabalert")[0];
            audio.play();
        }
    }

    // update which users are typing

    function istyping(u, t)
    {
        // set typing user

        var data = "datavars=" + u + "###" + t;

        $.ajax
        ({
            type: "POST",
            url: "chat/settyping.php",
            data: data,
            cache: false,
            success: function(result)
            {
                // get typing users

                $.ajax
                ({
                    url: "chat/gettyping.php",
                    cache: false,
                    success: function(result)
                    {
                        var typingusers = JSON.parse(result);

                        if( typingusers.length == 0 )  // no user typing
                        {
                            $("#istyping").html("");
                        }
                        else
                        {   //$("#istyping").html("<li class=\"chat-inverted\"><img src=\"images/ellipsis.png\" class=\"dont img-circle user-avatar online\" alt=\"user\"><div class=\"chat-panel red-bg\"><div class=\"chat-body\">" + typingusers[0] + "...</div></div></li>");
                            if( typingusers.length == 1 )  // one user typing
                            {
                                $("#istyping").html(typingusers[0] + " is typing...");
                                //$(".box").animate({ scrollTop: $('.box').prop("scrollHeight")}, 0);
                            }
                            else if( typingusers.length == 2 )  // two users typing
                            {
                                var whoistyping = typingusers[0] +
                                                  " and " + typingusers[1] +
                                                  " are typing...";
                                $("#istyping").html(whoistyping);
                                //$(".box").animate({ scrollTop: $('.box').prop("scrollHeight")}, 0);
                            }
                            else if( typingusers.length > 2 )  // more than two users typing
                            {
                                var whoistyping = typingusers[0] +
                                                  " and " + typingusers[1] +
                                                  " and others are typing...";
                                $("#istyping").html(whoistyping);
                                //$(".box").animate({ scrollTop: $('.box').prop("scrollHeight")}, 0);
                            }
                        }
                    }
                });
            }
        });
    }
});
