salesOrderReturns.directive('salesOrderReturnsTable', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            company_id : '=companyId',
            is_merchandise : '=isMerchandise',
            add_route : '=addRoute',
            edit_route : '=editRoute',
            detail_route : '=detailRoute',
            is_pallet : '=isPallet',
            tableOnCreated : '='
        },
        require:'ngModel',
        templateUrl: '/core/inventory/sales_order_returns/view/sales-order-returns-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, salesOrderReturnsService) {
            $scope.filterData={}

            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                order:[[0,'desc']],
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/operational_warehouse/pallet_sales_order_return_datatable',
                    data: e => Object.assign(e, $scope.filterData)
                },
                dom: 'Blfrtip',
                buttons: [
                  {
                    'extend' : 'excel',
                    'enabled' : true,
                    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                    'className' : 'btn btn-default btn-sm',
                    'filename' : 'Sales Order Return - '+new Date(),
                    'sheetName' : 'Data',
                    'title' : 'Sales Order Return'
                  },
                ],

                columns:[
                    {data:"code",name:"sales_order_returns.code",className:"font-bold"},
                    {data:"company_name",name:"companies.name"},
                    {data:"customer",name:"contacts.name"},
                    {data:"date_transaction",name:"sales_order_returns.date_transaction"},
                    {data:"status_name",name:"sales_order_return_statuses.name",className:"text-center"},
                    {
                        data:null,
                        orderable:false,
                        searchable:false,
                        className:"text-center",
                        render : function(item) {
                            var html = ''
                            html += "<a ng-click='edit(" + item.id + ")' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
                            html += "<a ng-show=\"$root.roleList.includes('sales.sales_order_return.detail')\" ui-sref='operational_warehouse.pallet_sales_order_return.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
                            if ( item.status == 1 ) {
                                html += '<a ng-show="$root.roleList.includes(\'sales.sales_order_return.delete\')" ng-click="deletes(' + item.id + ')"><i class="fa fa-trash"></i></a>';
                            }

                            return html;
                        }
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
            });

            oTable.buttons().container().appendTo( '#export_button' );

            $compile($('table thead'))($scope)

            $scope.add = () => {
                if($scope.add_route) {
                    $state.go($scope.add_route)
                } else {
                    $state.go("operational_warehouse.pallet_sales_order_return.create")
                }
            }

            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                  $http.delete(baseUrl+'/operational_warehouse/pallet_sales_order_return/'+ids,{_token:csrfToken}).then(function success(data) {
                    // $state.reload();
                    oTable.ajax.reload();
                    toastr.success("Data Berhasil Dihapus!");
                  }, function error(data) {
                    toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
                  });
                }
            }

            $scope.edit = function(id) {
                $state.go('operational_warehouse.pallet_sales_order_return.edit', {id : id})
            }
   
            $scope.filter=function() {
                if($scope.is_merchandise) {
                    $scope.filterData.is_merchandise = $scope.is_merchandise
                }
                if($scope.is_pallet) {
                    $scope.filterData.is_pallet = $scope.is_pallet
                }
                oTable.ajax.reload()
            }
            $scope.filter()

            $scope.reset_filter=function() {
                $scope.filterData={}
                oTable.ajax.reload()
            }
        }
    }
});