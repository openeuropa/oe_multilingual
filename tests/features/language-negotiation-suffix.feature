@api @language-negotiation-suffix
Feature: Language negotiation by suffix.
  In order to see the website translated
  As a visitor
  I want see the website in the language provided by the suffix

  Scenario: Access content in different languages
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
    When I am on "/test-page_en"
    Then I should see "Hello world"
    When I am on "/page-de-test_fr"
    Then I should see "Bonjour le monde"
    When I am on "/pagina-de-prueba_es"
    Then I should see "Hola Mundo"
    When I am on "/pagina-de-teste_pt"
    Then I should see "Olá Mundo"
