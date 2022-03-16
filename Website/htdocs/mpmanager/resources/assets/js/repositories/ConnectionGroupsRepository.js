const resource =  '/api/connection-groups'
import Client from './Client/AxiosClient'

export default {
    list(){
        return Client.get(`${resource}`)
    },
    create(name){
        return Client.post(`${resource}`,name)
    },
    update(connectionGroup){
        return Client.put(`${resource}/${connectionGroup.id}`,connectionGroup)
    }
}

