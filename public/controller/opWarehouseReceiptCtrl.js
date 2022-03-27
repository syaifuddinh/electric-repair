app.controller('opWarehouseReceipt', function ($scope, $http, $rootScope, $state, $stateParams, $timeout, $compile, $filter) {
    $rootScope.pageTitle = $rootScope.solog.label.general.title;
    $rootScope.emptyBuffer()
});

app.controller('opWarehouseReceiptCreate', function ($scope, $http, $rootScope, $state, $stateParams, $timeout, $compile, $filter, racksService, unitsService, warehouseReceiptsService) {
    $rootScope.pageTitle = "Add Good Receipt";
});

app.controller('opWarehouseReceiptShow', function ($scope, $http, $rootScope, $state, $stateParams, $timeout, $compile, $filter,emailService) {
    $rootScope.pageTitle = "Good Receipt";
    $('.ibox-content').addClass('sk-loading');
    $scope.id = $stateParams.id;
    $scope.item_migration_id = null
    $scope.openInfo = function() {
          $('.tab-item').hide()
          $('#info_detail').show()
    }
    $scope.openInfo()

    $scope.openMigration = function() {
        $('.tab-item').hide()
        $('#migration_detail').show()
    }

    $scope.openVoyage = function() {
        $('.tab-item').hide()
        $('#voyage_detail').show()
    }

    $scope.$on("getItemMigrationId", function(e, v) {
        $scope.item_migration_id = v
    })

    $scope.$on("getVoyageScheduleId", function(e, v) {
        $scope.voyage_schedule_id = v
    })
});

