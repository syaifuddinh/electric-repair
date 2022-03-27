warehouseStocks.directive('warehouseStocksTable', function () {
    return {
        restrict: 'E',
        scope: {
            is_merchandise : '=isMerchandise',
            hide_is_merchandise_filter : '=hideIsMerchandiseFilter',
            warehouse_id : '=warehouseId',
            purchase_order_id : '=purchaseOrderId',
            group_by_item : '=groupByItem'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_stocks/view/warehouse-stocks-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, warehouseStocksService) {
            $scope.formData = {};
            $('.ibox-content').addClass('sk-loading');

            var url = warehouseStocksService.url.datatable()
            if($scope.group_by_item == 1) {
                url = warehouseStocksService.url.itemDatatable()
            }

            var columnDefs = [
                { title : $rootScope.solog.label.general.branch },
                { title : $rootScope.solog.label.general.warehouse },
                { title : $rootScope.solog.label.item.name },
                { title : $rootScope.solog.label.general.category },
                { title : $rootScope.solog.label.general.stock }
            ]

            var columns = [
                {data:"company_name",name:"companies.name"},
                {data:"warehouse_name",name:"warehouses.name",className:"font-bold"},
                {data:"item_name",name:"items.name"},
                {data:"category_name",name:"categories.name"},
                {data:"qty",name:"warehouse_stocks.qty",className:"text-right"},
            ]

            if($scope.group_by_item == 1) {
                columnDefs.splice(1, 1)
                columns.splice(1, 1)
            }

            columnDefs = columnDefs.map((c, i) => {
                c.targets = i
                return c
            })
            var title = 'Stock By Warehouse'
            if($scope.group_by_item == 1) {
                title = 'Stock By Item'
            }
            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                dom: 'Blfrtip',
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url :url,
                    data : function(request) {
                        request['warehouse_id'] = $scope.formData.warehouse_id;
                        request['start_qty'] = $scope.formData.start_qty;
                        request['end_qty'] = $scope.formData.end_qty;
                        request['is_merchandise'] = $scope.formData.is_merchandise;

                        return request;
                    },
                    dataSrc: function(d) {
                        $('.ibox-content').removeClass('sk-loading');
                        return d.data;
                    }
                },
                buttons: [
                    {
                        'extend' : 'excel',
                        'enabled' : true,
                        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                        'className' : 'btn btn-default btn-sm',
                        'filename' : title + ' - '+new Date(),
                        'sheetName' : 'Data',
                        'title' : title
                    },
                ],
                columnDefs : columnDefs,
                columns : columns,
                createdRow: function(row, data, dataIndex) {
                    $compile(angular.element(row).contents())($scope);
                }
            });

            oTable.buttons().container().appendTo( '.export_button' );

            $compile($('thead'))($scope)

            $scope.searchData = function() {
                if($scope.is_merchandise) {
                    $scope.formData.is_merchandise = $scope.is_merchandise
                }
                oTable.ajax.reload();
            }
            $scope.searchData()

            $scope.resetFilter = function() {
                $scope.formData = {};
                oTable.ajax.reload();
            }
        }
    }
});
