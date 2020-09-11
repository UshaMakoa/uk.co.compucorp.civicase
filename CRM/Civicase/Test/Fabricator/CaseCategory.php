<?php

/**
 * CaseCategory Fabricator.
 */
class CRM_Civicase_Test_Fabricator_CaseCategory {

  /**
   * Fabricate a case category.
   *
   * @param array $params
   *   Parameters.
   *
   * @return array
   *   Results.
   */
  public static function fabricate(array $params = []) {
    $params = self::mergeDefaultParams($params);
    $result = civicrm_api3('OptionValue', 'create', $params);
    $caseCategoryResult = array_shift($result['values']);
    if (!empty($params['instance_id'])) {
      civicrm_api3('CaseCategoryInstance', 'create', [
        'instance_id' => $params['instance_id'],
        'category_id' => $caseCategoryResult['value'],
      ]);
    }

    return $caseCategoryResult;
  }

  /**
   * Merge to default parameters.
   *
   * @param array $params
   *   Parameters.
   *
   * @return array
   *   Resulting merged parameters.
   */
  private static function mergeDefaultParams(array $params) {
    $name = substr(uniqid(), 1, 5);
    $defaultParams = [
      'option_group_id' => 'case_type_categories',
      'label' => $name,
      'name' => $name,
      'is_active' => 1,
    ];

    return array_merge($defaultParams, $params);
  }

}
