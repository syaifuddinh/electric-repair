warehouseReceipts.directive('warehouseReceiptsShow', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            purchase_order_id : '=purchaseOrderId',
            indexRoute : '=indexRoute',
            storeUrl : '=storeUrl',
            id : '=id',
            voyageScheduleId : '=voyageScheduleId',
            showReceiptType : '=showReceiptType',
            receiptTypeCode : '=receiptTypeCode',
            isPallet : '=isPallet'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_receipts/view/warehouse-receipts-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, warehouseReceiptsService, racksService, unitsService, emailService) {  

            emailService.api.show(function(dt){
                if(dt) {
                    $scope.receipt_subject = dt.receipt_subject
                }
            })

            $scope.back = function() {
                if($scope.indexRoute) {
                    $state.go($scope.indexRoute)
                } else {
                    
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $state.go('operational_warehouse.receipt')
                    }
                }

            }
            
            $scope.previewEmail = function() {
                $rootScope.disBtn = true;
                $http.get(baseUrl+'/operational_warehouse/receipt/' + $scope.id + '/preview_email').then(function(data) {
                    $rootScope.disBtn = false;
                    $('#email_preview').html(data.data)
                    $('#modalPreviewEmail').modal()
                }, function(error) {
                      $rootScope.disBtn=false;
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

            $scope.sendEmail = function() {
                $rootScope.disBtn = true;
                $http.post(baseUrl+'/operational_warehouse/receipt/' + $scope.id + '/send_email').then(function(data) {
                    $rootScope.disBtn = false;
                    toastr.success(data.data.message)
                }, function(error) {
                      $rootScope.disBtn=false;
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

            $scope.delete=function(ids) {
              var cfs=confirm("Apakah Anda Yakin?");
              if (cfs) {
                $http.delete(baseUrl+'/operational_warehouse/receipt/' + ids).then(function(data) {
                  toastr.success("Data berhasil dihapus","Selamat !")
                  $state.go('operational_warehouse.receipt')
                }, function(error) {
                  $rootScope.disBtn=false;
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

            $scope.cariItem = function () {
                if ($scope.detailData.is_exists != -1) {
                    $('#modalItem').modal('hide')
                    setTimeout(function () {
                        $('#modalItemWarehouse').modal('show')
                    }, 400);
                    oTable.ajax.reload();
                }
            }

            $scope.showPrint = function () {
                window.open(baseUrl + '/operational_warehouse/receipt/print/' + $scope.id);
            }

  $scope.cancel = function() {
      var isCancel = confirm('Apakah anda yakin ?')
      if(isCancel) {
          $rootScope.disBtn = true
          $http.put(baseUrl + '/operational_warehouse/receipt/' + $scope.item.id + '/cancel').then(function (data) {
              $rootScope.disBtn = false
              toastr.success(data.data.message)
              $scope.show()
          }, function (error) {
              $rootScope.disBtn = false;
              if (error.status == 422) {
                var det = "";
                angular.forEach(error.data.errors, function (val, i) {
                  det += "- " + val + "<br>";
                });
                toastr.warning(det, error.data.message);
              } else {
                toastr.error(error.data.message, "Error Has Found !");
              }
          });
      }
  }

  $scope.cariPallet = function() {


    $('#modalItem').modal('hide')
    setTimeout(function () {
      $('#modalPallet').modal('show')

    }, 400);
    item_pallet_datatable.ajax.reload();

  }

  $scope.switchPallet = function () {
    $scope.detailData.pallet_id = $scope.detailData.is_use_pallet == 0 ? null : $scope.detailData.pallet_id;
    $scope.detailData.pallet_name = $scope.detailData.is_use_pallet == 0 ? null : $scope.detailData.pallet_name;
    $scope.detailData.pallet_qty = $scope.detailData.is_use_pallet == 0 ? null : $scope.detailData.pallet_qty;
  }

  $scope.chooseItem = function (json) {
    $('#modalItemWarehouse').modal('hide')
    setTimeout(function () {
      $('#modalItem').modal();
    }, 400);
    $scope.detailData.item_id = json.id
    $scope.detailData.item_name = json.name + ' (' + json.code + ')'
    $scope.detailData.barcode = json.barcode;
    $scope.detailData.long = json.long;
    $scope.detailData.wide = json.wide;
    $scope.detailData.high = json.height;
    $scope.detailData.weight = json.tonase;
  }

    $scope.choosePallet = function (json) {
        $('#modalPallet').modal('hide')
        setTimeout(function () {
            $('#modalItem').modal();
        }, 400);
        $scope.detailData.pallet_id = json.id
        $scope.detailData.pallet_name = json.name + ' (' + json.code + ')'
    }

  $scope.warehouseChange = function (id) {
    $scope.racks = []
    angular.forEach($scope.data.rack, function (val, i) {
      if (id == val.warehouse_id) {
        $scope.racks.push({
          id: val.id,
          name: val.name
        })
      }
    });

  }

  $scope.imposition = [{
      id: 1,
      name: 'Kubikasi'
    },
    {
      id: 2,
      name: 'Tonase'
    },
    {
      id: 3,
      name: 'Item'
    },
    {
      id: 4,
      name: 'Borongan'
    },
  ]
  $scope.is_export = [{
      id: 1,
      name: 'Export'
    },
    {
      id: 0,
      name: 'Local'
    },
  ]
  $scope.is_overtime = [{
      id: 1,
      name: 'YA'
    },
    {
      id: 0,
      name: 'TIDAK'
    },
  ]

  $(".signature").jSignature({
    height: 200
  });

  $scope.adjustField = function() {
        $scope.show_purchase_order = false
        $scope.show_customer = false
        $scope.show_shipper = false
        $scope.show_consignee = false
        $scope.show_destination = false
        $scope.show_imposition = false
        $scope.show_lembur = false
        $scope.show_vehicle_type = false
        $scope.show_reff_no = false
        $scope.show_driver = false
        $scope.show_phone_number = false
        $scope.show_nopol = false
        switch($scope.receipt_type_code) {
            case 'r01' :
                $scope.show_vehicle_type = true
                $scope.show_purchase_order = true
                $scope.show_driver = true
                $scope.show_phone_number = true
                $scope.show_nopol = true
                break
            case 'r04' :
                $scope.show_driver = true
                $scope.show_phone_number = true
                $scope.show_nopol = true
                $scope.show_vehicle_type = true
                $scope.show_reff_no = true
                $scope.show_customer = true
                $scope.show_shipper = true
                $scope.show_consignee = true
                $scope.show_destination = true
                $scope.show_imposition = true
                break
            case 'r10' :
                $scope.show_customer = true
                break
        }
  }

    $scope.show = function() {
        if($scope.id) {
            $http.get(baseUrl + '/operational_warehouse/receipt/' + $scope.id).then(function (data) {
                $scope.item = data.data.item;
                $scope.receipt_type_code = $scope.item.receipt_type_code
                $scope.detail = data.data.detail;
                $scope.receipt_type_code = $scope.item.receipt_type_code
                $scope.adjustField()
                $('.ibox-content').removeClass('sk-loading');
                $(".signature").jSignature('setData', JSON.parse($scope.item.ttd), 'native');
                var base64 = $('.signature').jSignature('getData', 'default');
                var img = $('<img>');
                img.attr('src', base64);
                img.css('width', '10cm');
                img.css('height', 'auto');
                $('.signature').replaceWith(img);

                if($scope.item.item_migration_id) {
                    $scope.$emit("getItemMigrationId", $scope.item.item_migration_id)
                }

                if($scope.item.voyage_schedule_id) {
                    $scope.$emit("getVoyageScheduleId", $scope.item.voyage_schedule_id)
                }
            });
        }
    }

    $scope.show()

  $scope.create = function () {

    $http.get(baseUrl + '/operational_warehouse/receipt/create').then(function (data) {
      $scope.data = data.data;
      $http.get(baseUrl + '/operational_warehouse/receipt/' + $scope.id).then(function (data) {
        $scope.item = data.data.item;
        $scope.detail = data.data.detail;
        $scope.surat_jalan = data.data.surat_jalan;
        $scope.warehouseChange($scope.item.warehouse.id);
        $('.ibox-content').removeClass('sk-loading');
        oTable = $('#pallet_datatable').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
            headers: {
              'Authorization': 'Bearer ' + authUser.api_token
            },
            url: baseUrl + '/api/operational_warehouse/general_item_datatable'
          },
          columns: [{
              data: "action_choose_item",
              name: "created_at",
              className: "text-center"
            },
            {
              data: "code",
              name: "code"
            },
            {
              data: "name",
              name: "name"
            },
            {
              data: "barcode",
              name: "barcode",
              className: 'hidden'
            },
            {
              data: "piece.name",
              name: "piece.name"
            },
            {
              data: "description",
              name: "description"
            },
          ],
          createdRow: function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
          }
        });
        item_pallet_datatable = $('#item_pallet_datatable').DataTable({
          processing: true,
          serverSide: true,
          ajax: {
            headers: {
              'Authorization': 'Bearer ' + authUser.api_token
            },
            url: baseUrl + '/api/operational_warehouse/master_pallet_datatable'
          },
          columns: [{
              data: "action_choose",
              name: "created_at",
              className: "text-center"
            },
            {
              data: "code",
              name: "code"
            },
            {
              data: "name",
              name: "name"
            },
            {
              data: "description",
              name: "description"
            },
          ],
          createdRow: function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
          }
        });
      }, function () {
        $scope.create()
      });
    }, function () {
      $scope.create()
    });
  }
  $scope.create()

  $scope.deleteItem = function (id) {

    var cofs = confirm("Apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $http.delete(baseUrl + '/operational_warehouse/receipt/delete_item/' + id + '?_token' + csrfToken).then(function (data) {
      $state.reload();
      toastr.success("Item telah dihapus !");
    })

  }

  $scope.addItem = function () {
    $scope.is_edit = 0;
    $scope.detailData = {};
    $('#modalItem').modal();
  }

  $scope.editItem = function (val) {

    $scope.detailData = {}
    $scope.detailData.id = val.id;
    $scope.detailData.is_exists = parseInt(val.is_exists);
    $scope.detailData.reff_no = val.no_reff;
    $scope.detailData.manifest_no = val.no_manifest;
    $scope.detailData.no_surat_jalan = val.no_surat_jalan;
    $scope.detailData.item_id = val.item_id;
    $scope.detailData.item_name = val.item_name;
    $scope.detailData.kemasan = val.kemasan;
    $scope.detailData.barcode = val.barcode;
    $scope.detailData.long = val.long;
    $scope.detailData.wide = val.wide;
    $scope.detailData.high = val.high;
    $scope.detailData.weight = val.weight;
    $scope.detailData.vehicle_type_id = val.vehicle_type_id;
    $scope.detailData.qty = val.qty;
    $scope.detailData.storage_type = val.storage_type;
    $scope.detailData.rack_id = val.rack_id;
    $scope.detailData.imposition = val.imposition;
    $scope.detailData.total_tonase = val.weight;
    $scope.detailData.total_volume = val.volume;
    $scope.detailData.description = val.description;
    $scope.detailData.is_use_pallet = val.pallet_id == null ? 0 : 1;
    $scope.detailData.pallet_id = val.pallet_id;
    $scope.detailData.pallet_name = val.pallet ? val.pallet.name : '';
    $scope.detailData.pallet_qty = val.pallet_qty;
    $scope.is_edit = 1;

    var request = {
      warehouse_id: $scope.item.warehouse.id,
      no_surat_jalan: val.no_surat_jalan,
      item_id: val.item_id,
      is_handling_area: 1
    };


    request = $.param(request);
    $http.get(baseUrl + '/inventory/item/cek_stok_warehouse?' + request).then(function (data) {
      $scope.itemData.stock = data.data.stok;
    });
    $("#modalItem").modal();

  }

  $scope.submitItem = function () {
    $rootScope.disBtn = true;
    // $scope.itemData.rack_id = $scope.rack_id;
    $scope.detailData.warehouse_id = $scope.item.warehouse_id;
    if ($scope.is_edit == 0) {
      var url_target = baseUrl + '/operational_warehouse/receipt/store_detail/' + $scope.id + '?_token=' + csrfToken;
      var method = 'post';
    } else {
      var url_target = baseUrl + '/operational_warehouse/receipt/update_detail/' + $scope.detailData.id + '?_token=' + csrfToken;
      var method = 'put';
    }
    $http[method](url_target, $scope.detailData).then(function (data) {
      // $state.go('operational.job_order');
      $('#modalItem').modal('hide');
      $timeout(function () {
        $scope.is_edit = 1;
        $state.reload();
      }, 1000)
      toastr.success("Item Barang berhasil disimpan!");
      $rootScope.disBtn = false;
    }, function (error) {
      $rootScope.disBtn = false;
      if (error.status == 422) {
        var det = "";
        angular.forEach(error.data.errors, function (val, i) {
          det += "- " + val + "<br>";
        });
        toastr.warning(det, error.data.message);
      } else {
        toastr.error(error.data.message, "Error Has Found !");
      }
    });
  }

        }
    }
});