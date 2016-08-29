jQuery(function($) {
  if(typeof jQuery.fn.datepicker !== "undefined") {
    var dates = $( '.range_datepicker' ).datepicker({
      changeMonth: true,
      changeYear: true,
      defaultDate: '',
      dateFormat: 'yy-mm-dd',
      numberOfMonths: 1,
      maxDate: '+0D',
      showButtonPanel: true,
      showOn: 'focus',
      buttonImageOnly: true,
      onSelect: function( selectedDate ) {
        var option = $( this ).is( '.from' ) ? 'minDate' : 'maxDate',
          instance = $( this ).data( 'datepicker' ),
          date = $.datepicker.parseDate( instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings );

        dates.not( this ).datepicker( 'option', option, date );
      }
    });
  }
});