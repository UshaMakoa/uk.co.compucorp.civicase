<?php

use CRM_Civicase_Service_CaseCategoryMenu as CaseCategoryMenu;
use CRM_Civicase_Service_CaseManagementCustomGroupPostProcessor as CaseManagementCustomGroupPostProcessor;
use CRM_Civicase_Helper_CaseManagementCustomGroupPostProcess as CaseManagementCustomGroupPostProcessHelper;

/**
 * CaseManagementUtils class for case instance type.
 */
class CRM_Civicase_Service_CaseManagementUtils extends CRM_Civicase_Service_CaseCategoryInstanceUtils {

  /**
   * Returns the menu object for the default category instance.
   *
   * @return \CRM_Civicase_Service_CaseCategoryMenu
   *   Menu object.
   */
  public function getMenuObject() {
    return new CaseCategoryMenu();
  }

  /**
   * {@inheritDoc}
   */
  public function getCaseTypePostProcessor() {
    // TODO: Implement getCaseTypePostProcessor() method.
  }

  /**
   * {@inheritDoc}
   */
  public function getCustomGroupDisplayFormatter() {
    // TODO: Implement getCustomGroupDisplayFormatter() method.
  }

  /**
   * {@inheritDoc}
   */
  public function getCustomGroupPostProcessor() {
    return new CaseManagementCustomGroupPostProcessor();
  }

  /**
   * {@inheritDoc}
   */
  public function getPostProcessHelper() {
    return new CaseManagementCustomGroupPostProcessHelper();
  }

}
