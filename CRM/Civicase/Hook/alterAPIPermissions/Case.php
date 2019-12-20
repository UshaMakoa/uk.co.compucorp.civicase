<?php

use CRM_Civicase_Helper_CaseCategory as CaseCategoryHelper;
use CRM_Civicase_Service_CaseCategoryPermission as CaseCategoryPermission;

/**
 * Class CRM_Civicase_Hook_APIPermissions_alterPermissions.
 */
class CRM_Civicase_Hook_alterAPIPermissions_Case {

  /**
   * Case category name.
   *
   * @var string
   *   Case Category Name.
   */
  private $caseCategoryName;

  /**
   * Alters the API permissions.
   *
   * @param string $entity
   *   The API entity.
   * @param string $action
   *   The API action.
   * @param array $params
   *   The API parameters.
   * @param array $permissions
   *   The API permissions.
   */
  public function run($entity, $action, array &$params, array &$permissions) {
    $this->caseCategoryName = $this->getCaseCategoryName($entity, $action, $params);
    $this->alterApiPermissions($entity, $permissions);
  }

  /**
   * Alters the API permissions.
   *
   * The function modifies the API for Case and Case Type entities based on
   * the case type category. It expects certain parameters to be present which
   * is peculiar for each API call, it uses this parameter to determine the case
   * type category, if this parameter is not found, the permissions default
   * to the case category civicase extension permissions.
   *
   * For example, when fetching a list of cases, the case type category
   * parameter is expected to be present(this is already been done
   * on frontend), the category name gotten is used to modify the API
   * permissions to be the case category permission equivalent which is
   * used for the API.
   *
   * @param string $entity
   *   The API entity.
   * @param array $permissions
   *   The API permissions.
   */
  private function alterApiPermissions($entity, array &$permissions) {
    $permissionService = new CaseCategoryPermission();
    $caseCategoryPermissions = $permissionService->get($this->caseCategoryName);
    $basicCasePermissions = $this->getBasicCasePermissions($caseCategoryPermissions);

    $permissions['case']['getfiles'] = [
      [
        $caseCategoryPermissions['ACCESS_MY_CASE_CATEGORY_AND_ACTIVITIES']['name'],
        $caseCategoryPermissions['ACCESS_CASE_CATEGORY_AND_ACTIVITIES']['name'],
      ],
      'access uploaded files',
    ];

    $permissions['case']['get'] = $permissions['custom_value']['gettreevalues'] = [$basicCasePermissions];
    $locationTypePermissions = array_merge($permissions['default']['default'], ['access CiviCRM']);
    $permissions['location_type']['get'] = [$locationTypePermissions];
    $permissions['relationship_type']['getcaseroles'] = $permissions['relationship_type']['get'];
    $permissions['case']['getcount'] = [$basicCasePermissions];
    $permissions['case_type']['get'] = [$basicCasePermissions];
    $permissions['case_type']['create'] = $permissions['case_type']['update'] = [$caseCategoryPermissions['ADMINISTER_CASE_CATEGORY']['name']];
    $permissions['casetype']['getcount'] = [$basicCasePermissions];
    $permissions['custom_value']['gettreevalues'] = [$basicCasePermissions];

    $this->alterPermissionsForSpecificApiActions($entity, $permissionService, $permissions);
  }

  /**
   * Alter permissions for specific API actions.
   *
   * The Civicase extension completely overrides the permissions for some
   * API actions such as get, getcount, but some API actions are un-affected.
   * This API's e.g Case.delete still have some civicase specific permission, we
   * need to do word replacements for these permissions to reflect the case
   * category under consideration.
   *
   * @param string $entity
   *   The API entity.
   * @param \CRM_Civicase_Service_CaseCategoryPermission $permissionService
   *   Permission service object.
   * @param array $permissions
   *   The API permissions.
   */
  private function alterPermissionsForSpecificApiActions($entity, CaseCategoryPermission $permissionService, array &$permissions) {
    if ($entity != 'case') {
      return;
    }

    $specificCaseActions = ['create', 'delete'];
    foreach ($specificCaseActions as $actionName) {
      $permissionToChange = $permissions['case'][$actionName];
      foreach ($permissionToChange as $key => $permissionName) {
        $permissions['case'][$actionName][$key] = $permissionService->replaceWords($permissionName, $this->caseCategoryName);
      }
    }
  }

