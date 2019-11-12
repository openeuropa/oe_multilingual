# OpenEuropa Multilingual URL Suffix

The module provides language negotiation and detection based on URL suffixes.
Suffixes can be configured for each language.

Note: Due to the nature of the suffix negotiation the front page alias is enforced 
in order to avoid urls like ("/_en", "/_fr"). So links that are pointing
to the front page will most likely contain the configured front page alias.
