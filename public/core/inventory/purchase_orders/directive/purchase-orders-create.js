purchaseOrders.directive('purchaseOrdersCreate', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            company_id : '=companyId',
            is_pallet : '=isPallet',
            is_merchandise : '=isMerchandise',
            index_route : '=indexRoute',
            tableOnCreated : '='
        },
        require:'ngModel',
        templateUrl: '/core/inventory/purchase_orders/view/purchase-orders-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, purchaseOrdersService, salesOrdersService) {
            $scope.formData = {}
            $scope.formData.detail = []
            $compile($('thead'))($scope)

            $scope.showDetail = function() {
                purchaseOrdersService.api.showDetail($stateParams.id, function(dt){
                    $scope.formData.detail = dt
                })
            }

            $scope.show = function() {
                if($stateParams.id) {
                    purchaseOrdersService.api.show($stateParams.id, function(dt){
                        dt.supplier_id = parseInt(dt.supplier_id)
                        $scope.formData = dt
                        $scope.formData.po_date =$filter('minDate')(dt.po_date)
                        $scope.showDetail()
                    })
                }
            }
            $scope.show()

            $scope.backward = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        $state.go('inventory.purchase_order')
                    }
                }
            }

            $scope.showItems = function() {
                $scope.$broadcast('showItemsModal', 0)
            }

            $scope.addItem = function(jsn) {
                var id = Math.round(Math.random() * 9999999999)
                var params = {}
                params.id = id
                params.item_name = jsn.name
                params.item_id = jsn.id
                params.price = jsn.harga_beli
                params.qty = 1
                $scope.formData.detail.push(params)
            }

            $scope.$on('getItem', function(e, v){
                $scope.addItem(v)
            })
            $scope.$on('getItems', function(e, items){
                for(i in items) {
                    $scope.addItem(items[i])
                }
            })

            $scope.delete = function(id) {
                $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id)
            }

            var sales_order_datatable = $('#sales_order_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                initComplete: null,
                ajax: {
                  headers: {
                    'Authorization': 'Bearer ' + authUser.api_token
                  },
                  url: baseUrl + '/api/sales/sales_order_datatable',
                  data: function(d) {
                    d.is_invoiced = false;
                  },
                  dataSrc: function(d) {
                    return d.data;
                  }
                },
                columns: [
                  {
                    data:null,
                    searchable:false,
                    orderable:false,
                    className : 'text-center',
                    render : function(resp) {
                        return '<button class="btn btn-sm btn-primary" ng-disabled="isLoading" ng-click="selectSalesOrder($event.currentTarget)">Pilih</button>'
                    }
                  },
                  { data: "code", name: "code" },
                  { data: null, name: 'shipment_date', render: e => $filter('fullDate')(e.shipment_date) },
                  { data: "customer_name", name: "customer_name", }
                ],
                columnDefs: [{
                  targets: 0,
                  width: '5px'
                }],
                createdRow: function(row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
            });

            $scope.openSalesOrder = function() {
                sales_order_datatable.ajax.reload()
                $('#modalSO').modal('show')
            }

            $scope.selectSalesOrder = function(e) {
                var tr = $(e).parents('tr')
                var dt = sales_order_datatable.row(tr).data()
                $scope.formData.sales_order_id = dt.id
                $scope.isLoading=true

                if($scope.formData.sales_order_id){
                    salesOrdersService.api.showDetail($scope.formData.sales_order_id, function(resp){
                        $scope.formData.detail = []

                        angular.forEach(resp, function(jsn){
                            console.log('jsn',jsn)
                            var id = Math.round(Math.random() * 9999999999)
                            var params = {}
                            params.id = id
                            params.item_name = jsn.item_name
                            params.item_id = jsn.item_id
                            params.price = jsn.price
                            params.qty = jsn.qty
                            $scope.formData.detail.push(params)
                        })
                        $('#modalSO').modal('hide')
                        $scope.isLoading=false
                    });
                } else {
                    $('#modalSO').modal('hide')
                    $scope.isLoading=false
                }
            }

            $scope.submitForm = function() {
                if($stateParams.id) {

                    purchaseOrdersService.api.update($scope.formData, $stateParams.id, function(){
                        $scope.backward()                        
                    })
                } else {

                    purchaseOrdersService.api.store($scope.formData, function(){
                        $scope.backward()                        
                    })
                }
            }
        }
    }
});