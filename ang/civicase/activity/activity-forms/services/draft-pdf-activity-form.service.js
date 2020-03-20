(function (_, angular, getCrmUrl) {
  var module = angular.module('civicase');

  module.service('DraftPdfActivityForm', DraftPdfActivityForm);

  /**
   * Draft PDF activity form service.
   *
   * @param {Function} checkIfDraftActivity the check if draft activity function.
   */
  function DraftPdfActivityForm (checkIfDraftActivity) {
    this.canHandleActivity = checkIfDraftPdfLetter;
    this.getActivityFormUrl = getActivityFormUrl;

    /**
     * @param {object} activity an activity object.
     * @returns {boolean} true when the activity status is draft and the type is a PDF letter.
     */
    function checkIfDraftPdfLetter (activity) {
      return checkIfDraftActivity(activity, ['Print PDF Letter']);
    }

    /**
     * @param {object} activity an activity object.
     * @param {object} [optionsWithoutDefaults={action: 'add'}]
     *   a list of options to display the form.
     * @returns {string} the form URL for activities that are PDF letter drafts.
     */
    function getActivityFormUrl (activity, optionsWithoutDefaults) {
      var options = _.defaults({}, optionsWithoutDefaults, { action: 'add' });

      return getCrmUrl('civicrm/activity/pdf/' + options.action, {
        action: options.action,
        caseid: activity.case_id,
        cid: activity.source_contact_id,
        context: 'standalone',
        draft_id: activity.id,
        reset: '1'
      });
    }
  }
})(CRM._, angular, CRM.url);
