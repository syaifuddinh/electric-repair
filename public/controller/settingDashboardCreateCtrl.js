app.controller('settingDashboardCreate', function($scope, $http, $rootScope,$state,$timeout,$compile, $stateParams) {
    $scope.formData = {
      detail : []
    }
    $scope.data = {}


    widget_datatable = $('#widget_datatable').DataTable({
      processing: true,
      serverSide: true,
      scrollX:false,
      ajax: {
        headers : {'Authorization' : 'Bearer '+authUser.api_token},
        url : baseUrl+'/api/setting/widget_datatable',
      },
      columns:[
        {
          data:null,
          name:"name",
          render:resp => '<div class="context-menu" ng-click="selectWidget($event.currentTarget)">' + resp.name + '</div>'
        },
        {
          data:null,
          name:"Q.name",
          render:resp => '<div class="context-menu" ng-click="selectWidget($event.currentTarget)">' + resp.query_name + '</div>'
        },
        {
          data:null,
          name:"type", 
          className:'capitalize',
          render:resp => '<div class="context-menu" ng-click="selectWidget($event.currentTarget)">' + resp.type + '</div>'
        }
      ],
      createdRow: function(row, data, dataIndex) {
        $compile(angular.element(row).contents())($scope);
      }
    });

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
              render:function(resp) {
                var length = $scope.formData.detail.length - 1
                var outp = "<input type='text' class='form-control' ng-model='formData.detail[" + length + "].row' only-num>"

                return outp
              }
            },
            {
              data:null,
              className:'text-center',
              render:function(resp) {
                var length = $scope.formData.detail.length - 1
                var outp = "<a ng-click='deleteDetail(" + length + ", $event.currentTarget)'><i class='fa fa-trash-o'></i></a>"

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
              $scope.formData.detail.push(results)
              dashboard_detail_datatable.row.add(result).draw()
          }
          $scope.formData.detail = results
        }, function error(data) {
          $scope.showDetail()
        });
    }
    if($stateParams.id != null) {
        $scope.show()
        $scope.showDetail()
    }
    
    $scope.showRole = function() {
        $http.get(baseUrl+'/setting/user/roles').then(function success(data) {
          $scope.data.roles = data.data
        }, function error(data) {
          $scope.showRole()
        });
    }
    $scope.showRole()  

    $scope.submitForm=function() {
      $rootScope.disBtn=true;
      var url = baseUrl + '/setting/dashboard';
      var method = 'post';
      if($scope.formData.id) {
          var url = baseUrl + '/setting/dashboard/' + $stateParams.id;
          var method = 'put';
      } 
      $http[method](url, $scope.formData).then(function(data) {
        $rootScope.disBtn = false
        toastr.success("Data Berhasil Disimpan !");
        $state.go('setting.dashboard_builder')
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
});
