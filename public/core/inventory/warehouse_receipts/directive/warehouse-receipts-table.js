warehouseReceipts.directive('warehouseReceiptsTable', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            purchase_order_id : '=purchaseOrderId',
            is_purchase_order : '=isPurchaseOrder',
            is_merchandise : '=isMerchandise',
            hide_customer : "=hideCustomer",
            isPallet : '=isPallet',
            itemMigrationId : '=itemMigrationId',
            voyageScheduleId : '=voyageScheduleId',
            salesOrderReturnId : '=salesOrderReturnId',
            addRoute : '=addRoute',
            detailRoute : '=detailRoute',
            editRoute : '=editRoute',
            addParams : '=addParams'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_receipts/view/warehouse-receipts-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state) {  
            $('.ibox-content').addClass('sk-loading');
            $scope.is_admin = authUser.is_admin;

              $scope.delete=function(ids) {
                  var cfs=confirm("Apakah Anda Yakin?");
                  if (cfs) {
                    $http.delete(baseUrl+'/operational_warehouse/receipt/' + ids).then(function(data) {
                      toastr.success("Data berhasil dihapus","Selamat !")
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

              $scope.formData = {};

                var columnDefs = [
                    { "title" : "No. BSTB" },
                    { "title" : $rootScope.solog.label.general.branch },
                    { "title" : $rootScope.solog.label.general.warehouse },
                ]

                if(!$scope.hide_customer) {
                    columnDefs.push({ "title" : $rootScope.solog.label.general.customer })
                }

                columnDefs = columnDefs.concat([
                    { "title" : $rootScope.solog.label.general.receive_date },
                    { "title" : "Selesai Stripping" },
                    { "title" : $rootScope.solog.label.general.total_item },
                    { "title" : $rootScope.solog.label.general.status },
                    { "title" : "" }
                ]);

                columnDefs = columnDefs.map((c, i) => {
                    c.targets = i
                    return c
                })

              var columns = [
                  {
                    data:null,
                    name:"code",
                    className : 'font-bold',
                    render : function(resp) {
                        if($rootScope.roleList.includes('inventory.receipt.detail')) {
                            return '<a ng-click="show(' + resp.id + ')">' + resp.code + '</a>'
                        } else {
                            return resp.code
                        }
                    }
                  },
                  {
                    data: "company.name",
                    name: "company.name"
                  },
                  {
                    data: "warehouse.name",
                    name: "warehouse.name"
                  }]

                  if(!$scope.hide_customer) {
                    columns.push({
                          data: "customer.name",
                          name: "customer.name",
                          className: "font-bold"
                    })
                  }

                  columns = columns.concat([
                        {
                          data: null,
                          name : 'receive_date',
                          searchable: false,
                          render: function (resp) {
                            var date = resp.receive_date.split(' ')
                            return $filter('fullDate')(date[0]) + ' ' + date[1]
                          }
                        },
                        {
                          data: null,
                          name : 'stripping_done',
                          searchable: false,
                          render: function (resp) {
                            return $filter('fullDate')(resp.stripping_done)
                          }
                        },
                        {
                          data: "total",
                          name: "det.total",
                          className: "text-right"
                        },
                        {
                          data: null,
                          name: "status",
                          className: "text-center",
                          render: function (resp) {
                            var status = resp.status == 0 ? 'Draft' : (resp.status == 1 ? 'Disetujui' : 'Dibatalkan');
                            var className = resp.status == 0 ? 'badge-warning' : (resp.status == 1 ? 'badge-primary' : 'badge-danger');
                            var outp = "<span class='badge " + className + "'>" + status + "</span>";
                  
                            return outp;
                          }
                        },
                        {
                          data: null,
                          searchable: false,
                          orderable: false,
                          className: "text-center",
                          render: function (item) {
                              item.action = null
                              var html = ''
                              if(item.status == 0 || item.status == 2) {   
                                  html += '<span><warehouse-receipts-approve-button on-submit="searchData()" id="' + item.id + '" /></span>&nbsp;&nbsp;';
                              }
                              
                              html += '<a title="Detail" ng-show="$root.roleList.includes(\'inventory.receipt.detail\')" ng-click="show(' + item.id + ')"><i class="fa fa-folder-o"></i></a>&nbsp;&nbsp;';
                  
                              if(item.status == 0 || item.status == 2) {                
                                  html += '<span><warehouse-receipts-edit-button id="' + item.id + '" /></span>';
                              }
                              
                              if(item.status != 1) {
                                  html += "&nbsp;&nbsp<a ng-click='delete(" + item.id + ")' data-toggle='tooltip' title='Hapus'><span class='fa fa-trash'></span>&nbsp;&nbsp;</a>";
                              }
                  
                              return html
                          }
                        }
                      ])

              oTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX : false,
                order: [
                  [4, 'desc'],
                  [0, 'desc']
                ],
                scrollX: true,
                dom: 'Blfrtip',
                lengthMenu: [
                  [10, 25, 50, 100, -1],
                  [10, 25, 50, 100, 'All']
                ],
                buttons: [{
                    extend: 'excel',
                    enabled: true,
                    action: newExportAction,
                    text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                    className: 'btn btn-default btn-sm pull-right',
                    filename: 'Penerimaan',
                    messageTop: 'Penerimaan',
                    sheetName: 'Data',
                    title: 'Penerimaan',
                    exportOptions: {
                        rows: {
                            selected: true
                        }
                    },
                }],
                ajax: {
                  headers: {
                    'Authorization': 'Bearer ' + authUser.api_token
                  },
                  url: baseUrl + '/api/operational_warehouse/warehouse_receipt_datatable',
                  dataSrc: function (d) {
                    $('.ibox-content').removeClass('sk-loading');
                    return d.data;
                  },
                  data: e => Object.assign(e, $scope.formData)
                },
                columnDefs: columnDefs,
                columns: columns,
                createdRow: function (row, data, dataIndex) {
                  $(row).find('td').css('cursor', 'context-menu')
                  if(data.status == 0) {
                      $(row).addClass('text-warning')
                  } else if(data.status == 2) {
                      $(row).addClass('text-danger')        
                  }
                  $compile(angular.element(row).contents())($scope);
                }
              });

                $scope.$watch(function() {
                    $scope.formData.purchase_order_id = $scope.purchase_order_id
                })

                $compile($('thead'))($scope)

                oTable.buttons().container().appendTo('#export_button');

                $scope.show = function(id) {
                    if($scope.detailRoute) {
                        $state.go($scope.detailRoute, {id : id})
                    } else {
                        $rootScope.insertBuffer()
                        $state.go('operational_warehouse.receipt.show', {id : id})
                    }
                }

                $scope.add = function() {
                    $rootScope.insertBuffer()
                    if($scope.addRoute) {
                        $state.go($scope.addRoute, $scope.addParams)
                    } else {
                        $state.go("operational_warehouse.receipt.create")
                    }
                }

              $scope.adjustField = function() {
                    $scope.hide_warehouse = false
                    $scope.hide_add = false
                    $scope.hide_branch_filter = false
                    $scope.hide_customer_filter = false
                    if($scope.warehouse_id || $attrs.hideWarehouse) {
                        $scope.hide_warehouse = true
                        $scope.hide_add = true
                    }
                    if($attrs.hideAdd) {
                        $scope.hide_add = true
                    }
                    if($attrs.hideBranchFilter) {
                        $scope.hide_branch_filter = true
                    }
                    if($attrs.hideCustomerFilter) {
                        $scope.hide_customer_filter = true
                    }
              }

                $scope.adjustField()

                $scope.searchData = function () {
                    if($scope.isPallet) {
                        $scope.formData.is_pallet = 1
                    }
                    if($scope.is_purchase_order) {
                        $scope.formData.is_purchase_order = 1
                    }
                    if($scope.is_merchandise) {
                        $scope.formData.is_merchandise = $scope.is_merchandise
                    }
                    if($scope.itemMigrationId) {
                        $scope.formData.item_migration_id = $scope.itemMigrationId
                    }
                    if($scope.voyageScheduleId) {
                        $scope.formData.voyage_schedule_id = $scope.voyageScheduleId
                    }
                    if($scope.salesOrderReturnId) {
                        $scope.formData.sales_order_return_id = $scope.salesOrderReturnId
                    }
                    oTable.ajax.reload();
                }

                $scope.$watch('itemMigrationId', function(){
                    $scope.searchData()
                })

                $scope.$watch('voyageScheduleId', function(){
                    $scope.searchData()
                })

                $scope.resetFilter = function () {
                    $scope.formData = {};
                    $scope.searchData()
                }

              $scope.$on('reloadWarehouseReceipt', function (e, v) {
                $scope.searchData()
              })

              if($attrs['warehouseId']) {
                  $scope.$watch('warehouse_id', function(){
                      $scope.formData.warehouse_id = $scope.warehouse_id
                      $scope.searchData()
                      $scope.adjustField()
                  })
              }


        }
    }
});