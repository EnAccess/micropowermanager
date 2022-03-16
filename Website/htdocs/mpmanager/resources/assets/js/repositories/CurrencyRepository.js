const resource = '/api/settings/currency-list'
import Client from './Client/AxiosClient'

export default {
    list(){
        return Client.get(`${resource}`)
    }
}
