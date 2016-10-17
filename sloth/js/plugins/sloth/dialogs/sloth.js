/**
 * @file
 * The sloth dialog definition.
 *
 */
(function ($) {

  "use strict";
  /**
   * @todo: Localize titles and labels.
   *
   * @todo: Create styles from CSS file names.
   */
  Drupal.SlothSpace.loadingIndicator = Drupal.t('Loading...');
  CKEDITOR.dialog.add('sloth', function (editor) {
    // var lang = editor.lang.admonition;
    return {

      // Basic properties of the dialog window: title, minimum size.
      title: "Sloth", //lang.dialogTitle,
      minWidth: 200,
      maxWidth: 480,
      minHeight: 200,
      maxHeight: 480,
      slothList: [], // new Array(),
      // Dialog window contents definition.
      contents: [
        {
          // Definition of the dialog tab.
          //@todo Need to define when have one tab?
          //@todo Change the alert() error call to something fancier?
          id: 'tab-basic',
          label: 'Basic Settings',

          // The tab contents.
          elements: [
            {
              //Explain what a sloth is.
              type: 'html',
              html: 'A sloth is MORE.'
            },
            { //Sloth select element.
              type: 'select',
              id: 'slothSelect',
              label: 'Choose a sloth',
              items: Drupal.SlothSpace.slothOptions,
              default: Drupal.SlothSpace.slothOptions[0][1],
              setup: function( widget ) {
                if ( widget.data.slothId && widget.data.slothId != 'empty'){
                  // console.log('Sloth field setup for: ' + widget.data.slothId);
                  this.setValue(widget.data.slothId);
                }
                else {
                  this.setValue(this.default);
                }
              },
              onChange: function(evnt) {
                // console.log('Start sloth onchange. getValue:' + this.getValue());
                //Get the selected nid.
                var selectedNid = this.getValue();
                var sloths = Drupal.SlothSpace.collections.pack;
                var selectedModel = sloths.get(selectedNid);
                $.when( this.loadPreview(selectedModel, editor.SlothSpace.currentViewMode) )
                  .then(function(){
                    var previewContent = selectedModel.getPreview(editor.SlothSpace.currentViewMode);
                    editor.SlothSpace.previewElement.html(previewContent);
                  });
              },
              commit: function( widget ) {
                widget.setData( 'slothId', this.getValue() );
              },
              loadPreview: function(selectedModel, viewMode) {
                // console.log('Start sloth load preview');
                if ( ! selectedModel ) {
                  // console.log('Nothing selected. Leave.');
                  return;
                }
                // console.log('Loading preview. nid: '
                //   + selectedModel.get('nid') + ' view mode' + viewMode );
                //Already got the preview?
                if ( selectedModel.isPreviewSet(viewMode) ) {
                  // console.log('Load preview: already got it.');
                  return;
                }
                // console.log('dinna have preview');
                var deferred = $.Deferred();
                editor.SlothSpace.previewElement.html(Drupal.SlothSpace.loadingIndicator);
                $.when( selectedModel.fetch(viewMode) )
                  .then(function(){
                    // console.log('Got preview.');
                    deferred.resolve();
                  });
                return deferred.promise();
              }
            }, //End sloth select widget
            { //Sloth view mode element.
              type: 'select',
              id: 'viewModeSelect',
              label: 'Choose a view mode',
              items: Drupal.SlothSpace.viewModeOptions,
              default: Drupal.SlothSpace.viewModeOptions[0][1],
              setup: function( widget ) {
                if ( widget.data.viewMode && widget.data.viewMode != 'empty') {
                  // console.log('View mode field setup for: ' + widget.data.viewMode);
                  this.setValue(widget.data.viewMode);
                }
                else {
                  this.setValue(this.default);
                }
              },
              onChange: function(evnt) {
                // console.log('Start view mode onchange');
                if ( ! $('#' + this.domId).is(":visible") || Drupal.SlothSpace.collections.viewModes.length == 1 ) {
                  // console.log('Only one. Leaving.');
                  return;
                }
                //Get the selected nid.
                // console.log('getValue:' + this.getValue());
                editor.SlothSpace.currentViewMode = this.getValue();
                //Trigger the sloth select to change, to redo preview.
                var slothSelect = this.getDialog().getContentElement('tab-basic', 'slothSelect');
                var selectedSloth = slothSelect.getValue();
                // console.log('Selected sloth:' + selectedSloth);
                // console.log('triggering sloth select');
                slothSelect.setValue(selectedSloth);
              },
              commit: function( widget ) {
                widget.setData( 'viewMode', this.getValue() );
              }
            }, //End view mode select widget
            {
              //Preview of selected sloth.
              type: 'html',
              id: 'preview',
              label: 'Preview',
              html:
              '<div class="sloth-preview-wrapper">'
              +   '<p class="sloth-preview-label">Preview</p>'
              +   '<div class="sloth-preview">'
              +      Drupal.SlothSpace.loadingIndicator
              +   '</div>'
              + '</div>'
            }
          ] //End elements
        }
      ],
      onShow: function() {
        // console.log('start on show');
        editor.SlothSpace.previewElement = $('#'
          + this.getContentElement( 'tab-basic', 'preview' ).domId
          + ' .sloth-preview');
        // editor.SlothSpace.views.preview = new Drupal.SlothSpace.views.SlothPreview();
        //Setup the view modes.
        // console.log('Init view modes. Length: ' + Drupal.SlothSpace.collections.viewModes.length);
        var viewModeWidget = this.definition.dialog.getContentElement('tab-basic', 'viewModeSelect');
        if ( Drupal.SlothSpace.collections.viewModes.length == 1 ) {
          //There's just one available, so it is the Chosen One.
          // console.log('Just one view mode exists');
          editor.SlothSpace.currentViewMode
            = Drupal.SlothSpace.collections.viewModes.models[0].get('machineName');
          $('#' + viewModeWidget.domId).hide();
        }
        else {
          editor.SlothSpace.currentViewMode = viewModeWidget.getValue();
        }
      },
      onOk: function(){
        //Stash the current preview for the caller to grab.
        editor.SlothSpace.currentPreview = editor.SlothSpace.previewElement.html();
      }
    };
  });

})(jQuery);