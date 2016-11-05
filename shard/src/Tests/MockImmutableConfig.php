<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 11/4/16
 * Time: 9:32 AM
 */

namespace Drupal\shard\Tests;

class MockImmutableConfig  {
public function get($name) {
  if ( $name == 'content_types' ) {
    return ['sloth', 'page'];
  }
  if ( $name == 'content_types' ) {
    return ['sloth', 'page'];
  }
  if ( $name == 'field_types' ) {
    return 'text_with_summary,text_long';
  }
  return '';
}


}