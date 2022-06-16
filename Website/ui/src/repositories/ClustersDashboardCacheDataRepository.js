import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/dashboard/clusters`

export default {

    list(){
        return Client.get(`${resource}`)
    },

    update(){
        return Client.put(`${resource}`)
    },

    detail(id){
        return Client.get(`${resource}/${id}`)
    }

}
