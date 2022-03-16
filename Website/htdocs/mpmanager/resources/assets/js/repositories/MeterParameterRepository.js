import Client from './Client/AxiosClient'
const resource = '/api/meters'


export default {

    update(meterId,params){
        return Client.put(`${resource}/${meterId}/parameters/`,params)
    }
}
