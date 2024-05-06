var Lse = Lse || {};
(function($) {
  Lse.Settings = function(element) {
    var self = this;
    self.element = element;
    self.resetCacheUrl = element.data('resetCacheUrl');
    self.resetCacheButton = $('.js-reset-cache', element);
    self.resetCacheResultSuccess = $('.js-reset-result-success', element);
    self.resetCacheResultError = $('.js-reset-result-error', element);
    self.listElements = $('.js-list', element);
    self.listContainer = $('.js-list-container', element);
    self.currentListId = null;
    self.showOptionRadios = $('.js-show-option-input', element);
    self.listsSettingsInput = $('.js-lists-settings', element);

    self.binds();
  }

  Lse.Settings.prototype.binds = function() {
    var self = this;

    self.resetCacheButton.on('click', function(e) {
      e.preventDefault();
      self.resetCache();
    })

    self.listElements.on('click', function(e) {
      e.preventDefault();
      self.listElements.removeClass('m--selected');
      $(this).addClass('m--selected');
      var listId = $(this).data('listId');
      var listName = $(this).data('listName');
      self.onListChanged(listId, listName);
    })

    self.showOptionRadios.on('change', function() {
      let showOption = self.showOptionRadios.filter(':checked').val();
      self.setListSettings({
        listId: self.currentListId,
        showOption: showOption,
      })
    });
  }

  Lse.Settings.prototype.resetCache = function() {
    var self = this;

    $.ajax({
      url: self.resetCacheUrl,
      type: 'get',
      dataType: 'json',
      success: function(response) {
        self.resetCacheResultSuccess.show();
        setTimeout(function() {
          self.resetCacheResultSuccess.hide();
        }, 3000)
      },
      error: function(response) {
        self.resetCacheResultError.show();
        setTimeout(function() {
          self.resetCacheResultError.hide();
        }, 3000)
      }
    });
  }

  Lse.Settings.prototype.setListSettings = function(newListSettings) {
    var self = this;

    var listsSettings = {};
    if (self.listsSettingsInput.val()) {
      listsSettings = JSON.parse(self.listsSettingsInput.val());
    }
    listsSettings = listsSettings || {};

    listsSettings[newListSettings.listId] = newListSettings;

    self.listsSettingsInput.val(JSON.stringify(listsSettings));
  }

  Lse.Settings.prototype.getListSettingsById = function(listId) {
    var self = this;

    var listsSettings = {};
    if (self.listsSettingsInput.val()) {
      listsSettings = JSON.parse(self.listsSettingsInput.val());
    }
    if (listsSettings && listsSettings.hasOwnProperty(listId)) {
      return listsSettings[listId];
    }

    return null;
  }

  Lse.Settings.prototype.onListChanged = function(listId, listName) {
    var self = this;

    self.currentListId = listId;
    
    var listSettings = self.getListSettingsById(listId);
    self.showOptionRadios
      .removeAttr('checked')
      .prop('checked', false);

    if (listSettings) {
      self.showOptionRadios.filter('[value='+listSettings.showOption+']')
        .attr('checked', 'checked')
        .prop('checked', true)
        .trigger('change');
    }

    self.listContainer.find('.js-list-id').text(listId);
    self.listContainer.find('.js-list-name').text(listName);
    self.listContainer.find('.js-laposta-embed-link').attr('href', 'https://app.laposta.nl/c.listconfig/s.memberforms/t.subscribe/v.embed-v2/?listconfig='+listId);
    self.listContainer.show();
  }
})(jQuery);