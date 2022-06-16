import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/connection-types`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    create(name) {
        return Client.post(`${resource}`, name)
    },
    show(connectionTypeId) {
        return Client.get(`${resource}/${connectionTypeId}?meter_count=1`)
    },
    update(connectionType){
        return Client.put(`${resource}/${connectionType.id}`,connectionType)
    }
}


