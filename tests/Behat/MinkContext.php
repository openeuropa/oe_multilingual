<?php

namespace Drupal\Tests\oe_multilingual\Behat;

use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Class MinkContext.
 */
class MinkContext extends RawMinkContext {

  /**
   * Assert that visitor is redirected to language selection page.
   *
   * @Then I should be redirected to the language selection page
   */
  public function assertLanguageSelectionPageRedirect() {
    $this->assertSession()->addressMatches("/.*\/select-language/");
  }

}
