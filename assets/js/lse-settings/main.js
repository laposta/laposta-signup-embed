(function($) {
  var lseSettings = $('.lse-settings');
  if (lseSettings.length) {
    new Lse.Settings(lseSettings);
  }

  $('body').on('change', '.js-select-target', function() {
    let targetSelector = $(this).data('targetSelector');
    let value = $(this).val();
    if (!$(targetSelector).length) {
      console.warning('targetSelector not found', targetSelector);
      return;
    }

    $(targetSelector).hide();
    $(targetSelector).filter('[data-select-target-value='+value+']').show();
  })

})(jQuery);