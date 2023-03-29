# Action links

The Action Links module allows site admins to create links which perform an
action when clicked.

For example, an action link could toggle a node's published status, or change
a field value on an entity, or add a product to the user's shopping cart. Custom
actions can be defined in code with plugins, and customized in the UI.

Action links can be configured to use a JavaScript link which doesn't cause a
page reload, or can reload the page.

## Requirements

This module requires no modules outside of Drupal core.

### Optional modules

- Computed Field module allows action links that control entities to be output
  as computed fields on the entity.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Go to Administration › Structure › Action Links.
2. Click 'Add action link' to create an action link.
3. Configure permissions to use the action link at the normal permissions page.
