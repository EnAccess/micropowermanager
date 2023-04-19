import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/wavecom/upload`

import Client from '@/repositories/Client/AxiosClient'

export default {
    post (transactionFile) {
        return Client.post(`${resource}`, transactionFile)
    }
}
