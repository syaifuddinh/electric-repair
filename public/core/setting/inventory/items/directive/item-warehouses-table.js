items.directive('itemWarehousesTable', function () {
    return {
        restrict: 'E',
        scope: {
            customer_id : '=customerId',
            warehouse_id : '=warehouseId',
            quotationId : '=',
            isPallet : '=isPallet',
            is_multiple : '=isMultiple',
            is_merchandise : '=isMerchandise',
            show_sale_price : "=showSalePrice",
            showPurchaseOrderCode : '=showPurchaseOrderCode',
            receiptTypeCode : '=receiptTypeCode'
        },
        templateUrl: '/core/setting/inventory/items/view/item-warehouses-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, itemsService) {
            var column, columnDefs, options
            if(!$scope.formData) {
                $scope.formData = {}
            }
            if($scope.isPallet) {
                $scope.formData.is_pallet = $scope.isPallet
            }
            if($scope.quotationId) {
                $scope.formData.quotation_id = $scope.quotationId
            }

            $scope.checklist = {}
            $scope.items = []

            $scope.isFilter = false
            $scope.openFilter = function(){
                $scope.isFilter = !$scope.isFilter
            }
            $scope.resetFilter = function(){
                $scope.formData.warehouse_id = null
                $scope.searchData()
            }

            $scope.setColumn = function() {
                columnDefs = [
                    {title : $rootScope.solog.label.general.code},
                    {title : $rootScope.solog.label.item.name},
                    {title : $rootScope.solog.label.general.unit},
                    {title : $rootScope.solog.label.warehouse_receipt.code},
                    {title : $rootScope.solog.label.general.warehouse},
                    {title : $rootScope.solog.label.general.rack},
                    {title : $rootScope.solog.label.general.stock}
                ]

                columns = [
                    {
                        data:null,
                        name:"code",
                        render: function(jsn) {
                            var resp = jsn
                            jsn.action_choose = null
                            jsn.action = null
                            var jsn = JSON.stringify(jsn)
                            var resp = '<a ng-click=\'choosePallet(' + jsn + ')\'>' + resp.code + '</a>'

                            return resp
                        }
                    },
                    {
                        data:null,
                        name:"name",
                        render: function(jsn) {
                            var resp = jsn
                            jsn.action_choose = null
                            jsn.action = null
                            var jsn = JSON.stringify(jsn)
                            var resp = '<a ng-click=\'choosePallet(' + jsn + ')\'>' + resp.name + '</a>'

                            return resp
                        }
                    },
                    {data:"piece_name",name:"pieces.name"},
                    {data:"warehouse_receipt_code",name:"warehouse_receipts.code"},
                    {data:"warehouse_name",name:"warehouses.name"},
                    {data:"rack_code",name:"racks.code"},
                    {data:"qty",searchable:false, orderable:false, className : 'text-right'}
                ]

                if($scope.show_sale_price) {
                    columnDefs.splice(2, 0, {
                        title : $rootScope.solog.label.general.sale_price
                    })

                    columns.splice(2, 0, {
                        data : null,
                        name : "items.harga_jual",
                        searchable : false,
                        className : 'text-right',
                        render : (r) => $filter('number')(r.harga_jual)
                    })
                }

            }
            $scope.setColumn()

            $scope.setMultiple = function() {
                if($scope.is_multiple) {
                    columnDefs.splice(0, 0, {
                        title : ''
                    })
                    columns.splice(0, 0, {
                            data:null,
                            searchable:false,
                            orderable:false,
                            className : 'text-center',
                            render: function(jsn) {
                                var arg = jsn
                                if(!$scope.checklist[arg.warehouse_receipt_detail_id]) {
                                    $scope.checklist[arg.warehouse_receipt_detail_id] = 0
                                }
                                jsn.action_choose = null
                                jsn.action = null
                                var jsn = JSON.stringify(jsn)
                                var resp = '<input type="checkbox" ng-model="checklist[' + arg.warehouse_receipt_detail_id + ']" ng-change=\'switchItem(' + arg.warehouse_receipt_detail_id + ' , ' + jsn + ')\'>'

                                return resp
                            }
                    })
                }
            }
            $scope.setMultiple()

            $scope.initDatatable = function() {
                options = {
                    order:[[1,'desc']],
                    lengthMenu:[[10,25,50,100],[10,25,50,100]],
                    ajax: {
                      headers : {'Authorization' : 'Bearer '+authUser.api_token},
                      url : itemsService.url.inWarehouseDatatable(),
                      data : e => Object.assign(e, $scope.formData),
                      dataSrc: function(d) {
                        $('.ibox-content').removeClass('sk-loading');
                        return d.data;
                      }
                    },
                    buttons: [
                      {
                        'extend' : 'excel',
                        'enabled' : true,
                        'action' : newExportAction,
                        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                        'className' : 'btn btn-default btn-sm',
                        'filename' : 'Master Item - '+new Date(),
                      },
                    ],
                    columnDefs : columnDefs,
                    columns:columns
                  }
                $scope.options = options
            }
            $scope.initDatatable()

            $scope.adjustField = function() {
                $scope.hide_multiple_choose = false
                if($scope.is_multiple) {
                    $scope.hide_multiple_choose = true
                }
            }
            $scope.adjustField()

            $scope.switchItem = function(warehouse_receipt_detail_id, jsn) {
                if($scope.checklist[warehouse_receipt_detail_id]) {
                    exist = $scope.items.findIndex(x => x.warehouse_receipt_detail_id == warehouse_receipt_detail_id)
                    if(exist == -1) {
                        $scope.items.push(jsn)
                    }
                } else {
                    exist = $scope.items.findIndex(x => x.warehouse_receipt_detail_id == warehouse_receipt_detail_id)
                    if(exist > -1) {
                        $scope.items = $scope.items.filter(x => x.warehouse_receipt_detail_id != warehouse_receipt_detail_id)
                    }

                }
              }

            $scope.searchData = function() {
                $scope.options.datatable.ajax.reload()
            }
            $timeout(() => {
                $scope.searchData()
            }, 500)

            $scope.$on('reloadItemWarehouses', function(e, v){
                if(v.warehouse_id) {
                    $scope.formData.warehouse_id = v.warehouse_id
                }
                if(v.customer_id) {
                    $scope.formData.customer_id = v.customer_id
                }
                if(v.show_picking) {
                    $scope.formData.show_picking = v.show_picking
                }

                if($scope.is_merchandise) {
                    $scope.formData.is_merchandise = $scope.is_merchandise
                }

                if($scope.quotationId) {
                    $scope.formData.quotation_id = $scope.quotationId
                }
                $scope.searchData()
            })

            $scope.initFilter = function() {
                if($attrs.customerId) {
                    $scope.$watch('customer_id', function(){
                        if($scope.customer_id) {
                            $scope.formData.customer_id = $scope.customer_id
                            $scope.options.datatable.ajax.reload()       
                        }
                    })
                }

            }
            $scope.initFilter()

            $scope.choosePallet = function(jsn) {
                $scope.items = []
                $scope.checklist = []
                $scope.$emit('chooseItemWarehouse', jsn)
            }
            $scope.chooseItemWarehouses = function() {
                var items =  $scope.items
                $scope.items = []
                $scope.checklist = []
                $scope.$emit('chooseItemWarehouses', items)
                $('#itemsModal').modal('hide')
            }
        }
    }
});