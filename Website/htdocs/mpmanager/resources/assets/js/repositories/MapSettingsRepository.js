const resource = '/api/map-settings'
import Client from './Client/AxiosClient'

export default {
    list(){
        return Client.get(`${resource}`)
    },
    update(id, mapSettings){
        return Client.put(`${resource}/${id}`, mapSettings)
    },
    checkBingApiKey(key) {
        return Client.get(`${resource}/key/${key}`)
    }
}
