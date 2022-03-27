containers.directive('containersTable', function () {
    return {
        restrict: 'E',
        scope: {
            'job_order_id' :'=jobOrderId'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/containers/view/containers-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, containersService) {
            if(!$scope.job_order_id) {
                $rootScope.pageTitle = $rootScope.solog.label.general.container;
            }
            $scope.formData = {};
            $scope.formData.job_order_id = $scope.job_order_id;
            $('.ibox-content').addClass('sk-loading');

            $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                order:[[14,'desc']],
                
                
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : baseUrl+'/api/operational/container_datatable',
                  data : function(request) {
                    request['start_date'] = $scope.formData.start_date;
                    request['end_date'] = $scope.formData.end_date;
                    request['company_id'] = $scope.formData.company_id;
                    request['job_order_id'] = $scope.formData.job_order_id;

                    return request;
                  },
                  dataSrc: function(d) {
                      $('.ibox-content').removeClass('sk-loading');
                      return d.data;
                  }
                },
                columns:[
                  {data:"company.name",name:"company.name"},
                  {data:"booking_number",name:"booking_number",className:'font-bold'},
                  {
                    data:null,
                    name:"container_no",
                    className : 'font-bold',
                    render : function(resp) {
                        if($rootScope.roleList.includes('operational.container.detail')) {
                            return '<a ui-sref="operational.container.show({id:' + resp.id + '})">' + resp.container_no + '</a>'
                        } else {
                            return resp.container_no
                        } 
                    }
                  },
                  {data:"voyage_schedule.vessel.name",name:"voyage_schedule.vessel.name"},
                  {data:"container_type.code",name:"container_type.code"},
                  {
                      data:null,
                      orderable:false,
                      searchable:false,
                      render:resp => $filter('fullDate')(resp.booking_date)
                  },
                  {
                      data:null,
                      orderable:false,
                      name:'stripping',
                      render:resp => $filter('fullDateTime')(resp.stripping)
                  },
                  {
                      data:null,
                      orderable:false,
                      name:'stuffing',
                      render:resp => $filter('fullDateTime')(resp.stuffing)
                  },
                  {data:"seal_no",name:"seal_no"},
                  {data:"total_item",name:"total_item"},
                  {data:"total_volume",name:"total_volume"},
                  {data:"total_tonase",name:"total_tonase"},
                  {data:"is_fcl",name:"is_fcl"},
                  {data:"work_order_id",name:"work_order_id"},
                  {
                    data:null,
                    searchable:false,
                    name:"created_at",
                    className:"text-center",
                    render:function(resp) {
                        str = ''
                        str += "<a ng-show=\"$root.roleList.includes('operational.container.detail')\" ng-click='show(" + resp.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;" 
                        str += "<a ng-show=\"$root.roleList.includes('operational.container.edit')\" ng-click='edit(" + resp.id + ")' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;" 
                        str += "<a ng-show=\"$root.roleList.includes('operational.container.delete')\" ng-click='deletes(" + resp.id + ")' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>" 

                        return str;
                    }
                  },
                ],
                createdRow: function(row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
              });

            $scope.searchData = function() {
                oTable.ajax.reload();
            }

            $scope.resetFilter = function() {
                $scope.formData = {};
                oTable.ajax.reload();
            }

            $scope.show = function(id) {
                $rootScope.insertBuffer()
                $state.go('operational.container.show', {'id':id})
            }

            $scope.edit = function(id) {
                $rootScope.insertBuffer()
                $state.go('operational.container.edit', {'id':id})
            }

            $scope.exportExcel = function() {
                var paramsObj = oTable.ajax.params();
                var params = $.param(paramsObj);
                var url = baseUrl + '/excel/container_export?';
                url += params;
                location.href = url;
            }

            $scope.add = function() {
                $rootScope.insertBuffer()
                if($scope.job_order_id) {
                    $state.go('operational.job_order.show.create_container', {id : $scope.job_order_id})
                } else {
                    $state.go('operational.container.create')
                }
            } 

            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    $http.delete(baseUrl+'/operational/container/'+ids,{_token:csrfToken}).then(function success(data) {
                        oTable.ajax.reload();
                        toastr.success("Data Berhasil Dihapus!");
                        $scope.$emit('showJobOrder', 1)
                    }, function error(data) {
                        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
                    });
                }
            }
        }
    }
});