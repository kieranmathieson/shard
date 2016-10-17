In DB:

```html
<section data-shard-type="sloth" data-shard-id="777">
    Custom content.
</section>
```

Shard id is the id of the instance of the sloth node's shard field that records 
this embedding. It's the PK of the field collection entity.

Ready to send to CKEditor:

```html
<section class="sloth-shard" data-shard-type="sloth" 
        data-sloth-id="666" data-view-mode="teaser">
    [Fixed template content goes here.]
    <div class="local-content">
        Custom content
    </div>
    [More fixed template content goes here.]
</section>
```

The section's class (`sloth-shard`) lets CK's widget system identify 
the whole thing as a widget. The div's class (`local-content`) lets the widget system
know what content is editable by the user within the widget. The rest is rendered by the
widget system, but not editable. 

Coming from CK to Drupal module:

```html
<section class="sloth-shard" data-shard-type="sloth" 
        data-sloth-id="666"  data-view-mode="teaser">
    Custom content
</section>
```

The CKEditor plugin's `downcast` function strips out the template content, and the
custom content's `<div>` wrapper.

