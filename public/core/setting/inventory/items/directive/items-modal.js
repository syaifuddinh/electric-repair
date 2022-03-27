items.directive('itemsModal', function () {
    return {
        restrict: 'E',
        scope: {
            is_multiple_select : '=isMultipleSelect',
            is_pallet : '=isPallet',
            is_merchandise : '=isMerchandise',
            purchase_order_id : '=purchaseOrderId',
            itemMigrationId : '=itemMigrationId',
            salesOrderReturnId : '=salesOrderReturnId'
        },
        templateUrl: '/core/setting/inventory/items/view/items-modal.html',
        link: function (scope, el, attr, ngModel) {
        },
        controller: function ($scope, $http, $attrs, $rootScope, $timeout) {
            $scope.checklist = {}
            $scope.items = []
            $scope.formData = {}
            if($scope.is_pallet) {
                $scope.formData.is_pallet = 1
            }
            $scope.$on('showItemsModal', function(){
                $('#itemsModal').modal()
                $scope.searchData()
                $scope.checklist = {}
                $scope.items = []
            })


            var columnDefs = [
                {title : $rootScope.solog.label.item.code},
                {title : $rootScope.solog.label.item.name},
                {title : $rootScope.solog.label.general.unit},
                {title : $rootScope.solog.label.general.description}
            ]

            var columns = [{
                    data: null,
                    name: "code",
                    render: resp => "<div title='Choose Item' class='context-menu' ng-click='chooseItem(" + JSON.stringify({
                      name: resp.name,
                      code: resp.code,
                      id: resp.id,
                      barcode: resp.barcode,
                      harga_beli: resp.harga_beli,
                      harga_jual: resp.harga_jual,
                      long: resp.long,
                      wide: resp.wide,
                      height: resp.height,
                      tonase: resp.tonase
                    }) + ")'>" + resp.code + "</div>"
                  },
                  {
                    data: null,
                    name: "name",
                    render: resp => "<div title='Choose Item' class='context-menu' ng-click='chooseItem(" + JSON.stringify({
                      name: resp.name,
                      harga_beli: resp.harga_beli,
                      harga_jual: resp.harga_jual,
                      code: resp.code,
                      id: resp.id,
                      barcode: resp.barcode,
                      long: resp.long,
                      wide: resp.wide,
                      height: resp.height,
                      tonase: resp.tonase
                    }) + ")'>" + resp.name + "</div>"
                  },
                  {
                    data: null,
                    name: "piece.name",
                    render: resp => "<div title='Choose Item' class='context-menu' ng-click='chooseItem(" + JSON.stringify({
                      name: resp.name,
                      harga_beli: resp.harga_beli,
                      harga_jual: resp.harga_jual,
                      code: resp.code,
                      id: resp.id,
                      barcode: resp.barcode,
                      long: resp.long,
                      wide: resp.wide,
                      height: resp.height,
                      tonase: resp.tonase
                    }) + ")'>" + (resp.piece ? resp.piece.name : '') + "</div>"
                  },
                  {
                    data: null,
                    name: "description",
                    render: resp => "<div title='Choose Item' class='context-menu' ng-click='chooseItem(" + JSON.stringify({
                      name: resp.name,
                      code: resp.code,
                      harga_beli: resp.harga_beli,
                      harga_jual: resp.harga_jual,
                      id: resp.id,
                      barcode: resp.barcode,
                      long: resp.long,
                      wide: resp.wide,
                      height: resp.height,
                      tonase: resp.tonase
                    }) + ")'>" + (resp.description || '') + "</div>"
                  },
                ]

            if($scope.is_multiple_select) {
                columnDefs.splice(0, 0, {
                    title : ''
                })
                columns.splice(0, 0, {
                    data : null,
                    searchable : false,
                    orderable : false,
                    className : 'text-center',
                    render : function(resp) {
                        var jsn = {
                          name: resp.name,
                          code: resp.code,
                          id: resp.id,
                          barcode: resp.barcode,
                          harga_beli: resp.harga_beli,
                          harga_jual: resp.harga_jual,
                          long: resp.long,
                          wide: resp.wide,
                          height: resp.height,
                          tonase: resp.tonase
                        }
                        if(!$scope.checklist[resp.id]) {
                            $scope.checklist[resp.id] = 0
                        }

                        var r = '<input type="checkbox" ng-change=\'switchItem(' + resp.id + ', ' + JSON.stringify(jsn) + ')\' ng-model="checklist[' + resp.id + ']" ng-true-value="1" ng-false-value="0">' 
                        return r
                    }
                })
            }

            var options = {
                ajax: {
                  headers: {
                    'Authorization': 'Bearer ' + authUser.api_token
                  },
                  url: baseUrl + '/api/operational_warehouse/general_item_datatable',
                  data : e => Object.assign(e, $scope.formData)
                },
                columnDefs : columnDefs,
                columns: columns,
                createdRow: function (row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
              } 


              $scope.options = options

              $scope.searchData = function() {
                    if($scope.is_merchandise) {
                        $scope.formData.is_merchandise = $scope.is_merchandise
                    }
                    $scope.formData.purchase_order_id = $scope.purchase_order_id
                    $scope.formData.sales_order_return_id = $scope.salesOrderReturnId
                    $scope.formData.item_migration_id = $scope.itemMigrationId
                    $scope.options.datatable.ajax.reload()
              }
              $timeout(() => {

                  $scope.searchData()
              }, 300)

              $scope.$watch('purchase_order_id', function(){
                    $scope.searchData()
              })

              $scope.switchItem = function(id, jsn) {
                if($scope.checklist[id]) {
                    exist = $scope.items.findIndex(x => x.id == id)
                    if(exist == -1) {
                        $scope.items.push(jsn)
                    }
                } else {
                    exist = $scope.items.findIndex(x => x.id == id)
                    if(exist > -1) {
                        $scope.items = $scope.items.filter(x => x.id != id)
                    }

                }
              }

              $scope.chooseItem = function(jsn) {
                  $scope.$emit('getItem', jsn)
                  $('#itemsModal').modal('hide')
              }

              $scope.chooseItems = function() {
                  $scope.$emit('getItems', $scope.items)
                  $('#itemsModal').modal('hide')
              }
        }
    }
});