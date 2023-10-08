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
  as computed fields on the entity. See the Action Link Field submodule in this
  project for details.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Go to Administration › Structure › Action Links.
2. Click 'Add action link' to create an action link.
3. Configure permissions to use the action link at the normal permissions page.

## Developers

An action link consists of an action_link config entity, which holds the
configuration, and a state action plugin, which holds the logic.

### Concepts

- Direction: An action can have one or more directions. For example, a subscribe
  action could have 'subscribe' and 'unsubscribe'; an add to cart action could
  have 'add', 'remove', and also 'remove all'; a workflow action would have a
  direction for each workflow transition. Directions can be repeatable, or not:
  in the case of the add to cart action, the user could perform the 'add' action
  multiple times, but that is not the case for the subscribe action.
- States: Using an action link puts the system in a particular state. Whether
  this state is per-user or sitewide depends on the state action plugin's
  implementation. For example, an add to cart action alters the user's cart; a
  publish action changes a field value on an entity. Each direction of an action
  has a different target state.

See the ActionLink entity class for further documentation.
