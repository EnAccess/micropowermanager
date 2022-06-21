const resource = '/api/spark-meters/sm-site'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    update(site){
        return Client.put(`${resource}/${site.id}`,site)
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