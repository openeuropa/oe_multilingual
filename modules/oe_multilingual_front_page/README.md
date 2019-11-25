# OpenEuropa Multilingual Front page

The module ensures that the homepage alias is always maintained when linking to the front page.

# Requirements
If you want to use this submodule, you need to add the following packages to your site:

drupal/redirect

Note: Due to the nature of the suffix negotiation the front page alias is enforced 
in order to avoid urls like ("/_en", "/_fr"). So links that are pointing
to the front page will contain the configured front page alias.
