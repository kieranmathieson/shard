/**
 * @file
 * Models a view mode.
 *
 * The data is shared across all instances of CKEditor on the page.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.SlothSpace.models.ViewMode = Backbone.Model.extend(
    {
      idAttribute: 'machineName',
      default: {
        //Only need two attributes for identifying view mode to embed.
        machineName: null, //Internal name of the view mode.
        label: null, //What admins see.
      },
    }
  );
})(jQuery, Drupal);
