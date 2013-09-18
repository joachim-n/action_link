Action link
===========

TODO: write some documentation.

aciton link itself should be agnostic of a lot of things.

ActionLinkInterface, 3 plugins:
  - Normal reload link
  - Ajax link
  - confirm form link
  
Each plugin:

- gets loaded on the router path
- knows from the path the config entity it has to go and work with.
  thus:
  action_link/CONF-TYPE/CONF-ID/TARGET-TYPE/TARGET-ID/NEW-STATE

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


read config from
- asks the config entity got an ActionLinkCOntroller... this is something like:
  - Flag
  - EntityPropertyController
  - CommerceAddToCart
  - other things I've not yet thought of it

about validity and access
- if access is ok, the config entity hands over to an AL controller. This is
  something like:
    - Flag
    - EntityPropertyController
    - CommerceAddToCart







