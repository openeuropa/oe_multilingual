@api
Feature: Language selection
  In order to comply with European standards
  The site languages have to be translated

  Scenario: The language switcher contains all language translations
    When I am on the homepage
    And I should see the following links in the language switcher:
      | български   |
      | español     |
      | čeština     |
      | dansk       |
      | Deutsch     |
      | eesti       |
      | ελληνικά    |
      | English     |
      | français    |
      | Gaeilge     |
      | hrvatski    |
      | italiano    |
      | latviešu    |
      | lietuvių    |
      | magyar      |
      | Malti       |
      | Nederlands  |
      | polski      |
      | português   |
      | română      |
      | slovenčina  |
      | slovenščina |
      | suomi       |
      | svenska     |
