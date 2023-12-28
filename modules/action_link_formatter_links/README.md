# Action Link Formatter Links

The Action Link Formatter Links module allows action links that target entities
to be output within the field's formatter.

For example, an action link which allows a date to be increased and decreased
could be output alongside the date like this:

> [decrease value] 1 January 2015 [increase value]

When the AJAX link style is used, the whole of the rendered field is replaced,
so that the new field value is shown.

## Requirements

This module requires the Action Links module.

Additionally, to show action links on fields shown in Views, the following core
patch is required:

- https://www.drupal.org/project/drupal/issues/2686145

## Configuration

1. Go to the 'Manage display' admin page for an entity bundle.
2. Edit the display settings for a field which has an action link that controls
   it.
3. Select the action link from the options.

## Limitations

When using the AJAX link style with a field that is rendered with custom display
options, there is no way for the module to know how to render the field value
that is returned AJAX, and so it falls back to the default display.

This includes fields that are show in Views.
