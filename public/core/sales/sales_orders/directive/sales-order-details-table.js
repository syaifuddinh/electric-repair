salesOrders.directive('salesOrderDetailsTable', function () {
    return {
        restrict: 'E',
        scope: {
            customerId : '=',
            is_pallet : '=isPallet',
            is_multiple : '=isMultiple'
        },
        transclude:true,
        require:'ngModel',
        template: '<solog-datatable options="options" id="sales_order_detail_datatable"></solog-datatable>',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, salesOrdersService) {
            if(!$scope.formData) {
                $scope.formData = {}
            }
            $scope.formData.customer_id = $scope.customerId
            $scope.checklist = {}
            $scope.items = []

            var columnDefs = [
                {title : $rootScope.solog.label.sales_order.code},
                {title : $rootScope.solog.label.item.name },
                {title : $rootScope.solog.label.sales_order.qty_in_sales },
                {title : $rootScope.solog.label.general.sale_price }
            ]

            var columns = [
                {data:"sales_order_code",name:"sales_order_code"},
                {
                    data:"item_name",
                    name:"item_name"
                },
                {
                    data:"qty",
                    name:"qty",
                    className : "text-right"
                },
                {
                    data:null,
                    className:"text-right",
                    name:"price",
                    render : function(resp) {
                        var r = $filter('number')(resp.price)

                        return r
                    }
                }
            ]

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

           var options = {
                order:[[0,'desc']],
                lengthMenu:[[10,25,50,100],[10,25,50,100]],
                ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : salesOrdersService.url.detailDatatable(),
                  data : e => Object.assign(e, $scope.formData),
                  dataSrc: function(d) {
                    $('.ibox-content').removeClass('sk-loading');
                    return d.data;
                  }
                },
                dom: 'lfrtip',
                buttons: [
                  {
                    'extend' : 'excel',
                    'enabled' : true,
                    'action' : newExportAction,
                    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                    'className' : 'btn btn-default btn-sm',
                    'filename' : $rootScope.solog.label.sales_order.title + ' - '+new Date(),
                  },
                ],
                columnDefs : columnDefs,
                columns:columns
            }

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

            if($attrs['inputMode']) {
                $scope.createdRow = function(row, data) {
                    var first = $(row).find('td:first-child');
                    var a, b
                    a = $('<a ng-click="chooseSalesOrderDetail(' + data.header_id + ', ' + data.id + ')">' + first.text() + '</a>')
                    first.empty()
                    first.append(a)
                }
            }

            $scope.options = options

            $scope.reload = function() {
                if($scope.is_pallet) {
                    $scope.formData.is_pallet = 1
                }
                $scope.$broadcast('reload', 0)
            }

            $scope.$watch('is_pallet', function(){
                $scope.reload()
            })

            $scope.$on('reloadSalesOrder', function(e, v) {
                $scope.formData.customer_id = v.customer_id
            })

            $scope.chooseSalesOrderDetail = function(sales_order_id, id) {
                salesOrdersService.api.showDetailInfo(sales_order_id, id, function(dt){
                    $scope.$emit('chooseSalesOrderDetail', dt)
                })
            }

            $scope.deletes = function(id) {
                var is_confirm = confirm($rootScope.solog.label.general.are_you_sure)
                if(is_confirm) {
                    salesOrdersService.api.destroy(id, function(){
                       $scope.options.datatable.ajax.reload() 
                    })
                }
            }
        }
    }
});