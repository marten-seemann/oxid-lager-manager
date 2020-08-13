# handle notifications displayed to the user via the jQuery Noty plugin
#
# @author Marten Seemann <martenseemann@gmail.com>
# @see http://needim.github.com/noty/

"use strict"
class window.NotificationHandler
  # Constructor
  #
  # will instantaneously display the loading notification, if neccessary
  #
  # @param [Boolean] loading_state should category_tree and article_table be assumed to be loading at the beginning
  constructor: ( )->


  showLoading: (text) ->
    noty
      text: text
      type: "alert"
      timeout: 5000

  hideLoading: ->
    $.noty.closeAll()

  # show a success box (green background color)
  #
  # @text [String] the text to be displayed
  showSuccess: (text) ->
    noty
      text: text
      type: "success"
      timeout: 1800

  # show an error box (red background color)
  #
  # will be displayed for 8000 ms (that is much longer than a success box is displayed)
  #
  # @param [String] text the text to be displayed
  # @param [Integer] timeot how long should the notification be displayed
  showError: (text, timeout = 8000 ) ->
    noty
      text: text
      type: "error"
      timeout: timeout
