$ ->
  "use strict"
  document.article_table_hidden_columns = [ # hide by default
    4, # EAN
    6, # DISTEAN
    7, # OXVENDOR
    10, # OXBPRICE
    11, # OXTPRICE
    15, # OXSTOCKFLAG
    16, # OXVARSTOCK
    17, # OXTIMESTAMP
    18, # OXINSERT
    ]

  document.notification_handler = new NotificationHandler()
  document.language_handler = new LanguageHandler()
  document.article_table = new ArticleTable $("#products")
  article_table = document.article_table
  article_table.initialize()


  $('#help').bind 'click', (event) ->  $('#modal_help').modal 'toggle'
  $('#refresh').bind 'click', () -> window.location.href = window.location.href
