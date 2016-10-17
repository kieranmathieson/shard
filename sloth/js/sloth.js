/**
 * @file
 * Init JS slothiness.
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Namespace for sloth related functionality. Also includes view modes that
   * are specific to sloth embedding.
   *
   * @namespace
   */
  Drupal.SlothSpace = { //The real final frontier.

    /**
     * @namespace Drupal.SlothSpace.models
     */
    models: {},

    /**
     * @namespace Drupal.SlothSpace.collections
     */
    collections: {},

    /**
     * @namespace Drupal.SlothSpace.views
     */
    views: {}
  };

  Drupal.behaviors.sloth = {
    attach: function (context) {
      Drupal.SlothSpace.collections.pack = new Drupal.SlothSpace.collections.Pack([]);
      Drupal.SlothSpace.collections.viewModes = new Drupal.SlothSpace.collections.Folio([]);
    }
  };

})(jQuery, Drupal);