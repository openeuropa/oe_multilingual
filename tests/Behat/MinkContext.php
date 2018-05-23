<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_multilingual\Behat;

use Drupal\DrupalExtension\Context\MinkContext as DrupalExtensionMinkContext;


/**
 * Class MinkContext.
 */
class MinkContext extends DrupalExtensionMinkContext {

  /**
   * Assert links in region.
   *
   * @param string $region
   *   Region name.
   * @param \Behat\Gherkin\Node\TableNode $links
   *   List of links.
   *
   * @throws \Exception
   *
   * @Then I should see the following links in (the ):region( region):
   */
  public function assertLinksInRegion($region, TableNode $links): void {
    $region = $this->getSession()->getPage()->find('region', $region);
    foreach ($links->getRows() as $row) {
      $result = $region->findLink($row[0]);
      if (empty($result)) {
        throw new \Exception(sprintf('No link to "%s" in the "%s" region on the page %s', $row[0], $region, $this->getSession()
          ->getCurrentUrl()));
      }
    }
  }
}
