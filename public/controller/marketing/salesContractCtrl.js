app.controller('marketingSalesContract', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle = $rootScope.solog.label.sales_contract.title;

    $scope.add = function() {
        $state.go('marketing.sales_contract.create')
    }
});

app.controller('marketingSalesContractCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add";
    $scope.store_url = baseUrl + "/marketing/sales_contract"
});

app.controller('marketingSalesContractShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail";
    $scope.state=$state;
    $scope.params=$stateParams;
});

app.controller('marketingSalesContractShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail";
});

app.controller('marketingSalesContractShowContract', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Create Contract";
});
