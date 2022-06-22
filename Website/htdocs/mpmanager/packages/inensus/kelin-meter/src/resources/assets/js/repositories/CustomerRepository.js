const resource = '/api/kelin-meters/kelin-customer'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    sync () {
        return Client.get(`${resource}/sync`)
    },
    get (customerId) {
        return Client.get(`${resource}/${customerId}`)
    },
    syncCheck () {
        return Client.get(`${resource}/sync-check`)
    },

    update(customer){
        return Client.put(`${resource}/${customer.id}`,customer)
    },
}