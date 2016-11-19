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
        //editor.addContentsCss(path + 'css/sloth.css');
        editor.widgets.add('sloth', {
          path: path,
          button: 'Insert a sloth',
          dialog: 'sloth',
          template: editor.config.template, //Need something.
          //Define the editable pieces of the template.
          editables: {
            content: {
              selector: '.local-content' //@TODO: get from server.
            }
          },
          //Add to content that ACF will allow.
          allowedContent: 'div[class,data-*](*)',
          extraAllowedContent: 'div[*]{*}(*)',
          requiredContent: 'div[data-shard-type]',
          upcast: function (element) {
            return element.name == 'div' && element.attributes && element.attributes['data-shard-type'];// element.getAttribute( 'data-shard-type' ) == 'sloth';
          },
          // Downcast the element.
          downcast: function (element) {
            element.attributes['data-shard-type'] = 'sloth';
            element.attributes.class = 'sloth-shard';

            return element;
          },
          init: function () {
            //Sloth nid
            if (this.element.hasAttribute('data-guest-id')) {
              this.setData('guestId', this.element.getAttribute('data-guest-id'));
            }
            //View mode
            if (this.element.hasAttribute('data-view-mode')) {
              this.setData('viewMode', this.element.getAttribute('data-view-mode'));
            }
            //Preview starts off MT.
            this.setData('preview', null);
          }, //End init().
          /**
           * Called when the widget data changes. That includes
           * when initialing the widget, and when
           * data is returned by the dialog.
           */
          data: function () {
            /* @var this.element CKEDITOR.dom.element */
            if ( this.data.guestId ) {
              this.element.setAttribute('data-guest-id', this.data.guestId);
            }
            if ( this.data.viewMode ) {
              this.element.setAttribute('data-view-mode', this.data.viewMode);
            }
            this.element.setAttribute('class', 'shard-sloth');
            this.element.setAttribute('data-shard-type', 'sloth');
            if (this.data.preview) {
              //Add tags needed for editing local content.
              var htmlToShow = Drupal.SlothSpace.makeLocalContentEditable(this.data.preview);
              this.element.setHtml(htmlToShow);
            }
            //See http://ckeditor.com/forums/Support/Mutable-templates-for-the-widgets-plugin
            this.initEditable( 'content', {
              selector: '.local-content'
            } );
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
          //Check whether button is allowed on this field.
          //NOTE: Not sure whether it is safe to assume that the name of
          //editor instance will always contain the field name.
          var re=/(.*)\[\d/g;
          var textareaName = $(editor.element.$).attr('name');
          if ( textareaName ) {
            var matches = re.exec(textareaName);
            if (!matches || !matches[1]) {
              throw new Error('Shard: Field name not found');
            }
            var fieldName = matches[1];
            if (!_.contains(drupalSettings.shard.eligibleField, fieldName)) {
              editor.commands['sloth'].disable();
              return;
            }
          }
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

  Drupal.SlothSpace.makeLocalContentEditable = function(htmlToProcess) {
    var element = $(htmlToProcess);
    var localContentElement = $(element).find('.local-content'); //@TODO Constant from server.
    if ( ! $(localContentElement).is('[contenteditable]') ) {
      $(localContentElement)
        .attr('contenteditable', 'true')
        .attr('data-cke-widget-editable', 'content')
        .attr('data-cke-enter-mode', '1')
        .addClass('cke_widget_editable');
    }
    return element[0].outerHTML;
  };

  /**
   * For reasons unknown, local content is not editable when the dialog
   * creates a new widget. The right attributes are not added to the local
   * content element. This slimy patch adds them.
   *
   * @param CKEDITOR.dom.element element The widget's DOM element.
   */
  Drupal.SlothSpace.makeEditablePatch = function(element) {
    //Look for an element with local content.
    //NB: element is a CKEDITOR.dom.element. Its $ property is sent to
    //findChildElementWithClass. The $ property is a
    //CKEDITOR.dom.node - a different thing.
    //The return value of findChildElementWithClass is also
    //a CKEDITOR.dom.node.
    /* @var CKEDITOR.dom.node localContentChild */
    var localContentChild
        = Drupal.SlothSpace.findChildElementWithClass(element.$, 'local-content');
    //Was it found?
    if ( localContentChild ) {
      //Does the local content element have the right attributes already?
      if ( ! localContentChild.hasAttribute('contenteditable') ) {
        //Nay. Add them.
        localContentChild.setAttribute('data-cke-widget-editable', 'content');
        localContentChild.setAttribute('data-cke-enter-mode', '1');
        localContentChild.setAttribute('contenteditable', 'true');
        localContentChild.classList.add('cke_widget_editable');
      }
    }
  };

  /**
   * Find the first child of an element that has a class.
   *
   * @param CKEDITOR.dom.node domNode Node to search.
   * @param className Class to look for.
   * @returns Child element, or false for none.
   */
  Drupal.SlothSpace.findChildElementWithClass = function(domNode, className){
    if ( domNode.classList.contains(className) ) {
      return domNode;
    }
    for ( var index = 0; index < domNode.children.length; index++) {
      var child = domNode.children.item(index);
      var result = Drupal.SlothSpace.findChildElementWithClass(child, className);
      if ( result ) {
        return result;
      }
    }
    return false;
  };

})(jQuery, Drupal);
