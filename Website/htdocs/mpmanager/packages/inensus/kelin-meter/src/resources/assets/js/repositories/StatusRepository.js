const resource = '/api/kelin-meters/kelin-meter/status'
import Client from './Client/AxiosClient'

export default {
    show (meterId) {
        return Client.get(`${resource}/${meterId}`)
    },
    update(statusPM){
        return Client.put(`${resource}/${statusPM.meterId}`,statusPM)
    }
}