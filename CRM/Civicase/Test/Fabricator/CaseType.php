<?php

/**
 * Fabricates case types.
 */
class CRM_Civicase_Test_Fabricator_CaseType {

  private static $defaultParams = [
    'title' => 'test case type',
    'name' => 'test_case_type',
    'is_active' => 1,
    'sequential'   => 1,
    'weight' => 100,
    'definition' => [
      'activityTypes' => [
        ['name' => 'Test'],
      ],
      'activitySets' => [
        [
          'name' => 'set1',
          'label' => 'Label 1',
          'timeline' => 1,
          'activityTypes' => [
            ['name' => 'Open Case', 'status' => 'Completed'],
          ],
        ],
      ],
    ],
  ];

  public static function fabricate($params = []) {
    $params = array_merge(self::$defaultParams, $params);
    $result = civicrm_api3(
      'CaseType',
      'create',
      $params
    );
    return array_shift($result['values']);
  }

}
