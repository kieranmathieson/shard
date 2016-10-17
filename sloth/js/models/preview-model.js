/**
 * @file
 * Models a preview.
 *
 * The data is shared across all instances of CKEditor on the page.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.SlothSpace.models.Preview = Backbone.Model.extend(
    {
      idAttribute: 'machineName',
      default: {
        machineName: null, //Internal name of the view mode the preview is for.
        html: null, //The preview.
      },
    }
  );
})(jQuery, Drupal);
