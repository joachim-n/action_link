# Action Link Field

The Action Link Field module allows action links that target entities to be
output as computed fields on entities.

For example, an action link which toggle's a node's published status can be
output as a link on an entity.

## Requirements

This module requires the Action Links and Computed Field modules.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Go to Administration › Structure › Action Links.
2. Edit an action link which targets an entity.
3. Enable the 'Show as field' option and save the action link.
4. A computed field will be automatically added to all the bundles of the entity
   type that the action link targets. You can set up its options in the 'Display
   options' for each bundle.
