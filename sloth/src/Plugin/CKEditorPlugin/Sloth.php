<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 9/22/16
 * Time: 9:59 AM
 */

namespace Drupal\sloth\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the Sloth CKEditor plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "sloth",
 *   label = @Translation("Sloth")
 * )
 */
class Sloth extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons() {
    return array(
      'sloth' => array(
        'label' => $this->t( 'Sloth' ),
        'image' => drupal_get_path('module', 'sloth')
          . '/js/plugins/sloth/icons/sloth.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   *
   * The path to the plugin.js file, that implements the CK plugin.
   */
  public function getFile() {
    return drupal_get_path('module', 'sloth') . '/js/plugins/sloth/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $path = drupal_get_path('module', 'sloth') . '/templates/sloth-template.html';
    $template = file_get_contents($path);
    if ( $template === FALSE ) {
      $template = $this->t('Failed to read sloth template.');
    }
    return [ 'template' => $template ];
  }

  /**
   * {@inheritdoc}
   *
   * Load files defined in the library, by sloth.libraries.yml.
   */
  public function getLibraries(Editor $editor) {
    return [ 'sloth/sloth', ];
  }

}