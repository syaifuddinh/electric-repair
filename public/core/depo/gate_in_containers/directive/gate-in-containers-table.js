gateInContainers.directive('gateInContainersTable', function () {
    return {
        restrict: 'E',
        scope: {
            hide_add : '=hideAdd',
            hide_action : '=hideAction',
            hide_status : '=hideStatus',
            input_mode : '=inputMode',
            is_pallet : '=isPallet',
            is_container_part : '=isContainerPart',
            is_container_yard : '=isContainerYard'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/depo/gate_in_containers/view/gate-in-containers-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, gateInContainersService) {
            $scope.formData = {}

            var columnDefs = [
                {title : $rootScope.solog.label.general.branch},
                {title : $rootScope.solog.label.general.code},
                {title : $rootScope.solog.label.general.date},
                {title : $rootScope.solog.label.container.code},
                {title : $rootScope.solog.label.general.owner}
            ]

            var columns = [
                {data:"company_name",name:"companies.name"},
                {data:"code",name:"gate_in_containers.code"},
                {
                    data:null,
                    name:"gate_in_containers.date",
                    searchable:false,
                    render : resp => $filter('fullDate')(resp.date)
                },
                {data:"no_container",name:"gate_in_containers.no_container"},
                {data:"owner_name",name:"contacts.name"}
            ]

            if(!$scope.hide_action) {
                columnDefs.push({title : ''})
                columns.push({
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
                })
            }

            if(!$scope.hide_status) {
                columnDefs.push({title : $rootScope.solog.label.general.status})
                columns.push({data:"status_name",name:"gate_in_container_statuses.name"})
            }

            var options = {
                order: [[2, 'desc'], [1, 'desc']],
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : gateInContainersService.url.datatable(),
                    data : r => Object.assign(r, $scope.formData)
                },
                columnDefs : columnDefs,
                columns:columns
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

            if($scope.input_mode) {
                $scope.createdRow = function(row, data) {
                    var first = $(row).find('td:first-child');
                    var second = $(row).find('td:nth-child(2)');
                    var a, b
                    a = $('<a ng-click="chooseItem(' + data.id + ')">' + first.text() + '</a>')
                    first.empty()
                    first.append(a)
                    b = $('<a ng-click="chooseItem(' + data.id + ')">' + second.text() + '</a>')
                    second.empty()
                    second.append(b)
                    $compile(angular.element(row).contents())($scope)
                }
            }

            $scope.chooseItem = function(id) {
                gateInContainersService.api.show(id, function(dt){
                    $scope.$emit('chooseGateInContainer', dt)
                })
            }
        }
    }
});