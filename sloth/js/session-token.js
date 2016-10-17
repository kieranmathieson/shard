/**
 * @file
 * Load session token from server.
 */
(function ($, Drupal) {

  "use strict";

  Drupal.SlothSpace.loadSessionToken = function () {
    if ( ! Drupal.SlothSpace.securityToken ) {
      var deferred = $.Deferred();
      $.ajax({ url: "/rest/session/token" })
        .done(function( data ) {
          Drupal.SlothSpace.securityToken = data;
          deferred.resolve();
        });
      return deferred.promise();
    }
  };

  $(document).ready(function(){
    Drupal.SlothSpace.loadSessionToken();
  });

})(jQuery, Drupal);
