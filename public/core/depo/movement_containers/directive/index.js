movementContainers.controller('movementContainers', function ($scope, $http, $rootScope, $filter, $state, $stateParams, $timeout, $compile, movementContainersService) {    
    $rootScope.pageTitle = $rootScope.solog.label.general.movement_container;
    $scope.formData = {}

    var options = {
        order: [[2, 'desc'], [1, 'desc']],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : movementContainersService.url.datatable(),
            data : r => Object.assign(r, $scope.formData)
        },
        columnDefs : [
            {title : $rootScope.solog.label.general.branch},
            {title : $rootScope.solog.label.general.code},
            {title : $rootScope.solog.label.general.date},
            {title : $rootScope.solog.label.general.operator},
            {title : $rootScope.solog.label.general.status},
            {title : ''}
        ],
        columns:[
            {data:"company_name",name:"companies.name"},
            {data:"code",name:"movement_containers.code"},
            {
                data:null,
                name:"movement_containers.date",
                searchable:false,
                render : resp => $filter('fullDate')(resp.date)
            },
            {data:"operator_name",name:"contacts.name"},
            {data:"status_name",name:"movement_container_statuses.name"},
            {
                data:null,
                searchable:false,
                orderable:false,
                className:"text-center",
                render : function(resp) {
                    var r = ''; 
                    r += '<a ui-sref="depo.movement_container.show({id:' + resp.id + '})"><i class="fa fa-folder-o"></i></a>'
                    r += '&nbsp;&nbsp;<a ng-click="edit(' + resp.id + ')"><i class="fa fa-edit"></i></a>'
                    r += '&nbsp;&nbsp;<a ng-click="delete(' + resp.id + ')"><i class="fa fa-trash-o"></i></a>'

                    return r
                }
            },
        ]
    }
    $scope.options = options

    $scope.searchData = function() {
        $scope.options.datatable.ajax.reload()
    }

    $scope.resetFilter = function() {
        $scope.formData = {}
        $scope.searchData()
    }

    $scope.add = function() {
        $state.go('depo.movement_container.create')
    }

    $scope.edit = function(id) {
        $state.go('depo.movement_container.edit', {id:id})
    }

    $scope.delete = function(id) {
        var is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            movementContainersService.api.destroy(id, function() {
                $scope.options.datatable.ajax.reload()
            })
        }
    }


    $scope.store = function() {
        $scope.$broadcast('getFormData', 1)
    }

    $scope.store = function() {
        $rootScope.disBtn = true
        movementContainersService.api.store($scope.formData, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }

    $scope.update = function() {
        $rootScope.disBtn = true
        var id = $scope.id
        movementContainersService.api.update($scope.formData, id, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }
});