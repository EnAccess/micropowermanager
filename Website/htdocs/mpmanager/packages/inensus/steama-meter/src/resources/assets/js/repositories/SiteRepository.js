const resource = '/api/steama-meters/steama-site'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    sync () {
        return Client.get(`${resource}/sync`)
    },
    syncCheck () {
        return Client.get(`${resource}/sync-check`)
    },
    count () {
        return Client.get(`${resource}/count`)
    },
    location(){
        return  Client.get(`${resource}/location`)
    },
}