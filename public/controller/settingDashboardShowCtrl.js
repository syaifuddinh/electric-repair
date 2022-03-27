app.controller('settingDashboardShow', function($scope, $http, $rootScope,$state,$timeout,$compile, $stateParams) {
    $scope.formData = {
      detail : []
    }
    $scope.data = {}



    dashboard_detail_datatable = $('#dashboard_detail_datatable').DataTable({
        'dom' : 'rt',
        'columns': [
            {
              data:null,
              render:function(resp) {
                var length = $scope.formData.detail.length - 1
                var outp = "<div ng-click='showWidgetModal(" + length + ")'><% formData.detail[" + length + "].widget_name %></div>"

                return outp
              }
            },
            {
              data:null,
              className :'text-right',
              render:function(resp) {
                var length = $scope.formData.detail.length - 1
                var outp = "<% formData.detail[" + length + "].row %>"

                return outp
              }
            }
        ],
        createdRow: function(row, data, dataIndex) {
          $compile(angular.element(row).contents())($scope);
        }
    })

    $scope.addDetail = function() {
        $scope.formData.detail.push({})
        dashboard_detail_datatable.row.add({}).draw()
        $scope.showWidgetModal($scope.formData.detail.length - 1)
    }

    $scope.showWidgetModal = function(index) {
        $scope.currentIndex = index
        $('#widgetModal').modal()        
    }

    $scope.deleteDetail = function(index, obj) {
        $scope.formData.detail[index] = {}
        var tr = $(obj).parents('tr')
        dashboard_detail_datatable.row(tr).remove().draw()
        
    }

    $scope.selectWidget = function(obj) {
        var tr = $(obj).parents('tr')
        var data = widget_datatable.row(tr).data()
        var length = $scope.currentIndex
        $scope.formData.detail[length].widget_name = data.name
        $scope.formData.detail[length].widget_id = data.id
        $('#widgetModal').modal('hide')
    }

    $scope.show = function() {
        $http.get(baseUrl+'/setting/dashboard/' + $stateParams.id).then(function success(data) {
          $scope.formData = data.data
          $scope.formData.detail = []
        }, function error(data) {
          $scope.show()
        });
    }

    $scope.showDetail = function() {
        $http.get(baseUrl+'/setting/dashboard/' + $stateParams.id + '/detail').then(function success(data) {
          var results = data.data
          var result
          for(x in results) {
              result = results[x]
              $scope.formData.detail.push(result)
              dashboard_detail_datatable.row.add(result).draw()
          }
        }, function error(data) {
          $scope.showDetail()
        });
    }
    $scope.show()
    $scope.showDetail()
    
});
