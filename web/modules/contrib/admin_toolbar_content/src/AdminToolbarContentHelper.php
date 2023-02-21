<?php

namespace Drupal\admin_toolbar_content;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

/**
 * Admin Toolbar Content helper service.
 */
class AdminToolbarContentHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Create an AdminToolbarToolsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('admin_toolbar_content.settings');
  }

  /**
   * Gets a list of content entities.
   *
   * @return array
   *   An array of metadata about content entities.
   */
  public function getBundleableEntitiesList() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $content_entities = [];
    foreach ($entity_types as $key => $entity_type) {
      if ($entity_type->getBundleEntityType() && ($entity_type->get('field_ui_base_route') != '')) {
        $content_entities[$key] = [
          'content_entity' => $key,
          'content_entity_bundle' => $entity_type->getBundleEntityType(),
        ];
      }
    }
    return $content_entities;
  }

  /**
   * Gets an array of entity types that should trigger a menu rebuild.
   *
   * @return array
   *   An array of entity machine names.
   */
  public function getRebuildEntityTypes() {
    $types = ['menu'];
    $content_entities = $this->getBundleableEntitiesList();
    $types = array_merge($types, array_column($content_entities, 'content_entity'));
    return $types;
  }

  /**
   * Returns the amount of recent items.
   *
   * @return int
   *   The amount of recent items to show.
   */
  public function recentItems(): int {
    return $this->config->get('recent_items') ?? 0;
  }

  /**
   * Check if menu link needs to be rebuild.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for.
   */
  public function menuLinkRebuild(EntityInterface $entity) {
    $entities = $this->getRebuildEntityTypes();
    if (in_array($entity->getEntityTypeId(), $entities)) {
      // Do not rebuild the menu link if entity is node and there are no recent
      // items.
      if ($entity instanceof Node) {
        if (!empty($this->recentItems())) {
          \Drupal::service('plugin.manager.menu.link')->rebuild();
        }
      }
      else {
        \Drupal::service('plugin.manager.menu.link')->rebuild();
      }
    }
  }

}
