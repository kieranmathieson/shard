/**
 * @file
 * Models a folio of view modes. Yes, that's the collective noun.
 *
 * The data is shared across all instances of CKEditor on the page.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.SlothSpace.collections.Folio = Backbone.Collection.extend({
      model: Drupal.SlothSpace.models.ViewMode,
      url: function(){
        return '/sloth/view-modes?_format=json';
      }
  });

})(jQuery, Drupal);