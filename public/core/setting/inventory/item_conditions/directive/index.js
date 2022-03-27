itemConditions.controller('itemConditions', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, itemConditionsService, typeTransactionsService) {
    $rootScope.pageTitle="Conditions";
    $scope.formData = {}

    var options = {
        order: [],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : itemConditionsService.url.datatable()
        },
        columnDefs : [
            {title : 'Name'},
            {title : 'Description'},
            {title : ''}
        ],
        columns:[
            {data:"name",name:"item_conditions.name"},
            {data:"description",name:"item_conditions.description"},
            {
                data:null,
                searchable:false,
                orderable:false,
                className:"text-center",
                render : function(resp) {
                    var r = ''; 
                    r += '<a ng-click="edit(' + resp.id + ')"><i class="fa fa-edit"></i></a>'
                    r += '&nbsp;&nbsp;<a ng-click="delete(' + resp.id + ')"><i class="fa fa-trash-o"></i></a>'

                    return r
                }
            },
        ]
    }
    $scope.options = options

    $scope.add = function() {
        $state.go('inventory.item_condition.create')
    }

    $scope.edit = function(id) {
        $state.go('inventory.item_condition.edit', {id:id})
    }

    $scope.delete = function(id) {
        var is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            itemConditionsService.api.destroy(id, function() {
                $scope.options.datatable.ajax.reload()
            })
        }
    }


    $scope.store = function() {
        $scope.$broadcast('getFormData', 1)
    }

    $scope.store = function() {
        $rootScope.disBtn = true
        itemConditionsService.api.store($scope.formData, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }

    $scope.update = function() {
        $rootScope.disBtn = true
        var id = $scope.id
        itemConditionsService.api.update($scope.formData, id, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }
});