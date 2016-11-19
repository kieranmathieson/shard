/**
 * @file
 * Init JS slothiness.
 */
// Drupal.behaviors.sloth = {
//   attach: function (context) {
 (function ($, Drupal) {

  "use strict";

  var r;
  r=3;


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

  Drupal.SlothSpace.models.Sloth = Backbone.Model.extend(
      {
        idAttribute: 'nid',
        default: {
          //Only need two attributes for identifying sloth to embed.
          nid: null,
          title: null //Machine of the field with the sloth's name.
          // previews: new Drupal.SlothSpace.collections.Album() //Collection of previews.
          //Previews are lazy loaded.
        },
        /**
         * Load a preview. The other data will be loaded for all sloths during
         * dialog initialization.
         * @returns {string}
         */
        url: function () {
          return '/shard/preview/' + this.get('nid')
              + '/' + Drupal.SlothSpace.currentViewMode + '?_format=json';
        },
        fetch: function (viewMode) {
          var deferred = $.Deferred();
          var thisyThis = this;
          // var viewModeBeingFetched - viewMode;
          $.ajax({
            type: "GET",
            dataType: "json",
            accepts: {
              text: "application/json"
            },
            url: '/shard/preview/' + this.get('nid') + '/' + viewMode + '?_format=json',
            beforeSend: function (request) {
              request.setRequestHeader("X-CSRF-Token", Drupal.SlothSpace.securityToken);
            }
          })
              .done(function (result) {
                //Cache the preview.
                // console.log('in model fetch.done.');
                // console.log('caching');
                var newPreview = new Drupal.SlothSpace.models.Preview({
                  'machineName': viewMode,
                  'html': result
                });
                thisyThis.get('previews').add(newPreview);
                // console.log('done with done.');
                deferred.resolve();
              });
          return deferred.promise();
        },
        isPreviewSet: function (viewMode) {
          // console.log('Is preview set? For nid=' + this.get('nid'));
          var previews = this.get('previews');
          if (previews.get(viewMode)) {
            // console.log('Yes');
            return true;
          }
          // console.log('No');
          return false;
        },
        getPreview: function (viewMode) {
          // console.log('Get preview. For nid=' + this.get('nid'));
          var previews = this.get('previews');
          if (previews.get(viewMode)) {
            // console.log('Found it.');
            return previews.get(viewMode).get('html');
          }
          // console.log('Did na find it.');
          return null;
        }
      }
  );

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

  Drupal.SlothSpace.models.Preview = Backbone.Model.extend(
      {
        idAttribute: 'machineName',
        default: {
          machineName: null, //Internal name of the view mode the preview is for.
          html: null, //The preview.
        },
      }
  );

  Drupal.SlothSpace.collections.Pack = Backbone.Collection.extend({
    model: Drupal.SlothSpace.models.Sloth,
    url: function(){
      return '/shard/index/sloth?_format=json';
    }
  });

  Drupal.SlothSpace.collections.Folio = Backbone.Collection.extend({
    model: Drupal.SlothSpace.models.ViewMode,
    url: function(){
      return '/shard/view-modes?_format=json';
    }
  });

  Drupal.SlothSpace.collections.Album = Backbone.Collection.extend({
    model: Drupal.SlothSpace.models.Preview
  });

  // Drupal.behaviors.sloth = {
  //   attach: function (context) {
  Drupal.SlothSpace.collections.pack = new Drupal.SlothSpace.collections.Pack([]);
  Drupal.SlothSpace.collections.viewModes = new Drupal.SlothSpace.collections.Folio([]);
  //   }
  // };

})(jQuery, Drupal);
//   }
// };