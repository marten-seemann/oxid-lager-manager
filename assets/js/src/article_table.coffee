"use strict"

# handle the article table.
#
# @author Marten Seemann <martenseemann@gmail.com>
class window.ArticleTable
  # Constructor
  #
  # build the article table using the jQuery DataTables plugin
  #
  # does not call the *initialize* function!
  #
  # @param [jQuery] dom_elem the DOM element where the table should be created
  # @see http://datatables.net
  constructor: (@dom_elem) ->
    @notifications = document.notification_handler

  # initialize the article table
  #
  # the whole configuration for the DataTable is done here, including setting all callbacks
  #
  # calls *addListeners* and *initAutoReload* at the end
  initialize: ->
    @oTable = @dom_elem.dataTable
      iDisplayLength: 15
      # needed for twitter bootstrap
      "sDom": "<'row'<'col-sm-9 view_options'<'#only_low_stock'><'#hidden_articles'><'#hide_parents_label'>><'col-sm-3'l>r><'#only_products_from_cat'>t<'row'<'col-sm-5'i><'col-sm-7'p>>",
      sPaginationType: "bootstrap"
      sWrapper: "dataTables_wrapper form-inline" # needed for datatables to work with bootstrap.
      oLanguage:
        "sProcessing":   lang.datatables_sProcessing
        "sLengthMenu":   lang.datatables_sLengthMenu
        "sZeroRecords":  lang.datatables_sZeroRecords
        "sInfo":         lang.datatables_sInfo
        "sInfoEmpty":    lang.datatables_sInfoEmpty
        "sInfoFiltered": lang.datatables_sInfoFiltered
        "sInfoPostFix":  lang.datatables_sPostFix
        "sSearch":       lang.datatables_sSearch
        "sInfoThousands": lang.datatables_sInfoThousands
        "sUrl":          "",
        "oPaginate":
          "sFirst":    lang.datatables_sFirst
          "sPrevious": lang.datatables_sPrevious
          "sNext":     lang.datatables_sNext
          "sLast":     lang.datatables_sLast
      # server side data processing
      bProcessing: false # dont show the "Processing" indicator
      bServerSide: true
      bAutoWidth: false
      sProcessing: ""
      aoColumns: [
        { sClass: 'is-active', bSortable: false }, #active
        { sClass: 'title', bSortable: false }, # title
        { bSortable: false},
        null, #artnum
        null, # ean
        null, # manufacturer
        null, # mpn
        null, # distean
        null, # vendor
        { sClass: 'price'}, # price
        { sClass: 'bprice'}, # bprice
        { sClass: 'tprice'}, # tprice
        { sClass: 'stock_remindamount', bSortable: true }, # remindamount
        { sClass: 'stock_available_date' }, # available
        { sClass: 'stock' }, # stock
        { sClass: 'stockflag', bSortable: false }, # stockflag
        { sClass: 'varstock' }, # varstock
        null, # oxtimestamp
        null, # oxinsert
      ]
      # aaSorting: [[2,"asc"]] # sort by article number
      aLengthMenu: [[10, 15, 20, 25, 50, 100 ], [10, 15, 20, 25, 50, 100 ]]
      sAjaxSource: 'ajax/products.php'
      fnServerParams: (aoData) =>
        # add additional params to the AJAX query
        aoData.push
          name: "show_only_low_stock",
          value: if $("#show_only_low_stock").is(":checked") then "true" else "false"
        aoData.push
          name: "hide_inactive_articles",
          value: if $("#hide_inactive_articles").is(":checked") then "true" else "false"
        aoData.push
          name: "hide_active_articles",
          value: if $("#hide_active_articles").is(":checked") then "true" else "false"
        aoData.push
          name: "hide_parents",
          value: if $("#hide_parents").is(":checked") then "true" else "false"
      fnServerData: (sSource, aoData, fnCallback) =>
        $.ajax
          dataType: 'json'
          url: sSource
          data: aoData
          cache: false
          error: (data) =>
            @notifications.hideLoading()
            @notifications.showError lang.error_product_list_load
          success: (json) =>
            @notifications.hideLoading()
            fnCallback(json)
          beforeSend: () =>
            @notifications.showLoading lang.loading
      fnDrawCallback: (oSettings) =>
        that = this
        @dom_elem.find('tbody td.stock_remindamount').bind 'click', ->
          $(this).find('.amount').trigger 'click'

        editable_submitdata = (value, settings) ->
          that.notifications.showLoading lang.saving
          return {
            field: $(this).closest('td').attr('class')
            id: $(this).parents('tr').attr('id')
          }
        editable_callback = (value, y, x) =>
          @notifications.hideLoading()
          if value is "false" or value is false
            @notifications.showError lang.save_error
            @reloadData()
          else
            # @notifications.showSuccess lang.save_success
            @reloadData()

        @dom_elem.find('tbody td.is-active').find("input").on "change", (ev) ->
          el = $(ev.currentTarget)
          value = null
          if $(el).is(":checked")
            $(el).parents('tr').removeClass 'article-inactive'
            value = 1
          else
            $(el).parents('tr').addClass 'article-inactive'
            value = 0
          data =
            id: $(el).parents('tr').attr('id')
            field: 'active'
            value: value
          jqxhr = $.ajax
            url: 'ajax/save.php'
            type: 'POST'
            dataType: 'json'
            data: data
            success: (data) ->
              $(el).prop('checked', data) # should not change anything. but if the request fails for some reason, the checkbox will be set accordingly

        @dom_elem.find('tbody td.price, tbody td.tprice, tbody td.bprice, tbody td.stock, tbody td.stock_remindamount .amount').editable 'ajax/save.php',
          height: '20px'
          width: '60px'
          select: true
          tooltip: lang.editable_tooltip
          submitdata: editable_submitdata
          callback: editable_callback
          onblur: 'submit'

        @dom_elem.find('tbody td.stock_available_date').editable 'ajax/save.php',
          height: '20px'
          width: '75px'
          select: true
          tooltip: lang.editable_tooltip
          submitdata: editable_submitdata
          callback: editable_callback
          onblur: 'submit'


        # hide user hidden columns (important when reloading the table)
        counter = 0
        for col in @columns
          continue if counter is @category_column_index
          unless col.visible then @hideColumn counter
          counter = counter+1

        # row highlighting on click
        row_selector = @dom_elem.find('tbody tr')
        row_selector.bind 'click', (event) =>
          target = $(event.currentTarget)
          row_selector.removeClass('row_selected')
          target.toggleClass('row_selected')

    # do some html-element moving towards the head of the datatable
    $('#hidden_articles').html($('#hidden_articles_proto').html())
    $('#hidden_articles_proto').html ""
    $('#only_low_stock').html($('#show_only_low_stock_proto').html())
    $('#show_only_low_stock_proto').html ""
    $('#hide_parents_label').html($('#hide_parents_proto').html())
    $('#hide_parents_proto').html ""

    # hide columns depending on the values read from the cookie
    # if no cookie exists, show all except the category column
    @columns = []
    if $.cookie("stock_manager_columns")
      try # catch error if the cookie for some strange reason does not contain valid JSON
        @columns = $.parseJSON($.cookie("stock_manager_columns"))

    # this code will be executed if 1. no cookie was available or 2. the cookie contained corrupt data for some reason
    # this could for example happen if a new column is added in a new version (such that one has now k+1 columns), and the cookie still contains the object with k columns. this mismatch would behave strange behaviour
    if @dom_elem.find("thead th").length isnt @columns.length
      $.cookie("stock_manager_columns", null) if $.cookie("stock_manager_columns") # delete the cookie if it contained corrupt data
      counter = 0
      for elem in @dom_elem.find("thead th")
        @columns.push
          title: $(elem).html()
          visible: if $(elem).hasClass "hidden" then false else true
        @columns[counter].visible = false if $.inArray(counter, document.article_table_hidden_columns) isnt -1 # hide columns that should be hidden by default
        counter++
      # make the table responsive, hide columns on small screens / devices
      if $.media({'max-width' : '1280px'})
        @hideColumn 5 # EAN
        @hideColumn 6 # Man. EAN
      if $.media({'max-width' : '1024px'})
        @hideColumn 3 # Manufacturer Art. Num.


      #   row_selector.bind 'click', (event) =>
      #     target = $(event.currentTarget)
      #     # handle multiple selections
      #     if event.ctrlKey || event.altKey then target.toggleClass @classstring_selected
      #     else if event.shiftKey
      #       if @last_selected.index() < target.index() then elements = @last_selected.nextUntil target
      #       else elements = @last_selected.prevUntil target
      #       elements.add(target).addClass @classstring_selected
      #       document.getSelection().removeAllRanges()
      #     else # no modifier key at all
      #       @getSelectedRows().removeClass @classstring_selected
      #       target.addClass @classstring_selected
      #     @last_selected = target
      #     @tree.highlightCategories()

      #   row_selector.bind 'mousedown', (event) =>
      #     if @getSelectedRows().length == 0
      #       target = $(event.currentTarget)
      #       target.toggleClass @classstring_selected
      #       @last_selected = target
      #       @tree.highlightCategories()
      #       # @prepareHighlighting()

    @addListeners()


  addListeners: ->
    els = [
      $("#hide_inactive_articles"),
      $("#hide_active_articles"),
      $("#show_only_low_stock"),
      $("#hide_parents")
    ]
    for el in els
      $(el).bind 'change', (event) => @reloadData() # trigger ajax reload of table data

    # column-wise search function
    @dom_elem.find('thead input').typeWatch
      callback: (data, el) =>
        @search($("thead input").index(el), data)
      wait: 600
      highlight: true
      captureLength: 0

    # context menu for hiding / showing columns in the table
    $.contextMenu
      selector: "#{@dom_elem.selector} thead th"
      position: (opt, x, y) ->
        opt.$menu.css({left: x, top: 50})
      build: (trigger, event) =>
        column_selector_items = []
        counter = 0
        for col in @columns
          column_selector_items.push(
            name: col.title
            icon: if col.visible then "ok" else ""
            )
          counter = counter+1
        return {
          callback: (key, options) =>
            @toggleColumn(key, true)
            true # hide the context menu
          items: column_selector_items
          }


  # hides a column in the table completely
  #
  # hides thead cells as well as tbody cells
  #
  # @param [Integer] index index of the column that should be hidden. Note that counting starts with 0
  # @param [Boolean] setCookie save the column visibility state in a cookie
  hideColumn: (index, setCookie = false) ->
    $(row).children().eq(index).addClass 'hidden' for row in @dom_elem.find('tr')
    @columns[index].visible = false
    @setColumnVisibilityCookie() if setCookie

  # shows a complete column in the table
  #
  # shows thead cells as well as tbody cells
  #
  # @param [Integer] index index of the column that should be shown. Note that counting starts with 0
  # @param [Boolean] setCookie save the column visibility state in a cookie
  showColumn: (index, setCookie = false) ->
    $(row).children().eq(index).removeClass 'hidden' for row in @dom_elem.find('tr')
    @columns[index].visible = true
    @setColumnVisibilityCookie() if setCookie

  # toggle a complete column in the table
  #
  # hides thead cells as well as tbody cells
  #
  # @param [Integer] index index of the column that should be hidden. Note that counting starts with 0
  # @param [Boolean] setCookie save the column visibility state in a cookie
  toggleColumn: (index, setCookie = false) ->
    if @columns[index].visible then @hideColumn(index, setCookie) else @showColumn(index, setCookie)

  # save which columns are shown in the table in a cookie
  #
  # data is saved in JSON format
  setColumnVisibilityCookie: ->
    $.cookie("stock_manager_columns", JSON.stringify(@columns), { expires: 365 })

  # search a field in the DataTable
  #
  # @param [Integer] table_column_index the column index of the column that should be searched. If the second table column should be search, pass a 2 here
  # @param [String] string the string to search for
  search: (table_column_index, string) ->
    @oTable.fnFilter(string, table_column_index)

  # perform a reload of data displayed in the DataTable via ajax
  reloadData: ->
    @oTable.fnDraw(false)
