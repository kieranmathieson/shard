/**
 * @file
 * Models a sloth.
 *
 * The data is shared across all instances of CKEditor on the page.
 */
(function ($, Drupal) {

  "use strict";

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
        return '/sloth/preview/' + this.get('nid')
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
          url: '/sloth/preview/' + this.get('nid') + '/' + viewMode + '?_format=json',
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
})(jQuery, Drupal);


