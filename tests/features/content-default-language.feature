@api
Feature: Content initial language.
  In order to create content
  As a content editor
  I can create an initial version of a node in any language

  Scenario: As an editor, when I create a node I can select the initial node language
    Given I am logged in as a user with the "create oe_demo_translatable_page content" permission
    And I visit the "Demo translatable page" creation page
    Then I should see "Language"

    When I fill in "Title" with "Title Default value"
    And I fill in "Body" with "Body Default value"
    And I press "Save"
    Then I should see the success message "Demo translatable page Title Default value has been created."
    And The only available translation for "Title Default value" is in the site's default language
