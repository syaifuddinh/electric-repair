gateInContainers.directive('gateInContainersCreate', function () {
    return {
        restrict: 'E',
        scope: {
            is_pallet : '=isPallet',
            is_container_part : '=isContainerPart',
            is_container_yard : '=isContainerYard'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/depo/gate_in_container/view/gate-in-container-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, gateInContainersService) {
            $scope.formData = {}

            var options = {
                order: [[2, 'desc'], [1, 'desc']],
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : gateInContainersService.url.datatable(),
                    data : r => Object.assign(r, $scope.formData)
                },
                columnDefs : [
                    {title : $rootScope.solog.label.general.branch},
                    {title : $rootScope.solog.label.general.code},
                    {title : $rootScope.solog.label.general.date},
                    {title : $rootScope.solog.label.container.code},
                    {title : $rootScope.solog.label.general.owner},
                    {title : $rootScope.solog.label.general.status},
                    {title : ''}
                ],
                columns:[
                    {data:"company_name",name:"companies.name"},
                    {data:"code",name:"gate_in_containers.code"},
                    {
                        data:null,
                        name:"gate_in_containers.date",
                        searchable:false,
                        render : resp => $filter('fullDate')(resp.date)
                    },
                    {data:"no_container",name:"gate_in_containers.no_container"},
                    {data:"owner_name",name:"contacts.name"},
                    {data:"status_name",name:"gate_in_container_statuses.name"},
                    {
                        data:null,
                        searchable:false,
                        orderable:false,
                        className:"text-center",
                        render : function(resp) {
                            var r = ''; 
                            r += '<a ui-sref="depo.gate_in_container.show({id:' + resp.id + '})"><i class="fa fa-folder-o"></i></a>'
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
                $state.go('depo.gate_in_container.create')
            }

            $scope.edit = function(id) {
                $state.go('depo.gate_in_container.edit', {id:id})
            }

            $scope.delete = function(id) {
                var is_confirm = confirm('Are you sure ?')
                if(is_confirm) {
                    gateInContainersService.api.destroy(id, function() {
                        $scope.options.datatable.ajax.reload()
                    })
                }
            }


            $scope.store = function() {
                $scope.$broadcast('getFormData', 1)
            }

            $scope.store = function() {
                $rootScope.disBtn = true
                gateInContainersService.api.store($scope.formData, function() {
                    $('#modal').modal('hide')
                    $scope.options.datatable.ajax.reload()
                })
            }

            $scope.update = function() {
                $rootScope.disBtn = true
                var id = $scope.id
                gateInContainersService.api.update($scope.formData, id, function() {
                    $('#modal').modal('hide')
                    $scope.options.datatable.ajax.reload()
                })
            }
        }
    }
});