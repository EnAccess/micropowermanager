const resource = '/api/settings/languages-list'
import Client from './Client/AxiosClient'

export default {
    list(){
        return Client.get(`${resource}`)
    }

}
