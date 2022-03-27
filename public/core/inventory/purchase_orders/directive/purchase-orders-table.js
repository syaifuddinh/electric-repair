purchaseOrders.directive('purchaseOrdersTable', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            company_id : '=companyId',
            is_pallet : '=isPallet',
            is_merchandise : '=isMerchandise',
            add_route : '=addRoute',
            edit_route : '=editRoute',
            detail_route : '=detailRoute',
            is_approved : '=isApproved',
            tableOnCreated : '='
        },
        require:'ngModel',
        templateUrl: '/core/inventory/purchase_orders/view/purchase-orders-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, purchaseOrdersService) {
            $scope.formData = {};
            $scope.formData.is_pallet = $scope.is_pallet
            $('.ibox-content').addClass('sk-loading');
            var columnDefs = [
                {title : $rootScope.solog.label.purchase_order.code },
                {title : $rootScope.solog.label.purchase_order.date },
                {title : $rootScope.solog.label.general.branch },
                {title : $rootScope.solog.label.general.supplier },
                {title : $rootScope.solog.label.general.status },
                {title : '' }
            ]

                var columns = [
                  {data:"code",name:"purchase_orders.code",className:"font-bold"},
                  {
                    data:null,
                    name:"purchase_orders.po_date",
                    searchable:false,
                    render:resp => $filter('fullDate')(resp.po_date)
                  },
                  {data:"company_name",name:"companies.name"},
                  {data:"supplier_name",name:"contacts.name",className:"font-bold"},
                  {
                        data:"status_name",
                        name:"purchase_order_statuses.name",
                  },
                  { 
                    data:null,
                    searchable:false,
                    orderable:false,
                    className:"text-center",
                    render : function(resp) {
                        var r = ''
                        if(resp.status <= 1) 
                            r += "<a  ng-click='edit(" + resp.id + ")' data-toggle='tooltip' title='Edit Data'><i class='fa fa-edit'></i></a>&nbsp;&nbsp";

                        r += "<a ng-show=\"$root.roleList.includes('inventory.purchase_order.detail')\" ng-click='show(" + resp.id + ")' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp";
                        r += "<a ng-click='delete(" + resp.id + ")' ><span class='fa fa-trash-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp";

                        return r
                    }
                  }
                ]

                $scope.show =  function(id) {
                    if($scope.detail_route) {
                        $state.go($scope.detail_route, {id:id})
                    } else {
                        $rootScope.insertBuffer()
                        $state.go('inventory.purchase_order.show', {id:id})
                    }
                }

                $scope.edit =  function(id) {
                    if($scope.edit_route) {
                        $state.go($scope.edit_route, {id:id})
                    } else {
                        $rootScope.insertBuffer()
                        $state.go('inventory.purchase_order.edit', {id:id})
                    }
                }

                $scope.delete = function(id) {
                    var cfs=confirm("Are you sure ?");
                    if (cfs) {
                        purchaseOrdersService.api.destroy(id, () => {
                            $scope.searchData()
                        })
                    }
                }

                $scope.adjustEl = function() {
                    $scope.hide_add = false
                    $scope.hide_status = false
                    $scope.hide_branch_filter = false
                    if($attrs.hideAdd) {
                        $scope.hide_add = true
                    }
                    if($attrs.hideAction) {
                        columns.pop()
                        columnDefs.pop()
                    }
                    if($attrs.hideStatus) {
                        $scope.hide_status = true
                        columns.pop()
                        columnDefs.pop()
                    }
                    if($attrs.hideBranchFilter) {
                        $scope.hide_branch_filter = true
                    }
                }
                $scope.adjustEl()


                var datatableUrl = purchaseOrdersService.url.datatable()
                if($scope.is_pallet) {
                    datatableUrl = purchaseOrdersService.url.palletDatatable()
                }
                
                var options = {
                    order : [[1, 'desc'], [0, 'desc']],
                    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
                    ajax: {
                        headers : {'Authorization' : 'Bearer '+authUser.api_token},
                        url : datatableUrl,
                        data : e => Object.assign(e, $scope.formData),
                        dataSrc: function(d) {
                            $('.ibox-content').removeClass('sk-loading');
                            return d.data;
                        }
                    },
                    columnDefs : columnDefs,
                    columns : columns              
                }

              $scope.options =options
              $compile($('thead'))($scope)

              if($scope.tableOnCreated) {
                 $scope.createdRow = $scope.tableOnCreated
              }

                $scope.searchData = function() {
                    if($scope.is_merchandise) {
                        $scope.formData.is_merchandise = 1
                    }

                    if($scope.is_approved) {
                        $scope.formData.is_approved = $scope.is_approved
                    }

                    $scope.options.datatable.ajax.reload();
                }

                $scope.$on('reloadPurchaseOrder', function(e, v){
                    if(v.company_id) {
                        $scope.formData.company_id = v.company_id
                    }
                    if(v.status) {
                        $scope.formData.status = v.status
                    }
                    $scope.searchData()
                })

              $timeout(function() {
                  $scope.searchData()
              }, 900)

            $scope.add = function() {
                if($scope.add_route) {
                    $state.go($scope.add_route)
                } else {
                    if($scope.is_pallet == 1) {
                        $state.go('operational_warehouse.pallet_purchase_order.create')
                    } else {
                        $state.go('inventory.purchase_order.create')
                    }
                }
            }

              $scope.choose = function(id) {
                    purchaseOrdersService.api.show(id, function(dt){
                        $scope.$emit('choosePurchaseOrder', dt)
                    })
                }

              $scope.resetFilter = function() {
                $scope.formData = {};
                $scope.searchData()
              }
        }
    }
});