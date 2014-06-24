var module =  angular.module('ldsUser', ['ngRoute']);

module.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/user/login',
        {
            templateUrl: "/app/template/login/index.html",
            controller: "UserLoginCtrl",
            navigationLabel: "Login"
        })
        .when('/user/logout',
        {
            template: "Logging you out...",
            controller: "UserLogoutCtrl",
            navigationLabel: "Logout"
        })
        .when('/user/password',
        {
            templateUrl: "/app/template/password/index.html",
            controller: "UserPasswordCtrl",
            navigationLabel: "Edit Profile"
        })
        .when('/user/profile',
        {
            templateUrl: "/app/template/profile/index.html",
            controller: "UserProfileCtrl",
            navigationLabel: "Edit Profile"
        })
}])

module.factory('active', [function() {
    return new ActiveService()
}])

module.directive('ldsAuthenticated', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var wasOriginallyDisplayed = element.css('display')
            $rootScope.$watch(
                function (scope) {
                    var expected = scope.$eval(attrs.ldsAuthenticated)
                    return expected === active.isLoggedIn()
                },
                function (isAsExpected, wasAsExpected, scope) {
                    if (!isAsExpected) {
                        element.css('display', 'none')
                    }
                    else {
                        element.css('display', wasOriginallyDisplayed)
                    }
                }
            )
        }
    }
}])

module.directive('ldsAuthorized', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var wasOriginallyDisplayed = element.css('display')
            $rootScope.$watch(
                function (scope) {
                    return active.getUser().isAuthorized(attrs.ldsAuthorized)
                },
                function (isAuthorized, wasAuthorized, scope) {
                    if (!isAuthorized) {
                        element.css('display', 'none');
                    }
                    else {
                        element.css('display', wasOriginallyDisplayed)
                    }
                }
            )
        }
    }
}])

module.directive('ldsUserInfo', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            scope.user = active.getUser()
        }
    }
}])

module.controller('UserLoginCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
    $scope.formChanged = function ($event) {
        var login = $scope.login;

        if (login.username != login.submittedUsername ||
            login.password != login.submittedPassword
        ) {
            login.error = '';
        }
    }
    $scope.processLogin = function ($event) {
        var login = $scope.login;

        login.error             = {};
        login.submittedUsername = login.username;
        login.submittedPassword = login.password;

        if (!login.username) {
            login.error.hasError = true;
            login.error.username = 'Please enter your username.';
        }
        if (!login.password) {
            login.error.hasError = true;
            login.error.password = 'Please enter your password.';
        }

        if (login.error.hasError) {
            return false;
        }

        var postData = {
            username: login.username,
            password: login.password
        }

        $http.post("/app/user/login/", postData)
            .success(function (data) {
                if (data.authenticated_user) {
                    active.getUser().setFromApi(data.authenticated_user)
                    login.error.form = 'Success!!';
                    $window.history.back()
                }
                else {
                    login.error.form = 'The provided username/password are incorrect.';
                }
            })
            .error(function (data) {
                login.error.form = 'There was an error processing your login request. Please contact an administrator.';
            })

        return false;
    }

    $scope.login = {
        submitEnabled: false,
        error: {},
        username: '',
        password: '',
        previousUsername: '',
        previousPassword: ''
    }
}])

module.controller('UserLogoutCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
    $http.post("/app/user/logout/")
        .success(function (data) {
            if (data.logged_out) {
                active.getUser().setFromApi({})
            }
            $window.history.back()
        })
        .error(function (data) {
        })
}])

module.controller('UserPasswordCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
}])

module.controller('UserProfileCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
}])
