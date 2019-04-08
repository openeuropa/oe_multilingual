@api
Feature: Content language selector
  When I'm viewing a content item not available in the current site language
  I'm given the possibility of choosing another language for it
  while the content is displayed by default in its default language
  i.e.: the language in which the content has been inserted

  Background:
    Given the following "Demo translatable page" content item:
      | Title | Page title |
      | Body  | Page body  |
    And the following "Greek" translation for the "Demo translatable page" with title "Page title":
      | Title | Τίτλος σελίδας  |
      | Body  | Σελίδα κειμένου |
    And the following "Spanish" translation for the "Demo translatable page" with title "Page title":
      | Title | Título de página  |
      | Body  | Texto de página   |
    And the following "Italian" translation for the "Demo translatable page" with title "Page title":
      | Title | Titolo pagina |
      | Body  | Testo pagina  |

  Scenario: Visitor navigating to the original content shouldn't see the language selector
    When I visit the "Page title" content
    Then I should see the heading "Page title"
    And I should see "Page body"

    And I should not see the link "ελληνικά" in the "page content"
    And I should not see the link "español" in the "page content"
    And I should not see the link "italiano" in the "page content"

  Scenario: Visitor navigating to an available translation shouldn't see the language selector
    When I visit the "Page title" content
    And I click "italiano"
    Then I should see the heading "Titolo pagina"
    And I should see "Testo pagina"

    And I should not see the link "ελληνικά" in the "page content"
    And I should not see the link "English" in the "page content"
    And I should not see the link "español" in the "page content"

  Scenario: Visitor navigating to an unavailable translation should see the language selector
    When I visit the "Page title" content
    And I click "Deutsch"
    Then I should see the heading "Page title"
    And I should see "Page body"

    And I should see the link "ελληνικά" in the "page content"
    And I should see the link "español" in the "page content"
    And I should see the link "italiano" in the "page content"
