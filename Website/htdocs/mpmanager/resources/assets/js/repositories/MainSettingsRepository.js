const resource = 'api/settings'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}/main`)
    },
    update (id, mainSettings) {
        return Client.put(`${resource}/main/${id}`, mainSettings)
    },
}
