@api
Feature: Language selection
  In order to comply with European standards
  The site languages have to be translated

  Scenario: The language switcher contains all language translations
    When I am on the homepage
    And the "language switcher" element should have the following links:
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
