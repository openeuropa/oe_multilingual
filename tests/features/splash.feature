@api @selection-page
Feature: Splash
  In order to be able to show the language selector page
  As an administrator
  I want to make sure that I can translate content and select an option to view it in a different language

  Background:
    Given the following "Demo translatable page" content item:
      | Title | Test page   |
      | Body  | Hello world |
    And the following "French" translation for the "Demo translatable page" with title "Test page":
      | Title | Page de test     |
      | Body  | Bonjour le monde |
    And the following "Spanish" translation for the "Demo translatable page" with title "Test page":
      | Title | Página de prueba |
      | Body  | Hola Mundo       |

  Scenario: Automatically generated URLs are generated for translated content

    Given I visit the "Test page" content
    Then the url should match "/language_selection"
    Then I should be redirected to the language selection page

    When I click "French"
    Then the url should match "/fr/page-de-test"
