@api
Feature: Interface default language.
  In order to navigate the site
  As a admin
  I want the interface language to be in the site default language

  Scenario: As an admin, I should see admin pages in the default site language
    Given I am logged in as a user with the "access administration pages" permission
    And I translate "Structure" in "French" to "French STR"
    And I go to "/en/admin"
    Then I should see the link "Structure"
    And I go to "/fr/admin"
    Then I should see the link "Structure"
    And I set the default site language to "French"
    And I go to "/en/admin"
    Then I should see the link "French STR"
    And I should not see the link "Structure"
    And I go to "/fr/admin"
    Then I should see the link "French STR"
    And I should not see the link "Structure"
