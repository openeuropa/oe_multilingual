<?php

namespace Drupal\oe_multilingual_front_page;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path by resolving it to the front page if empty.
 *
 * @todo - remove ::processOutbound() when we remove UrlGenerator::fromPath().
 */
class PathProcessorFront implements OutboundPathProcessorInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a PathProcessorFront object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory for retrieving the site front page configuration.
   * @param \Drupal\Core\Path\AliasManager $aliasManager
   *   The alias manager.
   */
  public function __construct(ConfigFactoryInterface $config, AliasManager $aliasManager) {
    $this->config = $config;
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // The special path '<front>' links to the default front page.
    if (in_array($path, ['/<front>', '/'])) {
      $front_uri = $this->config->get('system.site')->get('page.front');
      $front_alias = $this->aliasManager->getAliasByPath($front_uri);
      $path = $front_alias;
    }

    // Ensure front-page path has the configured alias of the front-page
    // in order to avoid links pointing to "/_[language_suffix]".
    if ($path === '/') {
      $front_uri = $this->config->get('system.site')->get('page.front');
      $front_alias = $this->aliasManager->getAliasByPath($front_uri);
      $path = $front_alias;
    }

    return $path;
  }

}
