const resource =  '/api/spark-meters/sm-customer'
import Client from './Client/AxiosClient'

export default {
    list(){
        return  Client.get(`${resource}`)
    },
    sync(){
        return  Client.get(`${resource}/sync`)
    },
    syncCheck(){
        return  Client.get(`${resource}/sync-check`)
    },
    count(){
        return  Client.get(`${resource}/count`)
    },

    connections(){
        return  Client.get(`${resource}/connection`)
    },
    update(customer){
        return Client.put(`${resource}/${customer.id}`,customer)
    },

}
