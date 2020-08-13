/*
    jQuery.media, a very lightweight plugin for media query testings

    Examples : 
    if ( $.media({'min-width' : '480px', 'max-width' : '768px'}) ) {
       (...)
    }

    if ( $.media({'min-width' : 480) ) {
       (...)
    }
*/

(function($) {
     $.media = function(options){
         var settings = {
             'max-width' : 99999999, // okay, it's ugly but hey !
             'min-width' : 0,
             'max-height' : 99999999, // okay, it's ugly but hey !
             'min-height' : 0
         };
         
         opts = $.extend({}, settings, options);
         var doc = document.documentElement,
             windowWidth = doc.clientWidth;
             windowHeight = doc.clientHeight;
         
         for (key in opts) {
             opts[key] = parseInt(opts[key]);
         }
         
         return opts['max-width'] > windowWidth + 1 && opts['min-width'] < windowWidth - 1 && opts['max-height'] > windowHeight + 1 && opts['min-height'] < windowHeight - 1;
     }
})(jQuery);