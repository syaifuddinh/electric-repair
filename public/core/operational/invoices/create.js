invoices.directive('invoicesCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/setting/general/invoices/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $filter, $state, $stateParams, $timeout, invoicesService) {
            
        }
    }
});