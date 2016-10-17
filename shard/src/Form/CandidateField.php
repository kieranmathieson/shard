<?php
/**
 * @file
 * Describes a field that is a candidate for hosting shards.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Form;


use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;

class CandidateField {
  /**
   * Machine name of the field.
   *
   * @var string
   */
  protected $machineName;

  /**
   * Display name of the field.
   *
   * @var string
   */
  protected $displayName;

  /**
   * Content types the field is in.
   *
   * @var string[]
   */
  protected $inContentTypes;

  /**
   * CandidateField constructor.
   *
   * @param string $machine_name Field's machine name.
   * @param string $display_name Field's display name.
   */
  public function __construct($machine_name, $display_name) {
    $this->machineName = $machine_name;
    $this->displayName = $display_name;
  }

  /**
   * Get the machine name of the field.
   *
   * @return string The name.
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * Set the machine name of a field.
   *
   * @param string $machine_name The name.
   * @return $this For chaining.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  public function setMachineName($machine_name) {
    if ( ! $machine_name ) {
      throw new ShardMissingDataException('Machine name missing.');
    }
    $this->machineName = $machine_name;
    return $this;
  }

  /**
   * Get the display name of a field.
   *
   * @return string The name.
   */
  public function getDisplayName() {
    return $this->displayName;
  }

  /**
   * Set the display name of a field.
   *
   * @param string $display_name The name.
   * @return $this  For chaining.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  public function setDisplayName($display_name) {
    $this->displayName = $display_name;
    if ( ! $display_name ) {
      throw new ShardMissingDataException('Display name missing.');
    }
    return $this;
  }

  /**
   * Add a content type to the list.
   *
   * @param string $content_type Name of the content type.
   * @return $this For chaining.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function addContentType($content_type) {
    if ( ! $content_type ) {
      throw new ShardMissingDataException('Content type missing.');
    }
    if ( in_array($content_type, $this->inContentTypes) ) {
      throw new ShardUnexpectedValueException(
        'Content type ' . $content_type . ' already stored.'
      );
    }
    $this->inContentTypes[] = $content_type;
    return $this;
  }

  /**
   * Return content type names in one string, separated by commas.
   *
   * @return string Names.
   */
  public function getContentTypeListString() {
    $list = implode(', ', $this->inContentTypes);
    return $list;
  }

}