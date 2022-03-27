additionalFields.controller('additionalFields', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, additionalFieldsService) {
    $rootScope.pageTitle="Additional Fields";
    $scope.formData = {}

    var options = {
        order: [],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : additionalFieldsService.url.datatable()
        },
        columnDefs : [
            {title : 'Name'},
            {title : 'Feature'},
            {title : 'Field Type'},
            {title : ''}
        ],
        columns:[
            {data:"name",name:"additional_fields.name"},
            {data:"type_transaction_name",name:"type_transactions.name"},
            {data:"field_type_name",name:"field_types.name"},
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
        $scope.modalTitle = $rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.additional_fields.title
        $scope.submitForm = $scope.store
        $('#modal').modal()
    }

    $scope.edit = function(id) {
        $scope.modalTitle = $rootScope.solog.label.general.edit + ' ' + $rootScope.solog.label.additional_fields.title
        $scope.id = id
        additionalFieldsService.api.show(id, function(dt){
            $scope.formData = dt
        })
        $scope.submitForm = $scope.update
        $('#modal').modal()
    }

    $scope.delete = function(id) {
        var is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            additionalFieldsService.api.destroy(id, function() {
                $scope.options.datatable.ajax.reload()
            })
        }
    }


    $scope.store = function() {
        $scope.$broadcast('getFormData', 1)
    }

    $scope.store = function() {
        $rootScope.disBtn = true
        additionalFieldsService.api.store($scope.formData, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }

    $scope.update = function() {
        $rootScope.disBtn = true
        var id = $scope.id
        additionalFieldsService.api.update($scope.formData, id, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }
});