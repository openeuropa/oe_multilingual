@api @selection-page
Feature: Language selection
  In order to be able choose the initial language of the site
  As a visitor
  I want to be presented with a language selection page

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

  Scenario: When I visit the site I'm presented with a language selection page
    # First visit the homepage.
    Given I am on the homepage
    Then I should be redirected to the language selection page
    When I click "français"
    Then the url should match "/fr"
    When I click "português"
    Then the url should match "/pt"
    # Now visit another page and assert the language links are correct.
    When I visit the "Test page" content
    Then I should be redirected to the language selection page
    When I click "français"
    Then the url should match "/fr/page-de-test"
    When I click "português"
    Then the url should match "/pt/pagina-de-teste"

  Scenario: File paths are excluded from the language selection page.
    Given I visit a test "private" file called "sample.pdf"
    Then the current page should be of the "sample.pdf" "private" file
    When I visit a test "public" file called "example_1.jpeg"
    Then the current page should be of the "example_1.jpeg" "public" file
