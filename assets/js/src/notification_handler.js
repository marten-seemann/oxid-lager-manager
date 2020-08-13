// Generated by CoffeeScript 1.6.2
(function() {
  "use strict";  window.NotificationHandler = (function() {
    function NotificationHandler() {}

    NotificationHandler.prototype.showLoading = function(text) {
      return noty({
        text: text,
        type: "alert",
        timeout: 5000
      });
    };

    NotificationHandler.prototype.hideLoading = function() {
      return $.noty.closeAll();
    };

    NotificationHandler.prototype.showSuccess = function(text) {
      return noty({
        text: text,
        type: "success",
        timeout: 1800
      });
    };

    NotificationHandler.prototype.showError = function(text, timeout) {
      if (timeout == null) {
        timeout = 8000;
      }
      return noty({
        text: text,
        type: "error",
        timeout: timeout
      });
    };

    return NotificationHandler;

  })();

}).call(this);
