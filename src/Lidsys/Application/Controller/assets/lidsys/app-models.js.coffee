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
        @displayName     = null
        @permissions     = {}


    isAuthorized: (permission) ->
        if @permissions[permission]? and @permissions[permission] then true else false


    setFromApi: (options) ->
        names     = options.name.split(' ')
        firstName = names[0] ? ''
        lastName  = names[1] ? ''

        @userId          = options.user_id
        @username        = options.username
        @name            = options.name
        @backgroundColor = options.background_color
        @displayName     = firstName.substring(0, 1) +
            firstName.substring(firstName.length - 1) +
            lastName.substring(0, 1)
        @
