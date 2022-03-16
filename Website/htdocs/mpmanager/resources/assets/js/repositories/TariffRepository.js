const resource = '/api/tariffs'
import Client from './Client/AxiosClient'

export default {

    list () {
        return Client.get(`${resource}`)
    },
    create (tariff) {
        return Client.post(`${resource}`, tariff)
    },
    update (tariff) {
        return Client.put(`${resource}/${tariff.id}`, tariff)
    },
    get(id){
        return Client.get(`${resource}/${id}`)
    },
    delete(id){
        return Client.delete(`${resource}/${id}`)
    },
    usages(id){
        return Client.get(`${resource}/${id}/usage-count`)
    },
    change(currentId,changeId){
        return Client.put(`${resource}/${currentId}/change-meters-tariff/${changeId}`)
    }
}
