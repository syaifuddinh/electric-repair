app.controller('inventoryQualityCheck', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.incoming_quality_check.title;

    $scope.formData = {};
    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: false,
        order: [[ 0, "desc" ]],
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/operational_warehouse/warehouse_receipt_detail_datatable',
            data : d => Object.assign(d, $scope.formData)
        },
        columns:[
            {data:"receive_date",name:"receive_date"},
            {data:"company_name",name:"company_name"},
            {data:"warehouse_name",name:"warehouse_name"},
            {data:"no_surat_jalan",name:"no_surat_jalan"},
            {data:"name",name:"name"},
            {
                data:"qty",
                name:'qty',
                className : 'text-right'
            },
            {
                data:"quality_status_name",
                name:"quality_status_name"
            },
            {
                data:null,
                orderable:false,
                searchable:false,
                className:"text-center",
                render : function(item) {
                    var html = ''
                    if(item.quality_status_slug == 'isDraft') {
                        html += "<a ng-show='roleList.includes(\"inventory.quality_check.approve\")' ng-click='approve(" + item.id + ")' ><span class='fa fa-check'  data-toggle='tooltip' title='Approve'></span></a>&nbsp;&nbsp;";
                        html += "<a ng-show='roleList.includes(\"inventory.quality_check.reject\")' ng-click='reject(" + item.id + ")' ><span class='fa fa-close'  data-toggle='tooltip' title='Approve'></span></a>";
                    } 


                    return html;
                }
            },
        ],
        createdRow: function(row, data, dataIndex) {
          $compile(angular.element(row).contents())($scope);
        }
    });
    $compile($('thead'))($scope)
  $scope.detail = function(e) {
      var tr = $(e).parents('tr')
      var data = oTable.row(tr).data();
      $state.go('operational_warehouse.stocklist.show', {id : data.id});
  }

    $scope.searchData = function() {
    oTable.ajax.reload();
    }

    $scope.approve = function(id) {
        var is_confirm = confirm('Are you sure ?')
        if(is_confirm) {

            $http.put(baseUrl + '/operational_warehouse/receipt_detail/' + id + '/approve_quality').then(function(resp) {
                $rootScope.disBtn=false;
                toastr.success(resp.data.message)
                $scope.searchData()
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

    $scope.reject = function(id) {
        var is_confirm = confirm('Are you sure ?')
        if(is_confirm) {

            $http.put(baseUrl + '/operational_warehouse/receipt_detail/' + id + '/reject_quality').then(function(resp) {
                $rootScope.disBtn=false;
                toastr.success(resp.data.message)
                $scope.searchData()
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
  
});
