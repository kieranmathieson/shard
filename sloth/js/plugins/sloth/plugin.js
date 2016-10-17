/**
 * @file
 * CKEditor plugin for sloth module.
 */
(function ($, Drupal) {

  "use strict";



  CKEDITOR.plugins.add('sloth', {
      requires: 'widget',
      icons: 'sloth',
      init: function (editor) {
        //Create a place for this plugin to keep state.
        editor.SlothSpace = {};
        editor.SlothSpace.views = {};
        // console.log('ck plug init');
        CKEDITOR.dialog.add('sloth', this.path + 'dialogs/sloth.js');
        var path = this.path;
        editor.addContentsCss(path + 'css/sloth.css');
        editor.widgets.add('sloth', {
          path: path,
          button: 'Insert a sloth',
          dialog: 'sloth',
          //Get the HTML template from the editor object.
          template: editor.config.template,
          //Define the editable pieces of the template.
          editables: {
            content: {
              selector: '.sloth-content'
            }
          },
          //Add to content that ACF will allow.
          allowedContent: 'div(!sloth-shard);'
          + 'div[*](*){*};p(*);',
          extraAllowedContent: '*[*]{*}(*)',
          requiredContent: 'div(sloth-shard);', //[data-shard-type=sloth]',
          upcast: function (element) {
            return element.name == 'div' && element.hasClass('sloth-shard');// element.getAttribute( 'data-shard-type' ) == 'sloth';
          },
          // Downcast the element.
          downcast: function (element) {
            element.attributes['data-shard-type'] = 'sloth';
            element.attributes.class = 'sloth-shard';

            // var localContent = '';
            // if ( $(element.getHtml()).find('.local-content').length > 0 ) {
            //   localContent = $(element.getHtml()).find('.local-content').html()
            // }
            // // Only keep the wrapping element.
            // element.setHtml(localContent);
            // Remove the auto-generated ID.
            // delete element.attributes.id;
            return element;
          },
          init: function () {
            //Sloth nid
            if (this.element.hasAttribute('data-sloth-id')) {
              this.setData('slothId', this.element.getAttribute('data-sloth-id'));
            }
            //View mode
            if (this.element.hasAttribute('data-view-mode')) {
              this.setData('viewMode', this.element.getAttribute('data-view-mode'));
            }
          }, //End init().
          /**
           * Called when initialing widget display in CK, and when
           * data is returned by the dialog.
           */
          data: function () {
            if (editor.SlothSpace.currentPreview) {
              this.element.setHtml(editor.SlothSpace.currentPreview);
            }
            this.element.setAttribute('data-sloth-id', this.data.slothId);
            this.element.setAttribute('data-view-mode', this.data.viewMode);
            this.element.setAttribute('class', 'shard-sloth');
          }
        });
        editor.ui.addButton('sloth', {
          label: 'Sloth',
          command: 'sloth'
        });
        var slothButton = editor.ui.get('sloth');
        // console.log('sloth button');
        // console.log(slothButton);

        editor.on("instanceReady", function() {
          //Data could already have been loaded by other instances.
          if ( Drupal.SlothSpace.collections.viewModes.length == 0 ) {
            //Disable the sloth button until the data it needs is loaded.
            editor.ui.get('sloth').setState(CKEDITOR.TRISTATE_DISABLED);
            $.when(
              Drupal.SlothSpace.collections.viewModes.fetch(),
              Drupal.SlothSpace.collections.pack.fetch()
              )
              .then(function () {
                // console.log(Drupal.SlothSpace.collections.pack);
                // console.log(Drupal.SlothSpace.collections.viewModes);
                //Add MT previews to the sloths.
                //Should be a cleaner way to do this.
                $.each(Drupal.SlothSpace.collections.pack.models, function (index, sloth) {
                  sloth.set('previews', new Drupal.SlothSpace.collections.Album());
                });
                //Create arrays used later to set <select> options.
                Drupal.SlothSpace.slothOptions = [];
                for (var i = 0; i < Drupal.SlothSpace.collections.pack.length; i++) {
                  Drupal.SlothSpace.slothOptions.push([
                    Drupal.SlothSpace.collections.pack.models[i].get('title'),
                    Drupal.SlothSpace.collections.pack.models[i].get('nid')
                  ]);
                }
                Drupal.SlothSpace.viewModeOptions = [];
                for (i = 0; i < Drupal.SlothSpace.collections.viewModes.length; i++) {
                  Drupal.SlothSpace.viewModeOptions.push([
                    Drupal.SlothSpace.collections.viewModes.models[i].get('label'),
                    Drupal.SlothSpace.collections.viewModes.models[i].get('machineName')
                  ]);
                }
                // console.log('set up arrays');
                editor.ui.get('sloth').setState(CKEDITOR.TRISTATE_OFF);
              });
          }
        }); //End editor instance ready.
      } //End plugin init.
    }); //End plugins add.
})(jQuery, Drupal);
