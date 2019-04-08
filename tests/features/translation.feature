@api
Feature: Translate content
  In order to be able to have multilingual content
  As an editor
  I need to create and translate content

  Scenario: As an editor I want to create and translate content
    Given I am logged in as a user with the "create content translations, translate oe_demo_translatable_page node, create oe_demo_translatable_page content" permission

    # Create a Translatable page content.
    When I am on "the demo content creation page"
    And I fill in "Title" with "Test page"
    And I fill in "Body" with "This is a test"
    And I press "Save"

    Then I should see the success message "Demo translatable page Test page has been created."
    And I should see "Test page" in the "page content"
    And I should see "This is a test" in the "page content"

    # Translate the Translatable page content into Spanish.
    When I click "Translate"
    And I click "Add" in the "Spanish" row
    And I fill in "Title" with "Página de prueba"
    And I fill in "Body" with "Esto es una prueba"
    And I press "Save (this translation)"

    Then I should see the success message "Demo translatable page Página de prueba has been updated."
    And I should see "Página de prueba" in the "page content"
    And I should see "Esto es una prueba" in the "page content"

