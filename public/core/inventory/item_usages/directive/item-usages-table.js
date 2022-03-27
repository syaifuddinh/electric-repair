itemUsages.directive('itemUsagesTable', function () {
    return {
        restrict: 'E',
        scope: {
            'addRoute' : '=addRoute',
            'edit_route' : '=editRoute',
            'showRoute' : '=showRoute',
            'isPallet' : '=isPallet'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/inventory/item_usages/view/item-usages-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state) {
            $scope.formData = {};
            $('.ibox-content').addClass('sk-loading');

            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/inventory/using_item_datatable',
                    data : function(request) {
                        request['start_date'] = $scope.formData.start_date;
                        request['end_date'] = $scope.formData.end_date;
                        request['start_date_penggunaan'] = $scope.formData.start_date_penggunaan;
                        request['end_date_penggunaan'] = $scope.formData.end_date_penggunaan;
                        request['company_id'] = $scope.formData.company_id;
                        request['status'] = $scope.formData.status;
                        request['is_pallet'] = $scope.isPallet;

                        return request;
                    },
                    dataSrc: function(d) {
                        $('.ibox-content').removeClass('sk-loading');
                        return d.data;
                    }
                },
                columns:[
                    {data:"company.name",name:"company.name"},
                    {data:"code",name:"code",className:"font-bold"},
                    {
                        data:null,
                        name:"date_request",
                        searchable:false,
                        render:resp => $filter('fullDate')(resp.date_request)
                    },
                    {
                        data:null,
                        name:"date_approve",
                        searchable:false,
                        render:resp => $filter('fullDate')(resp.date_approve)
                    },
                    {data:"status_name", searchable:false, orderable:false, className : 'text-center'},
                    {
                        data:null,
                        orderable:false,
                        searchabe:false,
                        className:"text-center",
                        render : function(item) {
                            var html = ''

                            html += "<a ng-show=\"$root.roleList.includes('inventory.good_issue.edit')\" ng-click='edit(" + item.id + ")' ><span class='fa fa-edit'  data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";

                            html += "<a ng-show=\"$root.roleList.includes('inventory.good_issue.detail')\" ng-click='show(" + item.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

                            html += "<a ng-show=\"$root.roleList.includes('inventory.good_issue.delete')\" ng-click='deletes(" + item.id + ")' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";

                            return html;
                        }
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    if($rootScope.roleList.includes('inventory.good_issue.detail')) {
                        $(row).find('td').attr('ng-click', 'show(' + data.id + ')')
                        $(row).find('td:last-child').removeAttr('ng-click')
                    } else {
                        $(oTable.table().node()).removeClass('table-hover')
                    }
                    $compile(angular.element(row).contents())($scope);
                }
            });

            $compile($('table'))($scope)

            $scope.add = function() {
                $rootScope.insertBuffer()
                if($scope.addRoute) {
                    $state.go($scope.addRoute)
                } else {
                    $state.go('inventory.using_item.create')
                }
            }

            $scope.show = function(id) {
                $rootScope.insertBuffer()
                if($scope.showRoute) {
                    $state.go($scope.showRoute, {'id' : id})
                } else {
                    $state.go('inventory.using_item.show', {'id' : id})
                }                
            } 

            $scope.edit = function(id) {
                if($scope.edit_route) {
                    $state.go($scope.edit_route, {'id' : id})
                } else {
                    $state.go('inventory.using_item.edit', {'id' : id})
                }                
            } 

            $scope.deletes=function(ids) {
                var cfs=confirm("Are you sure ?");
                if (cfs) {
                    $http.delete(baseUrl+'/inventory/using_item/'+ids,{_token:csrfToken}).then(function() {
                        oTable.ajax.reload();
                        toastr.success("Data Berhasil Dihapus!");
                    }, function(error) {
                        $scope.disBtn=false;
                        if (error.status==422) {
                            var det="";
                            angular.forEach(error.data.errors,function(val,i) {
                                det+="- "+val+"<br>";
                            });
                            toastr.warning(det,error.data.message);
                        } else {
                            toastr.error(error.data.message,"Error Has Found !");
                        }
                    });
                }
            }

            $scope.searchData = function() {
                oTable.ajax.reload();
            }
            $scope.resetFilter = function() {
                $scope.formData = {};
                oTable.ajax.reload();
            }


            $scope.exportExcel = function() {
                var paramsObj = oTable.ajax.params();
                var params = $.param(paramsObj);
                var url = baseUrl + '/excel/penggunaan_barang_export?';
                url += params;
                location.href = url; 
            }
        }
    }
});