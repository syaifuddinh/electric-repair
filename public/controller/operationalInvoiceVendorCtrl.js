app.controller('operationalInvoiceVendor', function($scope, $http, $rootScope, $state, $stateParams, $timeout, $compile, $filter) {
  $rootScope.pageTitle = "Invoice Vendor";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {};
  $http.get(baseUrl + '/operational/invoice_vendor').then(function(data) {
    $scope.data = data.data;
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: false,
    order: [
      [4, 'desc'],
      [8, 'desc']
    ],
    dom: 'Blfrtip',
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, 'All']
    ],
    ajax: {
      headers: {
        'Authorization': 'Bearer ' + authUser.api_token
      },
      url: baseUrl + '/api/operational/invoice_vendor_datatable',
      data: function(request) {
        request['start_date'] = $scope.formData.start_date;
        request['end_date'] = $scope.formData.end_date;
        request['company_id'] = $scope.formData.company_id;
        request['vendor_id'] = $scope.formData.vendor_id;
        request['status'] = $scope.formData.status;

        return request;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    buttons: [{
      'extend': 'pdf',
      'enabled': true,
      'text': '<span class="fa fa-file-pdf-o"></span> Export PDF',
      'className': 'btn btn-default btn-sm',
      'filename': ' - ' + new Date(),
      'sheetName': 'Data',
      'title': ''
    }, ],
    columns: [{
        data: "company.name",
        name: "company.name"
      },
      {
        data: "code",
        name: "code"
      },
      {
        data: "vendor.name",
        name: "vendor.name",
        className: "font-bold"
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        render: resp => $filter('fullDate')(resp.date_invoice)
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        render: resp => $filter('fullDate')(resp.date_receive)
      },
      {
        data: "total",
        name: "total",
        className: "text-right"
      },
      {
        data: "status",
        name: "status",
        className: ""
      },
      {
        data: null,
        name: "status_approve",
        className: "text-center",
        render: function(e) {
          const status = [
            {id: 1, value: `<span class="badge badge-warning">Draft</span>`},
            {id: 2, value: `<span class="badge badge-success">Disetujui</span>`},
            {id: 4, value: `<span class="badge badge-info">Dibayar</span>`}
          ]
          return status.find(r => r.id==e.status_approve).value
        }
      },
      {
        data: null,
        name: "created_at",
        className: "text-center",
        render: function(e) {
          let html = ``
          html+= `<a ng-show="roleList.includes('operational.invoice_vendor.detail')" ui-sref="operational.invoice_vendor.show({id:${e.id}})"><span class="fa fa-folder-o"></span></a>`
          if (e.status_approve==1) {
            html+=`&nbsp;&nbsp;<a ng-click="deletes(${e.id})"><span class="fa fa-trash"></span></a>`
          }
          return html
        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo('.ibox-footer');

  $scope.searchData = function() {

    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.deletes = function(ids) {
    var cfs = confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl + '/operational/invoice_vendor/' + ids, {
        _token: csrfToken
      }).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!", "Error Has Found!");
      });
    }
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/invoice_vendor_export?';
    url += params;
    location.href = url;
  }
});
app.controller('operationalInvoiceVendorCreate', function($scope, $http, $rootScope, $state, $stateParams, $timeout, $compile, $filter, $interval) {
  $rootScope.pageTitle = "Tambah Tagihan Vendor";
  $('.ibox-content').addClass('sk-loading');

  $scope.formData = {}
  $scope.formData.vendor_id = null;
  $scope.formData.company_id = compId;
  $scope.formData.date_invoice = dateNow;
  $scope.formData.date_receive = dateNow;
  $scope.formData.due_date = dateNow;
  $scope.formData.detail = [];
  $scope.formData.total = 0
  $scope.formData.subtotal = 0
  $scope.formData.ppn = 0
  $scope.formData.diskon = 0

  $scope.tax_template = {tax_id:null,amount:0}

  $http.get(baseUrl + '/operational/invoice_vendor/create').then(function(data) {
    $scope.data = data.data;
    $scope.contact = data.data.contact;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.cariJO=async function() {
    if(!$scope.formData.vendor_id) return toastr.warning('Anda harus memilih vendor dahulu','Oops!');
    await jo_cost_dt.ajax.reload()
    $('#jo_cost_dt_modal').modal()
  }
  $scope.alreadyAppendJO=function() {
    jo_append = []
    for (var i = 0; i < $scope.formData.detail.length; i++) {
      let val = $scope.formData.detail[i]
      jo_append.push(val.id)
    }
    return jo_append
  }

  $scope.taxDetail=function(row) {
    let val = $scope.formData.detail[row]
    var html = `
      <table class="table table-borderless">
        <tbody>`
        for (var i = 0; i < val.ppn_detail.length; i++) {
          let value = val.ppn_detail[i]
          html += `<tr>
            <td>
              <select class="form-control" ng-change="calculateTotal()" data-placeholder-text-single="'Pilih PPN'" chosen allow-single-deselect="true" ng-model="formData.detail[${row}].ppn_detail[${i}].tax_id" ng-options="s.id as s.name for s in data.taxes">
                <option value=""></option>
              </select>
            <td>
            <td style="width:50%; vertical-align: middle;" class="text-right"><span ng-bind="formData.detail[${row}].ppn_detail[${i}].amount|number"></span><td>
          </tr>`
        }
        html+=`</tbody>
      </table>
    `
    $('#ppnContent').html($compile(html)($scope))
    $('#ppnModal').modal()
  }

  $scope.deleteDetail=function(id) {
    $scope.formData.detail.splice(id, 1)
    $scope.calculateTotal()
  }

  $scope.calculateTotal = function() {
    $scope.formData.total = 0
    $scope.formData.ppn = 0
    $scope.formData.subtotal = 0
    for (var i = 0; i < $scope.formData.detail.length; i++) {
      const value = Object.assign({},$scope.formData.detail[i])
      $scope.formData.detail[i].ppn=0
      $scope.formData.detail[i].total=value.total_origin
      ppnInclude = 0
      for (var ii = 0; ii < value.ppn_detail.length; ii++) {
        const tax = Object.assign({},value.ppn_detail[ii])
        if (!tax.tax_id) {
          $scope.formData.detail[i].ppn_detail[ii].amount=0;
          continue;
        }
        const taxM = $scope.data.taxes.find(e => e.id == tax.tax_id);
        const vendM = $scope.data.contact.find(e => e.id == $scope.formData.vendor_id);
        if(vendM.pkp==1) {
          persentax = parseFloat(taxM.npwp)
        } else {
          persentax = parseFloat(taxM.non_npwp);
        }
        const amo = parseFloat(value.total_origin) * persentax / 100;
        $scope.formData.detail[i].ppn_detail[ii].amount = amo
        if (taxM.pemotong_pemungut==1) {
          $scope.formData.detail[i].total -= amo
        }
        $scope.formData.detail[i].ppn += amo
      }
      $scope.formData.subtotal+=parseFloat($scope.formData.detail[i].total)
      $scope.formData.ppn+=parseFloat($scope.formData.detail[i].ppn)
      $scope.formData.total+=parseFloat($scope.formData.detail[i].total+$scope.formData.detail[i].ppn)
    }
  }

  $scope.$watch('formData.vendor_id',function(v) {
    $scope.formData.detail=[]
    $scope.calculateTotal()
  })
  const jo_cost_dt = $('#jo_cost_dt').DataTable({
    processing: true,
    serverSide: true,
    scrollX: false,
    order: [
      [0, 'desc']
    ],
    ajax: {
      headers: {
        'Authorization': 'Bearer ' + authUser.api_token
      },
      url: baseUrl + '/api/operational/jo_cost_vendor_datatable',
      data: function(d) {
        d.vendor_id = $scope.formData.vendor_id;
        d.not_id = $scope.alreadyAppendJO();
      }
    },
    columns: [
      {
        data: null,
        name: "joc.id",
        render: function(e) {
          return `<a ng-click="chooseJO(${e.id})"><span class="fa fa-check"></span> Pilih</a>`
        }
      },
      {
        data: "code",
        name: "jo.code"
      },
      {
        data: "name",
        name: "name",
      },
      {
        data: null,
        name: 'jo.shipment_date',
        render: resp => $filter('fullDate')(resp.shipment_date)
      },
      {
        data: "description",
        name: "joc.description",
      },
      {
        data: null,
        name: "joc.total_price",
        className: "text-right",
        render: resp => $filter('number')(resp.total_price)
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  })

  $scope.chooseJO=function(id) {
    $http.get(`${baseUrl}/operational/invoice_vendor/get_jo_cost/${id}`).then(function(val) {
      const data = val.data
      taxes = []
      for (var i = 0; i < 2; i++) {
        taxes.push(Object.assign({},$scope.tax_template))
      }
      $scope.formData.detail.push(Object.assign({},{
        job_order_cost_id: data.id,
        manifest_cost_id: null,
        reff_no: `${data.code||''} ${data.name}`,
        total: data.total_price,
        total_origin: data.total_price,
        diskon: 0,
        description: data.description,
        ppn: 0,
        ppn_detail: taxes
      }))
    }).then(function() {
      $('#jo_cost_dt_modal').modal('hide')
      $scope.calculateTotal()
    })
  }

  $scope.disBtn = false;
  $scope.submitForm = function() {
    $scope.disBtn = true;
    $http.post(baseUrl + '/operational/invoice_vendor', $scope.formData).then(function(data) {
      $state.go('operational.invoice_vendor');
      toastr.success("Data Berhasil Disimpan!");
      $scope.disBtn = false;
    }, function(error) {
      $scope.disBtn = false;
      if (error.status == 422) {
        var det = "";
        angular.forEach(error.data.errors, function(val, i) {
          det += "- " + val + "<br>";
        });
        toastr.warning(det, error.data.message);
      } else {
        toastr.error(error.data.message, "Error Has Found !");
      }
    });
  }

});
app.controller('operationalInvoiceVendorShow', function($scope, $http, $rootScope, $state, $stateParams, $timeout, $compile, $filter) {
  $rootScope.pageTitle = "Detail Invoice Vendor";
  $('.ibox-content').addClass('sk-loading');

  $scope.show = function() {

    $http.get(baseUrl + '/operational/invoice_vendor/' + $stateParams.id).then(function(data) {
      $scope.item = data.data.item;
      $scope.detail = data.data.detail;
      $('.ibox-content').removeClass('sk-loading');
    });
  }
  $scope.show()

  $scope.approve = function() {
    var is_approve = confirm('Apakah anda ingin menyetujui transaksi ini ?');
    if (is_approve) {

      $http.put(baseUrl + '/operational/invoice_vendor/approve/' + $stateParams.id + '/').then(function(data) {
        $state.reload()
        toastr.success("Invoice sudah disetujui");
      }, function error(error) {
        $scope.disBtn = false;
        if (error.status == 422) {
          var det = "";
          angular.forEach(error.data.errors, function(val, i) {
            det += "- " + val + "<br>";
          });
          toastr.warning(det, error.data.message);
        } else {
          toastr.error(error.data.message, "Error Has Found !");
        }
      });
    }
  }


  $scope.abort_journal = function() {
    var is_approve = confirm('Apakah anda ingin membatalkan transaksi ini ?');
    if (is_approve) {

      $http.put(baseUrl + '/operational/invoice_vendor/abort_journal/' + $stateParams.id).then(function(data) {
        $state.reload()
        toastr.success("Transaksi telah dibatalkan");
      }, function error(error) {
        $scope.disBtn = false;
        if (error.status == 422) {
          var det = "";
          angular.forEach(error.data.errors, function(val, i) {
            det += "- " + val + "<br>";
          });
          toastr.warning(det, error.data.message);
        } else {
          toastr.error(error.data.message, "Error Has Found !");
        }
      });
    }
  }

  $scope.posting = function() {
    var is_approve = confirm('Apakah anda ingin menyetujui transaksi dan menyetujui jurnal dari transaksi ini ?');
    if (is_approve) {

      $http.put(baseUrl + '/operational/invoice_vendor/approve/' + $stateParams.id + '/post').then(function(data) {
        $state.reload()
        toastr.success("Invoice sudah disetujui");
      }, function error(error) {
        $scope.disBtn = false;
        if (error.status == 422) {
          var det = "";
          angular.forEach(error.data.errors, function(val, i) {
            det += "- " + val + "<br>";
          });
          toastr.warning(det, error.data.message);
        } else {
          toastr.error(error.data.message, "Error Has Found !");
        }
      });
    }
  }

});
