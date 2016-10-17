Node entities [->getStorage('node')]:
    Sloth
    Page
    
Field collection entities [->getStorage('field_collection')]:
    field_shard

Sloths have a field called field_shard, that has a number of item_ids 
in it. They are the item_ids (PK) of field collection entities.
 
Load sloths:

```php
$sloth = $this->entityTypeManager->getStorage('node')->load(1);
```

Get the item_ids of field_collection entities store in the field 
field_shard of the sloth:

```php
$shard_item_ids = [];
foreach($shard_entities_in_node as $shard_entity) {
  $shard_item_ids[] = $shard_entity['value'];
}
```

To load those field collection entities:

```php
$shards = $this->entityTypeManager->getStorage('field_collection_item')
  ->loadMultiple(array_values($shard_item_ids));
```

To get the nids of the pages that include the sloths:

```php
foreach ($shards as $shard) {
  $host_node_ids[] = $shard->field_host_node->getString();
}
```

To delete inclusions of sloths in page with nid of 8:

```php
//Load all of those shard items.
$shard_items = $this->entityTypeManager->getStorage('field_collection_item')
  ->loadMultiple(array_values($shard_item_ids));
//Erase the shard items that refer to node 8.
foreach ($shard_items as $item_id => $shard_item) {
  if ( $shard_item->field_host_node->getString() == 8 ) {
    $shard_item->delete();
  }
}
```

To record that node 2 (page) refers to the sloth with nid of 1:

```php
$sloth = $this->entityTypeManager->getStorage('node')->load(1);
//Make a new field collection entity.
$fc1 = FieldCollectionItem::create([
  'field_name' => 'field_shard', //Bundle setting.
  'field_host_node' => 2,
  'field_host_field' => 'body',
  'field_host_field_instance' => 0,
  'field_display_mode' => 'shard',
  'field_shard_location' => 555,
  'field_custom_content' => '<p>Stuff</p>',
]);
$fc1->setHostEntity($sloth);
$fc1->save();
```
