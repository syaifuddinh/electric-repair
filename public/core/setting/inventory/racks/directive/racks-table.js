racks.directive('racksTable', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId'
        },
        templateUrl: '/core/setting/inventory/racks/view/racks-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, racksService) {
            $scope.hide_warehouse = false
            $scope.filterData = {
                warehouse_id: null,
                is_used_only: 0
            }

            $scope.submitForm = function() {
                $rootScope.disBtn = true;
                if($scope.formData.id) {
                    racksService.api.update($scope.formData, $scope.formData.id, function(){
                        $('#modal').modal('hide');
                        rackTable.ajax.reload()
                    })
                } else {
                    racksService.api.store($scope.formData, function(){
                        $('#modal').modal('hide');
                        rackTable.ajax.reload()
                    })
                }
            }

            rackTable = $('#rack_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX : false,
                dom: 'Blfrtip',
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/operational_warehouse/rack_datatable',
                    dataSrc: function(d) {
                        $('.sk-container').removeClass('sk-loading');
                        return d.data;
                    },
                    data: function(d) {
                        d.warehouse_id = $scope.filterData.warehouse_id
                        d.is_used_only = $scope.filterData.is_used_only
                    }
                },
                buttons: [{
                    extend: 'excel',
                    enabled: true,
                    action: newExportAction,
                    text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                    className: 'btn btn-default btn-sm pull-right',
                    filename: 'Bin Location',
                    messageTop: 'Bin Location',
                    sheetName: 'Data',
                    title: 'Bin Location',
                    exportOptions: {
                        rows: {
                          selected: true
                        }
                    }
                }],
            columns:[
                {data:"warehouse.company.name",name:"warehouse.company.name"},
                {data:"warehouse.name",name:"warehouse.name"},
                {data:"code",name:"code"},
                {data:"storage_type.name",name:"storage_type.name"},
                {
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:'text-right',
                    render : resp => $filter('number')(resp.capacity_volume)
                },
                {
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:'text-right',
                    render : resp => $filter('number')(resp.capacity_volume_used)
                },
                {
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:'text-right',
                    render : resp => $filter('number')(resp.capacity_tonase)
                },
                {
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:'text-right',
                    render : resp => $filter('number')(resp.capacity_tonase_used)
                },
                {
                    data:null,
                    searchable:false,
                    orderable:false,
                    className:'text-center',
                    render : function(resp) {
                        var id = resp.id
                        resp.action = null
                        var jsn = JSON.stringify(resp)
                        var r = '' 
                        r += '<a ng-show="$root.roleList.includes(\'inventory.setting.bin_location.edit\')" ng-click=\'edit(' + jsn + ')\'><i class="fa fa-edit"></i></a>&nbsp;&nbsp;'
                        r += '<a ng-show="$root.roleList.includes(\'inventory.setting.bin_location.detail\')" ng-click="show(' + resp.id + ')"><i class="fa fa-folder-o"></i></a>&nbsp;'
                        return r
                    }
                }
            ],
            createdRow: function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            }
            });
            rackTable.buttons().container().appendTo('.btn-area')

            $scope.show = function(id) {
                $rootScope.insertBuffer()
                $state.go('operational_warehouse.bin_location.show', {id:id})
            }

            $scope.$watch('warehouse_id', function(){
                if($scope.warehouse_id) {
                    $scope.hide_warehouse = true
                    $scope.filterData.warehouse_id = $scope.warehouse_id
                    $timeout(function(){
                        rackTable.ajax.reload()
                    }, 300)
                }
            })

            $compile($('thead'))($scope)
            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    $http.delete(baseUrl+'/operational_warehouse/setting/delete_rack/'+ids,{_token:csrfToken}).then(function success(data) {
                        rackTable.ajax.reload();
                        toastr.success("Data Berhasil Dihapus!");
                    }, function error(data) {
                        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
                    });
                }
            }

            $http.get(baseUrl+'/operational_warehouse/setting/rack').then(function(data) {
                $scope.data=data.data;
            })

            $scope.add=function() {
                $rootScope.disBtn = false
                $scope.modalTitle="Add Bin Location";
                $scope.formData={}
                $scope.formData.capacity_volume=0
                $scope.formData.capacity_tonase=0
                if($scope.warehouse_id) {
                    $scope.formData.warehouse_id = $scope.warehouse_id
                }
                $('#modal').modal();
            }

            $scope.edit=function(jsn) {
                // console.log(jsn);
                $rootScope.disBtn = false
                $scope.modalTitle="Edit Bin Location";
                $scope.formData={}
                racksService.api.show(jsn.id, function(dt){
                    $scope.formData = dt
                    $scope.formData.warehouse_map_id = parseInt(dt.warehouse_map_id)
                    $('#modal').modal();
                })
            }

        }
    }
});