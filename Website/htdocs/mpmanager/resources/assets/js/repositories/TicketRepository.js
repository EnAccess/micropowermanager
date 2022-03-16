const resource = '/tickets/api'
import Client from './Client/AxiosClient'

export default {
    listCategory () {

        return Client.get(`${resource}/labels` + '/?outsource=1')
    },

    create (maintenanceData) {
        return Client.post(`${resource}/ticket`, maintenanceData)
    },
    close (id) {

        return Client.delete(`${resource}/ticket`, { data: { 'ticketId': id } })
    }

}
