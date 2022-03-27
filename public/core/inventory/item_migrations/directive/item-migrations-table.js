itemMigrations.directive('itemMigrationsTable', function () {
    return {
        restrict: 'E',
        scope: {
            'addRoute' : '=addRoute',
            'showRoute' : '=showRoute',
            'isPallet' : '=isPallet'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/inventory/item_migrations/view/item-migrations-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state) {
            $scope.formData = {};

            if($scope.isPallet) {
                $scope.formData.is_pallet = 1
            }

            oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/operational_warehouse/mutasi_transfer_datatable',
                    data : e => Object.assign(e, $scope.formData)
                },

                dom: 'Blfrtip',
                buttons: [{
                  extend: 'excel',
                  enabled: true,
                  action: newExportAction,
                  text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                  className: 'btn btn-default btn-sm pull-right',
                  filename: 'Putaway',
                  sheetName: 'Data',
                  title: 'Putaway',
                  exportOptions: {
                    rows: {
                      selected: true
                    }
                  },
                }],
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                columns:[
                  {data:"code",name:"im.code",className:"font-bold", 'width' : '20%'},
                  {data:"warehouse_from",name:"wfrom.name", 'width' : '25%'},
                  {
                    data:null,
                    orderable:false, 
                    searchable:false,
                    'width' : '25%',
                    render : resp => $filter('fullDate')(resp.date_transaction)
                  },
                  {data:"status_name",name:"im.status",className:"text-center"},
                  {
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:"text-center",
                    render : function(item)  {
                        var html = ""
                        html += "<a ng-show=\"$root.roleList.includes('inventory.transfer.edit')\" ng-click='edit(" + item.id + ")' ><span class='fa fa-edit'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
                        html += "<a ng-show=\"$root.roleList.includes('inventory.transfer.detail')\" ng-click='show(" + item.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
                        if (item.status==1) {
                            html += '<a ng-show="$root.roleList.includes(\'inventory.transfer.delete\')" ng-click="deletes(' + item.id + ')"><i class="fa fa-trash"></i></a>';
                        }
                        return html;
                    }
                  },
                ],
                createdRow: function(row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
              });

                oTable.buttons().container().appendTo('.ibox-tools')
                $compile($('thead'))($scope)

                $scope.searchData = function() {
                    oTable.ajax.reload();
                }

                $scope.resetFilter = function() {
                    $scope.formData = {};
                    oTable.ajax.reload();
                }

                $scope.add = function() {
                    if($scope.addRoute) {
                        $state.go($scope.addRoute)
                    } else {
                        $state.go('operational_warehouse.mutasi_transfer.create')
                    }
                }

                $scope.edit = function(id) {
                    var params = {}
                    params.id = id
                    if($scope.addRoute) {
                        $state.go($scope.addRoute, params)
                    } else {
                        $state.go('operational_warehouse.mutasi_transfer.edit', params)
                    }
                }

                $scope.show = function(id) {
                    $rootScope.insertBuffer()
                    if($scope.showRoute) {
                        $state.go($scope.showRoute, {'id' : id})
                    } else {
                        $state.go('operational_warehouse.mutasi_transfer.show', {'id' : id})
                    }                
                }

              $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                  $http.delete(baseUrl+'/operational_warehouse/mutasi_transfer/' + ids).then(function(data) {
                    toastr.success("Mutasi transfer berhasil dihapus","Selamat !")
                    oTable.ajax.reload();
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
        }
    }
});