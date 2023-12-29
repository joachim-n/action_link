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

There are two possible workarounds:
  - Configure the default view display to show the field in question with the
    same formatter options as the custom options used where the field is shown
    with action links. This only works if there is only one variety of custom
    formatter options in use.
  - Implement hook_action_link_style_info_alter() to swap the class for the
    ajax_entity_field plugin, and in the custom replacement class, return field
    formatted with the appropriate display options (including the
    third_party_settings for this module so that the returned field includes the
    action links). The request object can be used to determine which options to
    use if there are multiple options in use.
