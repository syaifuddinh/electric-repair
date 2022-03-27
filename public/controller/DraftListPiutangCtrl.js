app.controller('DraftListPiutang', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="List Piutang";

    $scope.formData={}
    $scope.contact_id = $stateParams.id
  
    $scope.sisa = 0;

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        order : [[1, 'desc']],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [
            {
                'extend' : 'excel',
                'enabled' : true,
                'action' : newExportAction,
                'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                'className' : 'btn btn-default btn-sm',
                'filename' : 'List Piutang' + ' - '+new Date(),
            },
        ],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/finance/draft_list_piutang_datatable2',
      
            data : function(d) {
                if($stateParams.id) {
                    d.customer_id = $stateParams.id
                } else {
                    d.customer_id = $scope.formData.customer_id
                }
                d.start_date_invoice = $scope.formData.start_date_invoice
                d.end_date_invoice = $scope.formData.end_date_invoice
                d.start_due_date = $scope.formData.start_due_date
                d.end_due_date = $scope.formData.end_due_date
                d.status = $scope.formData.status

                return d;
            },

            dataSrc : (r) => {
                var sisa = $filter("number")(r.sisa)
                $('#sisa_el').text(sisa)
                return r.data;
            }
        },
        columns:[
            {data:"code_invoice",name:"invoices.code"},
            {
              data:null,
              orderable:false,
              searchable:false,
              render:resp => $filter('fullDate')(resp.date_transaction)
            },
            {
              data:null,
              orderable:false,
              searchable:false,
              render:resp => $filter('fullDate')(resp.date_tempo)
            },
            {data:"customer",name:"contacts.name"},
            {data:"sisa",name:"sisa",className:'text-right'},
            //{data:"aju",name:"i.aju"},
            //{data:"no_bl",name:"i.no_bl"},
            {data:"umur",name:"umur",className:'text-right'},
            {data:"status_piutang",name:"umur",className:'text-center'},
            {data:"action",name:"created_at",className:'text-center'},
        ],

        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('finance.credit.draft_list_piutang.detail')) {
                $(row).find('td').attr('ui-sref', 'operational.invoice_jual.show({id:' + data.invoice_id + '})')
                $(row).find('td:last-child').removeAttr('ui-sref')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
  })

  $compile($('thead'))($scope)
  oTable.buttons().container().appendTo( '.export_button' )

  $scope.searchData=function() {
    oTable.ajax.reload()
  }

  $scope.resetFilter=function() {
    $scope.formData={}
    oTable.ajax.reload()
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/draft_list_piutang_export?';
    url += params;
    location.href = url;
  }
});

app.controller('DraftListPiutangShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Draft List Piutang";

});
