import { baseUrl } from '../../../../repositories/Client/AxiosClient'
const resource = `${baseUrl}/api/airtel/authentication`

import Client from '../../../../repositories/Client/AxiosClient'

export default {
    get() {
        return Client.get(`${resource}`)
    },
}
