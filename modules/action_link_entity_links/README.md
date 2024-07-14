# Action Link Entity Links

This module allows action links to be output in node or comment links.

## Requirements

This module requires no modules outside of Drupal core.

Either node or comment module should be enabled.

## Configuration

1. Go to Administration › Structure › Action Links.
2. Edit or add an action link which acts on nodes or comments.
3. Enable the 'Entity links' output option.
4. At the 'Manage display' settings page for either nodes or comments, ensure
   the 'Links' pseudofield is set to be displayed.

## Known issues

The 'Output locations' section of the Action Link form does not update correctly
when the form is being shown to add a new Action Link. You should save the form
and return to it to see the option for entity links.