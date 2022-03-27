app.controller('inventoryPurchaseOrder', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.purchase_order.title;
});

app.controller('inventoryPurchaseOrderCreate',function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, purchaseOrdersService){
    $rootScope.pageTitle = $rootScope.solog.label.general.add;
})

app.controller('inventoryPurchaseOrderShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Detail Purchase Order";
});
