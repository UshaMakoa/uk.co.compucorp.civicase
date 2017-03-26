<?php
require_once 'api/v3/Case.php';

/**
 * Case.getdetails API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_case_getdetails_spec(&$spec) {
  $result = civicrm_api3('Case', 'getfields', array('api_action' => 'get'));
  $spec = $result['values'];
}

/**
 * Case.getdetails API
 *
 * This is provided by the CiviCase extension. It gives more robust output than the regular get action.
 *
 * @param array $params
 * @return array API result
 * @throws API_Exception
 */
function civicrm_api3_case_getdetails($params) {
  $params += array('return' => array());
  if (is_string($params['return'])) {
    $params['return'] = explode(',', str_replace(' ', '', $params['return']));
  }
  $toReturn = $params['return'];
  $options = CRM_Utils_Array::value('options', $params, array());
  $extraReturnProperties = array('activity_summary', 'last_update', 'activity_count');
  $params['return'] = array_diff($params['return'], $extraReturnProperties);
  $result = civicrm_api3_case_get(array('sequential' => 0) + $params);
  if (!empty($result['values'])) {
    $ids = array_keys($result['values']);

    // Remove legacy cruft
    foreach ($result['values'] as &$case) {
      unset($case['client_id']);
    }

    // Get activity summary
    if (in_array('activity_summary', $toReturn)) {
      $catetoryLimits = CRM_Utils_Array::value('categories', $options, array_fill_keys(array('alert', 'milestone', 'task', 'communication'), 0));
      $categories = array_fill_keys(array_keys($catetoryLimits), array());
      foreach ($result['values'] as &$case) {
        $case['activity_summary'] = $categories + array('overdue' => array());
      }
      $allTypes = array();
      foreach (array_keys($categories) as $grouping) {
        $option = civicrm_api3('OptionValue', 'get', array(
          'return' => array('value'),
          'option_group_id' => 'activity_type',
          'grouping' => array('LIKE' => "%{$grouping}%"),
          'options' => array('limit' => 0),
        ));
        foreach ($option['values'] as $val) {
          $categories[$grouping][] = $allTypes[] = $val['value'];
        }
      }
      $activities = civicrm_api3('Activity', 'get', array(
        'return' => array('activity_type_id', 'subject', 'activity_date_time', 'status_id', 'case_id', 'assignee_contact_name'),
        'check_permissions' => !empty($params['check_permissions']),
        'case_id' => array('IN' => $ids),
        'is_current_revision' => 1,
        'is_test' => 0,
        'status_id' => array('NOT IN' => array('Completed', 'Cancelled')),
        'activity_type_id' => array('IN' => array_unique($allTypes)),
        'activity_date_time' => array('<' => 'now'),
        'options' => array(
          'limit' => 0,
          'sort' => 'activity_date_time',
          'or' => array(array('activity_date_time', 'activity_type_id')),
        ),
      ));
      foreach ($activities['values'] as $act) {
        $case =& $result['values'][$act['case_id']];
        unset($act['case_id']);
        foreach ($categories as $category => $grouping) {
          if (in_array($act['activity_type_id'], $grouping) && (!$catetoryLimits[$category] || count($case['activity_summary'][$category]) < $catetoryLimits[$category])) {
            $case['activity_summary'][$category][] = $act;
          }
        }
        if (strtotime($act['activity_date_time']) < time()) {
          $case['activity_summary']['overdue'][] = $act;
        }
      }
    }
    // Get activity count
    if (in_array('activity_count', $toReturn)) {
      foreach ($result['values'] as $id => &$case) {
        $query = "SELECT COUNT(a.id) as count, a.activity_type_id
          FROM civicrm_activity a
          INNER JOIN civicrm_case_activity ca ON ca.activity_id = a.id
          WHERE a.is_current_revision = 1 AND a.is_test = 0 AND ca.case_id = $id
          GROUP BY a.activity_type_id";
        $dao = CRM_Core_DAO::executeQuery($query);
        while ($dao->fetch()) {
          $case['activity_count'][$dao->activity_type_id] = $dao->count;
        }
      }
    }
    // Get last update
    if (in_array('last_update', $toReturn)) {
      // todo
    }
    if (!empty($params['sequential'])) {
      $result['values'] = array_values($result['values']);
    }
  }
  return $result;
}
