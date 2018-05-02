@api
Feature: Showcase
  In order to be able to showcase multilingual features
  As an administrator
  I want to make sure that I can translate content and customise its URL per language

  Scenario: Visitor can navigate translatable content

    Given the following "Demo translatable page" content item:
      | Title | Page title |
      | Body  | Page body  |
    And the following "it" translation for the "Demo translatable page" with title "Page title":
      | Title | Titolo pagina |
      | Body  | Testo pagina  |

    And I visit the "Page title" content
    Then I should see the heading "Page title"
    And I should see "Page body"

    When I click "Italian"
    Then I should see the heading "Titolo pagina"
    And I should see "Testo pagina"
