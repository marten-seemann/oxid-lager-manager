# handle both the language selectors.
#
# @author Marten Seemann <martenseemann@gmail.com>
"use strict"

class window.LanguageHandler
  language = null # HTML language of this document <html lang=??>
  oxid_language = null # the language in which the products / categories etc. should be displayed

  # Constructor
  constructor: ->
    @getLanguages()
    @initialize()

  # initialize both the language selectors
  #
  # the ddslick jQuery plugin transforms the select boxes into nice looking language selectors, using the famfamfam flags
  #
  # if it was not possible to get the available OXID languages (can occur in older OXID versions, see PHP source code), the OXID language selector will not be displayed at all
  #
  # @see http://www.famfamfam.com/lab/icons/flags/
  initialize: ->
    $('#stock_manager_language_switcher').ddslick
      width: '110'
      onSelected: (data) =>
        # console.log "switch"
        new_language = data.selectedData.value
        unless new_language is @language
          @language = new_language
          @setLanguageCookies()
          window.location.href = window.location.href

    if $('#oxid_language_switcher > form > select').length > 0
      $('#oxid_language_switcher').ddslick
        width: '130'
        onSelected: (data) =>
          new_language = data.selectedData.value
          unless new_language is @oxid_language
            @oxid_language = new_language
            @setLanguageCookies()
            window.location.href = window.location.href
            # # instead of reloading the page, here it is sufficient to reload the jstree and the datatable
            # loading.jstree = true
            # loading.datatable = true
            # checkLoading()
            # oTable.fnDraw() # trigger the ajax reload of the datatable
            # treeelem.jstree("refresh") # trigger the jstree reload

  # set the cookies where the selected languages are saved in
  #
  # sets two cookies
  #
  # 1. *category_master_language* for the language of the category master itself
  # 2. *oxid_language* for the OXID language
  #
  # both cookies contain only the language code of the selected language (e.g. "de")
  setLanguageCookies: ->
    # $.cookie("category_master_language", null)
    # $.cookie("oxid_language", null)
    $.cookie("stock_manager_language", @language, { expires: 365 }) # save the selected language in a cookie, valid 365 days
    $.cookie("oxid_language", @oxid_language, { expires: 365 }) # save the selected language in a cookie, valid 365 days

  # get both languages (category master and OXID)
  #
  # - get the category master language from the *lang* attribute of the *html* element
  # - get the OXID language from the *oxid_language* cookie. If this cookie does not exist, use the same language as selected for the category master
  #
  # at the end, save the determined languages in cookie using the *setLanguageCookies* method
  getLanguages: ->
    @language = $('html').attr('lang')
    @oxid_language = if !$.cookie("oxid_language") then @language else $.cookie("oxid_language")
    @setLanguageCookies() # make sure that the language is stored in a cookie, even if it was not set using the langauge selector (e.g. via URL param or just by using the default language)
