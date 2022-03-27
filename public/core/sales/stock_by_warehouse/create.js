stockByWarehouseSales.controller('stockByWarehouseSalesCreate', function () {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/setting/general/sales_orders/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, stockByWarehouseSalesService) {
        }
    }
});