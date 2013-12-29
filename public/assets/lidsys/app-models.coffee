window.ActiveService = class ActiveService
    constructor: ->
        @user = null


    setUser: (@user) ->
    getUser: -> @user

    isLoggedIn: -> if @user.userId then true else false



window.User = class User
    constructor: ->
        @userId = null
        @permissions = {}


    isAuthorized: (permission) ->
        if @permissions[permission]? and @permissions[permission] then true else false


    setFromApi: (options) ->
        @userId = options.user_id
        @username = options.username
        this