window.ActiveService = class ActiveService
    constructor: ->
        @user = null


    setUser: (@user) ->
    getUser: -> @user

    isLoggedIn: -> !!(@user && @user.userId)



window.User = class User
    constructor: ->
        @userId          = null
        @username        = null
        @name            = null
        @backgroundColor = null
        @permissions     = {}


    isAuthorized: (permission) ->
        if @permissions[permission]? and @permissions[permission] then true else false


    setFromApi: (options) ->
        @userId          = options.user_id
        @username        = options.username
        @name            = options.name
        @backgroundColor = options.background_color
        @
