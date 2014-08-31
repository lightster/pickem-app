var app =  angular.module('app', ['ngRoute', 'ldsFootball', 'ldsNavigation', 'ldsUser']);

app.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/',
        {
            templateUrl: "/app/template/index/main.html",
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
            if (attrs.href == '#') {
                element.on('click', function(event) {
                    event.preventDefault()
                })
            }
        }
    }
}])

app.factory('versionChecker', ['$http', '$timeout', '$window', function($http, $timeout, $window) {
    var versionChecker = {}

    versionChecker.version = null
    versionChecker.checker = function () {
        $http.post("/app/build-number/")
            .success(function (response) {
                if (versionChecker.version
                    && response.version != versionChecker.version
                ) {
                    $window.location.reload()
                }

                versionChecker.version = response.version
            })
            .error(function (response) {
                // :-/
            })
            .finally(function() {
                versionChecker.start()
            })
    }
    versionChecker.start = function () {
        $timeout(
            versionChecker.checker,
            60000,
            false // do not run apply
        )
    }

    return versionChecker;
}])

app.run(['$rootScope', 'active', 'versionChecker', function ($rootScope, active, versionChecker) {
    active.setUser(new User())
    versionChecker.start()
}])

app.controller('AppCtrl', ['$scope', '$http', '$location', 'active', function ($scope, $http, $location, active) {
    $http.post("/app/user/authenticated-user/")
        .success(function (data) {
            if (data.authenticated_user) {
                active.getUser().setFromApi(data.authenticated_user)
            }
        })
        .error(function (data) {
        })
}])
