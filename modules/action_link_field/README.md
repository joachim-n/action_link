# Action Link Field

The Action Link Field module allows action links that target entities to be
output as computed fields on entities.

For example, an action link which toggles a node's published status can be
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
3. Enable the 'Computed field' option and save the action link.
4. A computed field will be automatically added to all the bundles of the entity
   type that the action link targets. You can set up its options in the 'Display
   options' for each bundle.

## Known issues

The 'Output locations' section of the Action Link form does not update correctly
when the form is being shown to add a new Action Link. You should save the form
and return to it to see the option for entity links.
