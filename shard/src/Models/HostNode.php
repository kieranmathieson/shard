<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 10/17/16
 * Time: 1:47 PM
 */

namespace Drupal\sloth\Models;

use Drupal\node\NodeViewBuilder;
use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;


class HostNode extends Node {

  protected $hostFields = [];



  public function addHostField($field_name) {
  }

}