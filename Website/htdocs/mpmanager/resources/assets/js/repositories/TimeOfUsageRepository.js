import Client from './Client/AxiosClient'
const resource = '/api/time-of-usages'
export default {
    delete(id){
        return Client.delete(`${resource}/${id}`)
    }
}
