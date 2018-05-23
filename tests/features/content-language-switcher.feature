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
    And the following "Italian" translation for the "Demo translatable page" with title "Page title":
      | Title | Titolo pagina |
      | Body  | Testo pagina  |
    And the following "Greek" translation for the "Demo translatable page" with title "Page title":
      | Title | Τίτλος σελίδας  |
      | Body  | Σελίδα κειμένου |
    And the following "Spanish" translation for the "Demo translatable page" with title "Page title":
      | Title | Título de página  |
      | Body  | Texto de página   |

  Scenario: Visitor navigating to an available translation shouldn't see the language selector
    Given I visit the "Page title" content
    Then I should see the heading "Page title"
    And I should see "Page body"
    And I should not see the link "Spanish" in the "content" region

  Scenario: Visitor navigating to an unavailable translation should see the language selector
    Given I visit the "Page title" content
    Then I should see the heading "Page title"
    And I should see "Page body"
    When I click "Bulgarian"
    Then I should see the heading "Page title"
    And I should see "Page body"
    And I should see the link "Spanish" in the "content" region
