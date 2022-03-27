jobOrders.directive('jobOrdersCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/setting/general/job_orders/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $filter, $state, $stateParams, $timeout, jobOrdersService) {
            
        }
    }
});