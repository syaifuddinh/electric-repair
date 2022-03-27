vendorPrices.controller('vendorPrices', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, vendorPricesService) {
    $rootScope.pageTitle = $rootScope.solog.label.contract.title;
    $scope.formData = {}
    var options = {
        order: [],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : vendorPricesService.url.datatable()
        },
        columnDefs : [
            {title : 'Name'},
            {title : 'Feature'},
            {title : 'Field Type'},
        ],
        columns:[
            {data:"name",name:"vendorPrices.name"},
            {data:"type_transaction_name",name:"type_transactions.name"},
            {data:"field_type_name",name:"field_types.name"},
        ]
    }
    $scope.options = options

    $scope.add = function() {
        $scope.modalTitle = $rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.vendor_prices.title
        $('#modal').modal()
    }

    $scope.edit = function() {
        $scope.modalTitle = $rootScope.solog.label.general.edit + ' ' + $rootScope.solog.label.vendor_prices.title
        $('#modal').modal()
    }


    $scope.store = function() {
        $scope.$broadcast('getFormData', 1)
    }

    $scope.submitForm = function() {
        $rootScope.disBtn = true
        vendorPricesService.api.store($scope.formData, function() {
            $('#modal').modal('hide')
        })
    }
});