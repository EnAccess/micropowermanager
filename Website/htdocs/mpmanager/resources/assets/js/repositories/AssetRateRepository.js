const resource = '/api/assets/rates'
import Client from './Client/AxiosClient'


export default {

    update(id,terms){
        return Client.put(`${resource}/${id}`,terms )

    }
}
