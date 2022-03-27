app.controller('globalMessageController', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $scope.hasError=true;
  $scope.message={};
  // $scope.message.error=[
  //   {message:"Format Penomoran Belum Lengkap !"}
  // ]
});
app.controller('mainNotification', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$interval,$compile,$filter) {
  // console.log('controller Notif');
  $scope.refreshNotif=function() {
    $http.get(baseUrl+'/api/notification/get_notif',{headers: {'Authorization': 'Bearer '+authUser.api_token}}).then(function(data) {

      var notifs = data.data;
      $scope.notif_length = notifs.length;
      $scope.data = notifs.slice(0, 5);
    })
  }
  $rootScope.refreshNotif=function() {
    $scope.refreshNotif()
  }
  $scope.refreshNotif()
  $scope.goTo=function(data) {

    $http.post(baseUrl+'/api/notification/view_notif',data).then(function(d) {
        $state.go(data.url,JSON.parse(data.params), { reload: true })
    });
  }
});
app.controller('marketingNotification', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$interval,$compile,$filter) {
  $rootScope.pageTitle="Daftar Notifikasi";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/api/notification/get_notif').then(function(data) {

    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  })

  $scope.goTo=function(data) {
    $http.post(baseUrl+'/api/notification/view_notif',data).then(function(d) {
        $state.go(data.url,JSON.parse(data.params), { reload: true })
    });
  }
});

app.controller('journalTopNotification', function($scope, $http, $rootScope,$state, $interval) {
    $rootScope.refreshNotifJournal=function() {
      $http.get(baseUrl + '/api/journal_notification/get_notif', {
            headers: {'Authorization': 'Bearer '+authUser.api_token}
        }).then(function(data) {
        var notifs = data.data;
        $rootScope.notif_length = notifs.length;
        $scope.data = notifs.slice(0, 5);
      })
    }

    $scope.goTo = function(data) {
      $http.post(baseUrl+'/api/journal_notification/' + data.id).then(function(result) {
        $scope.refreshNotifJournal();
        $state.go('finance.journal.show', {id: data.id});
      }, function(){
          $scope.goTo()
      });
    }
});

app.controller('journalNotification', function($scope, $http, $rootScope,$state) {
    $rootScope.pageTitle = "Daftar Notifikasi Jurnal";
    $('.ibox-content').addClass('sk-loading');

    $http.get(baseUrl + '/api/journal_notification/detail_notification')
    .then(function(data) {
        $scope.data = data.data;
        $('.ibox-content').removeClass('sk-loading');
    })

    $scope.viewNotif = function(journal_id) {
      $http.post(baseUrl+'/api/journal_notification/' + journal_id).then(function(data) {
        $rootScope.refreshNotifJournal();
        $state.go('finance.journal.show', {"id": journal_id});
      }, function(){
          $scope.viewNotif()
      });
    }

    $scope.openDetail = function(value) {
        $state.go('finance.journal.show', {id: value});
    }
});
