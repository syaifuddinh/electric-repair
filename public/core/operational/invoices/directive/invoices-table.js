invoices.directive('invoicesTable', function () {
    return {
        restrict: 'E',
        scope: {
            isSalesOrder : '=isSalesOrder',
            isOperational : '=isOperational',
            hideWoGabungan : '=hideWoGabungan',
        },
        transclude:true,
        templateUrl: '/core/operational/invoices/view/invoices-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $state, $timeout, invoicesService, additionalFieldsService) {
            $('.ibox-content').addClass('sk-loading');
            $scope.formData = {};
            $scope.formData.is_operational = $scope.isOperational
            $scope.formData.is_sales_order = $scope.isSalesOrder
            $scope.isFilter = false;

            $scope.woGabunganPrint = function() {
                $('#woGabunganModal').modal();
                $scope.woData = {}
                $scope.woData.type_wo = 1
                $scope.woData.detail = []
                $scope.setNoData()
            }

            $scope.setNoData = function() {
                var no_data = "<tr><td colspan='7' class='text-center'><% solog.label.general.no_data %></td> </tr>"
                $('#bodyWo').html(no_data)
                $compile($('#bodyWo'))($scope)
            }

  $scope.changeCustomer = function(id) {
    $scope.woData.detail = []
    if (!id) {
      $scope.setNoData()
      $scope.woData.detail = []
      return null;
    }

    $http.get(baseUrl + '/operational/invoice_jual/cari_invoice', {
      params: { customer_id : id }
    }).then(function(data) {
      var dt = data.data
      var html = ""
      angular.forEach(dt, function(val, i) {
        var printed = ''
        if (val.is_printed == 1) {
          printed = 'text-navy'
        }
        html += "<tr>"
        html += "<td class='text-center '><input type='checkbox' ng-true-value='1' ng-false-value='0' ng-model='woData.detail[" + i + "].include'></td>"
        html += "<td class='" + printed + "'>" + val.code + "</td>"
        html += "<td class='" + printed + "'>" + (val.po_customer || '') + "</td>"
        html += "<td class='" + printed + "'>" + $filter('date')(val.date_invoice) + "</td>"
        html += "<td class='" + printed + "'>" + (val.aju || '') + "</td>"
        html += "<td class='" + printed + "'>" + (val.bl || '') + "</td>"
        html += "<td class='text-right " + printed + "'>" + $filter('number')(val.grand_total) + "</td>"
        html += "</tr>"

        $scope.woData.detail.push({
          invoice_id: val.id,
          include: 0
        })
      })
      if(html) {
          $('#bodyWo').html($compile(html)($scope))
      } else {
          $scope.setNoData()
      }
    }, function(error) {
      console.log(error)
    })
  }

  $scope.type_wo = [{
      id: 1,
      name: 'EXIM'
    },
    {
      id: 2,
      name: 'Pengiriman'
    },
  ]

  $scope.printWo = function() {
    var list = ""
    angular.forEach($scope.woData.detail, function(val, i) {
      if (val.include) {
        list += val.invoice_id + ',';
      }
    })
    list = list.substring(0, list.length - 1)
    window.open(baseUrl + '/operational/invoice_jual/print_wo_gabungan?list=' + list + '&type_wo=' + $scope.woData.type_wo)
  }

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order: [
        [2, 'desc'], [1, 'desc']
    ],
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, 'All']
    ],
    ajax: {
      headers: {
        'Authorization': 'Bearer ' + authUser.api_token
      },
      url: baseUrl + '/api/operational/invoice_jual_datatable',
      data: e => Object.assign(e, $scope.formData),
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns: [{
        data: "company_name",
        name: "companies.name"
      },
      {
        data: "code",
        name: "code",
        className: "font-bold"
      },
      {
        data: null,
        name : 'date_invoice',
        searchable: false,
        render: resp => $filter('fullDate')(resp.date_invoice)
      },
      {
        data: "customer_name",
        name: "contacts.name"
      },
      {
        data: "total",
        name: "total",
        className: "text-right"
      },
      {
        data: "status_name",
        orderable : false,
        searchable: false,
        className: ""
      },
      {
        data: null,
        orderable:false,
        searchable: false,
        className: "text-center",
        render: function(item) {
            var html = ''

            if($scope.isSalesOrder){
              html += "<a ng-show=\"$root.roleList.includes('sales.invoice.detail')\" ui-sref='sales_order.invoice.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            } else {
              html += "<a ng-show=\"$root.roleList.includes('operational.invoice_customer.detail')\" ui-sref='operational.invoice_jual.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            }

            if (item.status<3) {
              if($scope.isSalesOrder){
                html += "<a ui-sref='sales_order.invoice.edit({id:" + item.id + "})' ><span class='fa fa-pencil'  data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
              } else {
                html += "<a ui-sref='operational.invoice_jual.edit({id:" + item.id + "})' ><span class='fa fa-pencil'  data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
              }
              html += "<a ng-show=\"$root.roleList.includes('operational.invoice_customer.delete')\" ng-click='deletes(" + item.id + ")' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
            }
            return html;
        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      if ($rootScope.roleList.includes('operational.invoice_customer.detail')) {
        $(row).find('td').attr('ui-sref', 'operational.invoice_jual.show({id:' + data.id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  $compile($('thead'))($scope)
  $compile($('#woGabunganModal'))($scope)

  // Function untuk mem-filter dan reset data invoice jual
  $scope.filter_invoice_jual = function() {
    oTable.ajax.reload();
  }

  $scope.export = function(e) {
    var url = baseUrl + '/excel/invoicejual_export?';
    var params = $.param(oTable.ajax.params());
    url += params;
    location.href = url;
  }

  $scope.reset_filter = function() {
    $scope.formData = {};
    $scope.filter_invoice_jual();
  }
  // =================================================
  $scope.deletes = function(ids) {
    var cfs = confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl + '/operational/invoice_jual/' + ids, {
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
        }
    }
});