# Cockpit CMS Content Validation Addon

This addon examines all collection items for common error patterns and displays a list of errors/warnings. 

## Installation

1. Confirm that you have Cockpit CMS (Next branch) installed and working.
2. Download zip and extract to 'your-cockpit-docroot/addons' (e.g. cockpitcms/addons/ErrorCheck, the addon folder name must be ErrorCheck)
3. Confirm that the ContentValidation icon appears on the top left of the modules menu.

### Permissions

To Implement

## Usage

The AddOn adds a new menu item item "Content Validation". Navigating to it you will see a list of errors identified 

![Alt text](https://raw.github.com/MangoArt/CockpitCMSAddon-ContentValidation/master/docs/screenshot_cockpit_cms_content_validation.png?sanitize=true)

Currently the plugin detects the following errors:
 - Required fields that are empty (can happen if the required attribute was added later)
 - CollectionLink field values that link to collection types that no longer exist
 - CollectionLink field values that link to collection items that no longer exist
 - Image fields with values(urls) that are no longer valid
 
Additionally it will warn if a GIF image is used since gatsby-image-sharp cannot process GIF images. 

## Roadmap

The current plugin is a quick prototype that I hacked together in a few hours on the weekend. But while the code needs some
major cleanup it is already pretty useful and helped me identify a number of errors. I hope 

 1. Code cleanup/follow coding patters used for other addons
 1. Add support for Singletons
 1. Add support for Regions
 1. Add support for set type
 1. Add support for repeater type
 1. Add support for asset fields
 1. Add support for gallery fields
 1. Settings page to enable/disable validations
 
There is also some other functionality that I might consider adding to the plugin
 1. Data cleanup; when removing a field the corresponding values stay in the data object which might cause confusion later on.
 1. Collection Link name update: when changing the name of a linked collection item this change isn't reflected in the link itself which can lead to some confusion down the line.
 
Ideally this sort of cleanup can optionally happen automatically using a cron job every night.    

## Copyright and license

Copyright 2019 Markus Oehler under the MIT license.
