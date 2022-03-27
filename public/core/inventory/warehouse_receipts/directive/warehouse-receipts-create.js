warehouseReceipts.directive('warehouseReceiptsCreate', function () {
    return {
        restrict: 'E',
        scope: {
            warehouse_id : '=warehouseId',
            purchase_order_id : '=purchaseOrderId',
            indexRoute : '=indexRoute',
            indexParams : '=indexParams',
            storeUrl : '=storeUrl',
            voyageScheduleId : '=voyageScheduleId',
            itemMigrationId : '=itemMigrationId',
            salesOrderReturnId : '=salesOrderReturnId',
            showReceiptType : '=showReceiptType',
            receiptTypeCode : '=receiptTypeCode',
            hide_warehouse_add_button : '=hideWarehouseAddButton',
            is_merchandise : '=isMerchandise',
            isPallet : '=isPallet'
        },
        require:'ngModel',
        templateUrl: '/core/inventory/warehouse_receipts/view/warehouse-receipts-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, warehouseReceiptsService, racksService, unitsService, receiptTypesService, purchaseOrdersService) {  

            $scope.formData = {}
            $scope.formData.detail = []
            $scope.detailData = {
                is_exists: null
            }
            $scope.formData.is_export = 1
            $scope.formData.is_overtime = 0
            $scope.formData.stripping_type = 1
            $scope.formData.company_id = compId
            $scope.formData.receive_date = dateNow
            $scope.formData.receive_time = timeNow
            $scope.formData.stripping_date = dateNow
            $scope.formData.stripping_time = timeNow
            $scope.required_close_btn = true;
            $scope.optional_files = false;
            $scope.files = [];
            if($scope.voyageScheduleId) {
                $scope.formData.voyage_schedule_id = $scope.voyageScheduleId
            }
            if($scope.salesOrderReturnId) {
                $scope.formData.sales_order_return_id = $scope.salesOrderReturnId
            }
            if($scope.itemMigrationId) {
                $scope.formData.item_migration_id = $scope.itemMigrationId
            }
            if($scope.isPallet) {
                $scope.formData.is_pallet = 1
            }

            $scope.back = function() {
                if($rootScope.hasBuffer()) {
                    $rootScope.accessBuffer()
                } else {
                    if($scope.indexRoute) {
                        $state.go($scope.indexRoute, $scope.indexParams)
                    } else {
                        $state.go('operational_warehouse.receipt')
                    }
                }
            }

            $scope.downloadImportData = function() {
                window.open(warehouseReceiptsService.url.downloadImportItem())
            }

            $scope.importItem = function() {
                var fd = new FormData()
                fd.append('file', $('#import_item_file')[0].files[0])
                fd.append('warehouse_id', $scope.formData.warehouse_id)
                fd.append('is_pallet', $scope.formData.is_pallet)
                warehouseReceiptsService.api.importItem(fd, function(dt){
                    var x
                    for(x in dt) {
                        $scope.detailData = dt[x]
                        $scope.appendTable()
                    }
                })
            }

            $('#import_item_file').change(function(){
                $scope.importItem()
            })

            $scope.showPiece = function() {
                if($scope.detailData.piece_id) {
                    unitsService.api.show($scope.detailData.piece_id, function(dt){
                        $scope.detailData.piece_name = dt.name
                    })
                }
            }

            $scope.showPiece2 = function() {
                if($scope.detailData.piece_id_2) {
                    unitsService.api.show($scope.detailData.piece_id_2, function(dt){
                        $scope.detailData.piece_name_2 = dt.name
                    })
                }
            }


            $scope.$on('getReceiptType', function(e, v){
                $scope.receipt_type_code = v.code
                $scope.adjustField()
            })

            $scope.$on('getPurchaseOrder', function(e, v){
                if(!$scope.formData.warehouse_id) {
                    $scope.formData.warehouse_id = v.warehouse_id
                }

                unitsService.api.show($rootScope.settings.work_order.default_piece_id, function(piece){
                    purchaseOrdersService.api.showDetail(v.id, (dt) => {
                        dt.forEach((unit) => {
                            $scope.detailData.purchase_order_detail_id = unit.id
                            $scope.detailData.item_name = unit.item_name
                            $scope.detailData.item_id = unit.item_id
                            $scope.detailData.long = unit.long
                            $scope.detailData.wide = unit.wide
                            $scope.detailData.high = unit.high
                            $scope.detailData.tonase = unit.tonase
                            $scope.detailData.piece_name = piece.name
                            $scope.detailData.storage_type = 'HANDLING'
                            $scope.detailData.qty = 1
                            $scope.detailData.piece_id = $rootScope.settings.work_order.default_piece_id
                            $scope.appendTable()
                        })
                    })
                })
            })

            $scope.countColumnQty = function() {
                $scope.column_qty = $('#detail_header th').length
            }
            $scope.countColumnQty()

            $scope.getSuggestion = function() {
                racksService.api.suggestionDescending($scope.formData.warehouse_id, [], function(rack_id){
                    $scope.detailData.rack_id = rack_id
                    racksService.api.show(rack_id, function(dt){
                        $scope.detailData.rack_name = dt.code
                    })
                })
            }

          $scope.adjustField = function() {
                var purchase_order_id = $scope.formData.purchase_order_id

                $scope.formData.purchase_order_id = null
                $scope.show_purchase_order = false
                $scope.show_customer = false
                $scope.show_shipper = false
                $scope.show_consignee = false
                $scope.show_destination = false
                $scope.show_imposition = false
                $scope.show_lembur = false
                $scope.show_reff_no = false
                $scope.show_vehicle_type = false
                $scope.show_driver = false
                $scope.show_phone_number = false
                $scope.show_nopol = false
                $scope.show_receipt_type = false
                $scope.show_manual_input_item = false
                $scope.show_import = false
                switch($scope.receipt_type_code) {
                    case 'r01' :
                        $scope.show_driver = true
                        $scope.show_phone_number = true
                        $scope.show_nopol = true
                        $scope.show_purchase_order = true
                        $scope.show_vehicle_type = true
                        $scope.formData.purchase_order_id = purchase_order_id
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
                        $scope.show_manual_input_item = true
                        $scope.show_import = true
                        break
                    case 'r10' :
                        $scope.show_customer = true
                        $scope.show_manual_input_item = true
                        $scope.show_import = true
                        break
                }

                if($scope.showReceiptType !== null && $scope.showReceiptType !== undefined) {
                    $scope.show_receipt_type = $scope.showReceiptType
                } else {
                    $scope.show_receipt_type = true
                }
                $scope.countColumnQty()


                if($scope.receiptTypeCode) {
                    $scope.receipt_type_code = $scope.receiptTypeCode
                    $scope.formData.receipt_type_id = 1
                    receiptTypesService.api.showBySlug($scope.receiptTypeCode, function(dt){
                        $scope.formData.receipt_type_id = dt.id
                    })
                }
          }
          $scope.adjustField()

          if($scope.receiptTypeCode) {
              $scope.adjustField()
          }
          
          $scope.insertWarehouse = function() {
              $rootScope.insertBuffer()
              $state.go('operational_warehouse.setting.warehouse')
          }

            if(window.google) {

                new google.maps.places.Autocomplete(
                ($('[ng-model="formData.city_to"]')[0]), {
                    types: []
                });
            }

          $scope.tipeKemasan = [{
              id: 1,
              name: "Carton"
            },
            {
              id: 2,
              name: "Case"
            },
            {
              id: 3,
              name: "Peti"
            },
            {
              id: 4,
              name: "Pallet"
            },
            {
              id: 5,
              name: "Bag"
            }
          ]

          var signature_driver = $(".signature").jSignature({
            height: 200
          });

          $scope.resetSignature = function () {
            $(".signature").jSignature('clear');
          }

            $scope.switchPallet = function () {
                $scope.detailData.pallet_id = $scope.detailData.is_use_pallet == 0 ? null : $scope.detailData.pallet_id;
                $scope.detailData.pallet_name = $scope.detailData.is_use_pallet == 0 ? null : $scope.detailData.pallet_name;
                $scope.detailData.pallet_qty = $scope.detailData.is_use_pallet == 0 ? null : $scope.detailData.pallet_qty;
            }

            $scope.selectRack = function (dt) {
                $scope.selected_rack = dt
                $scope.detailData.rack_name = dt.name
            }

            $scope.$on('getRack', function(e, v){
                $scope.selectRack(v)
            })

          file_upload = $('#file_upload').dropzone({
            acceptedFiles: 'image/*',
            init: function () {
              this.on("addedfile", function (file) {
                $scope.files.push(file);
                $scope.validateForm();

              });

              this.on("removedfile", function (file) {
                for (x in $scope.files) {
                  if ($scope.files[x].upload.uuid == file.upload.uuid) {
                    $scope.files.splice(x, 1);
                  }
                }
                console.log($scope.files);
                $scope.validateForm();

              });
            }
          });

          $scope.validateDetail = function () {
            if (parseInt($scope.detailData.qty) > 0) {
              $scope.disAppendBtn = false;
            } else {
              $scope.disAppendBtn = true;

            }
          }

          var item_pallet_datatable = $('#item_pallet_datatable').DataTable({
            processing: true,
            serverSide: true,
            scrollX: false,
            initComplete: null,
            ajax: {
              headers: {
                'Authorization': 'Bearer ' + authUser.api_token
              },
              url: baseUrl + '/api/operational_warehouse/master_pallet_datatable',
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

          $scope.cariItem = function () {
            if ($scope.detailData.is_exists != -1) {
                $scope.$broadcast('showItemsModal')
            }
          }

          $scope.cariPallet = function () {


            $('#modalPallet').modal()
            item_pallet_datatable.ajax.reload();

          }

            $scope.choosePallet = function (json) {
                $('#modalPallet').modal('hide')
                $scope.detailData.pallet_id = json.id
                $scope.detailData.pallet_name = json.name + ' (' + json.code + ')'
            }

            $scope.chooseItem = function (json) {
                $scope.detailData.item_id = json.id
                $scope.detailData.item_name = json.name + ' (' + json.code + ')'
                $scope.detailData.barcode = json.barcode;
                $scope.detailData.long = json.long;
                $scope.detailData.wide = json.wide;
                $scope.detailData.high = json.height;
                $scope.detailData.weight = json.tonase;
            }
            $scope.$on('getItem', function(e, v) {
                $scope.chooseItem(v)
            })

          delete_preview = function (dom) {
            var card = $(dom).parents('.card');
            card.find('.card-body').html('');
            var inputfile = card.find('input');
            var fileclone = inputfile.val('').clone();
            inputfile.replaceWith(fileclone);
            optional_upload();
          }

          preview_image = function (dom) {
            var reader = new FileReader();
            var card = $(dom).parents('.card');
            var card_body = card.find('.card-body');
            reader.onload = function (e) {
              var img = $("<img style='width:100%;height:auto;'>");
              img.attr('src', e.target.result);
              card_body.append(img);
              optional_upload();
            }

            reader.readAsDataURL(dom.files[0]);
          }

          $scope.getPenerima = function () {
            $http.get(baseUrl + '/contact/contact/penerima').then(function (data) {
              $scope.data.penerima = data.data;
            }, function () {
              $scope.getPenerima()
            });
          }

          $scope.getData = function () {
            $http.get(baseUrl + '/operational_warehouse/receipt/create').then(function (data) {
              $scope.data = data.data;

              $scope.companyChange(compId);
              $scope.resetDetail();
              $('.ibox-content').removeClass('sk-loading');
              $scope.getPenerima()
            }, function () {
              $scope.getData()
            });
          }
          $scope.getData()

          $scope.showCustomer = function() {
              $http.get(baseUrl+'/contact/contact/customer').then(function(data) {
                $scope.customers=data.data;
              }, function(){
                  $scope.showCustomer()
              });
          }
          $scope.showCustomer()

          $scope.companyChange = function (sts) {
            $scope.warehouses = []


            angular.forEach($scope.data.warehouse, function (val, i) {
              if (sts == val.company_id) {
                $scope.warehouses.push({
                  id: val.id,
                  name: val.name
                })
              }
            });

          }

          $scope.customerChange = function (id) {
            $scope.contact_address = []
            $http.get(baseUrl + '/operational/job_order/cari_address/' + id).then(function (data) {
              angular.forEach(data.data.address, function (val, i) {
                $scope.contact_address.push({
                  id: val.id,
                  name: val.name + ', ' + val.address,
                  collectible_id: val.contact_bill_id
                })
              });

              $scope.formData.collectible_id = $rootScope.findJsonId(id, $scope.contact_address).collectible_id;
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

            $scope.urut = 0
            $scope.capacity_tonase_used = 0;
            $scope.capacity_volume_used = 0;

            $scope.appendTable = function () {
                var dt = $scope.detailData;
                var rack_name = $scope.detailData.storage_type == 'HANDLING' ? 'Handling Area' : $scope.detailData.rack_name;
                var total = parseInt($scope.formData.total) - parseInt($scope.detailData.qty)

                if(!$scope.selected_rack) {
                    $scope.selected_rack = {}
                }
                let capacity_volume = $scope.selected_rack.capacity_volume != undefined && $scope.detailData.storage_type == 'RACK' ? $scope.selected_rack.capacity_volume : '~';
                let capacity_tonase = $scope.selected_rack.capacity_tonase != undefined && $scope.detailData.storage_type == 'RACK' ? $scope.selected_rack.capacity_tonase : '~';
                let vehicle = $('#vehicle_type option:selected').text()
                var piece_name = ''
                var piece_name_2 = ''

                var piece_name = $scope.detailData.piece_name
                var piece_name_2 = $scope.detailData.piece_name_2

                $scope.formData.detail.push({
                    piece_name: piece_name,
                    piece_name_2: piece_name_2,
                    capacity_volume: capacity_volume,
                    capacity_tonase: capacity_tonase,
                    piece_id: dt.piece_id,
                    piece_id_2: dt.piece_id_2,
                    storage_type: dt.storage_type,
                    rack_name: rack_name,
                    rack_id: dt.rack_id,
                    kemasan: dt.kemasan == 'LAINNYA' ? dt.kemasan_lainnya : dt.kemasan,
                    piece_id: dt.piece_id,
                    vehicle: vehicle,
                    vehicle_type_id: dt.vehicle_type_id,
                    imposition_name: $rootScope.findJsonId(dt.imposition, $scope.imposition).name,
                    purchase_order_detail_id: dt.purchase_order_detail_id,
                    imposition: dt.imposition,
                    qty_2: dt.qty_2,
                    qty: dt.qty,
                    weight: dt.weight,
                    long: dt.long,
                    wide: dt.wide,
                    high: dt.high,
                    item_name: dt.item_name,
                    item_id: dt.item_id,
                    is_exists: dt.is_exists,
                    is_use_pallet: dt.is_use_pallet,
                    pallet_name: dt.pallet_name,
                    pallet_qty: dt.pallet_qty,
                    pallet_id: dt.pallet_id
                })

                $scope.urut++
                $scope.resetDetail()
                if (total > 0) {
                    $scope.detailData.imposition = dt.imposition;
                    $scope.detailData.item_name = dt.item_name;
                    $scope.detailData.item_id = dt.item_id;
                    $scope.detailData.is_exists = dt.is_exists;
                    $scope.detailData.long = dt.long;
                    $scope.detailData.wide = dt.wide;
                    $scope.detailData.high = dt.high;
                    $scope.detailData.weight = dt.weight;
                    $scope.formData.total = total;
                }

                $scope.getSuggestion()
            }

          $scope.deleteDetail = function (id) {
            $scope.formData.detail.splice(id, 1)
            // $('#tr-' + id).remove();
            // delete $scope.formData.detail[id];
          }

          $scope.resetDetail = function () {
            $scope.detailData = {}
            $scope.detailData.item_name = ""
            $scope.detailData.nopol = ""
            $scope.detailData.driver_name = ""
            $scope.detailData.phone_number = ""
            $scope.detailData.imposition = 1
            $scope.detailData.qty = 1
            $scope.detailData.qty_2 = 1
            $scope.detailData.long = 0
            $scope.detailData.wide = 0
            $scope.detailData.high = 0
            $scope.detailData.weight = 0
            $scope.detailData.vehicle_type_id = $scope.data.vehicle_type[0].id
            $scope.formData.total = 0;
            $scope.validateForm();
          }

          $scope.validateForm = function () {

            if (!$scope.formData.warehouse_id || !$scope.formData.company_id || !$scope.formData.receive_time || $scope.formData.detail.length == 0) {
                  $rootScope.disBtn = true;
                  $('.submitButton').attr('disabled', 'disabled');
            } else {
                  $rootScope.disBtn = false;
                  $('.submitButton').removeAttr('disabled');

            }
            console.log('complete');
            console.log($scope.isComplete);
          }

          $rootScope.disBtn = false;
          const b64toBlob = (b64Data, contentType = '', sliceSize = 512) => {
            contentType = contentType || '';
            sliceSize = sliceSize || 512;

            var byteCharacters = atob(b64Data);
            var byteArrays = [];

            for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
              var slice = byteCharacters.slice(offset, offset + sliceSize);

              var byteNumbers = new Array(slice.length);
              for (var i = 0; i < slice.length; i++) {
                byteNumbers[i] = slice.charCodeAt(i);
              }

              var byteArray = new Uint8Array(byteNumbers);

              byteArrays.push(byteArray);
            }

            var blob = new Blob(byteArrays, {
              type: contentType
            });
            return blob;
          }
          $scope.submitForm = function () {
            $scope.formData.receiver = $('#ex1_value').val()
            $scope.formData.city_to = $("[ng-model='formData.city_to']").val();
            var dropzone = $('.dropzone').find('img');
            if (dropzone.length < 1 && $scope.formData.status == 1) {
              toastr.error('Lampiran wajib diisi !');
            } else {

              if ($rootScope.disBtn == false) {

                if ($scope.formData.packing_id < 6)
                  $scope.formData.package = $rootScope.findJsonId($scope.formData.packing_id, $scope.tipeKemasan).name;

                $('.submitButton').attr('disabled', 'disabled');
                $rootScope.disBtn = true;
                var fd = new FormData();
                if ($scope.is_fill_signature == 1) {

                  var signature = $('.signature').jSignature("getData", "image");
                  const contentType = signature[0];
                  const b64Data = signature[1];
                  const blob = b64toBlob(b64Data, contentType);
                  console.log({
                    contentType,
                    b64Data,
                    blob
                  });
                  const ttd = blob;
                  fd.append('ttd', ttd);
                }
                for (x in $scope.formData) {
                  if (x != 'detail' && x != 'packing_id') {
                    fd.append(x, $scope.formData[x]);
                  } else {
                    fd.append('detail', JSON.stringify($scope.formData.detail));

                  }
                }

                for (x in $scope.files) {

                  fd.append('files[]', $scope.files[x]);
                }

                var storeUrl
                if($scope.storeUrl) {
                    storeUrl = $scope.storeUrl + '?_token=' + csrfToken
                } else {
                    storeUrl = baseUrl + '/operational_warehouse/receipt?_token=' + csrfToken
                }

                $.ajax({
                  url: storeUrl,
                  contentType: false,
                  processData: false,
                  type: 'POST',
                  data: fd,
                  beforeSend: function (request) {
                    request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
                  },
                  success: function (data) {
                    toastr.success("Data Berhasil Disimpan!");
                    $scope.back()
                    $rootScope.disBtn = false;
                    $('.submitButton').removeAttr('disabled');
                    $compile($('.submitButton'))($scope);
                  },
                  error: function (xhr) {
                    $('.submitButton').removeAttr('disabled');
                    $compile($('.submitButton'))($scope);
                    var resp = JSON.parse(xhr.responseText);
                    toastr.error(resp.message, "Error Has Found !");
                    $rootScope.disBtn = false;
                  }
                });
              }
            }
          }
        }
    }
});