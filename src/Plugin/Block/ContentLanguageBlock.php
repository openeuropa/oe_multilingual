<?php

namespace Drupal\oe_multilingual\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a 'Content Language Switcher' block.
 *
 * @Block(
 *   id = "content_language_block",
 *   admin_label = @Translation("OpenEuropa Content Language Switcher"),
 *   category = @Translation("OpenEuropa")
 * )
 */
class ContentLanguageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The route matcher.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatcher;

  /**
   * Constructs an LanguageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_matcher
   *   The route matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher, CurrentRouteMatch $route_matcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
    $this->routeMatcher = $route_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('path.matcher'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Get the potential rendered entity.
    if ($entity = $this->getEntityFromRoute()) {
      // Get current potentially unavailable language.
      $current_language = $this->languageManager->getCurrentLanguage();
      // Get the actual translation that is going to be rendered.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
      $translation = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $current_language->getId());
      $rendered_language = $translation->language();
      // If current language and rendered language don't match, render the
      // content language select block.
      if ($translation->language()->getId() != $current_language->getId()) {
        // Get available translation languages.
        $node_languages = $entity->getTranslationLanguages();
        // Generate language switcher links.
        $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
        $links = $this->languageManager->getLanguageSwitchLinks(LanguageInterface::TYPE_INTERFACE, Url::fromRoute($route_name));
        // Only use the available translation languages and remove the one
        // we are currently using.
        $available_languages = array_intersect_key($links->links, $node_languages);
        unset($available_languages[$rendered_language->getId()]);
        if (isset($links->links)) {
          $build = [
            '#theme' => 'content_language_block',
            '#links' => $available_languages,
            '#unavailable' => $current_language->getName(),
            '#current' => $rendered_language->getName(),
            '#attributes' => [
              'class' => [
                "language-switcher-{$links->method_id}",
              ],
            ],
            '#set_active_class' => TRUE,
          ];
        }
      }

    }

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2232375.
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Helper function to extract the entity for the supplied route.
   *
   * @return null|ContentEntityInterface
   *   Return the entity or null if no entity was found.
   */
  private function getEntityFromRoute() {
    // Entity will be found in the route parameters.
    if (($route = $this->routeMatcher->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $this->routeMatcher->getParameter($name);
          if ($entity instanceof ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
            return $entity;
          }

          // Since entity was found, no need to iterate further.
          return NULL;
        }
      }
    }
  }

}
