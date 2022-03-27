app.controller('inventoryPurchaseRequest', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = "Purchase Request";
});

app.controller('inventoryPurchaseRequestCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, purchaseRequestsService) {
    $rootScope.pageTitle = $rootScope.solog.label.general.add;
});

app.controller('inventoryPurchaseRequestShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.general.detail;
});
