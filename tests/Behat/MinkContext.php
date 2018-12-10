<?php

namespace Drupal\Tests\oe_multilingual\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use PHPUnit\Framework\Assert;

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
   * @Then I should see the following links in the language switcher:
   */
  public function assertLinksInRegion(TableNode $links): void {
    $switcher = $this->getSession()->getPage()->find('css', '#block-oe-multilingual-language-switcher');
    $switcher_links = $switcher->findAll('css', 'a');
    $actual_links = [];
    /** @var \Behat\Mink\Element\NodeElement $switcher_link */
    foreach ($switcher_links as $switcher_link) {
      $actual_links[] = $switcher_link->getText();
    }
    $expected_links = [];
    foreach ($links->getRows() as $row) {
      $expected_links[] = $row[0];

    }
    Assert::assertEquals($expected_links, $actual_links);
  }

}
