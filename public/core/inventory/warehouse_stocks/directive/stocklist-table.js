warehouseStocks.directive('stocklistTable', function () {
    return {
        restrict: 'E',
        scope: {
            hideFilter : '=hideFilter',
            hide_is_merchandise_filter : '=hideIsMerchandiseFilter',
            itemMigrationId : '=itemMigrationId',
            warehouse_id : '=warehouseId',
            item_id : '=itemId',
            is_merchandise : '=isMerchandise',            
            is_pallet : '=isPallet',            
            item_migration_id : '=itemMigrationId'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_stocks/view/stocklist-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, warehouseStocksService) {
            $scope.formData = {};
            $scope.formData.is_merchandise = 0

            var columnDefs = [
                {title : $rootScope.solog.label.warehouse_receipt.code },
                {title : $rootScope.solog.label.general.customer },
                {title : $rootScope.solog.label.general.shipper },
                {title : $rootScope.solog.label.general.consignee },
                {title : $rootScope.solog.label.item.name },
                {title : $rootScope.solog.label.general.warehouse },
                {title : $rootScope.solog.label.general.rack },
                {title : $rootScope.solog.label.general.receive_date },
                {title : $rootScope.solog.label.general.in_progress_qty },
                {title : $rootScope.solog.label.general.available_qty },
                {title : $rootScope.solog.label.general.qty },
            ]

            columnDefs = columnDefs.map((c, i) => {
                c.targets = i
                return c
            })

            stocklistDatatable = $('#stocklist_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                order: [[ 7, "desc" ]],
                'initComplete' : function() {
                    unitTable = this.api();
                },
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/operational_warehouse/stocklist_datatable',
                    data : (e) => Object.assign(e, $scope.formData),
                },
                columnDefs : columnDefs,
                columns:[
                    {data:"no_surat_jalan",name:"no_surat_jalan"},
                    {data:"customer_name",name:"customer_name"},
                    {data:"sender",name:"sender"},
                    {data:"receiver",name:"receiver"},
                {data:"name",name:"name"},
                    {data:"warehouse_name",name:"warehouse_name"},
                    {data:"rack_name",name:"rack_name"},
                    {
                        data:null,
                        name:'receive_date',
                        searchable:false,
                        render:function(resp) {
                          var date = resp.receive_date.split(' ');
                          return $filter('fullDate')(date[0]) + ' ' + date[1]
                        }
                    },
                    {
                        data:"onhand_qty",
                        name : 'onhand_qty',
                        searchable : false, 
                        className : 'text-right'
                    },
                    {
                        data:"available_qty",
                        name : 'available_qty',
                        searchable : false, 
                        className : 'text-right'
                    },
                    {
                        data:"qty",
                        name : 'qty',
                        searchable : false, 
                        className : 'text-right'
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td').attr('ng-click', 'detail($event.currentTarget)');
                    if(data.status == 2) {
                        $(row).addClass('text-danger');
                    }
                    $compile(angular.element(row).contents())($scope);
                }
          });

            $scope.detail = function(e) {
                var tr = $(e).parents('tr')
                var data = stocklistDatatable.row(tr).data();
                $state.go('operational_warehouse.stocklist.show', {id : data.id});
            }


            $scope.searchData = function() {
                if($scope.itemMigrationId) {
                    $scope.formData.item_migration_id = $scope.itemMigrationId
                }
                if($scope.is_merchandise) {
                    $scope.formData.is_merchandise = $scope.is_merchandise
                }
                if($scope.is_pallet) {
                    $scope.formData.is_pallet = $scope.is_pallet
                }
                stocklistDatatable.ajax.reload();
            }

            $scope.$watch('itemMigrationId', function(){
                $scope.searchData()  
            })

            $scope.exportExcel = function() {
                var params = stocklistDatatable.ajax.params();
                params = $.param(params);
                window.location.href = baseUrl+'/operational_warehouse/stocklist/excel?' + params
            }
            $scope.resetFilter = function() {
                $scope.formData = {};
                stocklistDatatable.ajax.reload();
            }
        }
    }
});