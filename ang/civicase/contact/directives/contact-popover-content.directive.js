(function (angular) {
  var module = angular.module('civicase');

  module.directive('civicaseContactPopoverContent', function () {
    return {
      controller: 'civicaseContactPopoverContentController',
      templateUrl: '~/civicase/contact/directives/contact-popover-content.directive.html',
      scope: {
        caseId: '<?',
        contactId: '<'
      }
    };
  });

  module.controller('civicaseContactPopoverContentController', function ($scope, ContactsCache) {
    $scope.contact = ContactsCache.getCachedContact($scope.contactId);
  });
})(angular);
