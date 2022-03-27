app.controller('marketingActivityWO', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Summary Work Order";
  $('.ibox-content').addClass('sk-loading');
  $scope.filterData={};
  $scope.is_admin = authUser.is_admin;

  $http.get(baseUrl+'/marketing/report/activity_wo_index').then(function(data) {
    $scope.data=data.data;
  });

  $scope.refreshTable=function() {
    oTable.ajax.reload();
  }

  $scope.reset=function() {
    $scope.filterData={}
    oTable.ajax.reload();
  }

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    dom: 'Blfrtip',

    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    buttons: [
      {
        extend : 'excel',
        action : newExportAction,
        enabled : true,
        text : '<span class="fa fa-file-excel-o"></span> Export Excel',
        className : 'btn btn-default btn-sm pull-right',
        filename : 'Excel Summary WO - '+new Date(),
        messageTop : 'Summary WO',
        sheetName : 'Data',
        title : 'Summary WO',
      },
    ],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/marketing/activity_work_order',
      data: function(d) {
        d.is_admin = authUser.is_admin;
        d.user_company_id = authUser.company_id;
        d.company_id = $scope.filterData.company_id;
        d.customer_id = $scope.filterData.customer_id;
        d.start_date = $scope.filterData.start_date;
        d.end_date = $scope.filterData.end_date;
      },
      dataSrc: function(d) {
          $('.ibox-content').removeClass('sk-loading');
          return d.data;
      }
    },
    columns:[
      {data:"company",name:"company.name"},
      {data:"code_wo",name:"wo.code"},
      {data:"date_wo",name:"wo.date"},
      {data:"customer",name:"contacts.name",className:"font-bold"},
      {data:"operational_price",name:"X.operasional",className:"text-right"},
      {data:"talangan_price",name:"X.reimburse",className:"text-right"},
      {data:"invoice_price",name:"Y.grand_total",className:"text-right"},
      {data:"profit",name:"profit",className:"text-right"},
      {data:"presentase",name:"presentase",className:"text-right"},
      {data:"code_invoice",name:"Y.code"},
      {data:"date_invoice",name:"Y.date_invoice"},
      {data:"description",name:"description"},
    ],
    createdRow: function(row, data, dataIndex) {
      if(true) {
        $(row).find('td').attr('ui-sref', 'marketing.work_order.show({id:' + data.wo_id + '})')
        $(row).find('td:last-child').removeAttr('ui-sref')
      } else {
        $(oTable.table().node()).removeClass('table-hover')
      }
      $compile(angular.element(row).contents())($scope);
    }
  });
  oTable.buttons().container().appendTo( '#export_button' );


})
app.controller('marketingActivityJO', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Summary Job Order";
    $('.ibox-content').addClass('sk-loading');
    $scope.filterData={}

    $http.get(baseUrl+'/marketing/report/activity_wo_index').then(function(data) {
        $scope.data=data.data;
    });

    $scope.refreshTable=function() {
        oTable.ajax.reload();
    }

    $scope.reset=function() {
        $scope.filterData={}
        oTable.ajax.reload();
    }

    

    additionalFieldsService.dom.getJobOrderSummaryKey(function(list){
        columnDefs = [
            {title : $rootScope.solog.label.job_order.code },
            {title : $rootScope.solog.label.job_order.date },
            {title : $rootScope.solog.label.general.customer },
            {title : $rootScope.solog.label.general.qty },
            {title : $rootScope.solog.label.general.service },
            {title : $rootScope.solog.label.general.origin },
            {title : $rootScope.solog.label.general.destination },
            {title : $rootScope.solog.label.general.revenue },
            {title : $rootScope.solog.label.general.cost },
            {title : $rootScope.solog.label.general.base_price },
            {title : $rootScope.solog.label.general.profit },
        ]

        columns = [
            {data:"code_jo",name:"code_jo"},
            {
                data:null,
                render:resp => $filter('fullDate')(resp.date_jo),
                name:"date_jo"
            },
            {data:"customer",name:"customer",className:"font-bold"},
            {data:"qty",name:"qty",className:"text-right"},
            {data:"service",name:"service",className:""},
            {data:"city_from",name:"city_from",className:""},
            {data:"city_to",name:"city_to",className:""},
            {data:"operational",name:"operational",className:"text-right"},
            {data:"biaya",name:"biaya",className:"text-right"},
            {
                data:null,
                className:"text-right",
                render:resp => $filter('number')(resp.base_price)
            },
            {
                data:null,
                searchable:false,
                orderable:false,
                className:"text-right",
                render : resp => $filter('number')(resp.profit)
            }
        ]

        for(x in list) {
            columns.push({
                data : list[x].slug,
                orderable:false,
                searchable:false
            })
            
            columnDefs.push({title : list[x].name})
        }
        columns.push({
            data: null, 
            name: "id", 
            render: function (e) {
                let html = `
                  <a ui-sref="operational.job_order.show({id: ${e.id}})"><span class="fa fa-folder-o"></span></a>
                `
                return html
            }
        })

        columnDefs = columnDefs.map((c, i) => {
            c.targets = i
            return c
        })

        $scope.initDatatable()
    })

    $scope.initDatatable = function() {
        oTable = $('#datatable').DataTable({
            processing: false,
            serverSide: false,
            ordering: false,
            dom: 'lBfrtip',
            lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
            buttons: [
                {
                    'extend' : 'excel',
                    'enabled' : true,
                    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                    'className' : 'btn btn-default btn-sm',
                    'filename' : 'Excel Summary JO - '+new Date(),
                    'messageTop' : 'Summary JO',
                    'sheetName' : 'Data',
                    'title' : 'Summary JO'
                },
            ],
            ajax : {
                headers : {'Authorization' : 'Bearer '+authUser.api_token},
                url : baseUrl+'/api/marketing/activity_job_order',
                data: function(d) {
                    d.customer_id=$scope.filterData.customer_id
                    d.service_id=$scope.filterData.service_id
                    d.start_date=$scope.filterData.start_date
                    d.end_date=$scope.filterData.end_date
                },
                dataSrc: function(d) {
                    $('.ibox-content').removeClass('sk-loading');
                    return d.data;
                }
            },
            columnDefs : columnDefs,
            columns:columns,
            createdRow: function(row, data, dataIndex) {
                if($rootScope.roleList.includes('operational.job_order.detail')) {
                    $(row).find('td').attr('ui-sref', 'operational.job_order.show({id:' + data.id + '})')
                    $(row).find('td:last-child').removeAttr('ui-sref')
                } else {
                    $(oTable.table().node()).removeClass('table-hover')
                }
                $compile(angular.element(row).contents())($scope);
            }
        });
        oTable.buttons().container().appendTo( '#export_button' );
    }
    
})
app.controller('marketingActivityJOShow', function ($scope, $http, $rootScope, $state, $stateParams) {
  $rootScope.pageTitle = "Detail Summary JO";
  $http.get(`${baseUrl}/operational/job_order/jo_margin_detail/${$stateParams.id}`).then(function (e) {
    $scope.item = e.data.item
    $scope.details = e.data.details
    $scope.invoice = e.data.invoice
  })
})
