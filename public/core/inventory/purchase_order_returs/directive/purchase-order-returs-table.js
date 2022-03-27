purchaseOrderReturs.directive('purchaseOrderRetursTable', function () {
    return {
        restrict: 'E',
        scope: {
            'isPallet' : '=isPallet',
            'is_merchandise' : '=isMerchandise',
            'add_route' : '=addRoute',
            'edit_route' : '=editRoute',
            'detail_route' : '=detailRoute'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/inventory/purchase_order_returs/view/purchase-order-returs-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state) {
            $('.ibox-content').addClass('sk-loading');

              $scope.formData = {};
              if($scope.isPallet) {
                $scope.formData.is_pallet = $scope.isPallet
              }
              if(authUser.is_admin == 0) {
                $scope.formData.company_id = compId
              }
              oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                ajax: {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/inventory/retur_datatable',
                    data : e => Object.assign(e, $scope.formData),
                    dataSrc: function(d) {
                        $('.ibox-content').removeClass('sk-loading');
                        return d.data;
                    }
                },
                columns:[
                  {data:"company.name",name:"company.name"},
                  {data:"warehouse_name",name:"warehouses.name"},
                  {data:"date_transaction",name:"date_transaction"},
                  {data:"code",name:"code"},
                  {data:"supplier.name",name:"supplier.name"},
                  {data:"status",name:"status",className:"text-center"},
                  {
                        data:null,
                        orderable:false,
                        searchable:false,
                        className:"text-center",
                        render : function(item) {
                            var html = ''
                            html += "<a ng-click='edit(" + item.id + ")' ><span class='fa fa-edit'  data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
                            html += "<a ng-show=\"$root.roleList.includes('inventory.retur.detail')\" ng-click='show(" + item.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
                            return html;
                        }
                  },
                ],
                createdRow: function(row, data, dataIndex) {
                  if($rootScope.roleList.includes('inventory.retur.detail')) {
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
                    if($scope.add_route) {
                        $state.go($scope.add_route)
                    } else {
                        $rootScope.insertBuffer()
                        $state.go('inventory.retur.create')
                    }
                }

              
                $scope.show = function(id) {
                    if($scope.detail_route) {
                        $state.go($scope.detail_route, {id : id})
                    } else {
                        $rootScope.insertBuffer()
                        $state.go('inventory.retur.show', {id : id})
                    }
                }
              
                $scope.edit = function(id) {
                    if($scope.edit_route) {
                        $state.go($scope.edit_route, {id : id})
                    } else {
                        $state.go('inventory.retur.edit', {id : id})
                    }
                }

                $scope.searchData = function() {
                    if($scope.is_merchandise) {
                        $scope.formData.is_merchandise = $scope.is_merchandise
                    }
                    if($scope.isPallet) {
                        $scope.formData.is_pallet = $scope.isPallet
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