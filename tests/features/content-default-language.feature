@api
Feature: Content initial language.
  In order to be create content
  As a editor
  I want to make sure that it impossible for a content editor to create an initial version of a content in any language other
  than the site default language

  Scenario: Content editor try to create node without possibility select initial node language
    Given I am logged in as a user with the "create oe_demo_translatable_page content,translate oe_demo_translatable_page node" permission
    And I am visiting the "Demo translatable page" create node page
    Then I should not see selectbox with label "Language"

    When I fill in "Title" with "Title Default value"
    And I fill in "Body" with "Body Default value"
    And I press "Save"
    Then I should see the success message "Demo translatable page Title Default value has been created."

    When I click "Translate"
    Then I have to be sure that "Title Default value" node translation only in site default language.
