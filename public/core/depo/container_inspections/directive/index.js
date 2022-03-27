containerInspections.controller('containerInspections', function ($scope, $http, $rootScope, $filter, $state, $stateParams, $timeout, $compile, containerInspectionsService) {    
    $rootScope.pageTitle = $rootScope.solog.label.general.container_inspection;
    $scope.formData = {}

    var options = {
        order: [],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : containerInspectionsService.url.datatable(),
            data : r => Object.assign(r, $scope.formData)
        },
        columnDefs : [
            {title : $rootScope.solog.label.general.date},
            {title : $rootScope.solog.label.general.checker},
            {title : $rootScope.solog.label.general.description},
            {title : ''}
        ],
        columns:[
            {
                data:null,
                name:"container_inspections.date",
                searchable:false,
                render : resp => $filter('fullDate')(resp.date)
            },
            {data:"checker_name",name:"contacts.name"},
            {data:"description",name:"container_inspections.description"},
            {
                data:null,
                searchable:false,
                orderable:false,
                className:"text-center",
                render : function(resp) {
                    var r = ''; 
                    r += '<a ui-sref="depo.container_inspection.show({id:' + resp.id + '})"><i class="fa fa-folder-o"></i></a>'
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

    $scope.add = function() {
        $state.go('depo.container_inspection.create')
    }

    $scope.edit = function(id) {
        $state.go('depo.container_inspection.edit', {id:id})
    }

    $scope.delete = function(id) {
        var is_confirm = confirm('Are you sure ?')
        if(is_confirm) {
            containerInspectionsService.api.destroy(id, function() {
                $scope.options.datatable.ajax.reload()
            })
        }
    }


    $scope.store = function() {
        $scope.$broadcast('getFormData', 1)
    }

    $scope.store = function() {
        $rootScope.disBtn = true
        containerInspectionsService.api.store($scope.formData, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }

    $scope.update = function() {
        $rootScope.disBtn = true
        var id = $scope.id
        containerInspectionsService.api.update($scope.formData, id, function() {
            $('#modal').modal('hide')
            $scope.options.datatable.ajax.reload()
        })
    }
});