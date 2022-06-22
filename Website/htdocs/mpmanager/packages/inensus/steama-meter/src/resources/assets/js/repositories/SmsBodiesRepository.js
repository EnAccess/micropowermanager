const resource = '/api/steama-meters/steama-setting/sms-setting/sms-body'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    update (smsBodies) {
        return Client.put(`${resource}`, smsBodies)
    }
}