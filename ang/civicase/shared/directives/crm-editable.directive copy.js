(function (angular, $, _, CRM) {
  var module = angular.module('civicase');

  // Angular binding for CiviCRM's jQuery-based crm-editable
  module.directive('civicaseInlineDatepicker', function ($timeout) {
    return {
      restrict: 'A',
      link: civicaseInlineDatepickerLink,
      scope: {
        model: '=',
        avalue: '='
      }
    };

    /**
     * Link function for crmEditable directive
     *
     * @param {object} scope scope of the directive
     * @param {object} elem element
     * @param {object} attrs attributes
     */
    function civicaseInlineDatepickerLink (scope, elem, attrs) {
      elem.datepicker();
    }
  });
})(angular, CRM.$, CRM._, CRM);
