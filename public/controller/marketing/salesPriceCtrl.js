app.controller('marketingSalesPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_price.title;
    $scope.formData = {}
    $scope.searchData = () => {
        $scope.$broadcast("reloadItem", $scope.formData)
    }

    $scope.resetData = () => {
        $scope.formData = {}
        $scope.$broadcast("resetItem", 0)
    }
});

app.controller('marketingSalesPriceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Sales Price";
});


app.controller('marketingSalesPriceShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail";
});
