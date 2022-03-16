import Client from './Client/AxiosClient'
const resource = 'api/dashboard/clusters'

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
