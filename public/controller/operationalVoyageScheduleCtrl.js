app.controller('operational', function($scope,$http,$rootScope,$state,$stateParams,$timeout) {
    $rootScope.pageTitle="Operasional";
});

app.controller('operationalVoyageSchedule', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.voyage_schedule.title;
    if($stateParams.id) {
        $scope.storeUrl = baseUrl+'/operational/voyage_schedule/' + $stateParams.id + '/receipt'
    }
});

app.controller('operationalVoyageScheduleReceipt', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle = $rootScope.solog.label.voyage_schedule.title;
    if($stateParams.id) {
        $scope.storeUrl = baseUrl+'/operational/voyage_schedule/' + $stateParams.id + '/receipt'
    }
});

app.controller('operationalVoyageScheduleCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.general.add;
    $('.ibox-content').addClass('sk-loading');

    $(".clockpick").clockpicker({
        placement:'top',
        autoclose:true,
        donetext:'DONE',
    });

    $scope.formData={};
    $scope.formData.etd_date=dateNow;
    $scope.formData.eta_date=dateNow;
    $scope.formData.etd_time=timeNow;
    $scope.formData.eta_time=timeNow;

    $http.get(baseUrl+'/operational/voyage_schedule/create').then(function(data) {
        $scope.data=data.data;
        $('.ibox-content').removeClass('sk-loading');
    });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/voyage_schedule',$scope.formData).then(function(data) {
      $state.go('operational.voyage_schedule');
      toastr.success("Data Berhasil Disimpan!");
      $scope.disBtn=false;
    }, function(error) {
      $scope.disBtn=false;
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

app.controller('operationalVoyageScheduleShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Jadwal Kapal";
    $scope.id = $stateParams.id
    $rootScope.insertBuffer()

    $scope.openInfo = function() {
          $('.tab-item').hide()
          $('#info_detail').show()
    }


    $scope.openReceipt = function() {
        $('.tab-item').hide()
        $('#receipt_detail').show()
    }

    $timeout(function() {
        $scope.openInfo()
    }, 900)
});
app.controller('operationalVoyageScheduleEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Jadwal Kapal";
  $('.ibox-content').addClass('sk-loading');

  $(".clockpick").clockpicker({
    placement:'top',
    autoclose:true,
    donetext:'DONE',
  });

  $scope.formData={};

  $http.get(baseUrl+'/operational/voyage_schedule/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.etd_date=$filter('minDate')(dt.etd);
    $scope.formData.eta_date=$filter('minDate')(dt.eta);

    if(dt.departure) {
        $scope.formData.departure_date=$filter('minDate')(dt.departure);
        $scope.formData.departure_time=$filter('aTime')(dt.departure);
    }

    if(dt.arrival) {
        $scope.formData.arrival_date=$filter('minDate')(dt.arrival);
        $scope.formData.arrival_time=$filter('aTime')(dt.arrival);
    }

    $scope.formData.etd_time=$filter('aTime')(dt.etd);
    $scope.formData.eta_time=$filter('aTime')(dt.eta);
    
    $scope.formData.voyage=dt.voyage;
    $scope.formData.vessel_id=dt.vessel_id;
    $scope.formData.pol_id=dt.pol_id;
    $scope.formData.pod_id=dt.pod_id;
    $scope.formData.countries_id=dt.countries_id;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/operational/voyage_schedule/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('operational.voyage_schedule');
      toastr.success("Data Berhasil Disimpan!");
      $scope.disBtn=false;
    }, function(error) {
      $scope.disBtn=false;
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