  /**
   * The basic civicase permission set.
   *
   * Most of the overridden Case API permission use this set of permissions
   * so it made sense to extract to a function so it can be used.
   *
   * @param array $caseCategoryPermissions
   *   Case category permissions.
   *
   * @return array
   *   The basic permission set.
   */
  private function getBasicCasePermissions(array $caseCategoryPermissions) {
    return [
      $caseCategoryPermissions['ACCESS_MY_CASE_CATEGORY_AND_ACTIVITIES']['name'],
      $caseCategoryPermissions['ACCESS_CASE_CATEGORY_AND_ACTIVITIES']['name'],
      $caseCategoryPermissions['BASIC_CASE_CATEGORY_INFO']['name'],
    ];
  }

  /**
   * Returns the case category name based on some conditions.
   *
   * @param string $entity
   *   The API entity.
   * @param string $action
   *   The API action.
   * @param array $params
   *   The API parameters.
   *
   * @return string|null
   *   Case category name.
   */
  private function getCaseCategoryName($entity, $action, array $params) {
    if ($entity == 'case' && $action == 'delete') {
      return $this->getCaseCategoryNameForCaseWhenActionIsDelete($params);
    }

    if ($entity == 'case' && $action == 'create') {
      return $this->getCaseCategoryNameForCaseWhenActionIsCreate($params);
    }

    if ($entity == 'case' && $action != 'delete') {
      return $this->getCaseCategoryNameForCaseWhenActionNotDelete($params);
    }

    if ($entity == 'case_type' && $action != 'delete') {
      return $this->getCaseCategoryNameForCaseTypeWhenActionNotDelete($params);
    }
  }

  /**
   * Returns the case category name when action is delete.
   *
   * @param array $params
   *   API parameters.
   *
   * @return string|null
   *   Case category name.
   */
  private function getCaseCategoryNameForCaseWhenActionIsDelete(array $params) {
    return CaseCategoryHelper::getCategoryName($params['id']);
  }

  /**
   * Returns the case category name when action is create.
   *
   * @param array $params
   *   API parameters.
   *
   * @return string|null
   *   Case category name.
   */
  private function getCaseCategoryNameForCaseWhenActionIsCreate(array $params) {
    if (empty($params['case_type_id'])) {
      return;
    }

    return CaseCategoryHelper::getCategoryNameForCaseType($params['case_type_id']);
  }

  /**
   * Returns the case category name when action is not delete.
   *
   * @param array $params
   *   API parameters.
   *
   * @return string|null
   *   Case category name.
   */
  private function getCaseCategoryNameForCaseWhenActionNotDelete(array $params) {
    if (empty($params['case_type_id.case_type_category'])) {
      return;
    }

    return $this->getCaseTypeCategoryNameFromOptions($params['case_type_id.case_type_category']);
  }

  /**
   * Returns the case category name when action is not delete.
   *
   * @param array $params
   *   API parameters.
   *
   * @return string|null
   *   Case category name.
   */
  private function getCaseCategoryNameForCaseTypeWhenActionNotDelete(array $params) {
    if (empty($params['case_type_category'])) {
      return;
    }

    return $this->getCaseTypeCategoryNameFromOptions($params['case_type_category']);
  }

  /**
   * Returns the case category name from case type id or name.
   *
   * @param mixed $caseTypeCategory
   *   Case category name.
   *
   * @return string
   *   Case category name.
   */
  private function getCaseTypeCategoryNameFromOptions($caseTypeCategory) {
    if (!is_numeric($caseTypeCategory)) {
      return $caseTypeCategory;
    }

    return CaseCategoryHelper::getCaseCategoryNameFromOptionValue($caseTypeCategory);
  }

}
