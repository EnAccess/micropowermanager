import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/bulk-register/import-csv`

import Client from '@/repositories/Client/AxiosClient'

export default {
    post(csvData) {
        return Client.post(`${resource}`, csvData)
    },
}
