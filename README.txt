Action link
===========

This is a proof of concept for decoupling the Flag UI from Flag.

See https://drupal.org/node/2071245 for background and motivation.

On install, this will add a demo link to all nodes which toggles the node's
published status.

Dev notes
---------

The action link style should come in 3 flavours, each one a plugin:
  - Normal reload link
  - Ajax link
  - Confirm form link

Each plugin:

- gets loaded on the router path
- knows from the path the config entity it has to go and work with.
  thus:
  action_link/LINK-STYLE/CONF-TYPE/CONF-ID/TARGET-TYPE/TARGET-ID/NEW-STATE

This could be one of:
  - Flag
  - generic ActionLinkConfig
  - CommerceAddToCart
  - Boolean Field if we can figure this one out. Might need a wrapper?
  - other uses that contrib will come up with...

This config entity is associated with a Controller....
    - built into the config entity type?
    - setting on the config entity?
  thus:
  - The flag config entity has a FlagController
  - The generic action link has several possibilities, such as:
    - EntityPropertyController
    - OG subscribe ?

We're now in a position to go!
  - the ActionLink plugin asks the controller:
    - is my request sane? (ie, does this bunch of settings work?
      can this node be flagged with that flag?)
    - does the user have access to do this?
  - if either is denied, the link returns an error.
    - The message is a setting on the config entity
    - The format of the error is determined by the plugin

