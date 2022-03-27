purchaseRequests.directive('purchaseRequestsTable', function () {
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
        templateUrl: '/core/inventory/purchase_requests/view/purchase-requests-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, purchaseRequestsService) {
            $scope.formData = {};
            $scope.filterData={}
            $scope.is_filter=false;

            if($scope.is_pallet) {
                $scope.filterData.is_pallet = 1;
            }

            $('.ibox-content').addClass('sk-loading');

            var columnDefs = [
                {title : $rootScope.solog.label.general.branch},
                {title : $rootScope.solog.label.purchase_request.code},
                {title : $rootScope.solog.label.general.requested_date},
                {title : $rootScope.solog.label.general.realization_date},
                {title : $rootScope.solog.label.general.supplier},
                {title : $rootScope.solog.label.general.status},
                {title : ''}
            ]

            columnDefs = columnDefs.map((c, i) => {
                c.targets = i
                return c
            })

            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                order:[[2,'desc'], [1,'desc']],
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/inventory/purchase_request_datatable',
                    data: e => Object.assign(e, $scope.filterData),
                    dataSrc: function(d) {
                        $('.ibox-content').removeClass('sk-loading');
                        return d.data;
                    }
                },
                columnDefs: columnDefs,
                columns:[
                    {data:"company_name",name:"companies.name"},
                    {data:"code",name:"purchase_requests.code",className:"font-bold"},
                    {
                        data:null,
                        name : 'date_request',
                        searchable:false,
                        render:resp => $filter('fullDate')(resp.date_request)
                    },
                    {
                        data:null,
                        name : 'date_needed',
                        searchable:false,
                        render:resp => $filter('fullDate')(resp.date_needed)
                    },
                    {data:"supplier_name",name:"suppliers.name",className:"font-bold"},
                    {data:"status_label", className:"text-center", searchable : false, orderable : false},
                    {
                        data:null,
                        name:"created_at",
                        className:"text-center", 
                        searchable : false,
                        render : function(resp) {
                            var html = "<a ng-show=\"$root.roleList.includes('inventory.purchase_request.detail')\" ng-click='show(" + resp.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

                            if (resp.status == 1) {
                                html += "<a ng-show=\"$root.roleList.includes('inventory.purchase_request.delete')\" ng-click='edit(" + resp.id + ")' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
                                html += "<a ng-show=\"$root.roleList.includes('inventory.purchase_request.delete')\" ng-click='deletes(" + resp.id + ")' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
                            }

                            return html;
                        }
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    if($rootScope.roleList.includes('inventory.purchase_request.detail')) {
                        $(row).find('td').attr('ui-sref', 'inventory.purchase_request.show({id:' + data.id + '})')
                        $(row).find('td:last-child').removeAttr('ui-sref')
                    } else {
                        $(oTable.table().node()).removeClass('table-hover')
                    }
                    $compile(angular.element(row).contents())($scope);
                }
          });

            $scope.add = function() {
                if($scope.add_route) {
                    $state.go($scope.add_route)
                } else {
                    if($scope.is_pallet) {
                        $state.go('operational_warehouse.pallet_purchase_request.create')
                    } else {
                        $state.go('inventory.purchase_request.create')
                    }
                }
            }
            
            $scope.show = function(id) {
                if($scope.detail_route) {
                    $state.go($scope.detail_route, {id:id})
                } else {
                    $state.go('inventory.purchase_request.show', {id:id})
                }
            }

            $scope.edit = function(id) {
                if($scope.edit_route) {
                    $state.go($scope.edit_route, {id:id})
                } else {
                    $rootScope.insertBuffer()
                    $state.go('inventory.purchase_request.edit', {id:id})
                }
            }

            $scope.filter=function() {
                if($scope.is_merchandise) {
                    $scope.filterData.is_merchandise = $scope.is_merchandise
                }
                oTable.ajax.reload();
            }

            $scope.reset_filter=function() {
                $scope.filterData={};
                $scope.filter();
            }

            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                  $http.delete(baseUrl+'/inventory/purchase_request/'+ids,{_token:csrfToken}).then(function success(data) {
                    oTable.ajax.reload();
                    toastr.success("Data Berhasil Dihapus!");
                  }, function error(data) {
                    toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
                  });
                }
            }
        }
    }
});