import { baseUrl } from '@/repositories/Client/AxiosClient'
const resource = `${baseUrl}/api/angaza-shs/angaza-credential`

import Client from '../../../../repositories/Client/AxiosClient'
export default {
    get() {
        return Client.get(`${resource}`)
    },
    put(credentials) {
        return Client.put(`${resource}`, credentials)
    },
}
