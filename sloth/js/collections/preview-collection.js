/**
 * @file
 * Models an album of previews. Yes, that's the collective noun.
 *
 * An album is put in the previews field of each model.
 *
 * The data is shared across all instances of CKEditor on the page.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.SlothSpace.collections.Album = Backbone.Collection.extend({
      model: Drupal.SlothSpace.models.Preview
  });

})(jQuery, Drupal);