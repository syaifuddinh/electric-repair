customerOrders.controller('customerOrdersCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/setting/general/customer_orders/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, customerOrdersService) {
            
        }
    }
});