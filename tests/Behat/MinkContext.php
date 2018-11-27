<?php

namespace Drupal\Tests\oe_multilingual\Behat;

use Behat\Gherkin\Node\TableNode;
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

  /**
   * Assert links in region.
   *
   * @param \Behat\Gherkin\Node\TableNode $links
   *   List of links.
   *
   * @throws \Exception
   *
   * @Then I should see the following links in the language switcher:
   */
  public function assertLinksInRegion(TableNode $links): void {
    $switcher = $this->getSession()->getPage()->find('css', '#block-oe-multilingual-language-switcher');

    foreach ($links->getRows() as $row) {
      $result = $switcher->findLink($row[0]);
      if (empty($result)) {
        throw new \Exception(sprintf('No link to "%s" in the "%s" region on the page %s', $row[0], $region, $this->getSession()->getCurrentUrl()));
      }
    }
  }

}
