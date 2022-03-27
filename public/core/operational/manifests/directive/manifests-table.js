manifests.directive('manifestsTable', function () {
    return {
        restrict: 'E',
        scope: {
            manifest_id :'=manifestId',
            hide_type :'=hideType',
            code_column_name :'=codeColumnName',
            addRoute : '=addRoute',
            addRouteId : '=addRouteId',
            detail_route : '=detailRoute',
            source :'=source',
            source_id :'=source_id',
            hideFilterButton : "=",
            hideAddButton : "=",
            addButtonAllowed : "=",
            deleteButtonAllowed : "=",
            detailButtonAllowed : "=",
            salesOrderId : "="
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/manifests/view/manifests-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, manifestsService, additionalFieldsService) {

            $('.ibox-content').addClass('sk-loading');

            if(!$scope.addButtonAllowed) {
                $timeout(function() {
                    $scope.createButtonAllowed = $rootScope.roleList.includes("operational.manifest.vehicle.create")
                }, 3000)
            } else {
                $scope.createButtonAllowed = $scope.addButtonAllowed
            }

            console.log($scope.hideAddButton)

            if(!$scope.detailButtonAllowed) {
                $timeout(function() {
                    $scope.showButtonAllowed = $rootScope.roleList.includes("operational.manifest.vehicle.detail")
                }, 3000)
            } else {
                $scope.showButtonAllowed = $scope.detailButtonAllowed 
            }

            if(!$scope.deleteButtonAllowed) {
                $timeout(function() {
                    $scope.destroyButtonAllowed = $rootScope.roleList.includes("operational.manifest.vehicle.delete")
                }, 3000)
            } else {
                $scope.destroyButtonAllowed = $scope.deleteButtonAllowed
            }

            $scope.formData = {};
            if($scope.source) {
                $scope.formData.source = $scope.source
            }
            if($scope.salesOrderId) {
                $scope.formData.sales_order_id = $scope.salesOrderId
            }

            additionalFieldsService.dom.getInIndexKey('manifest', function(list){
                var additional_fields = list
                var code_column_name = $rootScope.solog.label.manifest.code
                if($scope.code_column_name) {
                    code_column_name = $scope.code_column_name
                }
                columnDefs = [
                    { 'title' : code_column_name },
                    { 'title' : $rootScope.solog.label.general.date },
                    { 'title' : $rootScope.solog.label.general.branch },
                    { 'title' : $rootScope.solog.label.general.route },
                    { 'title' : $rootScope.solog.label.general.type },
                    { 'title' : $rootScope.solog.label.general.vehicle },
                    { 'title' : $rootScope.solog.label.general.driver },
                    { 'title' : $rootScope.solog.label.general.status },
                ]

                

                columns = [
                    {data:"code",name:"code",className:"font-bold"},
                    {
                    data:null,
                    searchable:false,
                    name:"date_manifest",
                    render:resp => $filter('fullDate')(resp.date_manifest)
                    },
                    {data:"company",name:"companies.name"},
                    {data:"trayek",name:"routes.name"},
                    {data:"tipe_angkut",name:"tipe_angkut",className:"text-center"},
                    {data:"kendaraan",name:"dod.vehicle_id"},
                    {data:"sopir",name:"dod.driver_id"},
                    {data:"job_status",name:"id"}
                ]
                for(x in additional_fields) {
                    columns.push({
                        data : additional_fields[x].slug,
                        name : 'additional_manifests.' + additional_fields[x].slug
                    })
                    columnDefs.push({
                        title : additional_fields[x].name
                    })
                }

                if($scope.hide_type) {
                    var indexType = columns.findIndex(x => x.data == 'tipe_angkut')
                    if(indexType > -1) {
                        columns.splice(indexType, 1)
                        columnDefs.splice(indexType, 1)
                    }
                }

                columns.push({
                    data:null, 
                    orderable:false, 
                    searchable:false,
                    className:"text-center",
                    render : function(resp) {
                        var html = ''
                        html += "<a ng-show='showButtonAllowed' ng-click='show(" + resp.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

                        html += "<a ng-show=\"destroyButtonAllowed\" ng-click='deletes(" + resp.id + ")' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";

                        return html
                    }
                })
                columnDefs.push({ 'title' : '' })

                columnDefs = columnDefs.map((c, i) => {
                    c.targets = i
                    return c
                })
                oTable = null
                oTable = $('#datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    order:[[2,'desc'], [1,'desc']],
                    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                    dom: 'Blfrtip',
                    buttons: [{
                        extend: 'excel',
                        enabled: true,
                        action: newExportAction,
                        text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                        className: 'btn btn-default btn-sm pull-right',
                        filename: 'Packing List FTL/LTL',
                        sheetName: 'Data',
                        title: 'Packing List FTL/LTL',
                        exportOptions: {
                          rows: {
                            selected: true
                          }
                        },
                    }],
                    ajax: {
                        headers : {'Authorization' : 'Bearer '+authUser.api_token},
                        url : baseUrl+'/api/operational/manifest_ftl_datatable',
                        data : (x) => Object.assign(x, $scope.formData),
                        dataSrc: function(d) {
                          $('.ibox-content').removeClass('sk-loading');
                          return d.data;
                        }
                    },
                    columnDefs: columnDefs,
                    columns : columns,
                    createdRow: function(row, data, dataIndex) {
                        if($rootScope.roleList.includes('operational.manifest.vehicle.detail')) {
                            $(row).find('td').attr('ng-click', 'show(' + data.id + ')')
                            $(row).find('td:last-child').removeAttr('ui-sref')
                        } else {
                            $(oTable.table().node()).removeClass('table-hover')
                        }
                        $compile(angular.element(row).contents())($scope);
                    }
                });
                oTable.buttons().container().appendTo('.ibox-tools-manifest')
            })

            $scope.add = function() {
                if($scope.addRoute) {
                    console.log($scope.addRouteId);
                    if($scope.addRouteId){
                        $state.go($scope.addRoute, {'id': $scope.addRouteId})
                    } else {
                        $state.go($scope.addRoute)
                    }
                } else {
                    $state.go('operational.manifest_ftl.create')
                }
            }

            $scope.show = function(id) {
                if($scope.detail_route) {
                    if($scope.addRouteId){
                        $state.go($scope.detail_route, {'id': $scope.addRouteId, 'id_shipment' : id})
                    } else {
                        $state.go($scope.detail_route, {'id' : id})
                    }
                } else {
                    $rootScope.insertBuffer()
                    $state.go('operational.manifest_ftl.show', {'id' : id})
                }
            }

            $scope.searchData = function() {
                if(window.oTable) {
                    oTable.ajax.reload();
                }
            }
            $scope.resetFilter = function() {
                $scope.formData = {};
                if($scope.source) {
                    $scope.formData.source = $scope.source
                }
                if($scope.salesOrderId) {
                    $scope.formData.sales_order_id = $scope.salesOrderId
                }
                oTable.ajax.reload();
            }

            $scope.submitAdditional = function() {

            }


            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    $http.delete(baseUrl+'/operational/manifest_ftl/'+ids,{_token:csrfToken}).then(function success(data) {
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