import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/assets/person`

export default {
    list(id) {
        return Client.get(`${resource}/people/${id}`)
    },

    create(appliance) {
        return Client.post(
            `${resource}/${appliance.id}/people/${appliance.person_id}`,
            appliance,
        )
    },

    show(applianceId) {
        return Client.get(`${resource}/people/detail/${applianceId}`)
    },
}
