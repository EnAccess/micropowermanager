import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/wave-money/wave-money-transaction/start`

export default {
  post(paymentRequest, companyId) {
    return Client.post(`${resource}/${companyId}`, paymentRequest)
  },
}
