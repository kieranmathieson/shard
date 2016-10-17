/**
 * @file
 * Models a pack of sloths. There is no accepted collective noun for sloths,
 * AFAIK.
 *
 * The data is shared across all instances of CKEditor on the page.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.SlothSpace.collections.Pack = Backbone.Collection.extend({
      model: Drupal.SlothSpace.models.Sloth,
      url: function(){
        return '/sloth/index?_format=json';
      }
  });

})(jQuery, Drupal);