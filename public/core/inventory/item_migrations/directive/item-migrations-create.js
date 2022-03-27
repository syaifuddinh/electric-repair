itemMigrations.directive('itemMigrationsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            'isPallet' : '=isPallet',
            'index_route' : '=indexRoute'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/inventory/item_migrations/view/item-migrations-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, itemMigrationsService) {
            $rootScope.disBtn = false
            
            $scope.formData={}
            $scope.formData.date_transaction=dateNow
            $scope.formData.detail=[]
            $scope.detailData={}
            $scope.detailData.qty=1
            $scope.detailData.stock=0

            $scope.showDetail = function() {
                if($stateParams.id) {
                    itemMigrationsService.api.showDetail($stateParams.id, function(dt){
                        dt = dt.map(function(v){
                            v.code = v.item_code
                            v.name = v.item_name

                            return v
                        })
                        $scope.formData.detail = dt
                    })
                }
            }

            $scope.show = function() {
                if($stateParams.id) {
                    itemMigrationsService.api.show($stateParams.id, function(dt){
                        dt.date_transaction = $filter('minDate')(dt.date_transaction)
                        $scope.formData = dt
                        $scope.showDetail()
                    })
                }
            }
            $scope.show()

            $scope.deletes = function(id) {
                $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id) 
            }

            $scope.appendItemWarehouse = function(v) {
                $scope.detailData = {}
                $scope.detailData.id = Math.round(Math.random() * 9999999)
                $scope.detailData.code = v.code
                $scope.detailData.item_id = v.id
                $scope.detailData.name = v.name
                $scope.detailData.rack_id = v.rack_id
                $scope.detailData.qty = v.qty
                $scope.detailData.warehouse_receipt_id = v.warehouse_receipt_id
                $scope.detailData.warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
                $scope.detailData.warehouse_receipt_code = v.warehouse_receipt_code
                $scope.detailData.rack_code = v.rack_code
                $scope.formData.detail.push($scope.detailData)
            }

            $scope.$on('getItemWarehouse', function(e, v){
                $scope.appendItemWarehouse(v)
            })

            $scope.$on('getItemWarehouses', function(e, items){
                var i
                for(i in items) {
                  $scope.appendItemWarehouse(items[i])
                }
            })

            $scope.back = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        $state.go('operational_warehouse.mutasi_transfer')
                    }
                }
            }

            $scope.submitForm=function() {
                if($stateParams.id) {
                    itemMigrationsService.api.update($scope.formData, $stateParams.id, function(){
                        $scope.back()
                    })
                } else {
                    itemMigrationsService.api.store($scope.formData, function(){
                        $scope.back()
                    })            
                }
            }
        }
    }
});