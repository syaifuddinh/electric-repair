voyageSchedules.directive('voyageSchedulesTable', function () {
    return {
        restrict: 'E',
        scope: {
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/voyage_schedules/view/voyage-schedules-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, voyageSchedulesService) {
            $('.ibox-content').addClass('sk-loading');

  $scope.formData = {};
  
  $http.get(baseUrl+'/operational/voyage_schedule').then(function(data) {
    $scope.data=data.data
  });

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    order:[[7,'desc']],
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/voyage_schedule_datatable',
      data: function(d){
        d.ships = $scope.formData.ship_id;
        d.start_date_etd = $scope.formData.start_date_etd;
        d.end_date_etd = $scope.formData.end_date_etd;
        d.start_date_eta = $scope.formData.start_date_eta;
        d.end_date_eta = $scope.formData.end_date_eta;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"vessel.name",name:"vessel.name"},
      {
        data:null,
        name:"voyage",
        className : 'font-bold',
        render : function(resp) {
            if($rootScope.roleList.includes('operational.voyage_schedule.detail')) {
                return '<a ui-sref="operational.voyage_schedule.show({id:' + resp.id + '})">' + resp.voyage + '</a>'
            } else {
                return resp.voyage
            } 
        }
      },
      {data:"pol.name",name:"pol.name"},
      {data:"pod.name",name:"pod.name"},
      {
        data:null,
        name:'etd',
        orderable:false,
        render:function(resp) {
          return $filter('fullDateTime')(resp.etd)
        }
      },
      {
        data:null,
        name:'eta',
        orderable:false,
        render:function(resp) {
          return $filter('fullDateTime')(resp.eta)
        }
      },
      {
        data:null,
        name:'departure',
        orderable:false,
        render:function(resp) {
          if(resp.departure != null) {
              return $filter('fullDateTime')(resp.departure)
          } else {
            return ''
          }
        }
      },
      {
        data:null,
        name:'arrival',
        orderable:false,
        render:function(resp) {
          if(resp.arrival != null) {
              return $filter('fullDateTime')(resp.arrival)
          } else {
              return ''
          }
        }
      },
      {data:"total",name:"total", className : 'text-right'},
      { 
        data:null,
        name : 'created_at',
        searchable:false,
        className:"text-center",
        render : function(item) {
            var html = ''
            html += "<a ng-show=\"$root.roleList.includes('operational.voyage_schedule.detail')\" ui-sref='operational.voyage_schedule.show({id:" + item.id + "})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            html += "<a ng-show=\"$root.roleList.includes('operational.voyage_schedule.edit')\" ui-sref='operational.voyage_schedule.edit({id:" + item.id + "})' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
            html += "<a ng-show=\"$root.roleList.includes('operational.voyage_schedule.delete')\" ng-click='deletes(" + item.id + ")' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";

            return html;
        }
      },
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/operational/voyage_schedule/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.searchData = function() {
    oTable.ajax.reload();
  }

  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.exportExcel = function() {
    var paramsObj = oTable.ajax.params();
    var params = $.param(paramsObj);
    var url = baseUrl + '/excel/jadwalkapal_export?';
    url += params;
    location.href = url; 
  }
        }
    }
});