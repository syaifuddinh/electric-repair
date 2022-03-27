app.controller('opWarehouseMutasiTransfer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle = $rootScope.solog.label.transfer_mutation.title;    
    $rootScope.emptyBuffer()
});

app.controller('opWarehouseMutasiTransferCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.general.add;    
});

app.controller('opWarehouseMutasiTransferCreateReceipt', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.general.add;    
    $scope.id = $stateParams.id
    if($stateParams.id) {
        $scope.storeUrl = baseUrl + '/operational_warehouse/mutasi_transfer/' + $stateParams.id + '/receipt'
    }
});

app.controller('opWarehouseMutasiTransferShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Migration";
    $scope.id = $stateParams.id;

    $scope.openInfo = function() {
          $('.tab-item').hide()
          $('#info_detail').show()
    }
    $scope.openInfo()

    $scope.openStocklist = function() {
          $('.tab-item').hide()
          $('#stocklist_detail').show()
    }

    $scope.openReceipt = function() {
          $('.tab-item').hide()
          $('#receipt_detail').show()
    }
})
