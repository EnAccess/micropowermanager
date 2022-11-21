import { baseUrl } from '../../../../repositories/Client/AxiosClient'
const resource = `${baseUrl}/api/wave-money/wave-money-transaction/start/`

import Client from '../../../../repositories/Client/AxiosClient'

export default {

    post (paymentRequest,companyId) {
        return Client.put(`${resource}/${companyId}`, paymentRequest)
    }
}
