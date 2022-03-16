const resource = '/api/cities'
import Client from './Client/AxiosClient'
export default {


    list() {
        return Client.get(`${resource}`)
    },
    create(city){
        return Client.post(`${resource}`, city)
    }
}
