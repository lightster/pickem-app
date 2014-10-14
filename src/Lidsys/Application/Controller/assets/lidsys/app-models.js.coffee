window.ActiveService = class ActiveService
    constructor: ->
        @user = null


    setUser: (@user) ->
    getUser: -> @user

    isLoggedIn: -> !!(@user && @user.userId)
