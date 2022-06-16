import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/time-of-usages`

export default {
    delete(id){
        return Client.delete(`${resource}/${id}`)
    }
}
