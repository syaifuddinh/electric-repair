vendorPrices.directive('vendorPricesTable', function () {
    return {
        restrict: 'E',
        scope: {
            'add_route' :'=addRoute',
            'add_params' :'=addParams',
            'edit_route' :'=editRoute',
            'edit_params' :'=editParams',
            'hide_type_filter' :'=hideTypeFilter',
            'vendor_id' : '=vendorId'
        },
        transclude:true,
        templateUrl: '/core/marketing/vendor_prices/view/vendor-prices-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, $timeout, contactsService) {
            $scope.filterData = {};

            $scope.refreshTable=function() {
                if($scope.vendor_id) {
                    $scope.filterData.vendor_id = $scope.vendor_id
                }
                oTable.ajax.reload();
            }

            $scope.add = () => {
                if($scope.add_route) {
                    $state.go($scope.add_route, $scope.add_params)
                } else {    
                    $state.go('marketing.vendor_price.create')
                }
            }


            $scope.edit = (id) => {
                if($scope.edit_route) {
                    $scope.edit_params.idprice = id
                    $state.go($scope.edit_route, $scope.edit_params)
                } else {    
                    $state.go('marketing.vendor_price.edit', { id : id })
                }
            }

            $scope.reset=function() {
                $scope.filterData={}
                $scope.refreshTable()
            }

            $scope.exportExcel = function() {
                var paramsObj = oTable.ajax.params();
                var params = $.param(paramsObj);
                var url = baseUrl + '/excel/price_list_vendor?';
                url += params;
                location.href = url; 
            }
              
            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                dom: 'Blfrtip',
                buttons: [{
                    extend: 'excel',
                    enabled: true,
                    action: newExportAction,
                    text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                    className: 'btn btn-default btn-sm pull-right m-l-sm m-r-sm',
                    filename: 'Logistic - Vendor Price - ' + new Date,
                    sheetName: 'Data',
                    title: 'Logistic - Vendor Price',
                    exportOptions: {
                    rows: {
                        selected: true
                    }
                    },
                }],
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    data: d => Object.assign(d, $scope.filterData),
                    url : baseUrl+'/api/vendor/vendor_price_datatable'
                },
                columns:[
                    {data:"cabang",name:"companies.name"},
                    {data:"vendor",name:"contacts.name"},
                    {data:"cost_type_name",name:"cost_types.name"},
                    {data:"trayek",name:"routes.name"},
                    {data:"vtype",name:"vtype"},
                    {data:"price_full",name:"price_full",className:'text-right'},
                    {
                        data:null,
                        className:"text-center",
                        orderable:false,
                        searchable:false,
                        render : function(item) {
                            var html = ''

                            html += "<a ng-show=\"$root.roleList.includes('marketing.price.vendor_price.detail')\" ui-sref=\"marketing.vendor_price.show({id:" + item.id + "})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
                            html  +=  "<a ng-show=\"$root.roleList.includes('marketing.price.vendor_price.edit')\" ng-click=\"edit(" + item.id + ")\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
                            html += "<a  ng-show=\"$root.roleList.includes('marketing.price.vendor_price.delete')\" ng-click=\"deletes(" + item.id + ")\"><span class='fa fa-trash-o'></span></a>";

                            return html;
                        }
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
              });

              oTable.buttons().container().appendTo('.ibox-tools')

              $scope.refreshTable()

              $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                  $http.delete(baseUrl+'/marketing/vendor_price/'+ids,{_token:csrfToken}).then(function(res) {
                    toastr.success("Data Berhasil Dihapus!");
                    oTable.ajax.reload();
                  }, function(res) {
                    toastr.error("Data Tidak dapat Dihapus!");
                  });
                }
              }
        }
    }
});