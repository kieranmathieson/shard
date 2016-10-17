/**
 * @file
 */
(function ($, Drupal) {

  "use strict";

  Drupal.SlothSpace.views.SlothPreview = Backbone.View.extend({
    el: '#sloth-preview',
    render: function(viewMode) {
      console.log('in preview.render, viewmode: ' + viewMode);
      var html = this.model.get('previews').get(viewMode).get('html');
      // console.log(html);
      //Next line is slimy hack, since at init this.$el is set, but this.el is not.
      //Don't know why. Suspect Satan's influence. (I used to be married to her... Ooo, burn!)
      $(this.$el.selector).html(html);
      //this.$el.html( html );
      return this;
    },
    showLoading: function() {
      this.$el.html(Drupal.SlothSpace.loadingIndicator);
    }
  });

})(jQuery, Drupal);

