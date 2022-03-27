warehouseMaps.directive('warehouseMapsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/setting/inventory/warehouse_maps/view/warehouse-maps-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $state, warehouseMapsService, warehousesService, racksService) {
            $scope.formData = {}
            $scope.rows = []
            $rootScope.disBtn = false

            $scope.showWarehouse = function() {
                warehousesService.api.show($scope.warehouse_id, function(dt){
                    $scope.formData.row = dt.row
                    $scope.formData.column = dt.column
                    $scope.formData.level = dt.level
                })
            }

            $scope.openRack = function(rack_id) {
                if(rack_id) {
                    $rootScope.insertBuffer()
                    $state.go('operational_warehouse.bin_location.show', {id:rack_id})
                }
            }

            $scope.show = function() {
                warehouseMapsService.api.indexMap($scope.warehouse_id, function(dt){
                    for(x in dt) {
                        for(y in dt[x].columns){                        
                            for(z in dt[x].columns[y].levels){
                                dt[x].columns[y].levels[z].origin_rack_id = dt[x].columns[y].levels[z].rack_id
                            }
                        }
                    }
                    $scope.rows = dt
                })
            }

            $scope.$watch('warehouse_id', function (){
                $scope.showWarehouse()
                $scope.show();
            })

            $scope.setMap = function(id, warehouse_map_id, x, y, z) {
                racksService.api.setMap(id, warehouse_map_id, function(){
                    $scope.refresh(x, y, z)
                }, function(){
                    $scope.rows[x].columns[y].levels[z].rack_id = $scope.rows[x].columns[y].levels[z].origin_rack_id

                })
            }

            $scope.refresh = function(a, b, c) {
                warehouseMapsService.api.indexMap($scope.warehouse_id, function(dt){
                    for(x in dt) {
                        for(y in dt[x].columns){                        
                            for(z in dt[x].columns[y].levels){
                                if(dt[x].columns[y].levels[z].rack_id == $scope.rows[a].columns[b].levels[c].rack_id) {
                                    $scope.rows[a].columns[b].levels[c] = dt[x].columns[y].levels[z]
                                }
                            }
                        }
                    }
                    
                })
            }

            $scope.submitForm = function() {
                warehouseMapsService.api.generateMap($scope.formData, $scope.warehouse_id, function(){
                    $scope.showWarehouse()
                    $scope.show()
                })
            }
        }
    }
});