const resource = '/api/meter-types'
import Client from './Client/AxiosClient'

export default {

    index () {
        return Client.get(`${resource}`)

    },
    store (meterType) {
        return Client.post(`${resource}`, meterType)
    },
    update (meterType) {
        return Client.put(`${resource}/${meterType}`)
    }


}
