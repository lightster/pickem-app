var app =  angular.module('app', ['ngRoute', 'ldsFootball', 'ldsNavigation', 'ldsUser']);

app.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/',
        {
            template: "Main",
            controller: "AppCtrl"
        })
        .otherwise({
            template: "This doesn't exist!"
        })
}])

app.directive('dropdownParent', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "C",
        link: function (scope, element, attrs) {
            console.log(attrs)
            if (attrs.href == '#') {
                element.on('click', function(event) {
                    event.preventDefault()
                })
            }
        }
    }
}])

app.run(['$rootScope', 'active', function ($rootScope, active) {
    active.setUser(new User())
}])

app.controller('AppCtrl', ['$scope', '$http', 'active', function ($scope, $http, active) {
    $http.post("/app/user/authenticated-user/")
        .success(function (data) {
            if (data.authenticated_user) {
                active.getUser().setFromApi(data.authenticated_user)
            }
        })
        .error(function (data) {
        })
}])
