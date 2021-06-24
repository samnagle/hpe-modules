<?php

namespace Drupal\permissions_by_term\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\permissions_by_term\Service\NodeEntityBundleInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class NodeEntityBundleController
 *
 * @package Drupal\permissions_by_term\Controller
 */
class NodeEntityBundleController extends ControllerBase {

  /**
   * @var EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * @var NodeEntityBundleInfo
   */
  private $nodeEntityBundleInfo;

  public function __construct(EntityFieldManager $entityFieldManager, NodeEntityBundleInfo $nodeEntityBundleInfo) {
    $this->entityFieldManager = $entityFieldManager;
    $this->nodeEntityBundleInfo = $nodeEntityBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('permissions_by_term.node_entity_bundle_info')
    );
  }

  /**
   * @param string $nodeType
   *
   * @return JsonResponse
   */
  public function getFormInfoByContentType($nodeType) {
    $fields = $this->entityFieldManager->getFieldDefinitions('node', $nodeType);

    $fieldNames = [];
    foreach ($fields as $field) {
      $fieldDefinitionSettings = $field->getSettings();
      if (!empty($fieldDefinitionSettings['target_type']) && $fieldDefinitionSettings['target_type'] == 'taxonomy_term') {
        $fieldNames[] = $field->getFieldStorageDefinition()->getName();
      }
    }

    return new JsonResponse(
      [
        'taxonomyRelationFieldNames' => $fieldNames,
        'permissions'                => $this->nodeEntityBundleInfo->getPermissions()
      ]
    );
  }

  /**
   * @return JsonResponse
   */
  public function getFormInfoByUrl() {
    $contentType = $this->getContentType(\Drupal::request()->query->get('url'));

    if ($contentType === NULL) {
      return new JsonResponse([]);
    }

    $fields = $this->entityFieldManager->getFieldDefinitions('node', $contentType);

    $fieldNames = [];
    foreach ($fields as $field) {
      $fieldDefinitionSettings = $field->getSettings();
      if (!empty($fieldDefinitionSettings['target_type']) && $fieldDefinitionSettings['target_type'] == 'taxonomy_term') {
        $fieldNames[] = $field->getFieldStorageDefinition()->getName();
      }
    }

    return new JsonResponse(
      [
        'taxonomyRelationFieldNames' => $fieldNames,
        'permissions'                => $this->nodeEntityBundleInfo->getPermissions()
      ]
    );
  }

  private function getContentType($nodeEditPath) {
    $alias = \Drupal::service('path.alias_manager')->getPathByAlias($nodeEditPath);
    $params = Url::fromUri("internal:" . $alias)->getRouteParameters();
    $entity_type = key($params);
    $node = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);

    if ($node instanceof Node) {
      return $node->getType();
    }

    return NULL;
  }

}
