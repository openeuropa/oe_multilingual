@api @language-negotiation-suffix
Feature: Language negotiation by suffix.
  In order to see the website translated
  As a visitor or privileged
  I want see the website in the language provided by the suffix

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
    And the following "Portuguese" translation for the "Demo translatable page" with title "Test page":
      | Title | Página de teste |
      | Body  | Olá Mundo       |

  Scenario: Access content in different languages
    Given I am on "/test-page_en"
    Then I should see "Hello world"
    When I am on "/page-de-test_fr"
    Then I should see "Bonjour le monde"
    When I am on "/pagina-de-prueba_es"
    Then I should see "Hola Mundo"
    When I am on "/pagina-de-teste_pt"
    Then I should see "Olá Mundo"

  Scenario: As a privileged user I am able to change the url suffix settings
    Given I am logged in as a user with the "administer languages, access administration pages, view the administration theme" permission

    When I am on "the url suffix settings page"
    And I fill in "French (fr) path suffix" with "french"
    And I press "Save configuration"
    And I am on "/page-de-test_french"
    Then I should see "Bonjour le monde"

    When I am on "the url suffix settings page"
    And I fill in "French (fr) path suffix" with "french_fr"
    And I press "Save configuration"
    Then I should see the error message 'The suffix may not contain the delimiter character: "_".'

    When I am on "the url suffix settings page"
    And I fill in "French (fr) path suffix" with ""
    And I press "Save configuration"
    Then I should see the error message 'The suffix may only be left blank for the selected detection fallback language.'

    When I am on "the url suffix settings page"
    And I fill in "French (fr) path suffix" with "es"
    And I press "Save configuration"
    Then I should see the error message 'The suffix for Spanish, es, is not unique.'
    Then I should see the error message 'The suffix for French, es, is not unique.'

