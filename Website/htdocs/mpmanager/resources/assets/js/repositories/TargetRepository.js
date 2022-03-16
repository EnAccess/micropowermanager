const resource = '/api/targets'
import Client from './Client/AxiosClient'

export default {

    store (target) {
        return Client.post(`${resource}`, target)
    }
}
