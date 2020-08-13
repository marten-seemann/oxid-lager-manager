$.noty.defaultOptions = {
  layout : 'center', // (top, topLeft, topCenter, topRight, bottom, center, bottomLeft, bottomRight)
  theme : 'noty_theme_twitter',  // theme name (accessable with CSS)
  animateOpen : {height: 'toggle'}, // opening animation
  animateClose : {height: 'toggle'}, // closing animation
  easing : 'swing', // easing
  text : '', // notification text
  type : 'alert', // noty type (alert, success, error)
  speed : 200, // opening & closing animation speed
  timeout : 2500, // delay for closing event. Set false for sticky notifications
  closeButton : true, // enables the close button when set to true
  closeOnSelfClick : true, // close the noty on self click when set to true
  closeOnSelfHover : false, // close the noty on self mouseover when set to true
  force : false, // adds notification to the beginning of queue when set to true
  onShow : false, // callback for on show
  onClose : false, // callback for on close
  buttons : true, // an array of buttons
  modal : false, // adds modal layer when set to true
  template: '<div class="noty_message"><span class="noty_text"></span><div class="noty_close"></div></div>',
  cssPrefix: 'noty_', // this variable will be a type and layout prefix.
  custom: {
    container: null // $('.custom_container')
  }
};
