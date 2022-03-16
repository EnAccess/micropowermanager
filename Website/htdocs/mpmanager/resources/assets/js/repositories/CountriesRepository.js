const resource = '/api/settings/country-list'
import Client from './Client/AxiosClient'

export default {
    list(){
        return Client.get(`${resource}`)
    }
}
