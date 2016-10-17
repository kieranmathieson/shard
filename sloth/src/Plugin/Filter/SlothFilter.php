<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 10/11/16
 * Time: 11:16 AM
 */

namespace Drupal\sloth\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\sloth\SlothTagHandler;

/**
 * @Filter(
 *   id = "filter_sloth",
 *   title = @Translation("Sloth Filter"),
 *   description = @Translation("Show sloths."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 * )
 */
class SlothFilter extends FilterBase {

  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object, and possibly
   *   with associated assets, cacheability metadata and placeholders.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode) {
    $container = \Drupal::getContainer();
    $sloth_tag_handler = new SlothTagHandler(
//    \Drupal::service('sloth.eligible_fields'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('renderer')
    );
    $text = $sloth_tag_handler->dbHtmlToViewHtml($text);

    return new FilterProcessResult($text);
  }
}