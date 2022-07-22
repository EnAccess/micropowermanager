import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/tickets/api`

export default {
    listCategory () {
        return Client.get(`${resource}/labels` + '?outsource=1')
    },

    create (maintenanceData) {
        return Client.post(`${resource}/ticket`, maintenanceData)
    },
    close (id) {
        return Client.delete(`${resource}/ticket/${id}`)
    }

}
