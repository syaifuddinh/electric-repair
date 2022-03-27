app.controller('DraftListHutang', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="List Hutang";

    $scope.formData={}
    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order:[[3,'desc']],
        
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/finance/draft_list_hutang_datatable',
            data : function(d) {
                d.contact_id = $scope.formData.contact_id
                d.start_date = $scope.formData.start_date
                d.end_date = $scope.formData.end_date
                d.start_due_date = $scope.formData.start_due_date
                d.end_due_date = $scope.formData.end_due_date
                d.status = $scope.formData.status
            },
            dataSrc : (r) => {
                var sisa = $filter("number")(r.sisa)
                $('#sisa_el').text(sisa)
                return r.data;
            }
        },
        columns:[
            {
                data:"company_name",
                name:"company_name",
            },
            {data:"code",name:"code"},
            {
                data:"contact_name",
                name:"contact_name"
            },
            {
                data:null,
                searchable:false,
                orderable:false,
                render:resp => $filter('fullDate')(resp.date_transaction)
            },
            {
                data:null,
                searchable:false,
                orderable:false,
                render:resp => $filter('fullDate')(resp.date_tempo)
            },
            {data:"sisa",name:"sisa",className:'text-right'},
            {data:"umur",name:"umur",className:'text-right'},
            {data:"status",name:"status",className:'text-center'},
            {data:"action",name:"created_at",className:'text-center'},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('finance.credit.draft.detail')) {
                $(row).find('td').attr('ui-sref', 'finance.draft_list_hutang.show({id:' + data.id + '})')
                $(row).find('td:last-child').removeAttr('ui-sref')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    })
  
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
  
app.controller('DraftListHutangShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Draft List Hutang";
    var url = baseUrl+'/finance/debt_payable/draft_list/' + $stateParams.id;

    $scope.status = [
        {id:1, name:"Lunas"},
        {id:2, name:"Outstanding"},
        {id:3, name:"Proses"}
    ];
    
    $http.get(url).then(function(data) {
        $scope.item=data.data.item;
        $scope.detail=data.data.item.payable_details;

        var urut = 1;
        for(var index = 0; index < $scope.detail.length; index++){
            $scope.detail[index].no = urut;

            if($scope.detail[index].description == "")
                $scope.detail[index].description = "-";

            urut++;
        };
    });
});
