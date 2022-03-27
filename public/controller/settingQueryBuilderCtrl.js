app.controller('settingQueryBuilder', function($scope, $http, $rootScope,$state,$timeout,$compile) {
    $scope.data = {}
    $scope.results = {}
    $scope.showTable = function() {
        $http.get(baseUrl+'/setting/query/table').then(function success(data) {
          var tables = data.data
          for(a in tables) {
              for(b in tables[a]) {
                  $('#tables').append(
                      $('<li class="list-group-item"><a ng-click="selectTable(\'' + tables[a][b] + '\')">' + tables[a][b] + '</a></li>')
                  )
              }
          }

          $compile( $('#tables') )($scope);
        }, function error(data) {
        });
    }
    $scope.showTable()

    $scope.changeQuery = function() {
        var query = $scope.data.queries.find(x => x.id == $scope.formData.id)
        if(query != null) {
            $scope.formData.query = query.query
            $scope.run()
        }
    }

    $scope.showQuery = function() {
        $http.get(baseUrl+'/setting/query').then(function success(data) {
          $scope.data.queries = data.data
        }, function error(data) {
          $scope.showQuery()
        });
    }
    $scope.showQuery()


    $scope.selectTable = function(tableName) {
        $http.get(baseUrl+'/setting/query/table/detail?table=' + tableName).then(function success(data) {
          $scope.renderTable(data.data)
        }, function error(data) {
          $scope.selectTable()
        });
    }

    $scope.run = function(){
        $http.get(baseUrl+'/setting/query/run?query=' + $scope.formData.query).then(function success(data) {
          var results = data.data
          $scope.renderTable(results)
        }, function error(error) {
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

    $scope.saveAs = function(){
        var name = prompt('Nama query baru ?')
        if(name == null) {
            return null
        }
        $scope.formData.name = name
        $rootScope.disBtn = true
        $http.post(baseUrl+'/setting/query', $scope.formData).then(function success(data) {
          $rootScope.disBtn = false
          toastr.success('Query baru berhasil ditambahkan')
          $scope.showQuery()
        }, function error(error) {
            $rootScope.disBtn = false
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

    $scope.save = function(){
        $rootScope.disBtn = true
        $http.put(baseUrl+'/setting/query/' + $scope.formData.id, $scope.formData).then(function success(data) {
          $rootScope.disBtn = false
          toastr.success('Query berhasil disimpan')
          $scope.showQuery()
        }, function error(error) {
            $rootScope.disBtn = false
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

    $scope.renderTable = function(results) {
        $('#preview_table tbody').html('')
        $('#preview_table thead tr').html('')
        var sample = results[0]
        for(x in sample) {
            $('#preview_table thead tr').append(
                $('<th>' + x + '</th>')
            )
        }

        for(i in results) {
            tr = $('<tr></tr>')
            for(j in results[i]) {
                td = $('<td>' + results[i][j] + '</td>')
                tr.append(td)
            }

            $('#preview_table tbody').append(
                tr
            )
        }
    }
});
