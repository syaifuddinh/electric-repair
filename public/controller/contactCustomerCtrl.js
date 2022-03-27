app.controller('contactCustomer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Customer";
});

app.controller('contactCustomerCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add Customer";
});

app.controller('contactCustomerShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Customer";
  $scope.states=$state;
  $scope.statesParams=$stateParams;
  if ($state.current.name=="contact.customer.show") {
    $state.go('contact.customer.show.detail',{id:$stateParams.id},{location:'replace'});
  }
});

app.controller('contactCustomerShowDetail', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
});
app.controller('contactCustomerShowAddressShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
});
app.controller('contactCustomerShowAddress', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {

});

app.controller('contactCustomerShowUser', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
});

app.controller('contactCustomerShowAddressEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {

});

app.controller('contactCustomerShowAddressCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {

});

app.controller('contactCustomerShowAddressCreatef', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {

});
