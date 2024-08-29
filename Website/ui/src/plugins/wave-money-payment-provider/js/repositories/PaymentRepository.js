import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/wave-money/wave-money-transaction/start`

export default {
  post(paymentRequest, companyId) {
    return Client.post(`${resource}/${companyId}`, paymentRequest)
  },
}
