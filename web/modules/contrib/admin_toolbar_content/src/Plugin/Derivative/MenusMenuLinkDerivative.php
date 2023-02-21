<?php

namespace Drupal\admin_toolbar_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Menus MenuLinkDerivative.
 */
class MenusMenuLinkDerivative extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Create an AdminToolbarToolsHelper object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $languageManager) {
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    $config = \Drupal::config('admin_toolbar_content.settings');
    $show_menus_item = $config->get('show_menus_item') ?? 0;

    if ($show_menus_item && $this->moduleHandler->moduleExists('menu_ui')) {

      $links['menus'] = [
        'title' => $this->t('Menus'),
        'route_name' => 'entity.menu.collection',
        'route_parameters' => [],
        'menu_name' => 'admin',
        'parent' => 'system.admin',
        'weight' => -8,
      ] + $base_plugin_definition;

      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
      $entityTypeManager = \Drupal::service('entity_type.manager');

      $menus_collections = \Drupal::service('module_handler')
        ->invokeAll('menus_collections');

      // Sort collections so grouping will use alphabetic order.
      asort($menus_collections);

      $group_collections = $config->get('group_collections') ?? 0;
      if ($group_collections) {
        switch ($group_collections) {
          case 'bottom':
            $group_collections_weight = 100;
            break;

          case 'top':
            $group_collections_weight = -100;
            break;
        }
      }

      $menus = $entityTypeManager->getStorage('menu')->loadMultiple();

      foreach ($menus_collections as $collection => $menu_collections) {
        $links['menus.' . $collection] = [
          'title' => $this->t((string) $menu_collections['label']),
          'route_name' => 'entity.menu.collection',
          'route_parameters' => [
            'collection' => $collection,
          ],
          'menu_name' => 'admin',
          'parent' => $base_plugin_definition['id'] . ':menus',
          'options' => [
            'attributes' => [
              'class' => [
                'admin-toolbar-collection',
                'admin-toolbar-collection--menus',
              ],
            ],
          ],
        ] + $base_plugin_definition;

        // Use group collections weight if group collections is enabled.
        if (!empty($group_collections_weight)) {
          $links['menus.' . $collection]['weight'] = $group_collections_weight;
          $group_collections_weight++;
        }

        foreach ($menu_collections['menus'] as $menu_id) {
          if (isset($menus[$menu_id])) {
            $this->addMenuLink($menus[$menu_id], $collection, $links, $base_plugin_definition);
            unset($menus[$menu_id]);
          }
        }
      }

      $collection = 'menus';
      foreach ($menus as $menu) {
        $this->addMenuLink($menu, $collection, $links, $base_plugin_definition);
      }
    }

    return $links;
  }

  /**
   * Add the menu link based on a collection.
   *
   * @param \Drupal\system\Entity\Menu $menu
   *   The menu.
   * @param string $collection
   *   The collection.
   * @param array $links
   *   The links.
   * @param array $base_plugin_definition
   *   The base plugin definition.
   */
  protected function addMenuLink(Menu $menu, string $collection, array &$links, array $base_plugin_definition) {

    $link_name = $collection . '.' . $menu->id();

    $links['menus.' . $link_name] = [
      'title' => $menu->label(),
      'route_name' => 'entity.menu.edit_form',
      'route_parameters' => [
        'menu' => $menu->id(),
      ],
      'menu_name' => 'admin',
      'parent' => !empty($links['menus.' . $collection]) ? $base_plugin_definition['id'] . ':menus.' . $collection : $base_plugin_definition['id'] . ':menus',
      'metadata' => [
        'entity_type' => 'menu',
        'entity_id' => $menu->id(),
      ],
    ] + $base_plugin_definition;

    $links['menus.' . $link_name . '.add'] = [
      'title' => $this->t('Add new'),
      'route_name' => 'entity.menu.add_link_form',
      'route_parameters' => [
        'menu' => $menu->id(),
      ],
      'menu_name' => 'admin',
      'parent' => $base_plugin_definition['id'] . ':menus.' . $link_name,
      'metadata' => [
        'entity_type' => 'menu',
        'entity_id' => $menu->id(),
      ],
    ] + $base_plugin_definition;

  }

}
