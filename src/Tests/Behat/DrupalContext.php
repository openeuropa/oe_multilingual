<?php

declare(strict_types = 1);

namespace Drupal\oe_multilingual\Tests\Behat;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\oe_multilingual\Tests\Behat\Traits\ContentManagerTrait;

/**
 * Class DrupalContext.
 */
class DrupalContext extends RawDrupalContext {
  use ContentManagerTrait;

  /**
   * Create content given its type and fields.
   *
   * @Given the following :arg1 content item:
   */
  public function createContent(string $entity_type_label, TableNode $table): void {
    $node = (object) $this->getContentValues($entity_type_label, $table);
    $this->nodeCreate($node);
  }

  /**
   * Assert viewing content given its type and title.
   *
   * @param string $title
   *   Content title.
   *
   * @Given I am visiting the :title content
   * @Given I visit the :title content
   */
  public function iAmViewingTheContent($title): void {
    $nid = $this->getEntityByLabel('node', $title)->id();
    $this->visitPath("node/$nid");
  }

  /**
   * Redirect user to the node creation page.
   *
   * @param string $content_type_name
   *   Content type name.
   *
   * @Given I visit the :content_type_name creation page
   */
  public function iAmVisitingTheCreationPage(string $content_type_name): void {
    $node_bundle = $this->getEntityTypeByLabel($content_type_name);
    $this->visitPath('node/add/' . $node_bundle);
  }

  /**
   * Check that the field is not present.
   *
   * @param string $field
   *   Input id, name or label.
   *
   * @Then I should not see the field :field
   */
  public function iShouldNotSeeTheField(string $field): void {
    $element = $this->getSession()
      ->getPage()
      ->findField($field);
    if ($element) {
      throw new \RuntimeException("Field '{$field}' is present.");
    }
  }

}
