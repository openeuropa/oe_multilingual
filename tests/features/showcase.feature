@api
Feature: Showcase
  In order to be able to showcase multilingual features
  As an administrator
  I want to make sure that I can translate content and customise its URL per language

  Background:
    Given the following "Demo translatable page" content item:
      | Title | Page title |
      | Body  | Page body  |
    And the following "Italian" translation for the "Demo translatable page" with title "Page title":
      | Title | Titolo pagina |
      | Body  | Testo pagina  |
    And the following "Greek" translation for the "Demo translatable page" with title "Page title":
      | Title | Τίτλος σελίδας  |
      | Body  | Σελίδα κειμένου |

  Scenario: Visitor can navigate translatable content with links in original languages
    Given I visit the "Page title" content
    Then I should see the heading "Page title"
    And I should see "Page body"

    When I click "italiano"
    Then I should see the heading "Titolo pagina"
    And I should see "Testo pagina"

  Scenario: Automatically generated URLs containing non-ASCII characters are transliterated
    Given I visit the "Page title" content

    When I click "italiano" in the "language switcher"
    Then the url should match "/it/titolo-pagina"

    When I click "ελληνικά" in the "language switcher"
    Then the url should match "/el/titlos-selidas"

  Scenario: Site visitor can see and change language using the language switcher with original languages.
    Given I visit the "Page title" content

    When I click "English" in the "language switcher"
    Then I should see the link "български" in the "language dialog"
    And I should not see the link "Bulgarian" in the "language dialog"
    Then I should see the link "português" in the "language dialog"
    And I should not see the link "Portuguese" in the "language dialog"
    Then I should see the link "Malti" in the "language dialog"
    And I should not see the link "Maltese" in the "language dialog"

    When I click "polski" in the "language dialog"
    Then the url should match "/pl"
    When I click "polski" in the "language switcher"
    Then I should see the link "български" in the "language dialog"
    And I should not see the link "Bulgarian" in the "language dialog"
    Then I should see the link "português" in the "language dialog"
    And I should not see the link "Portuguese" in the "language dialog"
    Then I should see the link "Malti" in the "language dialog"
    And I should not see the link "Maltese" in the "language dialog"
