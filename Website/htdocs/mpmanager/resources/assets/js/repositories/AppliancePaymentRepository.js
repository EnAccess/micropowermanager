const resource = '/api/assets/payment'
import Client from './Client/AxiosClient'


export default {

    update(id,data){
        return Client.post(`${resource}/${id}`,data )
    }
}
