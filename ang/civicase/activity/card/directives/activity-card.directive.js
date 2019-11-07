(function (angular, $, _) {
  var module = angular.module('civicase');

  module.directive('caseActivityCard', function () {
    return {
      restrict: 'A',
      templateUrl: function (elem, attrs) {
        switch (attrs.mode) {
          case 'big':
            return '~/civicase/activity/card/directives/activity-card-big.directive.html';
          case 'long':
            return '~/civicase/activity/card/directives/activity-card-long.directive.html';
          default:
            return '~/civicase/activity/card/directives/activity-card-short.directive.html';
        }
      },
      controller: caseActivityCardController,
      link: caseActivityCardLink,
      replace: true,
      scope: {
        activity: '=caseActivityCard',
        case: '=?',
        customDropdownClass: '@',
        refresh: '=refreshCallback',
        refreshOnCheckboxToggle: '=?',
        bulkAllowed: '=',
        type: '=type',
        customClickEvent: '='
      }
    };

    /**
     * Link function for caseActivityCard
     *
     * @param {object} scope scope object of the controller
     */
    function caseActivityCardLink (scope) {
      scope.bootstrapThemeElement = $('#bootstrap-theme');
    }
  });

  module.controller('caseActivityCardController', caseActivityCardController);

  /**
   *
   * @param {object} $scope scope object of the controller
   * @param {object} dialogService service to open the dialog box
   * @param {Function} crmApi service to interact with the civicrm api
   * @param {object} crmBlocker crm blocker service
   * @param {object} crmStatus crm status service
   * @param {object} DateHelper date helper service
   * @param {object} ts ts service
   * @param {Function} viewInPopup factory to view an activity in a popup
   */
  function caseActivityCardController ($scope, dialogService, crmApi, crmBlocker, crmStatus, DateHelper, ts, viewInPopup) {
    $scope.ts = ts;
    $scope.formatDate = DateHelper.formatDate;

    /**
     * Mark an activity as complete
     *
     * @param {object} activity activity object
     * @returns {Promise} api call promise
     */
    $scope.markCompleted = function (activity) {
      return crmApi([['Activity', 'create', { id: activity.id, status_id: activity.is_completed ? 'Scheduled' : 'Completed' }]])
        .then(function (data) {
          if (!data[0].is_error) {
            activity.is_completed = !activity.is_completed;
            $scope.refreshOnCheckboxToggle && $scope.refresh();
          }
        });
    };

    /**
     * Toggle an activity as favourite
     *
     * @param {object} $event event object
     * @param {object} activity activity object
     */
    $scope.toggleActivityStar = function ($event, activity) {
      $event.stopPropagation();
      activity.is_star = activity.is_star === '1' ? '0' : '1';
      // Setvalue api avoids messy revisioning issues
      $scope.refresh([['Activity', 'setvalue', {
        id: activity.id,
        field: 'is_star',
        value: activity.is_star
      }]], true);
    };

    /**
     * Click handler for Activity Card
     *
     * @param {object} $event event object
     * @param {object} activity activity object
     */
    $scope.viewActivityDetails = function ($event, activity) {
      if ($scope.customClickEvent) {
        $scope.$emit('civicaseAcitivityClicked', $event, activity);
      } else {
        $scope.viewInPopup($event, activity);
      }
    };

    /**
     * View the sent activity details in the popup
     *
     * @param {object} $event event object
     * @param {object} activity activity object
     */
    $scope.viewInPopup = function ($event, activity) {
      viewInPopup($event, activity)
        .on('crmFormSuccess', function () {
          $scope.refresh();
        });
    };

    /**
     * Gets attachments for an activity
     *
     * @param {object} activity activity object
     */
    $scope.getAttachments = function (activity) {
      if (!activity.attachments) {
        activity.attachments = [];
        CRM.api3('Attachment', 'get', {
          entity_table: 'civicrm_activity',
          entity_id: activity.id,
          sequential: 1
        }).done(function (data) {
          activity.attachments = data.values;
          $scope.$digest();
        });
      }

      /**
       * Deletes file of an activity
       *
       * @param {object} activity activity object
       * @param {object} file file object
       * @returns {Promise} promise
       */
      $scope.deleteFile = function (activity, file) {
        var promise = crmApi('Attachment', 'delete', { id: file.id })
          .then(function () {
            $scope.refresh();
          });

        return crmBlocker(crmStatus({
          start: $scope.ts('Deleting...'),
          success: $scope.ts('Deleted')
        }, promise));
      };
    };
  }
})(angular, CRM.$, CRM._);
