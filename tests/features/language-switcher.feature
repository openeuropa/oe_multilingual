@api
Feature: Language selection
  In order to comply to European standards
  The site languages have to be translated

  Scenario: The language switcher contains all language translations
    When I am on the homepage
    And I should see the following links in the language switcher:
      | български   |
      | čeština     |
      | dansk       |
      | Deutsch     |
      | eesti       |
      | ελληνικά    |
      | English     |
      | español     |
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