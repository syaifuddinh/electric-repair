warehouses.directive('warehousesCreate', function(){
    return {
        restrict: 'E',
        scope : {
            id : "=id",
            company_id : '=companyId',
            indexRoute : '=indexRoute',
            indexParams : '=indexParams'
        },
        templateUrl : '/core/setting/inventory/warehouses/view/warehouses-create.html',
        controller : function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, warehousesService, $filter) {
            $scope.updateMaps = function(lat,lng) {
                var latLng = {}
                latLng.lat = lat
                latLng.lng = lng
                $scope.$broadcast('updateLatLng', latLng)
            }

            $scope.show = function() {
                if($scope.id) {
                    warehousesService.api.show($scope.id, function(dt){
                        $scope.formData = dt
                        $scope.updateMaps(dt.latitude,dt.longitude)
                    })
                } else {
                    $scope.formData = {}
                    $scope.formData.latitude = -7.331438432711705
                    $scope.formData.longitude = 112.76870854695639
                    $scope.updateMaps($scope.formData.latitude, $scope.formData.longitude)
                }
            }


            $scope.$watch('id', function(){
                $scope.show()
            })

            
            $scope.$watch('company_id', function(){
                if($scope.company_id) {
                    $timeout(function(){
                        $scope.formData.company_id = parseInt($scope.company_id)
                    }, 600)
                }
            })

            $scope.$on('getLatLng', function(e, v){
                $scope.formData.latitude = v.lat
                $scope.formData.longitude = v.lng
                $scope.$apply();
            })

            $scope.$on('getAddress', function(e, v){
                var addr = v.address.road + ', ' +v.address.village + ', '
                addr += v.address.county ?? v.address.city ?? null

                if(!v.address.road){
                    addr = v.display_name
                }

                $scope.formData.address = addr
                $scope.$apply();
            })

            $scope.back = function() {
                if($scope.indexRoute) {
                    $state.go($scope.indexRoute, $scope.indexParams)
                } else {
                    $state.go('operational_warehouse.setting.warehouse')
                }
            }

            $rootScope.disBtn = false
            $scope.submitForm = function() {
                $rootScope.disBtn = true
                if($scope.id) {

                    warehousesService.api.update($scope.formData, $scope.id, function() {
                        $scope.back()
                    })
                } else {
                    warehousesService.api.store($scope.formData, function() {
                        $scope.back()
                    })
                }
            }
        }
    }
});