app.controller('opWarehouseReceiptEdit', function ($scope, $http, $rootScope, $state, $stateParams, $timeout, $compile, $filter) {
    $rootScope.pageTitle = "Edit Good Receipt";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {}
    $scope.item = {}
    $scope.detail = []
    $scope.detailData = {}

  $scope.showPiece = function() {
      $http.get(baseUrl+'/setting/general/satuan').then(function(data) {
        $scope.piece=data.data;
      }, function(){
          $scope.showPiece()
      });
  }
  $scope.showPiece()

  new google.maps.places.Autocomplete(
    ($('[ng-model="formData.city_to"]')[0]), {
      types: []
    });

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

  oTable = $('#pallet_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: false,
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
    scrollX: false,
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

  $scope.addItem = function () {
    $scope.is_edit = 0;
    $scope.detailData = {};
    $scope.detailData.qty = 1;
    $scope.detailData.qty_2 = 1;
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
    $scope.detailData.qty_2 = val.qty_2;
    $scope.detailData.piece_id = val.piece_id;
    $scope.detailData.piece_id_2 = val.piece_id_2;
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
      warehouse_id: $scope.formData.warehouse_id,
      is_handling_area: 1
    };


    request = $.param(request);
    $http.get(baseUrl + '/inventory/item/cek_stok_warehouse?' + request).then(function (data) {
      $scope.itemData.stock = data.data.stok;
    });
    $("#modalItem").modal();

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

  $scope.getRack = function () {
    $http.get(baseUrl + '/operational_warehouse/receipt/rack').then(function (data) {
      $scope.racks = data.data.filter(x => x.warehouse_id == $scope.formData.warehouse_id)
    }, function () {
      $scope.getRack()
    })
  }

  $scope.getJobOrderPengiriman = function () {
    $http.get(baseUrl + '/operational_warehouse/receipt/' + $scope.formData.customer_id + '/job_order_pengiriman').then(function (data) {
      $scope.job_orders = data.data
    }, function () {
      $scope.getJobOrderPengiriman()
    })
  }

  $scope.getPenerima = function () {
    $http.get(baseUrl + '/contact/contact/penerima').then(function (data) {
      $scope.data.penerima = data.data;
    }, function () {
      $scope.getPenerima()
    });
  }

  $scope.getDetail = function () {
    $http.get(baseUrl + '/operational_warehouse/receipt/' + $stateParams.id + '/detail').then(function (data) {
      $scope.detail = data.data.detail
    }, function () {
      $scope.getDetail()
    })
  }
  $scope.getDetail()

  $scope.submitItem = function () {
    $rootScope.disBtn = true;
    // $scope.itemData.rack_id = $scope.rack_id;
    $scope.detailData.warehouse_id = $scope.formData.warehouse_id;
    $scope.detailData.kemasan = $scope.detailData.kemasan == 'LAINNYA' ? $scope.detailData.kemasan_lainnya : $scope.detailData.kemasan
    if ($scope.is_edit == 0) {
      var url_target = baseUrl + '/operational_warehouse/receipt/store_detail/' + $stateParams.id + '?_token=' + csrfToken;
      var method = 'post';
    } else {
      var url_target = baseUrl + '/operational_warehouse/receipt/update_detail/' + $scope.detailData.id + '?_token=' + csrfToken;
      var method = 'put';
    }
    $http[method](url_target, $scope.detailData).then(function (data) {
      // $state.go('operational.job_order');
      $('#modalItem').modal('hide');
      $scope.is_edit = 1;
      $scope.getDetail()
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



  $scope.cariItem = function () {
    if ($scope.detailData.is_exists != -1) {

      $('#modalItem').modal('hide')
      setTimeout(function () {
        $('#modalItemWarehouse').modal('show')

      }, 400);
      oTable.ajax.reload();
    }
  }

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

  $scope.showAttachment = function () {
    $http.get(baseUrl + '/operational_warehouse/receipt/' + $stateParams.id + '/attachment').then(function (data) {
      $scope.attachment = data.data
      var img, x, unit;
      attachment_container = $('#attachment_container')
      for (x in $scope.attachment) {
        unit = $scope.attachment[x]
        img = $("<div class='col-md-4' style='position:relative'><div style='position:absolute;top:-3mm;left:-1mm;'><button ng-click='destroy_attachment($event.currentTarget, " + unit.id + ")' style='border-radius:80mm' class='btn btn-sm btn-danger' type='button'><i class='fa fa-trash'></i></button></div><img onclick='window.open(\"" + unit.name + "\")' style='height:55mm;width:auto;margin-bottom:1mm' class='img-thumbnail' src='" + unit.name + "'></div>")
        attachment_container.prepend(img)
      }
      $compile(attachment_container)($scope);

    }, function () {
      $scope.showAttachment()
    });
  }
  $scope.showAttachment()

  $scope.resetSignature = function () {
    $(".signature").jSignature('clear');
  }

  var signature_driver = $(".signature").jSignature({
    height: 200
  });

  $scope.getData = function () {

    $http.get(baseUrl + '/operational_warehouse/receipt/' + $stateParams.id + '/edit').then(function (data) {
      $scope.data = data.data;

      var dt = data.data.item;
      $scope.formData.is_export = dt.is_export
      $scope.formData.company_id = dt.company_id
      $scope.formData.description = dt.description
      $scope.formData.job_order_pengiriman_id = dt.job_order_pengiriman_id
      $scope.formData.receive_date = $filter('minDate')(dt.receive_date)
      $scope.formData.receive_time = $filter('aTime')(dt.receive_date)
      $scope.formData.stripping_date = $filter('minDate')(dt.stripping_done)
      $scope.formData.stripping_time = $filter('aTime')(dt.stripping_done)
      $scope.formData.customer_id = dt.customer_id
      $scope.formData.sender = dt.sender
      $scope.formData.receiver = dt.receiver
      $('#ex1_value').val($scope.formData.receiver)
      $scope.formData.warehouse_staff_id = dt.warehouse_staff_id
      $scope.formData.reff_no = dt.reff_no
      $scope.formData.city_to = dt.city_to
      $scope.formData.warehouse_id = dt.warehouse_id
      $scope.formData.nopol = dt.nopol
      $scope.formData.driver = dt.driver
      $scope.formData.ttd = dt.ttd
      $scope.formData.phone_number = dt.phone_number
      $scope.formData.vehicle_type_id = dt.vehicle_type_id
      $scope.formData.is_overtime = dt.is_overtime
      $scope.formData.packing_id = $scope.findPackageId(dt.package)
      $scope.companyChange(dt.company_id);
      $scope.customerChange(dt.customer_id);
      // $('.signature').jSignature('setData', JSON.parse(dt.ttd), 'native');
      $scope.getRack()
      $scope.getJobOrderPengiriman()
      $('.ibox-content').removeClass('sk-loading');
      // $scope.resetDetail();
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
      // if (sts == val.company_id) {
      $scope.warehouses.push({
        id: val.id,
        name: val.name
      })
      // }
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
      id: 2,
      name: 'Item'
    },
    {
      id: 4,
      name: 'Borongan'
    },
  ];

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

  $scope.back = function() {
    if($rootScope.hasBuffer()) {
        $rootScope.accessBuffer()
    } else {
        $rootScope.emptyBuffer()
        $state.go("operational_warehouse.receipt")
    }
  }

  $scope.findPackageId = function (package) {
    var tipe_id = 6;
    angular.forEach($scope.tipeKemasan, function (tipe, i) {
      if (tipe.name == package)
        tipe_id = tipe.id;
    });
    return tipe_id;
  }

  $rootScope.disBtn = false;
  $('#files').change(function () {
    var input = this
    $scope.store_attachment()
    if (input.files.length > 0) {
      var reader = new FileReader();

      reader.onload = function (e) {
        // $('#blah').attr('src', e.target.result);
      }

    }
  })
  $scope.store_attachment = function () {
    var fd = new FormData();
    var files = $('#files')[0].files;
    for (x in files) {

      fd.append('files[]', files[x])
    }
    store_attachment_btn = $('[for="files"]')
    store_attachment_btn.addClass('disabled')
    $.ajax({
      url: baseUrl + '/operational_warehouse/receipt/' + $stateParams.id + '/attachment?_token=' + csrfToken,
      contentType: false,
      processData: false,
      type: 'POST',
      data: fd,
      beforeSend: function (request) {
        request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
      },
      success: function (data) {
        store_attachment_btn.removeClass('disabled')
        toastr.success("Lampiran berhasil di-upload!");
        var unit
        var attachment_container = $('#attachment_container')
        for (x in data.attachments) {
          unit = data.attachments[x]
          img = $("<div class='col-md-4' style='position:relative;'><div style='position:absolute;top:-3mm;left:-1mm;'><button ng-click='destroy_attachment($event.currentTarget, " + unit.id + ")' style='border-radius:80mm' class='btn btn-sm btn-danger' type='button'><i class='fa fa-trash'></i></button></div><img style='height:55mm;width:auto' onclick='window.open(\"" + unit.name + "\")' class='img-thumbnail' src='" + unit.name + "'></div>")
          attachment_container.prepend(img)
        }
        $compile(attachment_container)($scope);
      },
      error: function (xhr) {
        store_attachment_btn.removeClass('disabled')
        var resp = JSON.parse(xhr.responseText);
        $('.submitButton').removeAttr('disabled');
        toastr.error(resp.message, "Error Has Found !");
      }
    });


  }

  $scope.destroy_attachment = function (element, id) {
    is_confirm = confirm('Anda yakin ingin menghapus lampiran ini ?')
    if (is_confirm) {
      store_attachment_btn = $('[for="files"]')
      store_attachment_btn.addClass('disabled')
      attachmentElement = $(element).parents('.col-md-4')
      $.ajax({
        url: baseUrl + '/operational_warehouse/receipt/' + $stateParams.id + '/attachment/' + id + '?_token=' + csrfToken,
        contentType: false,
        processData: false,
        type: 'DELETE',
        beforeSend: function (request) {
          request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
        },
        success: function (data) {
          store_attachment_btn.removeClass('disabled')
          toastr.success("Lampiran berhasil dihapus!");
          attachmentElement.remove()
          $compile(attachment_container)($scope);
        },
        error: function (xhr) {
          store_attachment_btn.removeClass('disabled')
          var resp = JSON.parse(xhr.responseText);
          $('.submitButton').removeAttr('disabled');
          toastr.error(resp.message, "Error Has Found !");
        }
      });
    }


  }
  $scope.submitForm = function () {
    $scope.formData.receiver = $('#ex1_value').val()
    $scope.formData.city_to = $("[ng-model='formData.city_to']").val();
    if ($scope.formData.packing_id < 6)
      $scope.formData.package = $rootScope.findJsonId($scope.formData.packing_id, $scope.tipeKemasan).name;

    $rootScope.disBtn = true;
    $('.submitButton').attr('disabled', 'disabled');
    var fd = new FormData();
    for (x in $scope.formData) {
      if (x != 'ttd' && x != 'packing_id') {
        if ($scope.formData[x]) {

          fd.append(x, $scope.formData[x]);
        }
      }
    }
    if ($scope.edit_signature == true) {

      var signature = $('.signature').jSignature("getData", "image");
      const contentType = signature[0];
      const b64Data = signature[1];
      const blob = b64toBlob(b64Data, contentType);
      const ttd = blob;
      fd.append('ttd', ttd);
    }

    $.ajax({
      url: baseUrl + '/operational_warehouse/receipt/update/' + $stateParams.id + '?_token=' + csrfToken,
      contentType: false,
      processData: false,
      type: 'POST',
      data: fd,
      beforeSend: function (request) {
        request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
      },
      success: function (data) {
        $('.submitButton').removeAttr('disabled');
        $compile($('.submitButton'))($scope);
        toastr.success("Data Berhasil Disimpan!");
        $state.go('operational_warehouse.receipt');
        $rootScope.disBtn = false;
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
});
