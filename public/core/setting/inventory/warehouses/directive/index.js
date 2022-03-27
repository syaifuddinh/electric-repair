warehouses.directive('warehousesIndex', function(){
    return {
        restrict: 'E',
        scope : {
            companyId : '=companyId',
            editRoute : '=editRoute',
            editParams : '=editParams',
            addRoute : '=addRoute',
            addParams : '=addParams'
        },
        templateUrl : '/core/setting/inventory/warehouses/view/index.html',
        controller : function($scope, $attrs, $http, $rootScope,$state,$stateParams,$timeout,$compile, warehousesService, $filter) {
            $scope.filterData = {}
            $scope.filterData.company_id = $scope.companyId
            $scope.formData = {}

            var createdRow = function(row, data, dataIndex) {
                var col = $(row).find('td:first-child')
                var txt = col.text()
                var a = $('<a></a>')
                var id = data.id
                a.attr('ui-sref', 'operational_warehouse.setting.warehouse.show({id:' + id + '})')
                col.empty()
                a.append(txt)
                col.append(a)
                $compile(col)($scope)
            }

            $scope.createdRow = createdRow

            var options = {
                order: [],
                dom: 'Blfrtip',
                buttons: [{
                    extend: 'excel',
                    enabled: true,
                    action: newExportAction,
                    text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                    className: 'btn btn-default btn-sm pull-right m-l-sm ',
                    filename: 'Vehicle - List Kendaraan - ' + new Date,
                    sheetName: 'Data',
                    title: 'Vehicle - List Kendaraan',
                    exportOptions: {
                        rows: {
                            selected: true
                        }
                    },
                }],
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : warehousesService.url.datatable(),
                    data : a => Object.assign(a, $scope.filterData)
                },
                columnDefs : [
                    { title : $rootScope.solog.label.general.code },
                    { title : $rootScope.solog.label.general.name },
                    { title : $rootScope.solog.label.general.address },
                    { title : $rootScope.solog.label.general.branch },
                    { title : $rootScope.solog.label.general.volume_capacity },
                    { title : $rootScope.solog.label.general.weight_capacity },
                    { title : $rootScope.solog.label.general.status },
                    { title : ''},
                ],
                columns:[
                    {data:"code",name:"code"},
                    {data:"name",name:"name"},
                    {data:"address",name:"address"},
                    {data:"company.name",name:"company.name"},
                    {
                        data:null,
                        searchable:false,
                        name:"capacity_volume",
                        className:'text-right',
                        render : resp => $filter('number')(resp.capacity_volume)
                    },
                    {
                        data:null,
                        searchable:false,
                        name:"capacity_tonase",
                        className:'text-right',
                        render : resp => $filter('number')(resp.capacity_tonase)
                    },
                    {data:"is_active",name:"is_active",className:"text-center"},
                    {
                        data:null,
                        searchable:false,
                        orderable:false,
                        render : function(item) {
                            var jsn = JSON.stringify(item)
                            var r = '<a ng-show="$root.roleList.includes(\'inventory.setting.warehouse.edit\')" ng-click=\'edit(' + jsn + ')\'><i class="fa fa-edit"></i></a>'
                            return r
                        }
                    }
                ],
            }
            $scope.options = options
            

            $scope.add = function() {
                if($scope.addRoute) {
                    $state.go($scope.addRoute, $scope.addParams);
                } else {
                    $state.go('operational_warehouse.setting.warehouse.create')
                }
            }

            $scope.edit = function(jsn) {
                if($scope.editRoute) {
                    $scope.editParams.warehouse_id = jsn.id;
                    $state.go($scope.editRoute, $scope.editParams);
                } else {
                    $state.go('operational_warehouse.setting.warehouse.edit', {id : jsn.id})
                }
            }

        }
    }
});